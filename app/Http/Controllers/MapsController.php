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
use Illuminate\View\View;

class MapsController extends Controller
{
    public function index(): View
    {
        $surfaceTypes = SurfaceType::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price_per_m2']);

        return view('maps.index', compact('surfaceTypes'));
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
                    $coords = $feature['geometry']['coordinates'] ?? [];
                    foreach ($coords as $segment) {
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
            return [
                'id' => $b->id,
                'application_no' => $b->application_no,
                'kurum_id' => $b->institution_id,
                'kurum_adi' => $b->institution ? $b->institution->name : '—',
                'durum' => $b->status,
                'tarih' => $b->created_at ? $b->created_at->format('d.m.Y') : '—',
                'lat' => $ea ? $ea->center_lat : null,
                'lng' => $ea ? $ea->center_lng : null,
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