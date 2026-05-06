<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-pause all running timers at end of work day.
// Runs every minute; the when() guard fires only when the current
// HH:MM matches the configured work_end_time setting.
Schedule::command('timers:pause-eod')
    ->everyMinute()
    ->when(function () {
        $timezone = Setting::get('timezone', 'UTC');
        $endTime  = Setting::get('work_end_time', '18:00');
        $now      = \Carbon\Carbon::now($timezone);
        return $now->format('H:i') === $endTime;
    })
    ->withoutOverlapping()
    ->runInBackground();
