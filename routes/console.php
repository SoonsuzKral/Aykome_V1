<?php

use App\Jobs\CheckFieldStaffStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckFieldStaffStatus)->everyMinute();

// Lisans kontrol cron — canlıda dailyAt('08:00'), geliştirmede everyMinute()
Schedule::command('licenses:check-expiry')
    ->everyMinute()   // TODO: canlıya alırken ->dailyAt('08:00') yap
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/license-expiry.log'));
