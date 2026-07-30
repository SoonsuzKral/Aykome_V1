<?php

namespace App\Notifications;

use App\Models\Application;
use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReceiptUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Application $application,
        public readonly Receipt $receipt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'receipt_uploaded',
            'title' => 'Makbuz Yüklendi',
            'message' => sprintf(
                '%s no\'lu başvuru için makbuz yüklendi. Onay bekleniyor.',
                $this->application->application_no ?? 'N/A',
            ),
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'receipt_id' => $this->receipt->id,
            'url' => route('admin.applications.show', $this->application),
        ];
    }
}
