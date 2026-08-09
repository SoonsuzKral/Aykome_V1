<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckFieldStaffStatus implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    /**
     * Zombi tespiti: is_on_field=1 ama 2 dakikadır ping atmayan kullanıcıları pasife al.
     * liveData() ile birebir aynı 2 dk eşiği — ikisi senkron çalışır.
     *
     * Kapsanan senaryolar:
     *   A) Ping atmış ama 2 dk'dan eski             → sekme kapatma, uygulama kapama
     *   B) Hiç ping atmamış, field_started_at 2 dk+ → GPS izni verilmedi, sayfa hemen kapatıldı
     *   C) İkisi de NULL (tutarsız kayıt)            → hepsini temizle
     */
    public function handle(): void
    {
        $cutoff = now()->subMinutes(2);

        $affected = User::where('is_on_field', 1)
            ->where(function ($q) use ($cutoff) {
                $q->where('last_seen_at', '<', $cutoff)
                  ->orWhere(function ($inner) use ($cutoff) {
                      $inner->whereNull('last_seen_at')
                            ->where('field_started_at', '<', $cutoff);
                  })
                  ->orWhere(function ($inner) {
                      $inner->whereNull('last_seen_at')
                            ->whereNull('field_started_at');
                  });
            })
            ->pluck('id');

        if ($affected->isNotEmpty()) {
            User::whereIn('id', $affected)->update([
                'is_on_field'      => 0,
                'current_lat'      => null,
                'current_lng'      => null,
                'field_started_at' => null,
            ]);

            Log::info('[CheckFieldStaffStatus] Zombi personel pasife alındı.', [
                'count'    => $affected->count(),
                'user_ids' => $affected->toArray(),
                'cutoff'   => $cutoff->toDateTimeString(),
            ]);
        }
    }
}
