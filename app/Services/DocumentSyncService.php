<?php

namespace App\Services;

use App\Models\Application;

/**
 * Modüller Arası Geri Besleme Motoru
 * ------------------------------------------------------------------
 * Editörde (Özel Taslağı Düzenle) memur bir Excel belgesinin (ruhsat /
 * tahakkuk / metraj) sayısal hücresini değiştirip kaydettiğinde:
 *   1. Belgedeki yüzey miktarları ayrıştırılır (data-aykome-* sözleşmesi).
 *   2. Yüzey tipi başına delta (docQty − dbQty) hesaplanır.
 *   3. Yalnızca delta ≠ 0 olan satırlar application_surface_areas'ta güncellenir
 *      (toplu satır — her tip tek satır kalır).
 *   4. AykomeMath ile App toplamları BAŞTAN hesaplanır (recalculateTotals).
 *   5. GIS adres sayısına göre EŞİT/ORANTILI dağıtım payı audit amacıyla
 *      applications.surface_sync_log (JSON) içine kaydedilir.
 *   6. Diğer excel override'larının sayı hücreleri DB'den tazelenir
 *      (el metinleri korunur) → Kazı/Tahakkuk/Ruhsat artık aynı paraya bakar.
 */
class DocumentSyncService
{
    protected array $excelTypes = ['ruhsat', 'tahakkuk', 'metraj'];

    public function __construct(protected PricingService $pricingService)
    {
    }

    /** Belge kaydından gelen yüzey miktar değişimlerini DB'ye geri besler. */
    public function syncFromDocument(Application $app, string $documentType, string $contentHtml): void
    {
        if (! in_array($documentType, $this->excelTypes, true)) {
            return;
        }

        $docRows = DocumentTemplateService::extractSurfaceRows($contentHtml);
        if (empty($docRows)) {
            return;
        }

        $app->loadMissing(['surfaceLines.surfaceType', 'institution']);

        $changes = [];
        foreach ($docRows as $r) {
            $name = mb_strtolower(trim((string) $r['name']), 'UTF-8');
            $line = collect($app->surfaceLines ?? [])->first(function ($sl) use ($name) {
                return $sl->surfaceType
                    && mb_strtolower(trim((string) $sl->surfaceType->name), 'UTF-8') === $name;
            });
            if (! $line) {
                continue;
            }

            $newQty = max((float) $r['quantity'], 0);
            $oldQty = (float) $line->quantity;
            $delta = round($newQty - $oldQty, 4);
            if (abs($delta) < 0.0001) {
                continue;
            }

            $line->update(['quantity' => $newQty]);
            $changes[] = [
                'name' => trim((string) $line->surfaceType->name),
                'old_qty' => $oldQty,
                'new_qty' => $newQty,
                'delta' => $delta,
            ];
        }

        if (empty($changes)) {
            return;
        }

        // Tüm App toplamlarını BAŞTAN hesapla (AykomeMath — tek kaynak).
        $this->pricingService->recalculateTotals($app);

        $this->recordDistribution($app, $documentType, $changes);
        $this->hydrateAllOverrides($app, $documentType);
    }

    /** Tüm excel override'larını DB rakamlarıyla tazeler (GÖREV 1 senkronu). */
    public function hydrateAllOverrides(Application $app, ?string $except = null): void
    {
        foreach ($this->excelTypes as $type) {
            if ($type === $except) {
                continue;
            }
            $content = DocumentTemplateService::overrideContent($app, $type);
            if ($content === null || trim($content) === '') {
                continue;
            }
            $hydrated = DocumentTemplateService::hydrateNumbers($content, $app);
            if ($hydrated !== $content) {
                DocumentTemplateService::saveOverride($app, $type, $hydrated);
            }
        }
    }

    /** Eşit/orantılı dağıtım payını audit JSON'ına ekler. */
    protected function recordDistribution(Application $app, string $documentType, array $changes): void
    {
        $app->loadMissing(['gisNoktalari', 'gisCizimleri.yolIliskileri']);

        $gisCount = 0;
        $gisCount += $app->relationLoaded('gisNoktalari') ? $app->gisNoktalari->count() : 0;
        foreach ($app->gisCizimleri ?? [] as $cizim) {
            $gisCount += $cizim->yolIliskileri?->count() ?? 0;
        }

        $log = $app->surface_sync_log ?? [];
        $log[] = [
            'at' => now()->toIso8601String(),
            'source' => $documentType,
            'gis_address_count' => $gisCount,
            'distribution_policy' => $gisCount > 0 ? 'equal_share_per_address' : 'direct_to_row',
            'changes' => array_map(function (array $c) use ($gisCount): array {
                return $c + ['equal_share' => $gisCount > 0 ? round($c['delta'] / $gisCount, 4) : $c['delta']];
            }, $changes),
        ];

        $app->update(['surface_sync_log' => array_slice($log, -50)]);
    }
}
