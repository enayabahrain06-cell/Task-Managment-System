<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;

class CalendarController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user->hasPermission('view_calendar')) {
            return redirect()->route('user.dashboard')->with('error', "You don't have permission to access Calendar.");
        }

        $tasks = match($user->role) {
            'admin', 'manager' => Task::with('project', 'assignee')->get(),
            default            => $user->tasks()->with('project')->get(),
        };

        $canManageMeetings = in_array($user->role, ['admin', 'manager']);
        $meetingsBaseUrl   = $user->role === 'manager' ? '/manager/meetings' : '/admin/meetings';

        $events = $tasks->map(fn($task) => [
            'id'       => $task->id,
            'title'    => $task->title,
            'start'    => $task->deadline->format('Y-m-d'),
            'editable' => false,
            'color'    => match($task->status) {
                'completed'   => '#10B981',
                'in_progress' => '#F59E0B',
                default       => match($task->priority) {
                    'high'   => '#EF4444',
                    'medium' => '#6366F1',
                    default  => '#9CA3AF',
                },
            },
            'extendedProps' => [
                'type'    => 'task',
                'id'      => $task->id,
                'status'  => $task->status,
                'project' => $task->project->name ?? '',
            ],
        ]);

        // Add meeting events to calendar
        $allMeetings = Meeting::with(['creator', 'attendees'])->get();
        $meetingEvents = $allMeetings->map(fn($m) => [
            'id'       => 'meeting-' . $m->id,
            'title'    => '📅 ' . $m->title,
            'start'    => $m->meeting_date->format('Y-m-d'),
            'color'    => $m->color,
            'editable' => $canManageMeetings,
            'display'  => 'block',
            'extendedProps' => [
                'type'       => 'meeting',
                'location'   => $m->location ?? '',
                'start_time' => substr($m->start_time, 0, 5),
            ],
        ]);

        $events = $events->merge($meetingEvents);

        $todayTasks    = $tasks->filter(fn($t) => $t->deadline->isToday());
        $upcomingTasks = $tasks->filter(fn($t) => $t->deadline->isFuture() && !$t->deadline->isToday())
            ->sortBy('deadline')->take(5);

        // Meetings
        $todayMeetings    = $allMeetings->filter(fn($m) => $m->meeting_date->isToday())->sortBy('start_time');
        $upcomingMeetings = $allMeetings->filter(fn($m) => $m->meeting_date->isFuture())->sortBy('meeting_date')->take(3);

        $teamMembers = $canManageMeetings ? User::where('id', '!=', $user->id)->orderBy('name')->get() : collect();

        return view('calendar.index', compact('events', 'todayTasks', 'upcomingTasks', 'todayMeetings', 'upcomingMeetings', 'teamMembers', 'allMeetings', 'canManageMeetings', 'meetingsBaseUrl'));
    }
}
