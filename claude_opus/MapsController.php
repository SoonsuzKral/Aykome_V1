<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MapsController extends Controller
{
    // =========================================================
    // AYARLAR
    // =========================================================
    private const WFS_URL  = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs';
    private const ILCE_NO  = '63011'; // Eyyübiye
    private const CACHE_TTL = 3600;   // 1 saat (mahalleler değişmez)

    // =========================================================
    // TÜRKÇe BÜYÜK HARF YARDIMCI (İ/I krizi yok)
    // =========================================================
    private function trUpper(string $str): string
    {
        $tr = ['i' => 'İ', 'ı' => 'I', 'ğ' => 'Ğ', 'ü' => 'Ü', 'ş' => 'Ş', 'ö' => 'Ö', 'ç' => 'Ç'];
        return strtr(mb_strtoupper($str, 'UTF-8'), $tr);
    }

    // =========================================================
    // WFS HTTP CLIENT (SSL verify=false — yerel sunucu)
    // =========================================================
    private function wfsGet(array $params): array
    {
        $defaults = [
            'service'      => 'WFS',
            'version'      => '1.1.0',      // 1.1.0 daha stabil, 2.0.0 400 veriyordu
            'request'      => 'GetFeature',
            'outputFormat' => 'application/json',
            'srsName'      => 'EPSG:4326',
        ];

        $query = array_merge($defaults, $params);

        Log::info('[WFS Request]', $query);

        $response = Http::withOptions([
            'verify'  => false,   // Yerel SSL sertifikası sorunsuz geç
            'timeout' => 15,
        ])->get(self::WFS_URL, $query);

        if (!$response->successful()) {
            Log::error('[WFS Error]', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'params' => $query,
            ]);
            throw new \Exception("WFS HTTP {$response->status()}: " . $response->body());
        }

        $data = $response->json();

        if (!isset($data['features'])) {
            Log::warning('[WFS] features anahtarı yok', ['body' => $response->body()]);
            throw new \Exception('WFS geçersiz yanıt: features bulunamadı');
        }

        return $data;
    }

    // =========================================================
    // CENTROID HESABI (external lib yok)
    // =========================================================
    private function centroid(array $geometry): ?array
    {
        if (empty($geometry['coordinates'])) return null;

        $coords = [];
        $this->flattenCoords($geometry['coordinates'], $coords);
        if (empty($coords)) return null;

        $sumLng = array_sum(array_column($coords, 0));
        $sumLat = array_sum(array_column($coords, 1));
        $count  = count($coords);

        return [
            'lat' => round($sumLat / $count, 7),
            'lng' => round($sumLng / $count, 7),
        ];
    }

    private function flattenCoords(array $arr, array &$out): void
    {
        if (isset($arr[0]) && is_numeric($arr[0])) {
            $out[] = $arr;
            return;
        }
        foreach ($arr as $item) {
            $this->flattenCoords($item, $out);
        }
    }

    // =========================================================
    // BBOX HESABI (geometry'den)
    // =========================================================
    private function getBbox(array $geometry): ?array
    {
        $coords = [];
        $this->flattenCoords($geometry['coordinates'] ?? [], $coords);
        if (empty($coords)) return null;

        $lngs = array_column($coords, 0);
        $lats = array_column($coords, 1);

        return [
            'minLng' => min($lngs),
            'minLat' => min($lats),
            'maxLng' => max($lngs),
            'maxLat' => max($lats),
            // WFS BBOX string formatı
            'bbox'   => implode(',', [min($lngs), min($lats), max($lngs), max($lats)]),
        ];
    }

    // =========================================================
    // ROUTE 1: GET /maps/mahalleler
    // Eyyübiye mahallelerini döner (cache'li)
    // =========================================================
    public function mahalleler(Request $request)
    {
        $cacheKey = 'wfs_mahalleler_' . self::ILCE_NO;

        $result = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $data = $this->wfsGet([
                'typeName'   => 'cbs:MISMAP_MAHALLE_KOYLER',
                'CQL_FILTER' => "ILCE_NO='" . self::ILCE_NO . "'",
                'maxFeatures'=> 300,
            ]);

            $mahalleler = [];
            foreach ($data['features'] as $f) {
                $ad = $f['properties']['MAHALLE_ADI'] ?? null;
                if (!$ad) continue;

                $geom   = $f['geometry'] ?? null;
                $bbox   = $geom ? $this->getBbox($geom) : null;
                $center = $geom ? $this->centroid($geom) : null;

                $mahalleler[] = [
                    'ad'     => $ad,
                    'center' => $center,
                    'bbox'   => $bbox,
                ];
            }

            // Türkçe sıralama
            usort($mahalleler, fn($a, $b) => strcmp(
                $this->trUpper($a['ad']),
                $this->trUpper($b['ad'])
            ));

            return $mahalleler;
        });

        // Query filtresi (frontend arama için)
        $q = $request->get('q', '');
        if ($q) {
            $qUpper = $this->trUpper($q);
            $result = array_values(array_filter(
                $result,
                fn($m) => str_contains($this->trUpper($m['ad']), $qUpper)
            ));
        }

        return response()->json([
            'success' => true,
            'count'   => count($result),
            'data'    => $result,
        ]);
    }

    // =========================================================
    // ROUTE 2: POST /maps/sokak-caddeler
    // Mahalle bbox'ı içindeki cadde/sokakları döner
    // Body: { bbox: "minLng,minLat,maxLng,maxLat" }
    // =========================================================
    public function sokakCaddeler(Request $request)
    {
        $request->validate([
            'bbox' => 'required|string',
        ]);

        $bbox = $request->input('bbox'); // "38.75,37.13,38.80,37.16"

        // Basit format doğrulama
        $parts = explode(',', $bbox);
        if (count($parts) !== 4) {
            return response()->json(['success' => false, 'message' => 'Geçersiz bbox formatı'], 422);
        }

        try {
            $data = $this->wfsGet([
                'typeName'    => 'cbs:MISMAP_CADDE_SOKAK',
                'BBOX'        => "{$bbox},EPSG:4326",
                'maxFeatures' => 500,
            ]);

            // Tekrar eden cadde adlarını temizle, centroid hesapla
            $unique = [];
            foreach ($data['features'] as $f) {
                $ad = $f['properties']['CADDE_SOKAK_ADI'] ?? null;
                if (!$ad) continue;

                $adUpper = $this->trUpper($ad);
                if (isset($unique[$adUpper])) continue; // duplicate atla

                $geom   = $f['geometry'] ?? null;
                $center = $geom ? $this->centroid($geom) : null;
                $fBbox  = $geom ? $this->getBbox($geom) : null;

                $unique[$adUpper] = [
                    'ad'     => $ad,
                    'center' => $center,
                    'bbox'   => $fBbox,
                ];
            }

            $list = array_values($unique);
            usort($list, fn($a, $b) => strcmp(
                $this->trUpper($a['ad']),
                $this->trUpper($b['ad'])
            ));

            return response()->json([
                'success' => true,
                'count'   => count($list),
                'data'    => $list,
            ]);

        } catch (\Exception $e) {
            Log::error('[sokakCaddeler]', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // ROUTE 3: POST /maps/kapi-numaralari
    // Cadde bbox'ı içindeki kapı numaralarını döner
    // Body: { bbox: "minLng,minLat,maxLng,maxLat" }
    // =========================================================
    public function kapiNumaralari(Request $request)
    {
        $request->validate([
            'bbox' => 'required|string',
        ]);

        $bbox  = $request->input('bbox');
        $parts = explode(',', $bbox);
        if (count($parts) !== 4) {
            return response()->json(['success' => false, 'message' => 'Geçersiz bbox'], 422);
        }

        // Bbox'ı biraz genişlet (sokak sınırı tam kapı içermeyebilir)
        [$minLng, $minLat, $maxLng, $maxLat] = array_map('floatval', $parts);
        $pad = 0.0003;
        $paddedBbox = implode(',', [
            $minLng - $pad, $minLat - $pad,
            $maxLng + $pad, $maxLat + $pad
        ]);

        try {
            $data = $this->wfsGet([
                'typeName'    => 'smpns:m_Numarataj',
                'BBOX'        => "{$paddedBbox},EPSG:4326",
                'maxFeatures' => 300,
            ]);

            $kapılar = [];
            foreach ($data['features'] as $f) {
                $props = $f['properties'] ?? [];
                $coords = $f['geometry']['coordinates'] ?? null;

                // Kapı no için olası alan adları
                $no = $props['KAPI_NO']
                    ?? $props['BINA_NO']
                    ?? $props['NO']
                    ?? $props['NUMARATAJ_NO']
                    ?? $props['ADRES_NO']
                    ?? null;

                if (!$no || !$coords) continue;

                $kapılar[] = [
                    'no'  => (string) $no,
                    'lat' => round($coords[1], 7),
                    'lng' => round($coords[0], 7),
                ];
            }

            // Numerik sıralama
            usort($kapılar, function ($a, $b) {
                $an = (int) filter_var($a['no'], FILTER_SANITIZE_NUMBER_INT);
                $bn = (int) filter_var($b['no'], FILTER_SANITIZE_NUMBER_INT);
                return $an <=> $bn;
            });

            return response()->json([
                'success' => true,
                'count'   => count($kapılar),
                'data'    => $kapılar,
            ]);

        } catch (\Exception $e) {
            Log::error('[kapiNumaralari]', ['error' => $e->getMessage()]);
            // Kapı verisi olmayabilir — 404 değil boş döndür
            return response()->json(['success' => true, 'count' => 0, 'data' => [], 'note' => $e->getMessage()]);
        }
    }

    // =========================================================
    // ROUTE 4: POST /maps/adres-ara
    // Serbest adres metninden koordinat bul
    // Body: { adres: "8125. Sk. 122 Kadıkendi Eyyübiye" }
    // =========================================================
    public function adresAra(Request $request)
    {
        $request->validate(['adres' => 'required|string|max:255']);

        $adres = trim($request->input('adres'));

        // ---- REGEX PARSE ----
        // "8125. Sk. 122" veya "8125 sokak no:122"
        $caddeNo  = null;
        $kapiNo   = null;
        $mahAdi   = null;

        // Sokak numarası: "8125. Sk." veya "8125 SOKAK" veya "8125 SK"
        if (preg_match('/(\d+)\s*[.\-]?\s*(?:sk|sokak|cad|cadde)\.?\s*(\d+)?/iu', $adres, $m)) {
            $caddeNo = $m[1];
            $kapiNo  = $m[2] ?? null;
        }

        // Kapı no ayrı: "No:122" veya " 122 "
        if (!$kapiNo && preg_match('/(?:no|num|kap[ıi])\s*:?\s*(\d+)/iu', $adres, $m)) {
            $kapiNo = $m[1];
        }

        // Mahalle adı: "XYZ Mah" veya "XYZ Mahallesi"
        if (preg_match('/([\wçğışöüÇĞİŞÖÜ]+(?:\s+[\wçğışöüÇĞİŞÖÜ]+)?)\s+(?:mah|mahalle|mahallesi)/iu', $adres, $m)) {
            $mahAdi = trim($m[1]);
        }

        Log::info('[adresAra] Parse', compact('caddeNo', 'kapiNo', 'mahAdi', 'adres'));

        // Eyyübiye genel bbox
        $eyBbox = '38.6800,37.0900,38.9000,37.2200';

        // ---- STRATEJI 1: Numarataj katmanı (kapı no varsa) ----
        if ($caddeNo && $kapiNo) {
            try {
                // Önce caddeyi bul
                $cadData = $this->wfsGet([
                    'typeName'    => 'cbs:MISMAP_CADDE_SOKAK',
                    'BBOX'        => "{$eyBbox},EPSG:4326",
                    'CQL_FILTER'  => "STRMATCHES(CADDE_SOKAK_ADI,'.*{$caddeNo}.*')",
                    'maxFeatures' => 5,
                ]);

                if (!empty($cadData['features'])) {
                    $caddeBbox = $this->getBbox($cadData['features'][0]['geometry']);
                    if ($caddeBbox) {
                        $nuData = $this->wfsGet([
                            'typeName'    => 'smpns:m_Numarataj',
                            'BBOX'        => "{$caddeBbox['bbox']},EPSG:4326",
                            'maxFeatures' => 50,
                        ]);

                        foreach ($nuData['features'] as $f) {
                            $props = $f['properties'];
                            $no = $props['KAPI_NO'] ?? $props['BINA_NO'] ?? $props['NO'] ?? null;
                            if ((string)$no === (string)$kapiNo) {
                                $coords = $f['geometry']['coordinates'];
                                return response()->json([
                                    'success'  => true,
                                    'method'   => 'numarataj',
                                    'lat'      => round($coords[1], 7),
                                    'lng'      => round($coords[0], 7),
                                    'label'    => "Cadde:{$caddeNo} No:{$kapiNo}",
                                    'confidence' => 'high',
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[adresAra] Numarataj hatası', ['err' => $e->getMessage()]);
            }
        }

        // ---- STRATEJI 2: Cadde centroidi ----
        if ($caddeNo) {
            try {
                $cadData = $this->wfsGet([
                    'typeName'    => 'cbs:MISMAP_CADDE_SOKAK',
                    'BBOX'        => "{$eyBbox},EPSG:4326",
                    'CQL_FILTER'  => "STRMATCHES(CADDE_SOKAK_ADI,'.*{$caddeNo}.*')",
                    'maxFeatures' => 3,
                ]);

                if (!empty($cadData['features'])) {
                    $center = $this->centroid($cadData['features'][0]['geometry']);
                    if ($center) {
                        return response()->json([
                            'success'    => true,
                            'method'     => 'cadde_centroid',
                            'lat'        => $center['lat'],
                            'lng'        => $center['lng'],
                            'label'      => $cadData['features'][0]['properties']['CADDE_SOKAK_ADI'],
                            'confidence' => 'medium',
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[adresAra] Cadde arama hatası', ['err' => $e->getMessage()]);
            }
        }

        // ---- STRATEJI 3: Mahalle centroidi (son çare) ----
        if ($mahAdi) {
            try {
                $mahUpper = $this->trUpper($mahAdi);
                $mahData  = $this->wfsGet([
                    'typeName'    => 'cbs:MISMAP_MAHALLE_KOYLER',
                    'CQL_FILTER'  => "ILCE_NO='" . self::ILCE_NO . "' AND STRMATCHES(MAHALLE_ADI,'.*{$mahUpper}.*')",
                    'maxFeatures' => 3,
                ]);

                if (!empty($mahData['features'])) {
                    $center = $this->centroid($mahData['features'][0]['geometry']);
                    if ($center) {
                        return response()->json([
                            'success'    => true,
                            'method'     => 'mahalle_centroid',
                            'lat'        => $center['lat'],
                            'lng'        => $center['lng'],
                            'label'      => $mahData['features'][0]['properties']['MAHALLE_ADI'] . ' Mahallesi',
                            'confidence' => 'low',
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[adresAra] Mahalle arama hatası', ['err' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Adres bulunamadı. Lütfen Mahalle→Cadde cascading kullanın.',
        ], 404);
    }

    // =========================================================
    // ROUTE 5: GET /maps/mahalle-bul?mahalle=KADIKENDİ
    // Tek mahalle arama (blade form için)
    // =========================================================
    public function mahalleBul(Request $request)
    {
        $request->validate(['mahalle' => 'required|string']);

        $q = $this->trUpper(trim($request->input('mahalle')));

        try {
            $data = $this->wfsGet([
                'typeName'    => 'cbs:MISMAP_MAHALLE_KOYLER',
                'CQL_FILTER'  => "ILCE_NO='" . self::ILCE_NO . "' AND STRMATCHES(MAHALLE_ADI,'.*{$q}.*')",
                'maxFeatures' => 10,
            ]);

            $result = [];
            foreach ($data['features'] as $f) {
                $ad   = $f['properties']['MAHALLE_ADI'] ?? null;
                $geom = $f['geometry'] ?? null;
                if (!$ad || !$geom) continue;

                $result[] = [
                    'ad'     => $ad,
                    'center' => $this->centroid($geom),
                    'bbox'   => $this->getBbox($geom)['bbox'] ?? null,
                ];
            }

            return response()->json(['success' => true, 'count' => count($result), 'data' => $result]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
