<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskTimerSegment;
use App\Notifications\TaskAutoPaused;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PauseEndOfDayTimers extends Command
{
    protected $signature   = 'timers:pause-eod {--dry-run : List affected tasks without making changes}';
    protected $description = 'Auto-pause all running timers at end of work day';

    public function handle(): int
    {
        $timezone  = Setting::get('timezone', 'UTC');
        $endTime   = Setting::get('work_end_time', '18:00');   // e.g. "18:00"
        $workDays  = json_decode(Setting::get('work_days', '[1,2,3,4,5]'), true) ?? [1,2,3,4,5];
        $dryRun    = $this->option('dry-run');

        $now = Carbon::now($timezone);

        // Only run on configured work days (1=Mon … 7=Sun)
        if (!in_array($now->dayOfWeekIso, $workDays)) {
            $this->info("Today ({$now->format('l')}) is not a work day — skipping.");
            return self::SUCCESS;
        }

        // Find all open timer segments
        $openSegments = TaskTimerSegment::whereNull('ended_at')
            ->with(['task', 'user'])
            ->get();

        if ($openSegments->isEmpty()) {
            $this->info('No running timers to pause.');
            return self::SUCCESS;
        }

        $paused = 0;

        foreach ($openSegments as $seg) {
            $task = $seg->task;
            if (!$task) continue;

            $this->line("  • [{$task->id}] {$task->title} — {$seg->user?->name}");

            if ($dryRun) continue;

            $seconds = (int) $seg->started_at->diffInSeconds(now());
            $seg->update([
                'ended_at'         => now(),
                'duration_seconds' => $seconds,
                'pause_reason'     => 'end_of_day',
            ]);

            if ($task->status === 'in_progress') {
                $task->updateQuietly(['status' => 'paused']);

                TaskLog::create([
                    'task_id'  => $task->id,
                    'user_id'  => $seg->user_id,
                    'action'   => 'auto_paused',
                    'note'     => "Timer auto-paused at end of work day ({$endTime}).",
                    'metadata' => [
                        'pause_reason'  => 'end_of_day',
                        'paused_at'     => now()->toDateTimeString(),
                        'work_end_time' => $endTime,
                    ],
                ]);

                if ($seg->user) {
                    $seg->user->notify(new TaskAutoPaused($task, 'end_of_day'));
                }
            }

            $paused++;
        }

        if ($dryRun) {
            $this->warn("Dry run — {$openSegments->count()} timer(s) would be paused.");
        } else {
            $this->info("Paused {$paused} timer(s) for end of day.");
        }

        return self::SUCCESS;
    }
}
