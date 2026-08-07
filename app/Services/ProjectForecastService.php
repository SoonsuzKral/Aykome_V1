<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationSurfaceArea;
use App\Models\SurfaceType;
use App\Support\AykomeMath;
use Illuminate\Support\Collection;

/**
 * KAZI METRAJ TAHMİN MOTORU (PRO)
 * ------------------------------------------------------------------
 * İstatistiksel tahmin çekirdeği. LLM/AI kullanmaz; tamamen geçmiş
 * başvuru verisine dayanır (offline, sıfır maliyet, KVKK güvenli).
 *
 * Adaptif hiyerarşi (veri azaldıkça düşer):
 *   L1  Kurum + Mahalle eşleşmesi  → en isabetli
 *   L2  Kurum (mahalle filtre yok) → orta
 *   L3  Global (tüm belediye)      → düşük
 *   L4  Varsayılan dağılım         → çok düşük (akıllı varsayılan)
 *
 * Her seviyede surface type başına toplam quantity / toplam alan oranı
 * yüzde dağılımı çıkarılır. Fiyat öngörüsü AykomeMath (TEK MUHASEBE
 * KAYNAĞI) ile üretilir — tahmin satırları gerçek zemin satırlarıyla
 * aynı şemada olur, tek tıkla surface_lines'a aktarılabilir.
 */
class ProjectForecastService
{
    /** Uygulama yaşı — daha eski veriler tahmine karışmaz. */
    public const LOOKBACK_DAYS = 730;

    /** L1→L2 (kurum+mahalle→kurum) düşüş eşiği: eşleşen başvuru sayısı. */
    public const MIN_MAHALLE_SAMPLES = 3;

    /** L2→L3 (kurum→global) düşüş eşiği. */
    public const MIN_KURUM_SAMPLES = 5;

    /** L3→L4 (global→varsayılan) düşüş eşiği. */
    public const MIN_GLOBAL_SAMPLES = 5;

    /** Tahmine girmeyecek statüler (tamamlanmamış/geçersiz işler). */
    public const EXCLUDED_STATUSES = ['draft', 'rejected', 'cancelled', 'archived', 'metrage_revision'];

    /**
     * Varsayılan dağılım — zemin adı → yüzde. Toplam 100 olmalı.
     * DB'de bu adlar yoksa yüzde oransal yeniden normalize edilir.
     */
    public const DEFAULT_DISTRIBUTION = [
        'ASFALT' => 55,
        'PARKE' => 18,
        'BETON' => 12,
        'STABİLİZE' => 6,
        'TOPRAK' => 6,
        'ÇİM' => 3,
    ];

    /**
     * Tam tahmin paketi üretir.
     *
     * @param  int|null  $institutionId  kurum; null = global arama
     * @param  string|null  $mahalle  mahalle adı (filtre opsiyonel)
     * @param  float  $totalM2  kazılan alan (harita çiziminden / formdan)
     * @param  int|null  $excludeApplicationId  tahmine dahil edilmeyecek başvuru (edit'te kendisi)
     * @return array{
     *     success: bool,
     *     has_data: bool,
     *     level: string,
     *     level_label: string,
     *     sample_count: int,
     *     total_m2: float,
     *     rows: list<array<string, mixed>>,
     *     forecast_total: float,
     *     confidence: string,
     *     message: string,
     * }
     */
    public function predict(
        ?int $institutionId,
        ?string $mahalle,
        float $totalM2,
        ?int $excludeApplicationId = null,
    ): array {
        $totalM2 = max((float) $totalM2, 0);

        [$distribution, $level, $sampleCount] = $this->surfaceDistribution(
            $institutionId,
            $mahalle,
            $excludeApplicationId
        );

        $rows = $this->buildRows($distribution, $totalM2);
        $forecastTotal = 0.0;
        foreach ($rows as $row) {
            $forecastTotal += (float) $row['amount'];
        }

        return [
            'success' => true,
            'has_data' => $sampleCount > 0,
            'level' => $level,
            'level_label' => $this->levelLabel($level),
            'sample_count' => $sampleCount,
            'total_m2' => round($totalM2, 2),
            'rows' => $rows,
            'forecast_total' => round($forecastTotal, 2),
            'confidence' => $this->confidenceLabel($level, $sampleCount),
            'message' => $sampleCount > 0
                ? "{$sampleCount} geçmiş başvuruya dayalı tahmin."
                : 'Geçmiş veri bulunamadı — örnek zemin dağılımı kullanıldı.',
        ];
    }

