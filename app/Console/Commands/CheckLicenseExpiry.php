<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLicenseExpiry extends Command
{
    protected $signature   = 'licenses:check-expiry';
    protected $description = 'Süresi dolan lisansları pasife çeker; 30 gün içinde dolacakları loglar.';

    public function handle(): int
    {
        // ── 1. Süresi BUGÜN veya DAHA ÖNCE dolan aktif lisanslar ──────────────
        $expired = License::query()
            ->where('is_active', true)
            ->whereDate('valid_until', '<', today())
            ->get();

        foreach ($expired as $license) {
            $license->update(['is_active' => false]);

            Log::channel('daily')->warning('[LisansKilidi] Lisans pasife alındı.', [
                'license_id'   => $license->id,
                'license_key'  => $license->license_key,
                'owner'        => $license->owner_name,
                'institution'  => $license->institution?->name ?? '—',
                'valid_until'  => $license->valid_until?->format('Y-m-d'),
            ]);

            $this->warn("🔴 KİLİTLENDİ  → #{$license->id} {$license->owner_name} ({$license->valid_until?->format('Y-m-d')})");
        }

        // ── 2. 30 gün içinde dolacak lisanslar — uyarı logu ──────────────────
        $expiringSoon = License::query()
            ->where('is_active', true)
            ->whereDate('valid_until', '>=', today())
            ->whereDate('valid_until', '<=', today()->addDays(30))
            ->get();

        foreach ($expiringSoon as $license) {
            $daysLeft = today()->diffInDays($license->valid_until);

            Log::channel('daily')->notice('[LisansUyarı] Lisans yakında doluyor.', [
                'license_id'  => $license->id,
                'license_key' => $license->license_key,
                'owner'       => $license->owner_name,
                'days_left'   => $daysLeft,
                'valid_until' => $license->valid_until?->format('Y-m-d'),
            ]);

            $this->line("⚠️  YAKINDA DOLUYOR → #{$license->id} {$license->owner_name} — {$daysLeft} gün kaldı");
        }

        $this->info("✅ Tamamlandı: {$expired->count()} kilitlendi, {$expiringSoon->count()} yakında dolacak.");

        return self::SUCCESS;
    }
}
