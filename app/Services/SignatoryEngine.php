<?php

namespace App\Services;

use App\Models\Application;
use App\Models\DocumentSignatorySetting;

/**
 * Global Signatory Engine — evrak/makam imzacılarını DB'den dinamik çözer.
 * Öncelik: kuruma özel ayar → Merkez (Global) ayar → uygulama/kurum alanları.
 */
class SignatoryEngine
{
    public static function documentTypes(): array
    {
        return [
            'ruhsat' => 'Ruhsat',
            'metraj' => 'Kazı Metraj',
            'tahakkuk' => 'Tahakkuk',
            'makbuz' => 'Tahsilat Makbuzu',
            'pre_permit' => 'Ön Kazı İzni',
            'taahhutname' => 'Taahhütname',
            'cover_letter' => 'Üst Yazı (Dilekçe)',
            'on_kazi' => 'Ön Kazı',
            'odeme_ust_yazi' => 'Ödeme Üst Yazı',
        ];
    }

    public static function roleKeys(): array
    {
        return [
            'aykome_sorumlusu' => 'AYKOME Birim Sorumlusu',
            'fen_isleri_muduru' => 'Fen İşleri Müdürü',
            'belediye_baskan_yardimcisi' => 'Belediye Başkan Yardımcısı',
            'tesis_sorumlusu' => 'Tesis Sorumlusu',
            'onay_imzaci' => 'Onay İmzacısı',
        ];
    }

    public static function resolve(string $documentType, ?int $institutionId = null, ?string $roleKey = null): ?DocumentSignatorySetting
    {
        $base = DocumentSignatorySetting::query()
            ->where('document_type', $documentType)
            ->where('is_active', true);

        if ($roleKey) {
            $base->where('role_key', $roleKey);
        }

        if ($institutionId) {
            $specific = (clone $base)->where('institution_id', $institutionId)->orderBy('sort')->first();
            if ($specific) {
                return $specific;
            }
        }

        return (clone $base)->whereNull('institution_id')->orderBy('sort')->first();
    }

    /**
     * Belirli bir evrak tipi için rol bazlı imzacı haritası döndürür.
     * Fallback'ler uygulama/kurum alanlarından beslenir (hard-coded yok).
     */
    public static function roleMap(string $documentType, Application $application): array
    {
        $instId = $application->institution_id;

        $get = function (string $roleKey) use ($documentType, $instId): ?array {
            $s = self::resolve($documentType, $instId, $roleKey);
            if ($s) {
                return ['ad_soyad' => $s->ad_soyad, 'unvan' => $s->unvan];
            }
            return null;
        };

        $inst = $application->institution;

        return [
            'aykome_sorumlusu' => $get('aykome_sorumlusu') ?? [
                'ad_soyad' => $inst?->mudur_adi ?: 'Yetkili',
                'unvan' => $inst?->mudur_unvani ?: 'AYKOME Birim Sorumlusu',
            ],
            'fen_isleri_muduru' => $get('fen_isleri_muduru') ?? [
                'ad_soyad' => $inst?->mudur_adi ?: 'Yetkili',
                'unvan' => 'Fen İşleri Müdürü',
            ],
            'belediye_baskan_yardimcisi' => $get('belediye_baskan_yardimcisi') ?? [
                'ad_soyad' => $application->vice_mayor_name ?: 'Yetkili',
                'unvan' => 'Belediye Başkan Yardımcısı',
            ],
            'tesis_sorumlusu' => $get('tesis_sorumlusu') ?? [
                'ad_soyad' => trim($application->tesis_sorumlusu ?: $inst?->tesis_sorumlusu_adi ?: 'Yetkili Görevli'),
                'unvan' => null,
            ],
            'onay_imzaci' => $get('onay_imzaci') ?? [
                'ad_soyad' => $application->creator?->name ?: 'Yetkili',
                'unvan' => 'Yetkili',
            ],
        ];
    }
}