    /**
     * Zemin tipi yüzde dağılımını adaptif hiyerarşiyle üretir.
     *
     * @return array{0: array<int, float>, 1: string, 2: int}  [typeId=>pct(0-100), level, sampleCount]
     */
    public function surfaceDistribution(
        ?int $institutionId,
        ?string $mahalle,
        ?int $excludeApplicationId = null,
    ): array {
        // L1: Kurum + Mahalle
        if ($institutionId && $mahalle && trim($mahalle) !== '') {
            [$dist, $apps] = $this->distributionFromSamples($this->sampleQuery($institutionId, $mahalle, $excludeApplicationId));
            if ($apps >= self::MIN_MAHALLE_SAMPLES) {
                return [$dist, 'mahalle', $apps];
            }
        }

        // L2: Kurum (mahalle filtre yok)
        if ($institutionId) {
            [$dist, $apps] = $this->distributionFromSamples($this->sampleQuery($institutionId, null, $excludeApplicationId));
            if ($apps >= self::MIN_KURUM_SAMPLES) {
                return [$dist, 'kurum', $apps];
            }
        }

        // L3: Global
        [$dist, $apps] = $this->distributionFromSamples($this->sampleQuery(null, null, $excludeApplicationId));
        if ($apps >= self::MIN_GLOBAL_SAMPLES) {
            return [$dist, 'global', $apps];
        }

        // L4: Varsayılan
        return [$this->defaultDistribution(), 'varsayilan', 0];
    }

    /**
     * Seçilen başvuru kümesinden surface type bazlı yüzde dağılımı + başvuru sayısı.
     *
     * @param  Collection<int, ApplicationSurfaceArea>  $lines
     * @return array{0: array<int, float>, 1: int}
     */
    private function distributionFromSamples(Collection $lines): array
    {
        if ($lines->isEmpty()) {
            return [[], 0];
        }

        // Uygulama başına toplam alanı topla; satırları uygulama id'sine göre grupla.
        $byApp = $lines->groupBy('application_id');
        $appCount = $byApp->count();

        if ($appCount === 0) {
            return [[], 0];
        }

        // Her uygulama için toplam alan (surface satırlarından toplam quantity).
        $appTotals = [];
        foreach ($byApp as $appId => $appLines) {
            $appTotals[$appId] = $appLines->sum(fn (ApplicationSurfaceArea $l) => max((float) $l->quantity, 0));
        }
        $grandTotal = array_sum($appTotals);
        if ($grandTotal <= 0) {
            return [[], 0];
        }

        // surface_type_id → toplam quantity
        $typeTotals = $lines->groupBy('surface_type_id')
            ->map(fn (Collection $typeLines) => $typeLines->sum(fn (ApplicationSurfaceArea $l) => max((float) $l->quantity, 0)));

        $distribution = [];
        foreach ($typeTotals as $typeId => $qty) {
            if ((int) $typeId <= 0 || $qty <= 0) {
                continue;
            }
            $distribution[(int) $typeId] = round($qty / $grandTotal * 100, 2);
        }

        // Yüzdeleri 100'e tamamla (en büyük kalana farkı ekle).
        $sum = array_sum($distribution);
        if ($sum > 0 && abs($sum - 100) > 0.01 && $distribution !== []) {
            $maxKey = array_search(max($distribution), $distribution, true);
            $distribution[$maxKey] = round($distribution[$maxKey] + (100 - $sum), 2);
        }

        return [$distribution, $appCount];
    }

