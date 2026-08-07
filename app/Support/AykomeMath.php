<?php

namespace App\Support;

/**
 * AYKOME TEK MUHASEBE ÇEKİRDEĞİ (Single Source of Truth).
 * ------------------------------------------------------------------
 * Belge üretiminde (Application::calcFigures), DB kalıcılığında
 * (PricingService::recalculateTotals), override hidratasyonunda ve
 * belge → DB senkronunda AYNI formül çalışır.
 *
 * Kırmızı Çizgi Kuralları (JS aynasıyla birebir aynı):
 *  - KDV sabit %20 ve çarpılır.
 *  - DİCLE ELEKTRİK kurumu → Ruhsat Harcı HER ZAMAN 0 TL.
 *  - TÜM alt kurumlar (is_municipality=false) ve Ek Ruhsat → TEMİNAT 0 TL;
 *    yalnızca ZTB + KDV + Harç + Keşif toplanır, teminat eklenmez/çarpılmaz.
 */
class AykomeMath
{
    public const KDV_RATE = 0.20;
    public const HARC_PER_M2 = 9;
    public const KESIF_BASE = 361;
    public const KESIF_RATE = 0.01;
    public const TEMINAT_RATE = 0.50;

    /**
     * Yüzey satırlarından toplam muhasebe figürlerini üretir.
     *
     * @param  array  $rows  [['quantity'=>float, 'price_per_m2'=>float], ...]
     * @param  array  $ctx   ['isDicle'=>bool, 'isInstitutionApp'=>bool, 'isAdditionalPermit'=>bool]
     * @return array
     */
    public static function compute(array $rows, array $ctx): array
    {
        $toplamMiktar = 0.0;
        $ztb = 0.0;
        foreach ($rows as $r) {
            $q = max((float) ($r['quantity'] ?? 0), 0);
            $p = max((float) ($r['price_per_m2'] ?? 0), 0);
            $toplamMiktar += $q;
            $ztb += min(round($q * $p, 3), 999999999999.99);
        }

        $isDicle = (bool) ($ctx['isDicle'] ?? false);
        $isInstApp = (bool) ($ctx['isInstitutionApp'] ?? false);
        $isAdditionalPermit = (bool) ($ctx['isAdditionalPermit'] ?? false);

        $kdv = $ztb * self::KDV_RATE;
        $licenseFee = $isDicle ? 0.0 : $toplamMiktar * self::HARC_PER_M2;
        $discoveryFee = self::KESIF_BASE + ($ztb * self::KESIF_RATE);
        $teminat = ($isInstApp || $isAdditionalPermit) ? 0.0 : $ztb * self::TEMINAT_RATE;
        $ztbTotal = $ztb + $kdv + $licenseFee + $discoveryFee;
        $generalTotal = $ztbTotal + $teminat;

        return [
            'toplam_miktar' => round($toplamMiktar, 2),
            'ztb_amount'    => round($ztb, 2),
            'kdv_amount'    => round($kdv, 2),
            'license_fee'   => round($licenseFee, 2),
            'discovery_fee' => round($discoveryFee, 2),
            'ztb_total'     => round($ztbTotal, 2),
            'teminat'       => round($teminat, 2),
            'general_total' => round($generalTotal, 2),
        ];
    }
}
