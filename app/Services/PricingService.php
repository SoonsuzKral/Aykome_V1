<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationSurfaceArea;
use App\Models\SurfaceType;

class PricingService
{
    public function upsertSurfaceLines(Application $application, array $lines): void
    {
        $application->surfaceLines()->delete();

        foreach ($lines as $data) {
            $surfaceType = SurfaceType::query()->findOrFail($data['surface_type_id']);
            $width = isset($data['width_m']) ? (float) str_replace(',', '.', $data['width_m']) : 0.0;
            $length = isset($data['length_m']) ? (float) str_replace(',', '.', $data['length_m']) : 0.0;
            $quantity = (float) str_replace(',', '.', $data['quantity'] ?? 0);
            $unit = (float) $surfaceType->price_per_m2;

            $patchM2 = $quantity;
            $amount = round($patchM2 * $unit, 3);
            $amount = min($amount, 999999999999.99);

            $application->surfaceLines()->create([
                'surface_type_id' => $surfaceType->id,
                'width_m' => $width ?: null,
                'length_m' => $length ?: null,
                'quantity' => $quantity,
                'multiplier' => 1,
                'amount' => $amount,
            ]);
        }
    }

    public function recalculateTotals(Application $application): void
    {
        $application->load(['surfaceLines.surfaceType', 'excavationAreas']);

        $areaM2 = (float) ($application->excavationAreas->first()?->total_area_m2 ?? $application->total_area_m2);

        $discovery = 0.0;
        foreach ($application->surfaceLines as $line) {
            $unit = (float) $line->surfaceType->price_per_m2;
            $qty = (float) $line->quantity;
            $lineAmount = round($qty * $unit, 3);
            $lineAmount = min($lineAmount, 999999999999.99);
            $line->update(['amount' => $lineAmount]);
            $discovery += $lineAmount;
        }

        $kdv = round($discovery * 0.20, 2);
        $ruhsatHarci = round($areaM2 * 9, 2);
        $kesifBedeli = round(361 + ($discovery * 0.01), 2);
        $ztbToplam = round($discovery + $kdv + $ruhsatHarci + $kesifBedeli, 2);
        $teminat = round($discovery * 0.50, 2);
        $genelToplam = round($ztbToplam + $teminat, 2);

        $application->update([
            'total_area_m2' => $areaM2,
            'discovery_amount' => round($discovery, 2),
            'kdv_amount' => $kdv,
            'ruhsat_harci' => $ruhsatHarci,
            'kesif_bedeli' => $kesifBedeli,
            'ztb_toplam' => $ztbToplam,
            'teminat_tutari' => $teminat,
            'genel_toplam' => $genelToplam,
            'deposit_amount' => $teminat,
            'excavation_amount' => $genelToplam,
            'total_price' => round($discovery, 2),
        ]);
    }
}
