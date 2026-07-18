<?php

namespace App\Http\Controllers;

use App\Models\GisBasvuruNokta;
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
        return view('maps.index');
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
        // Phase 5'te implement edilecek
        $q = $request->input('q');
        if (!$q) return response()->json([]);
        return response()->json([]);
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
            ->with('excavationArea')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $results = $basvurular->map(function ($b) {
            return [
                'id' => $b->id,
                'application_no' => $b->application_no,
                'kurum_id' => $b->institution_id,
                'kurum_adi' => $b->institution ? $b->institution->name : '—',
                'durum' => $b->status,
                'tarih' => $b->created_at ? $b->created_at->format('d.m.Y') : '—',
                'lat' => $b->excavationArea ? $b->excavationArea->center_lat : null,
                'lng' => $b->excavationArea ? $b->excavationArea->center_lng : null,
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