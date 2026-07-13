<?php

namespace App\Listeners;

use App\Events\ApplicationSubmitted;
use Illuminate\Support\Facades\Log;

class LogApplicationSubmitted
{
    public function handle(ApplicationSubmitted $event): void
    {
        Log::info('application.submitted', [
            'application_id' => $event->application->id,
            'application_no' => $event->application->application_no,
        ]);
    }
}
