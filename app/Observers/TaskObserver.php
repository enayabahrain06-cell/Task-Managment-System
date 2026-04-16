<?php

namespace App\Observers;

use App\Models\CalendarEvent;
use App\Models\Task;

class TaskObserver
{
    public function created(Task $task): void
    {
        if ($task->assigned_to) {
            CalendarEvent::create([
                'user_id' => $task->assigned_to,
                'title' => $task->title,
                'description' => $task->description,
                'start_date' => $task->deadline,
                'type' => 'task',
                'related_task_id' => $task->id,
            ]);
        }
    }

    public function updated(Task $task): void
    {
        if ($task->isDirty('assigned_to') || $task->isDirty('deadline')) {
            $event = CalendarEvent::where('related_task_id', $task->id)->first();
            if ($event) {
                $event->update([
                    'title' => $task->title,
                    'description' => $task->description,
                    'start_date' => $task->deadline,
                ]);
            }
        }
    }
}

