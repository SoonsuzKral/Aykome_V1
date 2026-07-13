<?php

namespace App\Notifications;

use App\Models\FieldTask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FieldStageCompleted extends Notification
{
    use Queueable;

    private const STAGE_LABELS = [
        1 => 'Kazı Öncesi Kontrol',
        2 => 'Kazı Sonrası Kontrol',
        3 => 'Zemin Onarım Kontrol',
    ];

    public function __construct(
        public readonly FieldTask $fieldTask,
        public readonly User      $inspector,
        public readonly int       $stage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $stageLabel = self::STAGE_LABELS[$this->stage] ?? "Aşama {$this->stage}";
        $appNo      = $this->fieldTask->application?->application_no ?? "#{$this->fieldTask->id}";

        return [
            'type'         => 'field_stage_completed',
            'title'        => 'Saha Aşaması Tamamlandı',
            'message'      => "{$this->inspector->name}, {$appNo} no'lu görevde «{$stageLabel}» aşamasını tamamladı.",
            'field_task_id' => $this->fieldTask->id,
            'application_no' => $appNo,
            'stage'        => $this->stage,
            'stage_label'  => $stageLabel,
            'inspector_id' => $this->inspector->id,
            'url'          => route('admin.field-tasks.show', $this->fieldTask),
        ];
    }
}
