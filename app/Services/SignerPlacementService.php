<?php

namespace App\Services;

use App\Models\Application;

/**
 * SignerPlacementService
 * ============================================================
 * AMAÇ
 * ============================================================
 * Hangi belge tipinde (ruhsat / on_kazi / ust_yazi / metraj ...), hangi süreç
 * adımı tamamlandıysa, imza hücresinin içine O ADIMIN onaylayanını; tamamlanmamış
 * adımların hücresini ise BOŞ doldurur.
 *
 * "Başkan Yardımcısı" gibi dinamik pozisyonların adı, o adım kriptografik olarak
 * tamamlanana kadar BOŞ kalır — imzalayanın bilgisi belgeye ancak gerçek imza
 * anında işlenir (rapordaki mimari karar).
 *
 * ============================================================
 * KRİTİK MİMARİ KURAL
 * ============================================================
 * Bu servis PDF'e SONRADAN yazı ÇİZMEZ. Sadece dompdf render'ından ÖNCE Blade'e
 * geçilecek $data (imza haritası) dizisini DOLDURUR/BOŞALTIR. İmza (PAdES) adımı
 * SADECE kriptografik imzalama yapar, görsel içeriğe DOKUNMAZ.
 *
 * Doğru kullanım:  yerlesimHazirla() -> render() -> imzala()  [sadece mühür]
 */
class SignerPlacementService
{
    /**
     * Süreç adımı (ProcessStep.role_key) => SignatoryEngine placeholder key.
     * Varsayılan süreç: staff (Büro) -> director (Aykome Şefi) -> vice_mayor (Makam).
     */
    private const STEP_TO_PLACEHOLDER = [
        'staff'       => 'aykome_sorumlusu',
        'director'    => 'fen_isleri_muduru',
        'vice_mayor'  => 'belediye_baskan_yardimcisi',
    ];

    /**
     * placeholder key => unvan fallback (SignatoryEngine üretemezse kullanılır).
     */
    private const PLACEHOLDER_UNVAN = [
        'aykome_sorumlusu'         => 'AYKOME Birim Sorumlusu',
        'fen_isleri_muduru'        => 'Fen İşleri Müdürü',
        'belediye_baskan_yardimcisi' => 'Belediye Başkan Yardımcısı',
        'tesis_sorumlusu'          => 'Tesis Sorumlusu',
        'onay_imzaci'              => 'Onay İmzacısı',
    ];

    /**
     * Belge render edilmeden ÖNCE (dompdf'e gitmeden önce) çağrılır.
     *
     * SignatoryEngine::roleMap() ile her rolün DB'den çözülmüş adını alır, sonra
     * application->approval_log üzerinden TAMAMLANMAMIŞ süreç adımlarının adını
     * boşaltır. Böylece belgeye yalnızca o ana kadar fiilen imza/onay vermiş
     * kişilerin adı basılır; henüz imzalanmamış pozisyonlar boş kalır.
     *
     * @return array<string,array{ad_soyad:string,unvan:?string}> $data['signatories'] yapısı
     */
    public function yerlesimHazirla(Application $application, string $documentType): array
    {
        $sigMap = SignatoryEngine::roleMap($documentType, $application);

        // Tamamlanmış adımların onaylayan isimleri (approval_log).
        $log = $application->approval_log ?? [];
        $tamamlanan = [];
        foreach ($log as $entry) {
            $rk = $entry['role_key'] ?? null;
            if ($rk && isset($entry['approved_by_name'])) {
                $tamamlanan[$rk] = $entry['approved_by_name'];
            }
        }

        foreach (self::STEP_TO_PLACEHOLDER as $roleKey => $placeholder) {
            if (! isset($sigMap[$placeholder])) {
                continue;
            }

            if (isset($tamamlanan[$roleKey])) {
                // O adım kriptografik/onay ile tamamlandı -> onaylayanın adı.
                $sigMap[$placeholder]['ad_soyad'] = $tamamlanan[$roleKey];

                // BAŞKAN YARDIMCISI: formda kullanıcının girdiği ad (vice_mayor_name)
                // en doğru kaynaktır. ProcessEngine onay anında approved_by_name'e
                // onaylayan kullanıcının adını yazabiliyordu (Süper Admin testi);
                // belgeye BASILACAK ad her zaman vice_mayor_name olmalıdır.
                if ($roleKey === 'vice_mayor' && ! empty($application->vice_mayor_name)) {
                    $sigMap[$placeholder]['ad_soyad'] = $application->vice_mayor_name;
                }
            } else {
                // Henüz tamamlanmadı -> ad BOŞ kalır ("Başkan Yardımcısı" dinamik beklentisi).
                $sigMap[$placeholder]['ad_soyad'] = '';
            }

            // Unvan fallback (DB'den gelmediyse süreç adımına göre).
            $sigMap[$placeholder]['unvan'] = $sigMap[$placeholder]['unvan']
                ?: (self::PLACEHOLDER_UNVAN[$placeholder] ?? null);
        }

        return $sigMap;
    }
}