    /**
     * Uygun uygulamaların zemin satırlarını çeker.
     *
     * @param  int|null  $institutionId
     * @param  string|null  $mahalle  PHP tarafında JSON alan filtrelemesi (Oracle JSON fonksiyonu yok)
     * @return Collection<int, ApplicationSurfaceArea>
     */
    private function sampleQuery(?int $institutionId, ?string $mahalle, ?int $excludeApplicationId = null): Collection
    {
        $since = now()->subDays(self::LOOKBACK_DAYS)->startOfDay();

        $appQuery = Application::query()
            ->where('created_at', '>=', $since)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->whereHas('surfaceLines');

        if ($institutionId) {
            $appQuery->where('institution_id', $institutionId);
        }
        if ($excludeApplicationId) {
            $appQuery->where('id', '!=', $excludeApplicationId);
        }

        $applications = $appQuery->select('id', 'address_components', 'address_text')->get();

        // Mahalle filtrelemesi — PHP tarafında (Oracle JSON uyumlu, hızlı değil ama güvenli).
        if ($mahalle && trim($mahalle) !== '') {
            $needle = mb_strtoupper(trim($mahalle), 'UTF-8');
            $applications = $applications->filter(function (Application $app) use ($needle): bool {
                $components = $app->address_components ?? [];
                foreach ((array) $components as $adres) {
                    $m = mb_strtoupper(trim((string) ($adres['mahalle'] ?? '')), 'UTF-8');
                    if ($m !== '' && ($m === $needle || str_contains($m, $needle) || str_contains($needle, $m))) {
                        return true;
                    }
                }
                if (stripos((string) $app->address_text, $mahalle) !== false) {
                    return true;
                }

                return false;
            });
        }

        if ($applications->isEmpty()) {
            return collect();
        }

        $ids = $applications->pluck('id');

        return ApplicationSurfaceArea::query()
            ->whereIn('application_id', $ids)
            ->get(['application_id', 'surface_type_id', 'quantity']);
    }

    /**
     * Varsayılan dağılım — DB'deki gerçek zemin tiplerine yüzde atar.
     *
     * @return array<int, float>
     */
    public function defaultDistribution(): array
    {
        $types = SurfaceType::query()->where('active', true)->get(['id', 'name']);

        $byName = $types->mapWithKeys(fn (SurfaceType $t) => [mb_strtoupper($t->name, 'UTF-8') => $t->id]);

        $distribution = [];
        $assigned = 0;
        foreach (self::DEFAULT_DISTRIBUTION as $name => $pct) {
            $upper = mb_strtoupper($name, 'UTF-8');
            $matchedId = null;
            foreach ($byName as $fullName => $id) {
                if ($fullName === $upper || str_starts_with($fullName, $upper . ' ')) {
                    $matchedId = $id;
                    break;
                }
            }
            if ($matchedId) {
                $distribution[$matchedId] = $pct;
                $assigned += $pct;
            }
        }

        // Eşleşmeyen yüzdeyi ilk aktif zemin tipine devret (boşluk kapanır).
        $firstId = $types->first()?->id;
        if ($distribution !== [] && $assigned < 100) {
            $topKey = array_key_first($distribution);
            $distribution[$topKey] = round($distribution[$topKey] + (100 - $assigned), 2);
        } elseif ($distribution === [] && $firstId) {
            $distribution[$firstId] = 100.0;
        }

        return $distribution;
    }

    /**
     * Yüzde dağılımını tahmin satırlarına çevirir.
     *
     * @param  array<int, float>  $distribution  [surface_type_id => pct]
     * @return list<array<string, mixed>>
     */
    public function buildRows(array $distribution, float $totalM2): array
    {
        if ($totalM2 <= 0 || $distribution === []) {
            return [];
        }

        $types = SurfaceType::query()
            ->whereIn('id', array_keys($distribution))
            ->get(['id', 'name', 'price_per_m2'])
            ->keyBy('id');

        $rows = [];
        foreach ($distribution as $typeId => $pct) {
            $type = $types->get((int) $typeId);
            if (! $type) {
                continue;
            }
            $m2 = round($totalM2 * ($pct / 100), 2);
            $unit = (float) $type->price_per_m2;
            $amount = min(round($m2 * $unit, 3), 999999999999.99);

            $rows[] = [
                'surface_type_id' => $type->id,
                'name' => $type->name,
                'price_per_m2' => round($unit, 3),
                'pct' => round((float) $pct, 2),
                'm2' => $m2,
                'quantity' => $m2,
                'amount' => $amount,
            ];
        }

        // Yüzdeleri yukarıda toplamı 100'e tamamlamadıysak farkı ilk satıra ekle.
        return $rows;
    }

    private function levelLabel(string $level): string
    {
        return match ($level) {
            'mahalle' => 'Mahalle bazlı tahmin',
            'kurum' => 'Kurum bazlı tahmin',
            'global' => 'Genel ortalama tahmin',
            default => 'Örnek dağılım',
        };
    }

    private function confidenceLabel(string $level, int $samples): string
    {
        if ($samples === 0) {
            return 'Çok Düşük';
        }

        return match (true) {
            $level === 'mahalle' => 'Yüksek',
            $level === 'kurum' => 'Orta',
            default => 'Düşük',
        };
    }
}
