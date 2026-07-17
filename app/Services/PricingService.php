<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationSurfaceArea;
use App\Models\SurfaceType;

class PricingService
{
    public function upsertSurfaceLine(Application $application, array $data): ApplicationSurfaceArea
    {
        $surfaceType = SurfaceType::query()->findOrFail($data['surface_type_id']);

        $areaM2 = (float) ($application->excavationAreas()->first()?->total_area_m2 ?? $application->total_area_m2);
        $width = isset($data['width_m']) ? (float) str_replace(',', '.', $data['width_m']) : 0.0;
        $length = isset($data['length_m']) ? (float) str_replace(',', '.', $data['length_m']) : 0.0;
        $quantity = (float) str_replace(',', '.', $data['quantity'] ?? 1);
        $multiplier = (float) str_replace(',', '.', $data['multiplier'] ?? 1);

        $patchM2 = ($width > 0 && $length > 0) ? $width * $length * $quantity : $areaM2 * $quantity;
        $unit = (float) $surfaceType->price_per_m2;
        $amount = round($patchM2 * $unit * $multiplier, 3);
        $amount = min($amount, 999999999999.99);

        $application->surfaceLines()->delete();

        return $application->surfaceLines()->create([
            'surface_type_id' => $surfaceType->id,
            'width_m' => $width ?: null,
            'length_m' => $length ?: null,
            'quantity' => $quantity,
            'multiplier' => $multiplier,
            'amount' => $amount,
        ]);
    }

    public function recalculateTotals(Application $application): void
    {
        $application->load(['surfaceLines.surfaceType', 'excavationAreas']);

        $areaM2 = (float) ($application->excavationAreas->first()?->total_area_m2 ?? $application->total_area_m2);

        $discovery = 0.0;
        foreach ($application->surfaceLines as $line) {
            $unit = (float) $line->surfaceType->price_per_m2;
            $mult = (float) $line->multiplier;
            $qty = (float) $line->quantity;
            $width = (float) ($line->width_m ?? 0);
            $length = (float) ($line->length_m ?? 0);
            $patch = ($width > 0 && $length > 0) ? $width * $length * $qty : $areaM2 * $qty;
            $lineAmount = round($patch * $unit * $mult, 3);
            $lineAmount = min($lineAmount, 999999999999.99);
            $line->update(['amount' => $lineAmount]);
            $discovery += $lineAmount;
        }

        $application->update([
            'discovery_amount' => round($discovery, 3),
            'total_price' => round($discovery, 3),
            'total_area_m2' => $areaM2,
        ]);
    }
}
