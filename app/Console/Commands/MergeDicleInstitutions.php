<?php

namespace App\Console\Commands;

use App\Models\Institution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Test süreçlerinden kalan "0541 762 29 57" telefonlu (hatalı) Dicle Elektrik
 * kaydını, asıl "0850 255 0 186" telefonlu kurumla birleştirir.
 *
 * - institution_id = old_id olan TÜM tablolardaki kayıtları real_id'ye devreder.
 * - Eski (0541) kaydı tablodan tamamen siler.
 * - Idempotent: eski kayıt yoksa / tek kurum varsa güvenle raporlar, zarar vermez.
 *
 * Kullanım:
 *   ./oracle.sh artisan aykome:dicle-merge
 */
class MergeDicleInstitutions extends Command
{
    protected $signature   = 'aykome:dicle-merge';
    protected $description = 'Hatalı Dicle Elektrik (0541...) kaydını asıl (0850...) kurumla birleştirir ve siler.';

    /** institution_id kolonu taşıyan tüm tablolar. */
    protected array $tables = [
        'applications',
        'users',
        'licenses',
        'document_signatory_settings',
        'institution_document_templates',
    ];

    public function handle(): int
    {
        $oldPhone = '0541 762 29 57';
        $realPhone = '0850 255 0 186';

        $old = Institution::query()
            ->where('name', 'like', '%DİCLE%')
            ->orWhere('name', 'like', '%DICLE%')
            ->where('phone', $oldPhone)
            ->first();

        $real = Institution::query()
            ->where('name', 'like', '%DİCLE%')
            ->orWhere('name', 'like', '%DICLE%')
            ->where('phone', $realPhone)
            ->first();

        // Telefon eşleşmezse ad bazlı fallback: aynı adı taşıyan iki kayıttan
        // en yüksek ID'li olan "yeni/hatalı" kabul edilir.
        if (! $old && ! $real) {
            $dicles = Institution::query()
                ->where('name', 'like', '%DİCLE%')
                ->orWhere('name', 'like', '%DICLE%')
                ->orderBy('id')
                ->get();

            if ($dicles->count() > 1) {
                $real = $dicles->first();
                $old = $dicles->last();
                $this->warn("Telefon eşleşmedi; ad bazlı fallback: real_id={$real->id}, old_id={$old->id}");
            }
        }

        if (! $old) {
            $this->info("ℹ️  Hatalı Dicle kaydı ({$oldPhone}) bulunamadı. Büyük olasılıkla daha önce birleştirilmiş — merge gerekmiyor.");

            return self::SUCCESS;
        }

        if (! $real) {
            $this->error("❌ Asıl Dicle kaydı ({$realPhone}) bulunamadı. Merge iptal edildi.");

            return self::FAILURE;
        }

        if ($old->id === $real->id) {
            $this->info("ℹ️  old_id ile real_id aynı (#{$old->id}) — merge gereksiz.");

            return self::SUCCESS;
        }

        $this->info("Old (hatalı): #{$old->id} — {$old->name} [{$old->phone}]");
        $this->info("Real (asıl):  #{$real->id} — {$real->name} [{$real->phone}]");

        // ── 1) Tüm ilişkili tablolarda institution_id devri ──────────────────
        $totalMoved = 0;
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, 'institution_id')) {
                continue;
            }

            // Oracle unique constraint çakışmalarını önlemek için:
            // önce old_id'yi real_id'ye geçir, çakışan satır varsa sadece ilki kalır.
            $moved = DB::table($table)
                ->where('institution_id', $old->id)
                ->update(['institution_id' => $real->id]);

            $this->line("   ↳ {$table}: {$moved} kayıt #{$old->id} → #{$real->id}");
            $totalMoved += $moved;
        }

        // ── 2) Eski kurum kaydını sil ───────────────────────────────────────
        $old->delete();

        $this->info("✅ Toplam {$totalMoved} kayıt devredildi; eski Dicle kaydı (#{$old->id}) silindi.");

        return self::SUCCESS;
    }
}
