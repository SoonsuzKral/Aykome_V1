<?php

namespace App\Events;

use App\Models\Application;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Application $application
    ) {
        Log::info('[ApplicationSubmitted] Event oluşturuldu — broadcast başlatılıyor.', [
            'application_id' => $application->id,
            'application_no' => $application->application_no,
        ]);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('admin-notifications');
    }

    public function broadcastAs(): string
    {
        return 'application.submitted';
    }

    public function broadcastWith(): array
    {
        try {
            $app = $this->application;

            $payload = [
                'application_id' => $app->id,
                'application_no' => $app->application_no,
                'applicant'      => trim($app->applicant_first_name . ' ' . $app->applicant_last_name),
                'institution'    => $app->institution?->name ?? '',
                'submitted_at'   => now()->format('d.m.Y H:i'),
                'message'        => ($app->application_no ? $app->application_no . ' numaralı' : 'Bir') . ' başvuru belediyeye gönderildi.',
                'detail_url'     => route('admin.applications.show', $app),
            ];

            Log::info('[ApplicationSubmitted] Payload gönderildi.', $payload);

            return $payload;
        } catch (Throwable $e) {
            Log::error('[ApplicationSubmitted] broadcastWith HATA.', [
                'error'          => $e->getMessage(),
                'application_id' => $this->application->id ?? null,
            ]);

            return [
                'application_id' => $this->application->id ?? null,
                'message'        => 'Bir başvuru belediyeye gönderildi.',
            ];
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('[ApplicationSubmitted] Broadcast BAŞARISIZ.', [
            'application_id' => $this->application->id ?? null,
            'error'          => $exception->getMessage(),
        ]);
    }
}
