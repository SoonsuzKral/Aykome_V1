<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationSurfaceArea;
use App\Models\SurfaceType;
use App\Support\AykomeMath;

class PricingService
{
    public function upsertSurfaceLines(Application $application, array $lines): void
    {
        if (empty($lines)) {
            return;
        }

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
        $application->load(['surfaceLines.surfaceType', 'excavationAreas', 'institution']);

        // TEK MUHASEBE KAYNAĞI: AykomeMath — calcFigures() ile birebir aynı formül.
        $rows = [];
        foreach ($application->surfaceLines as $line) {
            $unit = (float) ($line->surfaceType->price_per_m2 ?? 0);
            $qty = (float) ($line->quantity ?? 0);
            $lineAmount = min(round($qty * $unit, 3), 999999999999.99);
            $line->update(['amount' => $lineAmount]);
            $rows[] = ['quantity' => $qty, 'price_per_m2' => $unit];
        }

        $fig = AykomeMath::compute($rows, [
            'isDicle' => $application->isDicle(),
            'isInstitutionApp' => $application->isInstitutionApplication(),
            'isAdditionalPermit' => (bool) ($application->is_additional_permit ?? false),
        ]);

        $areaM2 = (float) ($application->excavationAreas->first()?->total_area_m2 ?? $application->total_area_m2);

        $application->update([
            'total_area_m2' => $areaM2,
            'discovery_amount' => $fig['ztb_amount'],
            'kdv_amount' => $fig['kdv_amount'],
            'ruhsat_harci' => $fig['license_fee'],
            'kesif_bedeli' => $fig['discovery_fee'],
            'ztb_toplam' => $fig['ztb_total'],
            'teminat_tutari' => $fig['teminat'],
            'genel_toplam' => $fig['general_total'],
            'deposit_amount' => $fig['teminat'],
            'excavation_amount' => $fig['general_total'],
            'total_price' => $fig['ztb_amount'],
        ]);
    }
}
