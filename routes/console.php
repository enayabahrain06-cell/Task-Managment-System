<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send subscription renewal reminders (daily at 9 AM system time).
Schedule::command('subscriptions:check-renewals')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// Send domain expiry reminders to responsible persons (daily at 9 AM system time).
Schedule::command('domains:check-renewals')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// Full system backup (DB + files) pushed to the NAS every night.
// Gated by the "Enable Automatic Backup" toggle in Settings > Backup — checked
// inside the command itself, so it can be turned off anytime without touching cron.
// Runs every minute; the when() guard fires only when the current HH:MM matches
// the configured auto_backup_time setting (default 02:00), so the time is
// changeable from the UI without touching cron.
Schedule::command('backup:auto-nas-sync')
    ->everyMinute()
    ->when(function () {
        $timezone = Setting::get('timezone', 'UTC');
        $runTime  = Setting::get('auto_backup_time', '02:00');
        $now      = \Carbon\Carbon::now($timezone);
        return $now->format('H:i') === $runTime;
    })
    ->withoutOverlapping()
    ->runInBackground();

// Generate weekly PDF reports for every active user and customer, and push them to the NAS.
// Runs Sunday night so the report covers the just-finished work week.
Schedule::command('reports:weekly-nas-sync')
    ->weeklyOn(0, '23:00')
    ->withoutOverlapping()
    ->runInBackground();

// Generate the company-wide "Task % by Customer" summary (broken down by month)
// and push it to the NAS. Runs once a month, on the 1st.
Schedule::command('reports:monthly-company-summary')
    ->monthlyOn(1, '23:00')
    ->withoutOverlapping()
    ->runInBackground();

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
