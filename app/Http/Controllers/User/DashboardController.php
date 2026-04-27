<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskTransfer;
use App\Models\User;
use App\Notifications\UserReportSubmitted;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $allTasks = $user->tasks()->with('project')->get();

        $doneStatuses   = ['approved', 'delivered', 'archived'];
        $activeStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        // IDs of tasks bulk-transferred TO this user
        $inheritedIds = TaskTransfer::where('to_user_id', $user->id)
            ->pluck('task_id')
            ->unique();

        // Individual reassignments TO this user — keyed by task_id for from_user lookup
        $reassignedLogsToMe = TaskLog::where('action', 'task_reassigned')
            ->whereIn('task_id', $allTasks->pluck('id'))
            ->get()
            ->filter(fn($log) => ($log->metadata['to_user_id'] ?? null) == $user->id)
            ->keyBy('task_id');

        // All "received from others" = bulk transfers + individual reassignments
        $receivedFromOthersIds = $inheritedIds->merge($reassignedLogsToMe->keys())->unique();

        $nativeTasks   = $allTasks->whereNotIn('id', $receivedFromOthersIds->toArray());
        $receivedTasks = $allTasks->whereIn('id', $receivedFromOthersIds->toArray());
        $inheritedTasks = $allTasks->whereIn('id', $inheritedIds->toArray()); // legacy compat

        $total      = $allTasks->count();
        $completed  = $allTasks->whereIn('status', $doneStatuses)->count();
        $inProgress = $allTasks->where('status', 'in_progress')->count();
        $pending    = $allTasks->whereIn('status', ['draft', 'assigned', 'viewed'])->count();
        $inReview   = $allTasks->where('status', 'submitted')->count();
        $overdue    = $allTasks->filter(
            fn($t) => $t->deadline && $t->deadline->isPast() && in_array($t->status, $activeStatuses)
        )->count();

        // Completion rate based on native (own) tasks only
        $nativeTotal     = $nativeTasks->count();
        $nativeCompleted = $nativeTasks->whereIn('status', $doneStatuses)->count();
        $rate            = $nativeTotal > 0 ? round($nativeCompleted / $nativeTotal * 100) : 0;

        // Received (reassigned/transferred) task stats
        $receivedTotal    = $receivedTasks->count();
        $receivedCompleted = $receivedTasks->whereIn('status', $doneStatuses)->count();

        // My tasks sorted: overdue → in_progress → submitted → upcoming → done
        $tasks = $allTasks->sortBy(function ($t) use ($doneStatuses) {
            if (in_array($t->status, $doneStatuses))    return '5_' . ($t->deadline?->format('Y-m-d') ?? '9999');
            if ($t->status === 'submitted')              return '3_' . ($t->deadline?->format('Y-m-d') ?? '9999');
            if ($t->deadline && $t->deadline->isPast()) return '1_' . $t->deadline->format('Y-m-d');
            if ($t->status === 'in_progress')            return '2_' . ($t->deadline?->format('Y-m-d') ?? '9999');
            return '4_' . ($t->deadline?->format('Y-m-d') ?? '9999');
        })->values();

        // Tag each task — mark received tasks and attach who it came from
        $tasks = $tasks->map(function ($t) use ($inheritedIds, $reassignedLogsToMe, $receivedFromOthersIds) {
            $t->is_inherited  = $inheritedIds->contains($t->id);
            $t->is_reassigned = $reassignedLogsToMe->has($t->id);
            $t->from_user     = $reassignedLogsToMe->get($t->id)?->metadata['from_user_name'] ?? null;
            $t->is_received   = $receivedFromOthersIds->contains($t->id);
            $t->is_social     = false;
            return $t;
        });

        // Merge pending social media assignments into My Tasks
        $pendingSocialTasks = Task::where('social_assigned_to', $user->id)
            ->whereNull('social_posted_at')
            ->with('project')
            ->get()
            ->map(function ($t) {
                $t->is_inherited = false;
                $t->is_social    = true;
                return $t;
            });

        $tasks = $tasks->merge($pendingSocialTasks)->values();

        // Next 4 upcoming (non-done, future deadline)
        $upcomingTasks = $allTasks
            ->filter(fn($t) => $t->deadline && $t->deadline->isFuture() && !in_array($t->status, $doneStatuses))
            ->sortBy('deadline')
            ->take(4);

        // Team tasks: tasks in my projects not assigned to me
        $myProjectIds = $user->projects()->pluck('projects.id')
            ->merge(Task::where('assigned_to', $user->id)->whereNotNull('project_id')->pluck('project_id'))
            ->merge(Task::where('social_assigned_to', $user->id)->whereNotNull('project_id')->pluck('project_id'))
            ->unique()->values();
        $teamTasks = Task::whereIn('project_id', $myProjectIds)
            ->where('assigned_to', '!=', $user->id)
            ->with(['project', 'assignee'])
            ->orderByRaw("CASE WHEN status IN ('approved','delivered','archived') THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(20)
            ->get();

        // All project IDs this user is involved in (member, task assignee, or social assignee)
        $involvedProjectIds = $user->projects()->pluck('projects.id')
            ->merge(Task::where('assigned_to', $user->id)->whereNotNull('project_id')->pluck('project_id'))
            ->merge(Task::where('social_assigned_to', $user->id)->whereNotNull('project_id')->pluck('project_id'))
            ->unique()
            ->values();

        // My projects with progress
        $myProjects = \App\Models\Project::whereIn('id', $involvedProjectIds)
            ->withCount([
                'tasks',
                'tasks as completed_count' => fn($q) => $q->whereIn('status', $doneStatuses),
            ])
            ->orderByRaw("CASE WHEN status='completed' THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(6)
            ->get();

        $myProjectStats = [
            'total'     => \App\Models\Project::whereIn('id', $involvedProjectIds)->count(),
            'active'    => \App\Models\Project::whereIn('id', $involvedProjectIds)->where('status', 'active')->count(),
            'completed' => \App\Models\Project::whereIn('id', $involvedProjectIds)->where('status', 'completed')->count(),
            'overdue'   => \App\Models\Project::whereIn('id', $involvedProjectIds)
                ->whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->where('status', '!=', 'completed')
                ->count(),
        ];

        // Recent activity (this user's task logs)
        $recentActivity = TaskLog::where('user_id', $user->id)
            ->with('task')
            ->latest()
            ->take(8)
            ->get();

        // Last 7 days activity bar chart
        $weekActivity = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = now()->subDays($daysAgo)->toDateString();
            return [
                'label' => now()->subDays($daysAgo)->format('D'),
                'count' => TaskLog::where('user_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->count(),
            ];
        });

        $inheritedCount = $receivedFromOthersIds->count();

        $pendingApproval = $inReview; // view uses old name, maps to 'submitted' status

        // Social media tasks assigned to this user
        $socialTasks = Task::where('social_assigned_to', $user->id)
            ->with(['project', 'socialPosts'])
            ->orderByRaw('social_posted_at IS NOT NULL')
            ->orderBy('deadline')
            ->get();

        $pendingSocialPosts   = $socialTasks->whereNull('social_posted_at')->count();
        $completedSocialPosts = $socialTasks->whereNotNull('social_posted_at')->count();

        return view('user.dashboard', compact(
            'total', 'completed', 'inProgress', 'pending', 'pendingApproval', 'overdue', 'rate',
            'tasks', 'upcomingTasks', 'recentActivity', 'weekActivity',
            'teamTasks', 'myProjects', 'myProjectStats', 'socialTasks',
            'inheritedCount', 'nativeTotal', 'nativeCompleted', 'pendingSocialPosts', 'completedSocialPosts',
            'receivedTotal', 'receivedCompleted'
        ));
    }

    public function submitReport(Request $request)
    {
        $request->validate([
            'report' => 'required|string|min:10|max:1000',
        ]);

        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new UserReportSubmitted(auth()->user(), $request->report));
        }

        return back()->with('success', 'Progress report submitted successfully.');
    }

    public function taskModal(Request $request)
    {
        $user   = auth()->user();
        $filter = $request->input('filter', 'total');

        $doneStatuses    = ['approved', 'delivered', 'archived'];
        $nonDoneStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        $base = Task::where(function ($q) use ($user) {
            $q->where('assigned_to', $user->id)
              ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                  ->from('task_assignees')
                  ->whereColumn('task_assignees.task_id', 'tasks.id')
                  ->where('task_assignees.user_id', $user->id));
        })->with(['project:id,name']);

        if ($filter === 'social') {
            $base = Task::where('social_assigned_to', $user->id)
                ->whereNull('social_posted_at')
                ->with(['project:id,name']);
        } elseif ($filter === 'date') {
            $date    = $request->input('date');
            $taskIds = TaskLog::where('user_id', $user->id)
                ->whereDate('created_at', $date)
                ->pluck('task_id')
                ->unique();
            $base->whereIn('id', $taskIds);
        } elseif ($filter === 'received') {
            $inheritedIds  = \App\Models\TaskTransfer::where('to_user_id', $user->id)->pluck('task_id');
            $reassignedIds = TaskLog::where('action', 'task_reassigned')
                ->whereIn('task_id', Task::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                          ->from('task_assignees')
                          ->whereColumn('task_assignees.task_id', 'tasks.id')
                          ->where('task_assignees.user_id', $user->id));
                })->pluck('id'))
                ->get()
                ->filter(fn($log) => ($log->metadata['to_user_id'] ?? null) == $user->id)
                ->pluck('task_id');
            $receivedIds = $inheritedIds->merge($reassignedIds)->unique();
            $base->whereIn('id', $receivedIds);
        } else {
            match ($filter) {
                'completed'   => $base->whereIn('status', $doneStatuses),
                'in_progress' => $base->where('status', 'in_progress'),
                'in_review'   => $base->whereIn('status', ['submitted', 'revision_requested']),
                'overdue'     => $base->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses),
                default       => $base->whereIn('status', $nonDoneStatuses),
            };
        }

        $statusMeta = [
            'draft'              => ['label' => 'Draft',        'color' => '#6B7280', 'bg' => '#F3F4F6'],
            'assigned'           => ['label' => 'Assigned',     'color' => '#4F46E5', 'bg' => '#EEF2FF'],
            'viewed'             => ['label' => 'Viewed',       'color' => '#0369A1', 'bg' => '#E0F2FE'],
            'in_progress'        => ['label' => 'In Progress',  'color' => '#D97706', 'bg' => '#FEF3C7'],
            'submitted'          => ['label' => 'In Review',    'color' => '#7C3AED', 'bg' => '#EDE9FE'],
            'revision_requested' => ['label' => 'Revision',     'color' => '#DC2626', 'bg' => '#FEE2E2'],
            'approved'           => ['label' => 'Approved',     'color' => '#059669', 'bg' => '#D1FAE5'],
            'delivered'          => ['label' => 'Delivered',    'color' => '#047857', 'bg' => '#ECFDF5'],
            'archived'           => ['label' => 'Archived',     'color' => '#6B7280', 'bg' => '#F3F4F6'],
        ];
        $priorityMeta = [
            'high'   => ['label' => 'High', 'color' => '#EF4444'],
            'medium' => ['label' => 'Med',  'color' => '#F59E0B'],
            'low'    => ['label' => 'Low',  'color' => '#10B981'],
        ];

        $tasks = $base->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->take(50)
            ->get()
            ->map(function ($task) use ($statusMeta, $priorityMeta) {
                $sm = $statusMeta[$task->status] ?? ['label' => ucfirst($task->status ?? ''), 'color' => '#6B7280', 'bg' => '#F3F4F6'];
                $pm = $priorityMeta[$task->priority] ?? null;
                return [
                    'id'          => $task->id,
                    'title'       => $task->title,
                    'status'      => $task->status,
                    'statusLabel' => $sm['label'],
                    'statusColor' => $sm['color'],
                    'statusBg'    => $sm['bg'],
                    'priority'    => $task->priority,
                    'priorityMeta'=> $pm,
                    'deadline'    => $task->deadline?->format('M d, Y'),
                    'isOverdue'   => $task->deadline && $task->deadline->isPast() && !in_array($task->status, ['approved', 'delivered', 'archived']),
                    'project'     => $task->project?->name,
                    'url'         => route('user.tasks.show', $task->id),
                ];
            });

        return response()->json(['tasks' => $tasks, 'filter' => $filter]);
    }
}
