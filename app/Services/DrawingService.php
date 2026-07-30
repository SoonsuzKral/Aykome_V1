<?php

namespace App\Services;

use App\Models\GisCizim;
use App\Models\GisCizimYolIliskisi;
use Illuminate\Support\Facades\Log;

class DrawingService
{
    public function saveDrawing(array $data): GisCizim
    {
        $cizim = GisCizim::create([
            'user_id' => $data['user_id'] ?? auth()->id(),
            'tip' => $data['tip'] ?? 'alan',
            'geometri' => $data['geometri'] ?? null,
            'basvuru_id' => $data['basvuru_id'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'aciklama' => $data['aciklama'] ?? null,
        ]);

        // Çizim altındaki yolları otomatik bul ve ilişkilendir
        if ($cizim->geometri) {
            $roads = $this->findRelatedRoads($cizim->geometri);
            foreach ($roads as $road) {
                GisCizimYolIliskisi::updateOrCreate(
                    ['cizim_id' => $cizim->id, 'hat_kimligi' => $road['hat_kimligi']],
                    $road
                );
            }
        }

        return $cizim;
    }

    public function updateDrawing(int $id, array $data): ?GisCizim
    {
        $cizim = GisCizim::find($id);
        if (!$cizim) return null;

        $cizim->update($data);

        // Yol ilişkilerini güncelle
        if (isset($data['geometri']) && $data['geometri']) {
            $cizim->yolIliskileri()->delete();
            $roads = $this->findRelatedRoads($data['geometri']);
            foreach ($roads as $road) {
                GisCizimYolIliskisi::create(array_merge(
                    ['cizim_id' => $cizim->id, 'hat_kimligi' => $road['hat_kimligi']],
                    $road
                ));
            }
        }

        return $cizim->fresh();
    }

    public function deleteDrawing(int $id): bool
    {
        $cizim = GisCizim::find($id);
        if (!$cizim) return false;
        $cizim->yolIliskileri()->delete();
        return $cizim->delete();
    }

    public function getByApplication(int $appId)
    {
        return GisCizim::where('basvuru_id', $appId)->with('yolIliskileri')->get();
    }

    public function getByUser(int $userId)
    {
        return GisCizim::where('user_id', $userId)->with('yolIliskileri')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Çizim geometrisi ile kesişen yolları bul (bbox yaklaşımı)
     */
    public function findRelatedRoads(array $geometri): array
    {
        $roads = [];
        $bbox = $this->extractBbox($geometri);
        if (!$bbox) return $roads;

        foreach (['15_alti', '15_ustu'] as $file) {
            $path = storage_path("shp/{$file}.js");
            if (!file_exists($path)) continue;

            $content = file_get_contents($path);
            $json = preg_replace('/^var\s+\w+\s*=\s*/', '', $content);
            $json = rtrim($json, ";\n\r ");
            $data = json_decode($json, true);
            if (!$data || !isset($data['features'])) continue;

            foreach ($data['features'] as $feature) {
                $props = $feature['properties'] ?? [];
                $coords = $feature['geometry']['coordinates'] ?? [];

                if ($this->lineIntersectsBbox($coords, $bbox)) {
                    $roads[] = [
                        'hat_kimligi' => $props['CADDE_SOKA'] ?? 0,
                        'yol_adi' => $props['CADDE_SO_1'] ?? '',
                        'yol_turu' => $props['CADDE_SO_2'] ?? '',
                        'mahalle' => $props['MAHALLE_AD'] ?? '',
                        'ilce' => $props['ILÇE'] ?? '',
                        'genislik' => is_numeric($props['GENISLIGI'] ?? null) ? (float)$props['GENISLIGI'] : null,
                        'uzunluk' => is_numeric(str_replace(',', '.', $props['UZUNLUGU'] ?? '0')) ? (float)str_replace(',', '.', $props['UZUNLUGU']) : null,
                        'sorumluluk' => $props['SORUMLULUK'] ?? '',
                        'properties' => $props,
                    ];
                }
            }
        }

        return $roads;
    }

    private function extractBbox(array $geometri): ?array
    {
        $coords = $geometri['coordinates'] ?? null;
        if (!$coords) return null;

        $type = $geometri['type'] ?? '';

        // Polygon, MultiLineString, vs için coordinate düzleştirme
        $flat = [];
        if ($type === 'Polygon') {
            foreach ($coords[0] ?? [] as $c) {
                $flat[] = $c;
            }
        } elseif ($type === 'LineString') {
            $flat = $coords;
        } elseif ($type === 'MultiLineString' || $type === 'MultiPolygon') {
            foreach ($coords as $part) {
                foreach ($part[0] ?? $part as $c) {
                    if (is_array($c) && count($c) >= 2) $flat[] = $c;
                }
            }
        } elseif ($type === 'Point') {
            $flat = [$coords];
        }

        if (empty($flat)) return null;

        $minLng = $maxLng = $flat[0][0];
        $minLat = $maxLat = $flat[0][1];
        foreach ($flat as $c) {
            if (count($c) < 2) continue;
            $minLng = min($minLng, $c[0]);
            $maxLng = max($maxLng, $c[0]);
            $minLat = min($minLat, $c[1]);
            $maxLat = max($maxLat, $c[1]);
        }

        return [$minLat, $minLng, $maxLat, $maxLng];
    }

    /**
     * Bir çizginin bbox ile kesişip kesişmediğini kontrol et
     * Bbox: [minLat, minLng, maxLat, maxLng]
     */
    private function lineIntersectsBbox(array $coords, array $bbox): bool
    {
        [$minLat, $minLng, $maxLat, $maxLng] = $bbox;

        // LineString coordinates: [lng, lat] format (GeoJSON standard)
        // 15m data: coordinates[lng, lat]
        foreach ($coords as $point) {
            if (is_array($point) && count($point) >= 2) {
                $lng = (float)$point[0];
                $lat = (float)$point[1];
                // Bbox içinde mi kontrol et (tampon 0.001 ~100m)
                if ($lat >= $minLat - 0.001 && $lat <= $maxLat + 0.001 &&
                    $lng >= $minLng - 0.001 && $lng <= $maxLng + 0.001) {
                    return true;
                }
            }
            // MultiLineString için recursive
            if (is_array($point[0] ?? null) && is_array($point[0][0] ?? null)) {
                if ($this->lineIntersectsBbox($point, $bbox)) return true;
            }
        }

        return false;
    }

    public function calculateArea(?array $geometri): ?float
    {
        if (!$geometri) return null;
        $type = $geometri['type'] ?? '';
        if (!in_array($type, ['Polygon', 'MultiPolygon'])) return null;

        // Basit alan hesaplama: bounding box yaklaşımı
        $bbox = $this->extractBbox($geometri);
        if (!$bbox) return null;
        $width = ($bbox[3] - $bbox[1]) * 111320 * cos(deg2rad(($bbox[0] + $bbox[2]) / 2));
        $height = ($bbox[2] - $bbox[0]) * 110540;
        return abs($width * $height);
    }
}
