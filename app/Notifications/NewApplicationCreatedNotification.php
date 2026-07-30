<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewApplicationCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Application $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_application',
            'title' => 'Yeni Başvuru',
            'message' => sprintf(
                '%s tarafından %s no\'lu başvuru oluşturuldu.',
                $this->application->creator?->name ?? 'Bilinmiyor',
                $this->application->application_no ?? 'N/A',
            ),
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'url' => route('admin.applications.show', $this->application),
        ];
    }
}
