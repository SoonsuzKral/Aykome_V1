<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ExcavationArea;

class MapDrawingService
{
    public function syncPrimaryArea(Application $application, array $payload): ExcavationArea
    {
        $application->excavationAreas()->delete();

        return $application->excavationAreas()->create([
            'polygon_geojson' => $payload['polygon_geojson'] ?? null,
            'total_area_m2' => $payload['total_area_m2'] ?? 0,
            'center_lat' => $payload['center_lat'] ?? null,
            'center_lng' => $payload['center_lng'] ?? null,
            'address_text' => $payload['address_text'] ?? null,
        ]);
    }

    public function calculateAreaM2FromGeoJson(?string $geojson): float
    {
        if ($geojson === null || trim($geojson) === '') {
            return 0.0;
        }

        $data = json_decode($geojson, true);
        if (! is_array($data)) {
            return 0.0;
        }

        $geometries = $this->extractGeometries($data);
        if ($geometries === []) {
            return 0.0;
        }

        $totalArea = 0.0;

        foreach ($geometries as $geometry) {
            $type = $geometry['type'] ?? '';
            $coordinates = $geometry['coordinates'] ?? null;

            if (! is_array($coordinates)) {
                continue;
            }

            if ($type === 'Polygon') {
                $totalArea += $this->areaFromPolygonCoordinates($coordinates);
                continue;
            }

            if ($type === 'MultiPolygon') {
                foreach ($coordinates as $polygonCoordinates) {
                    if (! is_array($polygonCoordinates)) {
                        continue;
                    }

                    $totalArea += $this->areaFromPolygonCoordinates($polygonCoordinates);
                }
            }
        }

        return round(max($totalArea, 0), 4);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractGeometries(array $data): array
    {
        if (($data['type'] ?? '') === 'FeatureCollection' && isset($data['features']) && is_array($data['features'])) {
            $geometries = [];

            foreach ($data['features'] as $feature) {
                if (! is_array($feature)) {
                    continue;
                }

                $geometry = $feature['geometry'] ?? null;
                if (is_array($geometry) && isset($geometry['type'])) {
                    $geometries[] = $geometry;
                }
            }

            return $geometries;
        }

        if (($data['type'] ?? '') === 'Feature' && isset($data['geometry']) && is_array($data['geometry'])) {
            return [$data['geometry']];
        }

        if (isset($data['type'])) {
            return [$data];
        }

        return [];
    }

    /**
     * @param  list<mixed>  $coordinates
     */
    private function areaFromPolygonCoordinates(array $coordinates): float
    {
        if (! isset($coordinates[0]) || ! is_array($coordinates[0])) {
            return 0.0;
        }

        $outerArea = $this->areaFromPolygonLatLng($coordinates[0]);

        $holeArea = 0.0;
        foreach (array_slice($coordinates, 1) as $holeRing) {
            if (! is_array($holeRing)) {
                continue;
            }

            $holeArea += $this->areaFromPolygonLatLng($holeRing);
        }

        return max($outerArea - $holeArea, 0.0);
    }


    /**
     * Shoelace formula for WGS84 — approximate m² for small regions.
     *
     * @param  list<array{0: float, 1: float}>  $ring
     */
    private function areaFromPolygonLatLng(array $ring): float
    {
        $normalized = array_values(array_filter($ring, static fn ($point) => is_array($point) && count($point) >= 2));

        $n = count($normalized);
        if ($n < 3) {
            return 0.0;
        }

        $first = $normalized[0];
        $last = $normalized[$n - 1];
        if ((float) $first[0] === (float) $last[0] && (float) $first[1] === (float) $last[1]) {
            array_pop($normalized);
            $n = count($normalized);
            if ($n < 3) {
                return 0.0;
            }
        }

        $avgLat = array_sum(array_map(static fn ($point) => (float) $point[1], $normalized)) / $n;
        $mPerDegLat = 111_320;
        $mPerDegLng = 111_320 * cos(deg2rad($avgLat));

        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $xi = (float) $normalized[$i][0] * $mPerDegLng;
            $yi = (float) $normalized[$i][1] * $mPerDegLat;
            $xj = (float) $normalized[$j][0] * $mPerDegLng;
            $yj = (float) $normalized[$j][1] * $mPerDegLat;
            $sum += ($xi * $yj) - ($xj * $yi);
        }

        return round(abs($sum / 2), 4);
    }
}
