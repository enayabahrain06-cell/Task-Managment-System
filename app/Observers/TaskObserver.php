<?php

namespace App\Observers;

use App\Models\CalendarEvent;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskLog;
use App\Notifications\TaskAssigned;
use App\Services\AssignmentNotifier;

class TaskObserver
{
    public function created(Task $task): void
    {
        if ($task->assigned_to) {
            CalendarEvent::create([
                'user_id'         => $task->assigned_to,
                'title'           => $task->title,
                'description'     => $task->description,
                'start_date'      => $task->deadline,
                'type'            => 'task',
                'related_task_id' => $task->id,
            ]);

            $assignee = $task->assignee;
            if ($assignee && Setting::get('notify_on_assign') === '1') {
                $assignee->notify(new TaskAssigned($task));
            }
            if ($assignee) {
                AssignmentNotifier::taskAssigned($task, $assignee);
            }
        }

        // Load relationship for metadata if not already loaded
        $task->loadMissing('assignee');

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id() ?? $task->assigned_to,
            'action'   => 'task_created',
            'note'     => 'Task created and assigned to ' . ($task->assignee->name ?? 'user'),
            'metadata' => [
                'assigned_to_id'   => $task->assigned_to,
                'assigned_to_name' => $task->assignee->name ?? null,
                'priority'         => $task->priority,
                'deadline'         => $task->deadline?->toDateString(),
            ],
        ]);
    }

    public function updated(Task $task): void
    {
        if ($task->isDirty('assigned_to') || $task->isDirty('deadline')) {
            $event = CalendarEvent::where('related_task_id', $task->id)->first();
            if ($event) {
                $event->update([
                    'user_id'     => $task->assigned_to,
                    'title'       => $task->title,
                    'description' => $task->description,
                    'start_date'  => $task->deadline,
                ]);
            }
        }

        // When a task goes in_progress, pause any other in_progress tasks for the same assignee
        // Note: startTimer controller already handles this for timer-triggered transitions;
        // this observer catches any edge cases (admin overrides, etc.)
        if ($task->isDirty('status') && $task->status === 'in_progress' && $task->assigned_to) {
            Task::where('assigned_to', $task->assigned_to)
                ->where('id', '!=', $task->id)
                ->where('status', 'in_progress')
                ->each(function (Task $other) use ($task) {
                    $other->updateQuietly(['status' => 'paused']);

                    TaskLog::create([
                        'task_id'  => $other->id,
                        'user_id'  => auth()->id() ?? $task->assigned_to,
                        'action'   => 'auto_paused',
                        'note'     => 'Auto-paused: assignee started working on "' . $task->title . '"',
                        'metadata' => [
                            'paused_by_task_id'    => $task->id,
                            'paused_by_task_title' => $task->title,
                            'old_status'           => 'in_progress',
                            'new_status'           => 'paused',
                        ],
                    ]);
                });
        }
    }
}

