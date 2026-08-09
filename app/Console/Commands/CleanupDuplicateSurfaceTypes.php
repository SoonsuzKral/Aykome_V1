<?php

namespace App\Console\Commands;

use App\Models\SurfaceType;
use App\Models\ApplicationSurfaceArea;
use Illuminate\Console\Command;

class CleanupDuplicateSurfaceTypes extends Command
{
    protected $signature = 'surfacetype:cleanup';
    protected $description = 'Duplicate BETON ve PARKE surface type\'larını temizler - başvuruları taşır ve duplicate\'leri siler';

    public function handle(): int
    {
        $this->info('=== Duplicate Zemin Tipi Temizliği ===');
        $this->info('');

        // Canonical mapping: (key) duplicate name → (value) canonical name
        $mapping = [
            'BETON'        => 'Beton',
            'PARKE'        => 'Beton Parke',
            'BETON PARKE'  => 'Beton Parke',
        ];

        foreach ($mapping as $dupName => $canonicalName) {
            $this->cleanupDuplicate($dupName, $canonicalName);
        }

        // Also handle case-insensitive duplicates
        $this->cleanupCaseInsensitiveDuplicates();

        $this->info('');
        $this->info('Tamamlandı!');

        return Command::SUCCESS;
    }

    private function cleanupDuplicate(string $dupName, string $canonicalName): void
    {
        $duplicate = SurfaceType::where('name', $dupName)->first();

        if (!$duplicate) {
            $this->line("  [İngore] '{$dupName}' bulunamadı");
            return;
        }

        $canonical = SurfaceType::where('name', $canonicalName)->first();

        if (!$canonical) {
            // Canonical yoksa, duplicate'i rename et ve renk kodu ekle
            $this->warn("  [UYARI] '{$canonicalName}' bulunamadı. '{$dupName}' yeniden adlandırılacak...");
            $duplicate->update([
                'name' => $canonicalName,
                'color_code' => $this->generateColorCode($canonicalName),
            ]);
            $this->info("  [OK] '{$dupName}' → '{$canonicalName}' olarak yeniden adlandırıldı (color_code eklendi)");
            return;
        }

        // Başvuru sayısını kontrol et
        $appCount = ApplicationSurfaceArea::where('surface_type_id', $duplicate->id)->count();

        if ($appCount > 0) {
            $this->info("  [İşlem] '{$dupName}' → '{$canonicalName}' taşınıyor ({$appCount} başvuru)...");

            // Başvuruları taşı
            ApplicationSurfaceArea::where('surface_type_id', $duplicate->id)
                ->update(['surface_type_id' => $canonical->id]);

            $this->info("  [OK] {$appCount} başvuru taşındı");
        } else {
            $this->line("  [İngore] '{$dupName}' - kullanılmayan duplicate");
        }

        // Duplicate'i sil
        $duplicate->delete();
        $this->info("  [OK] '{$dupName}' silindi");
    }

    private function cleanupCaseInsensitiveDuplicates(): void
    {
        // Tüm surface type'ları al
        $all = SurfaceType::orderBy('name')->get();

        // Case-insensitive olarak grupla
        $groups = [];
        foreach ($all as $st) {
            $key = strtolower($st->name);
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $st;
        }

        // Sadece birden fazla olanları işle
        foreach ($groups as $name => $variants) {
            if (count($variants) <= 1) {
                continue;
            }

            $this->info("  Case-insensitive duplicate bulundu: '{$name}'");

            // En fazla kullanılanı bul (canonical olacak)
            $canonical = $variants[0];
            $maxUsage = ApplicationSurfaceArea::where('surface_type_id', $canonical->id)->count();

            foreach ($variants as $st) {
                $usage = ApplicationSurfaceArea::where('surface_type_id', $st->id)->count();
                if ($usage > $maxUsage) {
                    $canonical = $st;
                    $maxUsage = $usage;
                }
            }

            $this->info("    Canonical: '{$canonical->name}' (ID: {$canonical->id}, {$maxUsage} kullanım)");

            // Diğerlerini taşı ve sil
            foreach ($variants as $st) {
                if ($st->id === $canonical->id) {
                    continue;
                }

                $usage = ApplicationSurfaceArea::where('surface_type_id', $st->id)->count();
                if ($usage > 0) {
                    ApplicationSurfaceArea::where('surface_type_id', $st->id)
                        ->update(['surface_type_id' => $canonical->id]);
                    $this->info("    → {$usage} başvuru '{$st->name}' → '{$canonical->name}' taşındı");
                }

                $st->delete();
                $this->info("    → '{$st->name}' silindi");
            }
        }
    }

    private function generateColorCode(string $name): string
    {
        // Generate a consistent color based on name
        $colors = [
            'Beton'       => '#808080',
            'Beton Parke' => '#A0522D',
            'Asfalt'      => '#36454F',
            'Ham Toprak'  => '#8B4513',
        ];

        return $colors[$name] ?? '#' . substr(md5($name), 0, 6);
    }
}
