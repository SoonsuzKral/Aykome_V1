<?php

namespace App\Events;

use App\Models\FieldTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // ← synchronous, no queue worker needed
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FieldTaskCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FieldTask $fieldTask
    ) {
        Log::info('[FieldTaskCompleted] Event oluşturuldu — broadcast başlatılıyor.', [
            'task_id'        => $fieldTask->id,
            'application_id' => $fieldTask->application_id,
        ]);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('admin-notifications');
    }

    public function broadcastAs(): string
    {
        return 'field-task.completed';
    }

    public function broadcastWith(): array
    {
        try {
            $task = $this->fieldTask;
            $app  = $task->application;

            $payload = [
                'task_id'        => $task->id,
                'application_id' => $app?->id,
                'application_no' => $app?->application_no,
                'address'        => $app?->address_text,
                'assignee'       => $task->assignee?->name ?? 'Saha Personeli',
                'completed_at'   => now()->format('d.m.Y H:i'),
                'message'        => ($app?->application_no ? $app->application_no . ' numaralı' : '') . ' saha görevi tamamlandı.',
            ];

            Log::info('[FieldTaskCompleted] Payload gönderildi.', $payload);

            return $payload;
        } catch (Throwable $e) {
            Log::error('[FieldTaskCompleted] broadcastWith HATA.', [
                'error'   => $e->getMessage(),
                'task_id' => $this->fieldTask->id ?? null,
            ]);

            return [
                'task_id' => $this->fieldTask->id ?? null,
                'message' => 'Bir saha görevi tamamlandı.',
            ];
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('[FieldTaskCompleted] Broadcast BAŞARISIZ.', [
            'task_id' => $this->fieldTask->id ?? null,
            'error'   => $exception->getMessage(),
        ]);
    }
}
