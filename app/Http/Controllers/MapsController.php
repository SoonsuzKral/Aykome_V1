<?php

namespace App\Http\Controllers;

use App\Models\GisBasvuruNokta;
use App\Models\SurfaceType;
use App\Models\Application;
use App\Models\GisCizim;
use App\Services\DrawingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MapsController extends Controller
{
    public function index(): View
    {
        $surfaceTypes = SurfaceType::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price_per_m2']);

        // Kişisel katman renkleri: users.map_preferences JSON (birincil) + eski
        // gis_katman_ayarlari tablosu (uyumluluk). maps/index.blade.php re-bind için kullanır.
        $authColorPreferences = [];
        $authWidthPreferences  = [];

        try {
            $userPref = auth()->user()?->getMapColorSettings() ?? [];
            if (is_array($userPref)) {
                $authColorPreferences = $userPref;
            }
        } catch (\Exception $e) {
            Log::warning('[maps.index] map_preferences okunamadı: ' . $e->getMessage());
        }

        // Kalıcı depo: gis_katman_ayarlari (renk + stroke_width) — Oracle dahil tüm ortamlarda var
        try {
            $rows = \DB::table('gis_katman_ayarlari')
                ->where('user_id', auth()->id())
                ->whereNotNull('renk')
                ->get(['katman_adi', 'renk', 'stroke_width']);
            foreach ($rows as $r) {
                $authColorPreferences[$r->katman_adi] = $authColorPreferences[$r->katman_adi] ?? $r->renk;
                if ($r->stroke_width) {
                    $authWidthPreferences[$r->katman_adi] = (int) $r->stroke_width;
                }
            }
        } catch (\Exception $e) {
            // tablo yoksa sessiz geç
        }

        return view('maps.index', compact('surfaceTypes', 'authColorPreferences', 'authWidthPreferences'));
    }

    // ─── CBS — Katman Renk Tercihleri (kişisel color-picker) ───
    // Debounce'lı gizli AJAX ile tetiklenir. Kalıcı depo: gis_katman_ayarlari.renk
    // (Oracle'da users.map_preferences kolonu yok; bu tablo her ortamda var).
    // users.map_preferences JSON'ı varsa oraya da yedeklenir (MySQL/uyumluluk).

    public function renkKaydet(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Oturum açmanız gerekli'], 401);
        }

        $renkler = $request->input('renkler');
        $kalinliklar = $request->input('kalinliklar');
        if (!is_array($renkler)) {
            return response()->json(['success' => false, 'message' => 'Geçersiz veri'], 422);
        }
        if (!is_array($kalinliklar)) $kalinliklar = [];

        $kaydedilen = 0;

        foreach ($renkler as $layer => $hex) {
            if (!is_string($layer) || !is_string($hex)) continue;
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) continue;

            // Kalınlığı aynı satırda kaydet (kullanıcı width belirttiyse, yoksa 2)
            $width = isset($kalinliklar[$layer]) ? (int) $kalinliklar[$layer] : 2;
            if ($width < 1) $width = 1;
            if ($width > 10) $width = 10;

            // Birincil depo: gis_katman_ayarlari.renk (Oracle dahil tüm ortamlarda var)
            try {
                \DB::table('gis_katman_ayarlari')->updateOrInsert(
                    ['user_id' => $user->id, 'katman_adi' => $layer],
                    [
                        'renk' => strtoupper($hex),
                        'stroke_width' => $width,
                        'gorunur' => true,
                        'opacity' => 0.70,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $kaydedilen++;
            } catch (\Exception $e) {
                Log::warning('[maps.renkKaydet] gis_katman_ayarlari kaydı başarısız: ' . $e->getMessage());
                continue;
            }

            // İkincil yedek: users.map_preferences JSON — kolon yalnızca MySQL'de varsa yaz
            try {
                if (Schema::hasColumn('users', 'map_preferences')) {
                    $user->setMapColorSetting($layer, $hex);
                }
            } catch (\Exception $e) {
                // Oracle'da kolon yoksa sessiz geç
            }
        }

        Log::info('[maps.renkKaydet] kullanıcı katman rengi + kalınlık kaydı (gis_katman_ayarlari)', [
            'user_id'     => $user->id,
            'toplam'      => $kaydedilen,
            'renkler'     => $renkler,
            'kalinliklar' => $kalinliklar,
            'kaydeden'    => $user->email ?? $user->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => $kaydedilen . ' katman rengi kaydedildi.',
            'kaydedilen' => $kaydedilen,
        ]);
    }

    // ─── CBS v7 — 15m Yol Analizi ───

    public function geoJson15Alti()
    {
        $path = storage_path('shp/15_alti.js');
        if (!file_exists($path)) {
            return response()->json(['type' => 'FeatureCollection', 'features' => []], 200);
        }
        $content = file_get_contents($path);
        $json = preg_replace('/^var\s+\w+\s*=\s*/', '', $content);
        $json = rtrim($json, ";\n\r ");
        $data = json_decode($json, true);
        if (!$data || !isset($data['features'])) {
            return response()->json(['type' => 'FeatureCollection', 'features' => []], 200);
        }
        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
    }

    public function geoJson15Ustu()
    {
        $path = storage_path('shp/15_ustu.js');
        if (!file_exists($path)) {
            return response()->json(['type' => 'FeatureCollection', 'features' => []], 200);
        }
        $content = file_get_contents($path);
        $json = preg_replace('/^var\s+\w+\s*=\s*/', '', $content);
        $json = rtrim($json, ";\n\r ");
        $data = json_decode($json, true);
        if (!$data || !isset($data['features'])) {
            return response()->json(['type' => 'FeatureCollection', 'features' => []], 200);
        }
        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
    }

    public function roadQuery(Request $request)
    {
        $hatKimligi = $request->input('hat_kimligi');
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        // Veri dosyalarını tara
        $files = [
            storage_path('shp/15_alti.js'),
            storage_path('shp/15_ustu.js'),
        ];

        foreach ($files as $path) {
            if (!file_exists($path)) continue;
            $content = file_get_contents($path);
            $json = preg_replace('/^var\s+\w+\s*=\s*/', '', $content);
            $json = rtrim($json, ";\n\r ");
            $data = json_decode($json, true);
            if (!$data || !isset($data['features'])) continue;

            foreach ($data['features'] as $feature) {
                $props = $feature['properties'] ?? [];

                // Kimlik no ile ara
                if ($hatKimligi && ($props['CADDE_SOKA'] ?? null) == $hatKimligi) {
                    return response()->json([
                        'found' => true,
                        'source' => basename($path),
                        'properties' => $props,
                        'geometry' => $feature['geometry'],
                    ]);
                }

                // Koordinat ile ara (noktanın 50m yakınındaki yol)
                if ($lat && $lng) {
                    $geomType = $feature['geometry']['type'] ?? '';
                    $coords = $feature['geometry']['coordinates'] ?? [];
                    // LineString → düz [lon,lat] listesi; MultiLineString → segment listesi
                    $segments = [];
                    if ($geomType === 'MultiLineString') {
                        $segments = $coords;
                    } elseif ($geomType === 'LineString') {
                        $segments = [$coords];
                    } elseif (is_array($coords) && isset($coords[0]) && is_array($coords[0]) && isset($coords[0][0]) && !is_array($coords[0][0])) {
                        $segments = [$coords];
                    } else {
                        $segments = $coords;
                    }
                    foreach ($segments as $segment) {
                        foreach ($segment as $coord) {
                            if (is_array($coord) && count($coord) >= 2) {
                                $d = $this->haversineDistance((float)$lat, (float)$lng, (float)$coord[1], (float)$coord[0]);
                                if ($d < 0.05) { // 50 metre
                                    return response()->json([
                                        'found' => true,
                                        'source' => basename($path),
                                        'properties' => $props,
                                        'distance_km' => round($d, 4),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return response()->json(['found' => false, 'error' => 'Yol bulunamadı']);
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }

    // ─── CBS v7 — Çizim Yönetimi ───

    public function drawingSave(Request $request)
    {
        $data = $request->validate([
            'tip' => ['required', 'string', 'in:nokta,cizgi,alan'],
            'geometri' => ['required', 'json'],
            'basvuru_id' => ['nullable', 'integer', 'exists:applications,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'aciklama' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['geometri'] = json_decode($data['geometri'], true);
        $data['user_id'] = auth()->id();

        $service = app(DrawingService::class);
        $cizim = $service->saveDrawing($data);

        return response()->json([
            'success' => true,
            'message' => 'Çizim kaydedildi.',
            'data' => [
                'id' => $cizim->id,
                'tip' => $cizim->tip,
                'basvuru_id' => $cizim->basvuru_id,
                'yol_sayisi' => $cizim->yolIliskileri()->count(),
                'yollar' => $cizim->yolIliskileri,
            ],
        ]);
    }

    public function drawingUpdate(Request $request, $id)
    {
        $data = $request->validate([
            'tip' => ['nullable', 'string', 'in:nokta,cizgi,alan'],
            'geometri' => ['nullable', 'json'],
            'basvuru_id' => ['nullable', 'integer', 'exists:applications,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'aciklama' => ['nullable', 'string', 'max:1000'],
        ]);

        if (isset($data['geometri']) && is_string($data['geometri'])) {
            $data['geometri'] = json_decode($data['geometri'], true);
        }

        $service = app(DrawingService::class);
        $cizim = $service->updateDrawing((int)$id, $data);

        if (!$cizim) {
            return response()->json(['success' => false, 'message' => 'Çizim bulunamadı'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Çizim güncellendi.',
            'data' => $cizim->load('yolIliskileri'),
        ]);
    }

    public function drawingDelete($id)
    {
        $service = app(DrawingService::class);
        $deleted = $service->deleteDrawing((int)$id);

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Çizim bulunamadı'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Çizim silindi.']);
    }

    public function drawingGetByApp($appId)
    {
        $service = app(DrawingService::class);
        $cizimler = $service->getByApplication((int)$appId);

        $features = [];
        foreach ($cizimler as $cizim) {
            if (!$cizim->geometri) continue;
            $features[] = [
                'type' => 'Feature',
                'geometry' => $cizim->geometri,
                'properties' => [
                    'id' => $cizim->id,
                    'tip' => $cizim->tip,
                    'aciklama' => $cizim->aciklama,
                    'created_at' => $cizim->created_at ? $cizim->created_at->format('d.m.Y H:i') : '',
                    'yollar' => $cizim->yolIliskileri->map(function ($y) {
                        return [
                            'hat_kimligi' => $y->hat_kimligi,
                            'yol_adi' => $y->yol_adi,
                            'genislik' => $y->genislik,
                            'sorumluluk' => $y->sorumluluk,
                        ];
                    }),
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function drawingGetByUser(Request $request)
    {
        $service = app(DrawingService::class);
        $cizimler = $service->getByUser(auth()->id());

        $features = [];
        foreach ($cizimler as $cizim) {
            if (!$cizim->geometri) continue;
            $features[] = [
                'type' => 'Feature',
                'geometry' => $cizim->geometri,
                'properties' => [
                    'id' => $cizim->id,
                    'tip' => $cizim->tip,
                    'basvuru_id' => $cizim->basvuru_id,
                    'aciklama' => $cizim->aciklama,
                    'created_at' => $cizim->created_at ? $cizim->created_at->format('d.m.Y H:i') : '',
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    // ─── CBS v7 — Katman Tercihleri ───

    public function katmanKaydet(Request $request)
    {
        $data = $request->validate([
            'katmanlar' => ['required', 'array'],
            'katmanlar.*.layer' => ['required', 'string'],
            'katmanlar.*.visible' => ['required', 'boolean'],
            'katmanlar.*.opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Oturum açmanız gerekli'], 401);
        }

        // Kullanıcının mevcut tercihlerini sil
        \DB::table('gis_katman_ayarlari')->where('user_id', $user->id)->delete();

        // Yeni tercihleri kaydet
        foreach ($data['katmanlar'] as $k) {
            \DB::table('gis_katman_ayarlari')->insert([
                'user_id' => $user->id,
                'katman_adi' => $k['layer'],
                'gorunur' => $k['visible'],
                'opacity' => $k['opacity'] ?? 0.7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Katman tercihleri kaydedildi.']);
    }

    public function katmanYukle(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([]);
        }

        $ayarlar = \DB::table('gis_katman_ayarlari')
            ->where('user_id', $user->id)
            ->get(['katman_adi', 'gorunur', 'opacity']);

        return response()->json($ayarlar);
    }

    // ─── CBS v7 — Adres Arama ───

    public function search(Request $request)
    {
        $q = $request->input('q');
        if (!$q || strlen(trim($q)) < 2) return response()->json([]);
        $q = trim($q);

        $results = [];
        $cacheKey = 'maps_search_' . md5($q);
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) return response()->json($cached);

        // 0) LOCAL-FIRST — caddeler_ve_sokaklar.json + geometri (hız + %100 yerel veri).
        //    Mahalle + cadde/sokak eşleşmesi anında döner; WFS sadece parsel/ada yedeği.
        $results = array_merge($results, $this->searchLocal($q));

        // Yerel cadde sonuçları yetersizse WFS cadde yedeği (geo3)
        $yerelCaddeSayisi = count(array_filter($results, fn ($r) => $r['type'] === 'cadde'));
        if ($yerelCaddeSayisi < 3) {
            try {
                $caddeUrl = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs'
                    . '?service=WFS&version=2.0.0&request=GetFeature'
                    . '&typeNames=cbs:MISMAP_CADDE_SOKAK'
                    . '&cql_filter=' . urlencode("CADDE_SO_1 ILIKE '%{$q}%' OR CADDE_SO_2 ILIKE '%{$q}%'")
                    . '&outputFormat=application/json&srsName=EPSG:4326&count=6';
                $resp = Http::withOptions(['verify' => false, 'timeout' => 5])->get($caddeUrl);
                if ($resp->successful()) {
                    $data = $resp->json();
                    if (!empty($data['features'])) {
                        foreach ($data['features'] as $f) {
                            $p = $f['properties'] ?? [];
                            $name = trim(($p['CADDE_SO_1'] ?? '') . ' ' . ($p['CADDE_SO_2'] ?? ''));
                            if (!$name) continue;
                            $center = $this->centroidFromGeoJson($f['geometry']);
                            if (!$center) continue;
                            $results[] = [
                                'type' => 'cadde',
                                'label' => $name,
                                'detail' => ($p['MAHALLE_AD'] ?? '') . ', ' . ($p['ILCE'] ?? ''),
                                'lat' => $center['lat'],
                                'lon' => $center['lng'],
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Cadde arama hatası: ' . $e->getMessage());
            }
        }

        try {
            $parselUrl = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs'
                . '?service=WFS&version=2.0.0&request=GetFeature'
                . '&typeNames=smpns:MISMAP_NUM_KADASTRO_PARSEL'
                . '&cql_filter=' . urlencode("ADA ILIKE '%{$q}%' OR PARSEL ILIKE '%{$q}%'")
                . '&outputFormat=application/json&srsName=EPSG:4326&count=5';
            $resp = Http::withOptions(['verify' => false, 'timeout' => 5])->get($parselUrl);
            if ($resp->successful()) {
                $data = $resp->json();
                if (!empty($data['features'])) {
                    foreach ($data['features'] as $f) {
                        $p = $f['properties'] ?? [];
                        $label = 'Ada ' . ($p['ADA'] ?? '') . ' / Parsel ' . ($p['PARSEL'] ?? '');
                        $center = $this->centroidFromGeoJson($f['geometry']);
                        if (!$center) continue;
                        $results[] = [
                            'type' => 'parsel',
                            'label' => $label,
                            'detail' => ($p['MAHALLE_AD'] ?? '') . ', ' . ($p['ILCE'] ?? ''),
                            'lat' => $center['lat'],
                            'lon' => $center['lng'],
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Parsel arama hatası: ' . $e->getMessage());
        }

        $seen = [];
        $filtered = [];
        foreach ($results as $r) {
            $key = round($r['lat'], 5) . '|' . round($r['lon'], 5);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $filtered[] = $r;
            if (count($filtered) >= 15) break;
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $filtered, now()->addMinutes(10));

        return response()->json($filtered);
    }

    /**
     * LOCAL-FIRST arama — caddeler_ve_sokaklar.json (mahalle + cadde/sokak) +
     * geometri centroid'i. WFS'e gerek kalmadan anında sonuç üretir.
     * @return array<int, array{type:string,label:string,detail:string,lat:float,lon:float}>
     */
    private function searchLocal(string $q): array
    {
        $path = storage_path('shp/caddeler_ve_sokaklar.json');
        if (!file_exists($path)) return [];
        $content = file_get_contents($path);
        if (!$content) return [];

        $fixed = preg_replace('/\}\s*\n\s*\{/', '},' . "\n" . '{', $content);
        $data = json_decode('[' . $fixed . ']', true);
        if (!is_array($data)) return [];

        $qU = $this->trUppercase(trim($q));
        if ($qU === '') return [];

        // Hızlı geometri centroid index (bir kez oku, tekrar tekrar centroid)
        $geomIndex = $this->geomCentroidIndex();

        $results = [];
        $mahalleSet = [];      // mahalle → koordinat toplamı (centroid ortalaması için)
        $mahalleCount = [];

        foreach ($data as $r) {
            $mahalle = trim((string) ($r['MAHALLE_AD'] ?? ''));
            $caddeAdi = trim((string) ($r['CADDE SOKAK ADI'] ?? ''));
            if ($mahalle === '') continue;

            $mahU = $this->trUppercase($mahalle);

            // 1) MAHALLE EŞLEŞMESİ
            if (str_contains($mahU, $qU)) {
                $csa = (int) ($r['CADDE_SOKA'] ?? 0);
                $coord = $csa > 0 ? ($geomIndex[$csa] ?? null) : null;
                if ($coord) {
                    if (!isset($mahalleSet[$mahU])) {
                        $mahalleSet[$mahU] = ['ad' => $mahalle, 'lat' => 0, 'lon' => 0];
                        $mahalleCount[$mahU] = 0;
                    }
                    $mahalleSet[$mahU]['lat'] += $coord['lat'];
                    $mahalleSet[$mahU]['lon'] += $coord['lng'];
                    $mahalleCount[$mahU]++;
                }
            }

            // 2) CADDE/SOKAK EŞLEŞMESİ
            // 2) CADDE/SOKAK EŞLEŞMESİ — sayısal sorguda ters-substring istemeyiz (8013→"1" gürültü)
            $caddeU = $this->trUppercase($caddeAdi);
            $qIsNumeric = preg_match('/^\d+$/', $qU);
            $caddeDirTan = $this->containsU($caddeU, $qU);
            $caddeTers = !$qIsNumeric && mb_strlen($caddeU) >= 3 && $this->containsU($qU, $caddeU);
            if ($caddeAdi !== '' && ($caddeDirTan || $caddeTers)) {
                $csa = (int) ($r['CADDE_SOKA'] ?? 0);
                $coord = $csa > 0 ? ($geomIndex[$csa] ?? null) : null;
                $sorumluluk = trim((string) ($r['SORUMLULUK'] ?? ''));
                $genislik = (float) ($r['GENISLIGI'] ?? 0);
                $results[] = [
                    'type' => 'cadde',
                    'label' => $caddeAdi,
                    'detail' => $mahalle . ($sorumluluk !== '' ? ' · ' . $sorumluluk : '') . ($genislik > 0 ? ' · ' . $genislik . ' m' : ''),
                    'lat' => $coord ? $coord['lat'] : 0,
                    'lon' => $coord ? $coord['lng'] : 0,
                ];
            }
        }

        // Mahalle sonuçları (cadde centroid ortalaması)
        foreach ($mahalleSet as $mahU => $m) {
            if (($mahalleCount[$mahU] ?? 0) <= 0) continue;
            $results[] = [
                'type' => 'mahalle',
                'label' => $m['ad'],
                'detail' => 'Eyyübiye',
                'lat' => round($m['lat'] / $mahalleCount[$mahU], 7),
                'lon' => round($m['lon'] / $mahalleCount[$mahU], 7),
            ];
        }

        // En fazla 12 sonuç (cadde öncelik, mahalle sonra)
        usort($results, function ($a, $b) {
            if ($a['type'] === $b['type']) return 0;
            return $a['type'] === 'cadde' ? -1 : 1;
        });
        $results = array_slice($results, 0, 12);

        return $results;
    }

    /** TR-duyarlı büyük-harfe çevrilmiş stringlerde \tdkısmı "q substr içerir" testi. */
    private function containsU(string $hay, string $needle): bool
    {
        return str_contains($hay, $needle);
    }

    /**
     * 15_alti.js + 15_ustu.js geometrilerinden CADDE_SOKA → centroid index.
     * @return array<int, array{lat:float,lng:float}>
     */
    private function geomCentroidIndex(): array
    {
        static $index = null;
        if ($index !== null) return $index;
        $index = [];

        foreach (['15_alti.js', '15_ustu.js'] as $fn) {
            $path = storage_path('shp/' . $fn);
            if (!file_exists($path)) continue;
            $raw = file_get_contents($path);
            if (!$raw) continue;
            $json = preg_replace('/^var\s+[\w]+\s*=\s*/', '', $raw);
            $json = rtrim($json, "; \n\r");
            $fdc = json_decode($json, true);
            if (!is_array($fdc) || !isset($fdc['features'])) continue;

            foreach ($fdc['features'] as $f) {
                $cid = (int) ($f['properties']['CADDE_SOKA'] ?? 0);
                if ($cid <= 0 || isset($index[$cid])) continue;
                $ct = $this->centroid($f['geometry'] ?? []);
                if ($ct) $index[$cid] = $ct;
            }
        }

        return $index;
    }

    /**
     * WMS NOKTA ATIŞI ADRES BULMA — tam adres metnini çöz, WFS'ten doğrula.
     * "15 TEMMUZ MAHALLESİ, 123. SOKAK, 5" → mahalle + cadde/sokak + kapı no.
     * S2S: GeoServer (geo3) WFS 2.0.0 sorguları doğrudan Http ile (proxy'siz).
     */
    public function adresAra(Request $request)
    {
        $q = trim((string) ($request->input('q') ?? $request->input('adres') ?? ''));
        if ($q === '' || mb_strlen($q) < 3) {
            return response()->json(['success' => false, 'message' => 'Adres çok kısa.']);
        }

        $cacheKey = 'maps_adres_ara_' . md5(mb_strtolower($q, 'UTF-8'));
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) return response()->json($cached);

        $parsed = $this->parseAddress($q);

        $sonuc = ['success' => false, 'message' => 'Adres isabetli bulunamadı.'];

        if ($parsed['cadde'] !== '') {
            // LOCAL-FIRST — caddeler_ve_sokaklar.json + 15_alti/15_ustu geometrisi.
            // Doğru adres, WFS'e gerek kalmadan yerel veriden bulunur (hız + isabet).
            $yerel = $this->localCaddeBul($parsed['cadde'], $parsed['mahalle']);
            if ($yerel && isset($yerel['lat'], $yerel['lon']) && $yerel['lat'] !== null && $yerel['lon'] !== null) {
                $sonuc = array_merge($sonuc, [
                    'success' => true,
                    'lat' => round((float) $yerel['lat'], 7),
                    'lon' => round((float) $yerel['lon'], 7),
                    'mahalle' => $yerel['mahalle'],
                    'cadde' => $yerel['name'],
                    'kapi' => $parsed['kapi'],
                    'detail' => trim(implode(', ', array_filter([
                        $yerel['mahalle'],
                        $yerel['name'],
                        $parsed['kapi'],
                    ]))),
                    'sorumluluk' => $yerel['sorumluluk'],
                    'genislik' => $yerel['genislik'],
                    'source' => 'local',
                    'confidence' => 'yuksek',
                ]);
                \Illuminate\Support\Facades\Cache::put($cacheKey, $sonuc, now()->addMinutes(10));
                return response()->json($sonuc);
            }
        }

        // WFS YEDEĞİ — yerel veride bulunamazsa geoserver (geo3) doğrulaması.
        try {
            // 1) MAHALLE — cbs:MISMAP_MAHALLE_KOYLER (geo3), MAHALLE_ADI
            $mahalle = null;
            if ($parsed['mahalle'] !== '') {
                $mahalle = $this->wfsMahalleBul($parsed['mahalle']);
                if ($mahalle) $sonuc['mahalle'] = $mahalle['name'];
            }

            // 2) CADDE/SOKAK — mahalle poligonunun bboxı içinde ara (isabetli)
            $cadde = null;
            if ($parsed['cadde'] !== '') {
                $cadde = $this->wfsCaddeBul($parsed['cadde'], $mahalle);
                if ($cadde) $sonuc['cadde'] = $cadde['name'];
            }

            // 3) KAPI NO
            $kapi = $parsed['kapi'];
            $sonuc['kapi'] = $kapi;

            // Koordinat önceliği: cadde > mahalle merkezi
            $lat = $cadde['lat'] ?? $mahalle['lat'] ?? null;
            $lon = $cadde['lon'] ?? $mahalle['lon'] ?? null;

            if ($lat !== null && $lon !== null) {
                $sonuc = array_merge($sonuc, [
                    'success' => true,
                    'lat' => round((float) $lat, 7),
                    'lon' => round((float) $lon, 7),
                    'detail' => trim(implode(', ', array_filter([
                        $sonuc['mahalle'] ?? null,
                        $sonuc['cadde'] ?? null,
                        $kapi,
                    ]))),
                    'confidence' => $cadde ? 'yuksek' : ($mahalle ? 'orta' : 'dusuk'),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Adres ara hatası: ' . $e->getMessage());
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $sonuc, now()->addMinutes(10));
        return response()->json($sonuc);
    }

    /**
     * LOCAL-FIRST cadde çözümü — caddeler_ve_sokaklar.json + geometri (CADDE_SOKA köprüsü).
     * Mahalle + cadde no verilir; yerel veride CADDE_SOKA ile eşleşen kaydın
     * 15_alti.js/15_ustu.js geometrisinden centroid koordinatı döner.
     * @return array|null ['name','mahalle','lat','lon','sorumluluk','genislik','source']
     */
    private function localCaddeBul(string $cadde, string $mahalle = ''): ?array
    {
        $path = storage_path('shp/caddeler_ve_sokaklar.json');
        if (!file_exists($path)) return null;
        $json = file_get_contents($path);
        if (!$json) return null;

        // NDJSON normalize (JSON array'e çevir)
        $fixed = preg_replace('/\}\s*\n\s*\{/', '},' . "\n" . '{', $json);
        $data  = json_decode('[' . $fixed . ']', true);
        if (!is_array($data)) return null;

        $caddeNo = trim((string) $cadde);
        if ($caddeNo === '') return null;

        // CADDE_SOKA ADI alanı sayısal olabilir ("8013") — string karşılaştır
        $adaylar = [];
        $mahU = $this->trUppercase(trim($mahalle));
        foreach ($data as $r) {
            $ad = trim((string) ($r['CADDE SOKAK ADI'] ?? ''));
            if ($ad === '' || ltrim($ad, '0') !== ltrim($caddeNo, '0')) continue;
            $adaylar[] = $r;
        }
        if (empty($adaylar)) return null;

        // Mahalle varsa onunla eşleştir
        $secim = null;
        $secimMahalle = null;
        foreach ($adaylar as $a) {
            $m = trim((string) ($a['MAHALLE_AD'] ?? ''));
            if ($mahU !== '' && $this->trUppercase($m) === $mahU) {
                $secim = $a;
                $secimMahalle = $m;
                break;
            }
        }
        if (!$secim) {
            $secim = $adaylar[0];
            $secimMahalle = trim((string) ($secim['MAHALLE_AD'] ?? ''));
        }

        $csa = (int) ($secim['CADDE_SOKA'] ?? 0);

        // Geometri centroid — 15_alti.js + 15_ustu.js
        $coord = $this->shpCaddeSokaCentroid($csa);

        return [
            'name' => trim((string) ($secim['CADDE SOKAK ADI'] ?? $caddeNo)),
            'mahalle' => $secimMahalle,
            'lat' => $coord ? $coord['lat'] : null,
            'lon' => $coord ? $coord['lng'] : null,
            'sorumluluk' => trim((string) ($secim['SORUMLULUK'] ?? '')),
            'genislik' => (float) ($secim['GENISLIGI'] ?? 0),
            'source' => 'local',
        ];
    }

    /**
     * 15_alti.js / 15_ustu.js'de CADDE_SOKA ile eşleşen geometriden centroid döner.
     * @return array{lat:float,lng:float}|null
     */
    private function shpCaddeSokaCentroid(int $caddeSoka): ?array
    {
        if ($caddeSoka <= 0) return null;

        $files = ['15_alti.js', '15_ustu.js'];
        foreach ($files as $fn) {
            $path = storage_path('shp/' . $fn);
            if (!file_exists($path)) continue;
            $raw = file_get_contents($path);
            if (!$raw) continue;
            // "var EybAlti = {...;" → "{...}"
            $json = preg_replace('/^var\s+[\w]+\s*=\s*/', '', $raw);
            $json = rtrim($json, "; \n\r");
            $fdc = json_decode($json, true);
            if (!is_array($fdc) || !isset($fdc['features'])) continue;

            foreach ($fdc['features'] as $f) {
                $cid = (int) ($f['properties']['CADDE_SOKA'] ?? 0);
                if ($cid !== $caddeSoka) continue;
                $centroid = $this->centroid($f['geometry'] ?? []);
                if ($centroid) return $centroid;
            }
        }
        return null;
    }


    /**
     * WMS MAHALLE → CADDE/SOKAK LİSTESİ — alt kurumların çoklu çalışması için.
     * Girilen mahallenin TÜM cadde/sokaklarını WFS'ten döndürür (autocomplete).
     */
    public function mahalleCaddeler(Request $request)
    {
        $mahalle = trim((string) $request->input('mahalle'));
        if ($mahalle === '' || mb_strlen($mahalle) < 2) {
            return response()->json(['success' => false, 'message' => 'Mahalle adı çok kısa.']);
        }

        $cacheKey = 'maps_mahalle_caddeler_' . md5(mb_strtoupper($mahalle, 'UTF-8'));
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) return response()->json($cached);

        $caddeler = [];
        try {
            $caddeler = $this->wfsMahalleCaddeleri($mahalle);
        } catch (\Exception $e) {
            Log::warning('Mahalle cadde listesi hatası: ' . $e->getMessage());
        }

        $sonuc = [
            'success' => !empty($caddeler),
            'mahalle' => $mahalle,
            'caddeler' => $caddeler,
        ];

        \Illuminate\Support\Facades\Cache::put($cacheKey, $sonuc, now()->addMinutes(10));
        return response()->json($sonuc);
    }

    /**
     * Türkçe adres çözücü — "8125. Sk. 122 Kadıkendi, 63000 Eyyübiye/Şanlıurfa"
     * → {mahalle:'Kadıkendi', cadde:'8125', kapi:'122'}
     * Formatlar: "MAHALLE + CADDE + KAPI", "Kadıkendi 8125. Sokak", "15 TEMMUZ MAHALLESİ, 123. Sokak, 5"
     */
    private function parseAddress(string $q): array
    {
        $mahalle = '';
        $cadde = '';
        $kapi = '';

        // 1) Şehir/ilçe/posta kodu temizle (sadece mahalle+cadde+kapi kalır)
        $temiz = preg_replace('/\b\d{5}\b\s*/', ' ', $q);
        $temiz = str_replace(['/', ';'], ',', $temiz);
        $parcalar = array_values(array_filter(array_map('trim', explode(',', $temiz)), fn ($p) => $p !== ''));
        $parcalar = array_values(array_filter($parcalar, function ($p) {
            return ! preg_match('/(eyyübiye|şanlıurfa|sanliurfa|yyübiye)/iu', $p);
        }));
        $asil = implode(' ', $parcalar);

        // 2) Sokak no: sayı + SK/SOK/CADDE etiketi ("8125. Sk.", "123. Sokak", "3097 SOKAK")
        if (preg_match('/(?:^|[\s,])?(\d{1,5})[\s\.]*\.?\s*(?:SK\.?|SOK\.?|SOKAK|SOKAĞI|SK|Cad\.?|CADDE|CD\.?|Cd\.?)/iu', $asil, $m)) {
            $cadde = $m[1];
        } elseif (preg_match('/(\d{1,5})\s+(?:SOKAK|SOKAĞI|SK|SOK|CADDE|CAD)/iu', $asil, $m2)) {
            $cadde = $m2[1];
        } elseif (preg_match('/^(\d{1,5})/u', $asil, $m3)) {
            $cadde = $m3[1];
        }

        // 3) Kapı no: cadde numarasından SONRAKİ sayı (122) — sokak no karşıolmaz
        if ($cadde !== '') {
            $pos = strpos($asil, $cadde);
            $sonrasi = substr($asil, $pos + strlen($cadde));
            if (preg_match('/(\d{1,4}[A-Za-z]?)/u', $sonrasi, $mk)) {
                // "122" gibi — etiketsiz ilk sayı kapı no
                $kapi = $mk[1];
            }
        }

        // 4) Mahalle: "MAHALLESİ" ekiyle biten grup VEYA sayı/etiket içermeyen son kelime
        if (preg_match('/([A-Za-zÇĞİÖŞÜçğıöşü0-9\s\.\-]*?(?:MAHALLESİ|MAHALESİ|MAHALLE|MAH\.|MAH|MH))/iu', $asil, $mm)) {
            $mahalle = trim($mm[1]);
        } elseif (preg_match_all('/\b([A-Za-zÇĞİÖŞÜçğıöşü]{3,})\b/iu', $asil, $kelt)) {
            $etiketler = ['SK', 'SOK', 'SOKAK', 'SOKAĞI', 'CAD', 'CADDE', 'CD', 'MAH', 'MAHALLE', 'MAHALLESİ', 'MH'];
            $adaylar = array_values(array_filter($kelt[1], function ($w) use ($etiketler) {
                $u = mb_strtoupper($w, 'UTF-8');
                $etiks = array_map(fn ($e) => mb_strtoupper($e, 'UTF-8'), $etiketler);
                return ! in_array($u, $etiks, true) && mb_strlen($w, 'UTF-8') >= 4;
            }));
            if (! empty($adaylar)) {
                $mahalle = mb_convert_case(end($adaylar), MB_CASE_TITLE, 'UTF-8');
            }
        }

        // 5) Mahalle eklerini temizle: "BATIKENT MAHALLESİ"/"BATIKENT MAH." → "BATIKENT"
        // (local JSON MAHALLE_AD ve WFS MAHALLE_ADI ek'sizdir — ILIKE/local eşleşme için şart)
        if ($mahalle !== '') {
            $mahalle = trim((string) preg_replace(
                '/\s*(?:MAHALLESİ|MAHALESİ|MAHALLE|MAH\.|MAH|MH)\s*$/iu',
                '',
                $mahalle
            ));
        }

        return ['mahalle' => $mahalle, 'cadde' => $cadde, 'kapi' => $kapi];
    }

    /** GeoServer WFS uç noktası — Eyyübiye Büyükşehir. */
    private const WFS_URL = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs';

    /** Mahalle listesi cache süresi (1 saat — mahalleler nadiren değişir). */
    private const CACHE_TTL = 3600;

    /**
     * AYKOME yalnızca Eyyübiye ilçesi için çalışır. Şanlıurfa'da aynı isimli
     * mahalleler birden çok ilçede olabilir (BATIKENT Eyyübiye + Karaköprü).
     * Bu sabit, tüm WMS mahalle sorgularında ILCE_NO=63011 (Eyyübiye) filtreler.
     */
    private const EYYUBIYE_ILCE_NO = 63011;

    /**
     * Ortak WFS HTTP client — WFS 1.1.0 (Opus'tan doğrulanmış, 2.0.0'dan stabil).
     * BBOX query parametresi: "minLng,minLat,maxLng,maxLat,EPSG:4326"
     */
    private function wfsGet(array $params): array
    {
        $defaults = [
            'service'      => 'WFS',
            'version'      => '1.1.0',
            'request'      => 'GetFeature',
            'outputFormat' => 'application/json',
            'srsName'      => 'EPSG:4326',
        ];

        $query = array_merge($defaults, $params);

        $resp = Http::withOptions(['verify' => false, 'timeout' => 15])->get(self::WFS_URL, $query);

        if (!$resp->successful()) {
            Log::warning('[maps.wfsGet] Hata', ['status' => $resp->status(), 'params' => $query]);
            throw new \Exception("WFS HTTP {$resp->status()}");
        }

        return $resp->json();
    }

    /**
     * GET /maps/mahalleler — tüm Eyyübiye mahalleleri (cache'li, client-side arama).
     * Her mahalle: {ad, center:{lat,lng}, bbox:{minLng,minLat,maxLng,maxLat}}
     */
    public function mahalleler(Request $request)
    {
        $cacheKey = 'wfs_mahalleler_' . self::EYYUBIYE_ILCE_NO;

        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $data = $this->wfsGet([
                'typeName'    => 'cbs:MISMAP_MAHALLE_KOYLER',
                'CQL_FILTER'  => "ILCE_NO='" . self::EYYUBIYE_ILCE_NO . "'",
                'maxFeatures' => 300,
            ]);

            $mahalleler = [];
            foreach ($data['features'] ?? [] as $f) {
                $ad = $f['properties']['MAHALLE_ADI'] ?? null;
                if (!$ad) continue;

                $geom = $f['geometry'] ?? null;
                $bbox = $geom ? $this->getBbox($geom) : null;
                $center = $geom ? $this->centroid($geom) : null;

                $mahalleler[] = [
                    'ad' => $ad,
                    'center' => $center,
                    'bbox' => $bbox,
                ];
            }

            usort($mahalleler, fn ($a, $b) => strcmp(
                $this->trUppercase($a['ad']),
                $this->trUppercase($b['ad'])
            ));

            return $mahalleler;
        });

        // Client-side arama filtresi (?q=)
        $q = $request->get('q', '');
        if ($q !== '') {
            $qUpper = $this->trUppercase($q);
            $result = array_values(array_filter(
                $result,
                fn ($m) => str_contains($this->trUppercase($m['ad']), $qUpper)
            ));
        }

        return response()->json([
            'success' => true,
            'count' => count($result),
            'data' => $result,
        ]);
    }

    /**
     * Kapı numarası fallback — smpns:MISMAP_NUM_BINA (ULUSAL_BINA_NO).
     * m_Numarataj katmanı GeoServer'da 500 verdiği için bina katmanı kullanılır.
     * @param  string  $bbox  "minLng,minLat,maxLng,maxLat"
     */
    public function kapiNoAra(Request $request)
    {
        $bbox = trim((string) $request->input('bbox', ''));
        if ($bbox === '') {
            return response()->json(['success' => false, 'message' => 'bbox gerekli'], 422);
        }

        try {
            $data = $this->wfsGet([
                'typeName'    => 'smpns:MISMAP_NUM_BINA',
                'BBOX'        => "{$bbox},EPSG:4326",
                'maxFeatures' => 100,
            ]);

            $binalar = [];
            foreach ($data['features'] ?? [] as $f) {
                $p = $f['properties'] ?? [];
                $no = $p['ULUSAL_BINA_NO'] ?? $p['BINA_NO'] ?? null;
                if ($no === null) continue;

                $center = $this->centroid($f['geometry'] ?? null);
                if (!$center) continue;

                $binalar[] = [
                    'no' => (string) $no,
                    'ada' => $p['ADA'] ?? null,
                    'parsel' => $p['PARSEL'] ?? null,
                    'mahalle' => $p['MAHALLE_ADI'] ?? null,
                    'lat' => $center['lat'],
                    'lng' => $center['lng'],
                ];
            }

            // Numerik sıralama
            usort($binalar, function ($a, $b) {
                $an = (int) filter_var($a['no'], FILTER_SANITIZE_NUMBER_INT);
                $bn = (int) filter_var($b['no'], FILTER_SANITIZE_NUMBER_INT);
                return $an <=> $bn;
            });

            return response()->json([
                'success' => true,
                'count' => count($binalar),
                'data' => $binalar,
            ]);
        } catch (\Exception $e) {
            Log::warning('[maps.kapiNoAra]', ['err' => $e->getMessage()]);
            return response()->json(['success' => true, 'count' => 0, 'data' => [], 'note' => $e->getMessage()]);
        }
    }

    /**
     * GET /maps/cadde-veri — Eyyübiye tüm cadde/sokak kayıtları (caddeler_ve_sokaklar.json).
     * TEK DOĞRU KAYNAK: mahalle + cadde adı + 15m durumu (SORUMLULUK) + CADDE_SOKA köprüsü.
     * 15_alti.js/15_ustu.js geometrisiyle CADDE_SOKA üzerinden eşleşir (%100 kanıtlandı).
     */
    public function caddeVeri(Request $request)
    {
        $cacheKey = 'wfs_cadde_veri_eyyubiye';
        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $path = storage_path('shp/caddeler_ve_sokaklar.json');
            if (!file_exists($path)) {
                return ['success' => false, 'message' => 'Cadde veri dosyası bulunamadı'];
            }

            $content = file_get_contents($path);
            // NDJSON formatı: {..} {..} arka arkaya, köşeli parantez yok → normalize et
            $fixed = preg_replace('/\}\s*\n\s*\{/', '},' . "\n" . '{', $content);
            $json = '[' . $fixed . ']';
            $data = json_decode($json, true);

            if (!is_array($data)) {
                return ['success' => false, 'message' => 'Cadde veri parse edilemedi'];
            }

            // Sadeleştirilmiş kayıtlar (gereksiz alanları çıkar, boyut küçük)
            $kayitlar = [];
            foreach ($data as $r) {
                $mahalle = trim((string) ($r['MAHALLE_AD'] ?? ''));
                $caddeAdi = (string) ($r['CADDE SOKAK ADI'] ?? '');
                if ($mahalle === '' || $caddeAdi === '') continue;

$kayitlar[] = [
                    'cadde_soka' => (int) ($r['CADDE_SOKA'] ?? 0),
                    'mahalle' => $mahalle,
                    'cadde_adi' => trim($caddeAdi),
                    'turu' => trim((string) ($r['TÜRÜ'] ?? '')),
                    // 15 METRE ALTI / 15 METRE ÜSTÜ — direkt 15m bilgisi
                    'sorumluluk' => trim((string) ($r['SORUMLULUK'] ?? '')),
                    'genislik' => (float) ($r['GENISLIGI'] ?? 0),
                    'uzunluk' => (string) ($r['UZUNLUGU'] ?? ''),
                    'kaplama' => trim(html_entity_decode((string) ($r['KAPLAMA_CI'] ?? ''))),
                    'arter' => trim((string) ($r['ANA__ARTER'] ?? '')),
                    'trafik_yolu' => trim((string) ($r['TRAFIK_YÖ'] ?? '')),
                    'serit' => trim((string) ($r['SERIT_SAYI'] ?? '')),
                    'uavt_turu' => trim(html_entity_decode((string) ($r['UAVT_YOL_T'] ?? ''))),
                ];
            }

            return ['success' => true, 'count' => count($kayitlar), 'data' => $kayitlar];
        });

        return response()->json($result);
    }

    /** GeoJSON geometrisinden centroid (tüm tipler: Point/Polygon/LineString/Multi*). */
    private function centroid(array $geometry): ?array
    {
        $coords = [];
        $this->flattenCoords($geometry['coordinates'] ?? [], $coords);
        if (empty($coords)) return null;

        return [
            'lat' => round(array_sum(array_column($coords, 1)) / count($coords), 7),
            'lng' => round(array_sum(array_column($coords, 0)) / count($coords), 7),
        ];
    }

    /** Koordinat dizisini düzleştirir. */
    private function flattenCoords(array $arr, array &$out): void
    {
        if (isset($arr[0]) && is_numeric($arr[0])) {
            $out[] = $arr;
            return;
        }
        foreach ($arr as $item) {
            if (is_array($item)) {
                $this->flattenCoords($item, $out);
            }
        }
    }

    /** GeoJSON geometrisinden bbox. */
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
            'bbox' => implode(',', [min($lngs), min($lats), max($lngs), max($lats)]),
        ];
    }

    /**
     * WFS mahalle sorgusu — cbs:MISMAP_MAHALLE_KOYLER (geo3, WFS 2.0.0)
     * @return array|null ['name', 'lat', 'lon', 'bbox']
     */
    private function wfsMahalleBul(string $mahalle): ?array
    {
        // TÜRKÇE KARAKTER ÇÖZÜMÜ: GeoServer ILIKE, 'İ'/'I' ayrımını yapmaz.
        // Kullanıcı 'Kadıkendi' yazar; büyük harfte 'KADIKENDI'(ascii) ya da
        // 'KADIKENDİ'(Türkçe) olabilir. Veride GERÇEK değer neyse onu eşleştirir:
        // → ASCII ve Türkçe İ varyantlarını sırayla dene.
        $adlar = $this->turkeVariants(trim($mahalle));
        if (empty($adlar)) return null;

        $url = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs'
            . '?service=WFS&version=2.0.0&request=GetFeature'
            . '&typeNames=cbs:MISMAP_MAHALLE_KOYLER'
            . '&outputFormat=application/json&srsName=EPSG:4326&count=3';

        foreach ($adlar as $q) {
            // EYYÜBİYE İLÇE FİLTRESİ: aynı isimli mahalle başka ilçede olmasın
            $u = $url . '&cql_filter=' . urlencode("MAHALLE_ADI ILIKE '%{$q}%' AND ILCE_NO=" . self::EYYUBIYE_ILCE_NO) . '&outputFormat=application/json&srsName=EPSG:4326&count=1';
            $resp = Http::withOptions(['verify' => false, 'timeout' => 5])->get($u);
            if (!$resp->successful()) continue;

            $data = $resp->json();
            $f = $data['features'][0] ?? null;
            if (!$f) continue;

            $center = $this->centroidFromGeoJson($f['geometry'] ?? null);
            if (!$center) continue;

            // Mahalle poligonunun TAM bbox'ı — cadde arama kuruluşu için (KADIKENDİ büyük mahalle)
            $bbox = null;
            $geom = $f['geometry'] ?? null;
            $ring = $geom['coordinates'][0] ?? null;
            if (is_array($ring) && count($ring) >= 3) {
                $lons = array_column($ring, 0);
                $lats = array_column($ring, 1);
                $bbox = [
                    'min_x' => min($lons), 'max_x' => max($lons),
                    'min_y' => min($lats), 'max_y' => max($lats),
                ];
            }

            return [
                'name' => trim((string) ($f['properties']['MAHALLE_ADI'] ?? $mahalle)),
                'lat' => $center['lat'],
                'lon' => $center['lng'],
                'bbox' => $bbox,
            ];
        }

        return null;
    }

    /**
     * WFS cadde/sokak sorgusu — cbs:MISMAP_CADDE_SOKAK (geo3, WFS 2.0.0)
     * DOĞRULANMIŞ ŞEMA: cadde adı = CADDE_SOKAK_ADI (CADDE_SO_1/2 yok).
     * Mahalle (array) verilirse BBOX-CRS ile o mahallenin sınırı içinde arar
     * → "8125 SOKAK" hangi ilçede olduğu belirsizse Kadıkendi'deki bulunur.
     * @param  array|null  $mahalle  ['name','lat','lon'] wfsMahalleBul sonucu
     * @return array|null ['name', 'lat', 'lon']
     */
    private function wfsCaddeBul(string $cadde, ?array $mahalle = null): ?array
    {
        $adlar = $this->turkeVariants(trim($cadde));
        if (empty($adlar)) return null;

        $url = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs'
            . '?service=WFS&version=2.0.0&request=GetFeature'
            . '&typeNames=cbs:MISMAP_CADDE_SOKAK'
            . '&outputFormat=application/json&srsName=EPSG:4326&count=5';

        foreach ($adlar as $q) {
            $filter = "CADDE_SOKAK_ADI ILIKE '%{$q}%'";

            // EYYÜBİYE SINIRI: AYKOME yalnızca Eyyübiye için çalışır.
            // Mahalle bbox'ı varsa onu kullan, yoksa ilçe geniş bbox'ı zorla
            // (aynı cadde adı başka ilçede de varsa Karaköprü'ye gitmesin).
            if ($mahalle && !empty($mahalle['bbox'])) {
                $b = $mahalle['bbox'];
                $minX = $b['min_x'] - 0.001; $maxX = $b['max_x'] + 0.001;
                $minY = $b['min_y'] - 0.001; $maxY = $b['max_y'] + 0.001;
            } elseif ($mahalle && isset($mahalle['lat'])) {
                $minX = $mahalle['lon'] - 0.005; $maxX = $mahalle['lon'] + 0.005;
                $minY = $mahalle['lat'] - 0.005; $maxY = $mahalle['lat'] + 0.005;
            } else {
                // Eyyübiye ilçe bbox'ı (Karaköprü 37.19+ / güneyde kapsar; geniş ama
                // aynı isimli cadde Karaköprü'ye kaçmasın diye üst sınır dar tutulur)
                $minX = 38.60; $maxX = 39.00;
                $minY = 36.90; $maxY = 37.18;
            }
            $filter .= " AND BBOX(GEOMETRY, {$minX},{$minY},{$maxX},{$maxY},'EPSG:4326')";

            $u = $url . '&cql_filter=' . urlencode($filter);
            $resp = Http::withOptions(['verify' => false, 'timeout' => 6])->get($u);
            if (!$resp->successful()) continue;

            $data = $resp->json();
            $f = $data['features'][0] ?? null;
            if (!$f) continue;

            $center = $this->centroidFromGeoJson($f['geometry'] ?? null);
            if (!$center) continue;

            return [
                'name' => trim((string) ($f['properties']['CADDE_SOKAK_ADI'] ?? $cadde)),
                'lat' => $center['lat'],
                'lon' => $center['lng'],
            ];
        }

        return null;
    }

    /**
     * WFS mahalle → tüm cadde/sokaklar — INTERSECTS/BBOX CRS'li (geo3, WFS 2.0.0)
     * DOĞRULANMIŞ: cadde katmanında MAHALLE yok; mahalle poligonunun bbox'ı
     * ile 'EPSG:4326' CRS'li BBOX sorgusu o mahallenin caddelerini verir.
     * @return array [{name, lat, lon}]
     */
    private function wfsMahalleCaddeleri(string $mahalle): array
    {
        // TÜRKÇE/ASCII varyantları dene (trUppercase tek varyant yetmez)
        $adlar = $this->turkeVariants(trim($mahalle));
        if (empty($adlar)) return [];

        $mahFeature = null;
        $mahResp = null;
        // 1) Mahalle poligonu — cbs:MISMAP_MAHALLE_KOYLER (MAHALLE_ADI)
        // EYYÜBİYE İLÇE FİLTRESİ: aynı isimli mahalle başka ilçede olmasın
        $mahUrl = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs'
            . '?service=WFS&version=2.0.0&request=GetFeature'
            . '&typeNames=cbs:MISMAP_MAHALLE_KOYLER'
            . '&outputFormat=application/json&srsName=EPSG:4326&count=1';

        foreach ($adlar as $q) {
            $mahUrlQ = $mahUrl . '&cql_filter=' . urlencode("MAHALLE_ADI ILIKE '%{$q}%' AND ILCE_NO=" . self::EYYUBIYE_ILCE_NO);
            $mahResp = Http::withOptions(['verify' => false, 'timeout' => 6])->get($mahUrlQ);
            if (!$mahResp->successful()) continue;
            $mahData = $mahResp->json();
            if (!empty($mahData['features'])) {
                $mahFeature = $mahData['features'][0];
                break;
            }
        }
        if (!$mahFeature || empty($mahFeature['geometry']['coordinates'][0])) return [];

        // Mahalle poligonunun bbox'ı (küçük genişletme ile)
        $coords = $mahFeature['geometry']['coordinates'][0];
        $lons = array_column($coords, 0);
        $lats = array_column($coords, 1);
        // Genişletilmiş bbox (0.002° ~ 200m) — mahalle sınırındaki caddeler de dahil olsun
        $minX = min($lons) - 0.002;
        $maxX = max($lons) + 0.002;
        $minY = min($lats) - 0.002;
        $maxY = max($lats) + 0.002;

        // 2) Cadde katmanı — BBOX CRS'li (EPSG:4326) — DOĞRULANMIŞ ÇALIŞIYOR
        $cql = "BBOX(GEOMETRY, {$minX},{$minY},{$maxX},{$maxY},'EPSG:4326')";
        $cadUrl = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs'
            . '?service=WFS&version=2.0.0&request=GetFeature'
            . '&typeNames=cbs:MISMAP_CADDE_SOKAK'
            . '&cql_filter=' . urlencode($cql)
            . '&outputFormat=application/json&srsName=EPSG:4326&count=250';
        $resp = Http::withOptions(['verify' => false, 'timeout' => 10])->get($cadUrl);
        if (!$resp->successful()) return [];

        $data = $resp->json();
        $features = $data['features'] ?? [];

        $seen = [];
        $caddeler = [];
        foreach ($features as $f) {
            $p = $f['properties'] ?? [];
            $name = trim((string) ($p['CADDE_SOKAK_ADI'] ?? ''));
            if ($name === '') continue;
            $key = mb_strtoupper($name, 'UTF-8');
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $center = $this->centroidFromGeoJson($f['geometry'] ?? null);
            if (!$center) continue;

            $caddeler[] = [
                'name' => $name,
                'lat' => round((float) $center['lat'], 7),
                'lon' => round((float) $center['lng'], 7),
            ];
            if (count($caddeler) >= 100) break;
        }

        usort($caddeler, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $caddeler;
    }

    /**
     * Türkçe/ASCII karakter varyantları — GeoServer ILIKE 'İ' vs 'I' ayrımı yapmaz.
     * Girdi 'Kadıkendi' ise Türkçe doğru büyütme 'KADIKENDİ' (noktalı İ) üretir;
     * 'md_strtoupper' ASCII 'I' üretir. İkisi de GeoServer sorgularında denenir.
     * @return list<string>
     */
    private function turkeVariants(string $ad): array
    {
        $ad = trim($ad);
        if ($ad === '') return [];

        $tr = $this->trUppercase($ad);            // Kadıkendi → KADIKENDİ
        $ascii = strtr($tr, ['İ' => 'I', 'Ş' => 'S', 'Ğ' => 'G', 'Ü' => 'U', 'Ö' => 'O', 'Ç' => 'C']);

        $list = array_values(array_unique(array_filter([$ad, $tr, $ascii], fn ($v) => $v !== '')));

        return $list;
    }

    /**
     * Türkçe kurallı büyük harfe çevirir: küçük i → büyük İ (noktalı),
     * küçük ı → büyük I (noktasız) — mb_strtoupper'ın İ→I hatasını düzeltir.
     */
    private function trUppercase(string $s): string
    {
        $out = '';
        $len = mb_strlen($s, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($s, $i, 1, 'UTF-8');
            $out .= match ($ch) {
                'i' => 'İ',
                'ı' => 'I',
                'ş' => 'Ş', 'ğ' => 'Ğ', 'ü' => 'Ü', 'ö' => 'Ö', 'ç' => 'Ç',
                default => mb_strtoupper($ch, 'UTF-8'),
            };
        }

        return $out;
    }

    private function centroidFromGeoJson($geom)
    {
        if (!$geom) return null;
        $type = $geom['type'] ?? '';
        $coords = $geom['coordinates'] ?? [];

        if ($type === 'Point') {
            return ['lng' => $coords[0], 'lat' => $coords[1]];
        }

        $points = [];
        if ($type === 'Polygon') {
            $points = $coords[0] ?? [];
        } elseif ($type === 'MultiLineString' || $type === 'MultiPolygon') {
            $ring = $coords[0][0] ?? $coords[0] ?? [];
            $points = $ring;
        } elseif ($type === 'LineString') {
            $points = $coords;
        }

        if (empty($points)) return null;

        $sumLat = 0; $sumLng = 0; $count = 0;
        foreach ($points as $p) {
            if (is_array($p) && count($p) >= 2) {
                $sumLng += $p[0];
                $sumLat += $p[1];
                $count++;
            }
        }
        if ($count === 0) return null;

        return ['lng' => $sumLng / $count, 'lat' => $sumLat / $count];
    }

    public function proxy(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            return response()->json(['error' => 'URL parametresi gerekli'], 400);
        }

        $decodedUrl = urldecode($url);

        if (!str_contains($decodedUrl, 'geo4.sanliurfa.bel.tr') &&
            !str_contains($decodedUrl, 'geo2.sanliurfa.bel.tr') &&
            !str_contains($decodedUrl, 'geo3.sanliurfa.bel.tr')) {
            return response()->json(['error' => 'İzin verilmeyen domain'], 403);
        }

        try {
            $response = Http::withOptions(['verify' => false])->timeout(30)->get($decodedUrl);
            return response($response->body(), $response->status(), [
                'Content-Type' => $response->header('Content-Type', 'application/xml'),
            ]);
        } catch (\Exception $e) {
            Log::error('WFS Proxy hatası: ' . $e->getMessage());
            return response()->json(['error' => 'WFS sorgusu başarısız'], 500);
        }
    }

    public function basvuruSorgula(Request $request)
    {
        $q = $request->input('q');
        $kurum = $request->input('kurum');
        $tarihBaslangic = $request->input('tarih_baslangic');
        $tarihBitisi = $request->input('tarih_bitis');

        $query = Application::query();

        if ($q) {
            $query->where('application_no', 'like', '%' . $q . '%');
        }

        if ($kurum) {
            $query->where('institution_id', $kurum);
        }

        if ($tarihBaslangic) {
            $query->whereDate('created_at', '>=', $tarihBaslangic);
        }

        if ($tarihBitisi) {
            $query->whereDate('created_at', '<=', $tarihBitisi);
        }

        $basvurular = $query->select('id', 'application_no', 'institution_id', 'status', 'created_at')
            ->with('excavationAreas')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $results = $basvurular->map(function ($b) {
            $ea = $b->excavationAreas->first();
            $lat = $ea ? $ea->center_lat : null;
            $lng = $ea ? $ea->center_lng : null;

            // Fallback: gis_basvuru_noktalar
            if (!$lat || !$lng) {
                $nokta = \App\Models\GisBasvuruNokta::where('basvuru_id', $b->id)->first();
                if ($nokta) {
                    $lat = $nokta->lat;
                    $lng = $nokta->lng;
                }
            }

            // Fallback: gis_cizimler
            if (!$lat || !$lng) {
                $cizim = \App\Models\GisCizim::where('basvuru_id', $b->id)->first();
                if ($cizim && $cizim->lat && $cizim->lng) {
                    $lat = $cizim->lat;
                    $lng = $cizim->lng;
                }
            }

            return [
                'id' => $b->id,
                'application_no' => $b->application_no,
                'kurum_id' => $b->institution_id,
                'kurum_adi' => $b->institution ? $b->institution->name : '—',
                'durum' => $b->status,
                'tarih' => $b->created_at ? $b->created_at->format('d.m.Y') : '—',
                'lat' => $lat,
                'lng' => $lng,
            ];
        });

        return response()->json(['data' => $results]);
    }

    public function noktaKaydet(Request $request)
    {
        $data = $request->validate([
            'kurum_id' => ['nullable', 'integer'],
            'basvuru_tipi' => ['nullable', 'in:kazi_ruhsat,ortak_kazi'],
            'ortak_kurumlar' => ['nullable', 'string', 'max:500'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'mahalle' => ['nullable', 'string', 'max:100'],
            'ada' => ['nullable', 'string', 'max:50'],
            'parsel' => ['nullable', 'string', 'max:50'],
            'selected_parsellers' => ['nullable', 'string'],
            'geometri' => ['nullable', 'json'],
            'draw_type' => ['nullable', 'string', 'max:20'],
            'work_type' => ['nullable', 'string', 'max:100'],
            'address_text' => ['nullable', 'string', 'max:500'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'applicant_first_name' => ['nullable', 'string', 'max:100'],
            'applicant_last_name' => ['nullable', 'string', 'max:100'],
            'applicant_national_id' => ['nullable', 'string', 'max:11'],
            'applicant_phone' => ['nullable', 'string', 'max:20'],
            'secili_caddeler' => ['nullable', 'array'],
            'secili_caddeler.*' => ['nullable', 'string'],
        ]);

        $nokta = GisBasvuruNokta::create([
            'basvuru_tipi' => $data['basvuru_tipi'] ?? 'kazi_ruhsat',
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'ilce' => $data['ilce'] ?? '',
            'mahalle' => $data['mahalle'] ?? '',
            'ada' => $data['ada'] ?? '',
            'parsel' => $data['parsel'] ?? '',
            'wfs_response' => json_encode([
                'kurum_id' => $data['kurum_id'] ?? null,
                'ortak_kurumlar' => $data['ortak_kurumlar'] ?? '',
                'selected_parsellers' => $data['selected_parsellers'] ?? '[]',
                'geometri' => $data['geometri'] ?? null,
                'draw_type' => $data['draw_type'] ?? null,
                'work_type' => $data['work_type'] ?? '',
                'address_text' => $data['address_text'] ?? '',
                'start_date' => $data['start_date'] ?? '',
                'end_date' => $data['end_date'] ?? '',
                'applicant_first_name' => $data['applicant_first_name'] ?? '',
                'applicant_last_name' => $data['applicant_last_name'] ?? '',
                'applicant_national_id' => $data['applicant_national_id'] ?? '',
                'applicant_phone' => $data['applicant_phone'] ?? '',
                'secili_caddeler' => $data['secili_caddeler'] ?? [],
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Başvuru kaydedildi.',
            'data' => $nokta,
        ]);
    }

    public function basvuruOlustur(Request $request)
    {
        $data = $request->validate([
            'basvuru_tipi' => ['nullable', 'in:kazi_ruhsat,ortak_kazi'],
            'ortak_kurumlar' => ['nullable', 'string', 'max:500'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'mahalle' => ['nullable', 'string', 'max:100'],
            'ada' => ['nullable', 'string', 'max:50'],
            'parsel' => ['nullable', 'string', 'max:50'],
            'address_text' => ['nullable', 'string', 'max:500'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'applicant_first_name' => ['required', 'string', 'max:100'],
            'applicant_last_name' => ['required', 'string', 'max:100'],
            'applicant_national_id' => ['nullable', 'string', 'max:11'],
            'applicant_phone' => ['nullable', 'string', 'max:20'],
            'excavation_reason' => ['nullable', 'string', 'max:255'],
            'work_type' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'surface_type_id' => ['nullable', 'integer', 'exists:surface_types,id'],
            'width_m' => ['nullable', 'numeric', 'min:0'],
            'length_m' => ['nullable', 'numeric', 'min:0'],
            'polygon_geojson' => ['nullable', 'json'],
            'total_area_m2' => ['nullable', 'numeric', 'min:0'],
            'drawing_type' => ['nullable', 'string', 'in:polygon,polyline'],
            'drawing_length_m' => ['nullable', 'numeric', 'min:0'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'excavation_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $user = auth()->user();

            $application = \App\Models\Application::query()->create([
                'application_no' => null,
                'institution_id' => $data['institution_id'] ?? $user?->institution_id ?? 1,
                'created_by' => $user?->id ?? 1,
                'status' => \App\Enums\ApplicationStatus::Draft,
                'applicant_first_name' => $data['applicant_first_name'],
                'applicant_last_name' => $data['applicant_last_name'],
                'applicant_national_id' => $data['applicant_national_id'] ?? null,
                'tc_no' => $data['applicant_national_id'] ?? null,
                'identity_no' => $data['applicant_national_id'] ?? null,
                'applicant_phone' => $data['applicant_phone'] ?? null,
                'excavation_reason' => $data['excavation_reason'] ?? null,
                'work_type' => $data['work_type'] ?? null,
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? now()->addDay(),
                'end_date' => $data['end_date'] ?? now()->addDays(30),
                'address_text' => $data['address_text'] ?? null,
                'width_m' => $data['width_m'] ?? null,
                'length_m' => $data['length_m'] ?? null,
                'deposit_amount' => $data['deposit_amount'] ?? null,
                'excavation_amount' => $data['excavation_amount'] ?? null,
                'total_area_m2' => $data['total_area_m2'] ?? 0,
            ]);

            $application->update([
                'application_no' => now()->year . '-' . str_pad($application->id, 4, '0', STR_PAD_LEFT),
            ]);

            if (! empty($data['polygon_geojson']) || ! empty($data['center_lat'])) {
                $service = app(\App\Services\MapDrawingService::class);
                $service->syncPrimaryArea($application, [
                    'polygon_geojson' => $data['polygon_geojson'] ?? null,
                'total_area_m2' => $data['total_area_m2'] ?? 0,
                'drawing_type' => $data['drawing_type'] ?? null,
                'drawing_length_m' => $data['drawing_length_m'] ?? null,
                    'center_lat' => $data['center_lat'] ?? null,
                    'center_lng' => $data['center_lng'] ?? null,
                    'address_text' => $data['address_text'] ?? null,
                ]);
            }

            if (! empty($data['surface_type_id'])) {
                $pricing = app(\App\Services\PricingService::class);
                $pricing->upsertSurfaceLine($application, $data);
                $pricing->recalculateTotals($application);
            }

            // GIS noktasını da kaydet
            if (! empty($data['lat']) && ! empty($data['lng'])) {
                GisBasvuruNokta::create([
                    'basvuru_id' => $application->id,
                    'basvuru_tipi' => $data['basvuru_tipi'] ?? 'kazi_ruhsat',
                    'lat' => $data['lat'],
                    'lng' => $data['lng'],
                    'ilce' => $data['ilce'] ?? '',
                    'mahalle' => $data['mahalle'] ?? '',
                    'ada' => $data['ada'] ?? '',
                    'parsel' => $data['parsel'] ?? '',
                    'wfs_response' => json_encode([
                        'ortak_kurumlar' => $data['ortak_kurumlar'] ?? '',
                    ]),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Başvuru başarıyla oluşturuldu.',
                'application_no' => $application->application_no,
                'data' => ['id' => $application->id],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('basvuruOlustur hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Başvuru oluşturulamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function tcknSorgula($tckn)
    {
        if (strlen($tckn) < 10) {
            return response()->json(['found' => false]);
        }

        $application = Application::where('applicant_national_id', $tckn)
            ->orWhere('tc_no', $tckn)
            ->orWhere('identity_no', $tckn)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$application) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'data' => [
                'first_name' => $application->applicant_first_name,
                'last_name' => $application->applicant_last_name,
                'phone' => $application->applicant_phone,
                'address' => $application->address_text,
            ],
        ]);
    }

    public function basvurularGeoJson()
    {
        try {
            $features = [];

            try {
                $noktalar = GisBasvuruNokta::select('id', 'basvuru_id', 'basvuru_tipi', 'lat', 'lng', 'ilce', 'mahalle', 'ada', 'parsel')
                    ->whereNotNull('lat')
                    ->whereNotNull('lng')
                    ->where('lat', '!=', 0)
                    ->where('lng', '!=', 0)
                    ->get();

                foreach ($noktalar as $nokta) {
                    $features[] = [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [(float) $nokta->lng, (float) $nokta->lat],
                        ],
                        'properties' => [
                            'id' => $nokta->id,
                            'source' => 'gis_nokta',
                            'basvuru_id' => $nokta->basvuru_id,
                            'basvuru_tipi' => $nokta->basvuru_tipi,
                            'application_no' => '',
                            'kurum_adi' => '',
                            'durum' => 'submitted',
                            'tarih' => $nokta->created_at ? $nokta->created_at->format('d.m.Y') : '',
                            'ilce' => $nokta->ilce,
                            'mahalle' => $nokta->mahalle,
                            'ada' => $nokta->ada,
                            'parsel' => $nokta->parsel,
                        ],
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('gis_basvuru_noktalar sorgusu başarısız: ' . $e->getMessage());
            }

            try {
                $basvurular = Application::whereIn('status', [
                    'submitted', 'licensed', 'field_work', 'awaiting_payment',
                    'receipt_pending', 'completed', 'rejected',
                ])->select('id', 'application_no', 'institution_id', 'status', 'address_text', 'created_at')
                    ->with('excavationAreas')
                    ->orderBy('created_at', 'desc')
                    ->limit(500)
                    ->get();

                foreach ($basvurular as $app) {
                    $area = $app->excavationAreas->first();
                    if ($area && $area->center_lat && $area->center_lng) {
                        $features[] = [
                            'type' => 'Feature',
                            'geometry' => [
                                'type' => 'Point',
                                'coordinates' => [(float) $area->center_lng, (float) $area->center_lat],
                            ],
                            'properties' => [
                                'id' => $app->id,
                                'source' => 'application',
                                'application_no' => $app->application_no,
                                'kurum_adi' => $app->institution ? $app->institution->name : '',
                                'durum' => $app->status,
                                'tarih' => $app->created_at ? $app->created_at->format('d.m.Y') : '',
                                'address' => $app->address_text ?? '',
                            ],
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Application sorgusu başarısız: ' . $e->getMessage());
            }

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
            ]);
        } catch (\Exception $e) {
            Log::error('basvurularGeoJson hatası: ' . $e->getMessage());
            return response()->json(['type' => 'FeatureCollection', 'features' => []]);
        }
    }
}