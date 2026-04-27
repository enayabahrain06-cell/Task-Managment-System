<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermission('view_reports')) {
            abort(403);
        }

        $range = $request->input('range', '30');

        $from = match ($range) {
            '7'   => now()->subDays(7)->startOfDay(),
            '30'  => now()->subDays(30)->startOfDay(),
            '90'  => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => null,
        };

        $doneStatuses    = ['approved', 'delivered'];
        $nonDoneStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        // Base query: tasks assigned to this user
        $base = function () use ($user, $from) {
            return Task::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                      ->from('task_assignees')
                      ->whereColumn('task_assignees.task_id', 'tasks.id')
                      ->where('task_assignees.user_id', $user->id));
            })->when($from, fn($q) => $q->where('tasks.created_at', '>=', $from));
        };

        // ── KPIs ──────────────────────────────────────────────────────────────
        $totalTasks     = $base()->count();
        $completedTasks = $base()->whereIn('status', $doneStatuses)->count();
        $inProgress     = $base()->where('status', 'in_progress')->count();
        $inReview       = $base()->whereIn('status', ['submitted', 'revision_requested'])->count();
        $overdueTasks   = $base()->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses)->count();
        $completionRate = $totalTasks > 0 ? round($completedTasks / $totalTasks * 100) : 0;

        $onTimeCount = $base()
            ->whereIn('status', $doneStatuses)
            ->whereHas('logs', function ($q) {
                $q->whereIn('action', ['status_updated_approved', 'status_updated_delivered', 'status_updated_completed'])
                  ->whereColumn('task_logs.created_at', '<=', 'tasks.deadline');
            })->count();
        $onTimeRate = $completedTasks > 0 ? round($onTimeCount / $completedTasks * 100) : 0;

        // ── Status Breakdown ─────────────────────────────────────────────────
        $statusGroups = [
            'pending'     => ['label' => 'Pending',     'statuses' => ['draft', 'assigned', 'viewed'],         'color' => '#6B7280', 'bg' => '#F3F4F6'],
            'in_progress' => ['label' => 'In Progress', 'statuses' => ['in_progress'],                         'color' => '#F59E0B', 'bg' => '#FEF3C7'],
            'in_review'   => ['label' => 'In Review',   'statuses' => ['submitted', 'revision_requested'],     'color' => '#8B5CF6', 'bg' => '#EDE9FE'],
            'completed'   => ['label' => 'Completed',   'statuses' => ['approved'],                            'color' => '#10B981', 'bg' => '#D1FAE5'],
            'delivered'   => ['label' => 'Delivered',   'statuses' => ['delivered', 'archived'],               'color' => '#047857', 'bg' => '#ECFDF5'],
            'overdue'     => ['label' => 'Overdue',     'statuses' => $nonDoneStatuses,                        'color' => '#EF4444', 'bg' => '#FEE2E2'],
        ];

        $statusBreakdown = [];
        foreach ($statusGroups as $key => $group) {
            $q = $base()->whereIn('status', $group['statuses']);
            if ($key === 'overdue') {
                $q->where('deadline', '<', now());
            }
            $count = $q->count();
            $statusBreakdown[$key] = array_merge($group, [
                'count' => $count,
                'pct'   => $totalTasks > 0 ? round($count / $totalTasks * 100) : 0,
            ]);
        }

        // ── Priority Breakdown ────────────────────────────────────────────────
        $priorityBreakdown = [];
        foreach (['low' => ['#10B981', '#D1FAE5'], 'medium' => ['#F59E0B', '#FEF3C7'], 'high' => ['#EF4444', '#FEE2E2']] as $p => [$color, $bg]) {
            $count = $base()->where('priority', $p)->count();
            $priorityBreakdown[$p] = [
                'label' => ucfirst($p),
                'count' => $count,
                'color' => $color,
                'bg'    => $bg,
                'pct'   => $totalTasks > 0 ? round($count / $totalTasks * 100) : 0,
            ];
        }

        // ── Monthly Trend (last 6 months) ─────────────────────────────────────
        $monthLabels      = [];
        $monthlyCreated   = [];
        $monthlyCompleted = [];
        for ($i = 5; $i >= 0; $i--) {
            $month          = now()->subMonths($i);
            $monthLabels[]  = $month->format('M Y');
            $monthlyCreated[] = Task::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                      ->from('task_assignees')
                      ->whereColumn('task_assignees.task_id', 'tasks.id')
                      ->where('task_assignees.user_id', $user->id));
            })->whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->count();

            $monthlyCompleted[] = Task::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                      ->from('task_assignees')
                      ->whereColumn('task_assignees.task_id', 'tasks.id')
                      ->where('task_assignees.user_id', $user->id));
            })->whereIn('status', $doneStatuses)
              ->whereYear('updated_at', $month->year)
              ->whereMonth('updated_at', $month->month)
              ->count();
        }

        // ── Project Performance (user's projects) ─────────────────────────────
        $projects = $user->projects()
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($proj) use ($user, $from, $doneStatuses, $nonDoneStatuses) {
                $tasks   = $proj->tasks()
                    ->where(function ($q) use ($user) {
                        $q->where('assigned_to', $user->id)
                          ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                              ->from('task_assignees')
                              ->whereColumn('task_assignees.task_id', 'tasks.id')
                              ->where('task_assignees.user_id', $user->id));
                    })
                    ->when($from, fn($q) => $q->where('tasks.created_at', '>=', $from))
                    ->get();

                $total   = $tasks->count();
                $done    = $tasks->whereIn('status', $doneStatuses)->count();
                $overdue = $tasks->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses)->count();
                $inProg  = $tasks->where('status', 'in_progress')->count();

                return [
                    'id'          => $proj->id,
                    'name'        => $proj->name,
                    'status'      => $proj->status,
                    'total'       => $total,
                    'completed'   => $done,
                    'in_progress' => $inProg,
                    'overdue'     => $overdue,
                    'rate'        => $total > 0 ? round($done / $total * 100) : 0,
                ];
            })->filter(fn($p) => $p['total'] > 0)->values();

        // ── Recent Task Activity ──────────────────────────────────────────────
        $recentLogs = TaskLog::with('task')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        // ── Overdue tasks list ────────────────────────────────────────────────
        $overdueList = $base()
            ->with(['project'])
            ->where('deadline', '<', now())
            ->whereIn('status', $nonDoneStatuses)
            ->orderBy('deadline')
            ->take(20)
            ->get()
            ->map(fn($t) => [
                'id'        => $t->id,
                'title'     => $t->title,
                'project'   => $t->project->name ?? '—',
                'deadline'  => $t->deadline->format('M d, Y'),
                'days_late' => abs(now()->diffInDays($t->deadline)),
                'priority'  => $t->priority ?? 'medium',
                'status'    => $t->status,
            ]);

        // ── Task IDs for this user (for reassigned/reopened queries) ──────────
        $userTaskIds = Task::where(function ($q) use ($user) {
            $q->where('assigned_to', $user->id)
              ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                  ->from('task_assignees')
                  ->whereColumn('task_assignees.task_id', 'tasks.id')
                  ->where('task_assignees.user_id', $user->id));
        })->pluck('id');

        // ── Reassigned Tasks ──────────────────────────────────────────────────
        $reassignedList = TaskLog::with(['task.project', 'user'])
            ->whereIn('action', ['task_reassigned', 'task_transferred'])
            ->whereIn('task_id', $userTaskIds)
            ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
            ->orderByDesc('task_logs.created_at')
            ->take(50)
            ->get()
            ->map(fn($log) => [
                'task'      => $log->task?->title ?? '—',
                'task_id'   => $log->task_id,
                'project'   => $log->task?->project?->name ?? '—',
                'from_user' => $log->metadata['from_user_name'] ?? '—',
                'to_user'   => $log->metadata['to_user_name']   ?? '—',
                'by'        => $log->metadata['reassigned_by']  ?? $log->user?->name ?? '—',
                'reason'    => $log->metadata['reason'] ?? null,
                'date'      => $log->created_at->format('M d, Y'),
                'time'      => $log->created_at->format('H:i'),
            ]);

        // ── Reopened Tasks ────────────────────────────────────────────────────
        $reopenedList = TaskLog::with(['task.project', 'user'])
            ->where('action', 'status_updated_reopened')
            ->whereIn('task_id', $userTaskIds)
            ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
            ->orderByDesc('task_logs.created_at')
            ->take(50)
            ->get()
            ->map(fn($log) => [
                'task'       => $log->task?->title ?? '—',
                'task_id'    => $log->task_id,
                'project'    => $log->task?->project?->name ?? '—',
                'old_status' => ucfirst(str_replace('_', ' ', $log->metadata['old_status'] ?? '—')),
                'by'         => $log->metadata['reopened_by_name'] ?? $log->user?->name ?? '—',
                'date'       => $log->created_at->format('M d, Y'),
                'time'       => $log->created_at->format('H:i'),
            ]);

        // ── All Tasks with timeline (ignores range — always full history) ────────
        $allTaskDetails = $this->buildTaskDetails($user, $doneStatuses, $nonDoneStatuses);

        return view('user.reports.index', compact(
            'range', 'from',
            'totalTasks', 'completedTasks', 'inProgress', 'inReview',
            'overdueTasks', 'completionRate', 'onTimeRate',
            'statusBreakdown', 'priorityBreakdown',
            'monthLabels', 'monthlyCreated', 'monthlyCompleted',
            'projects', 'recentLogs', 'overdueList',
            'reassignedList', 'reopenedList',
            'allTaskDetails'
        ));
    }

    private function buildTaskDetails($user, array $doneStatuses, array $nonDoneStatuses): \Illuminate\Support\Collection
    {
        $tasks = Task::where(function ($q) use ($user) {
            $q->where('assigned_to', $user->id)
              ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                  ->from('task_assignees')
                  ->whereColumn('task_assignees.task_id', 'tasks.id')
                  ->where('task_assignees.user_id', $user->id));
        })->with(['project:id,name'])
          ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
          ->orderBy('deadline')
          ->get();

        $taskIds = $tasks->pluck('id');

        $logsByTask = TaskLog::whereIn('task_id', $taskIds)
            ->whereIn('action', [
                'status_updated_in_progress',
                'status_updated_submitted',
                'status_updated_approved',
                'status_updated_delivered',
                'status_updated_completed',
            ])
            ->orderBy('created_at')
            ->get()
            ->groupBy('task_id');

        return $tasks->map(function ($task) use ($logsByTask, $doneStatuses, $nonDoneStatuses) {
            $logs        = $logsByTask->get($task->id, collect());
            $startedLog  = $logs->firstWhere('action', 'status_updated_in_progress');
            $submittedLog = $logs->firstWhere('action', 'status_updated_submitted');
            $completedLog = $logs->first(fn($l) => in_array($l->action, [
                'status_updated_approved', 'status_updated_delivered', 'status_updated_completed',
            ]));

            $startedAt   = $startedLog?->created_at;
            $submittedAt = $submittedLog?->created_at;
            $completedAt = $completedLog?->created_at;

            $daysToSubmit   = ($startedAt && $submittedAt)  ? (int) $startedAt->diffInDays($submittedAt)  : null;
            $daysToComplete = ($startedAt && $completedAt)  ? (int) $startedAt->diffInDays($completedAt)  : null;

            $isDone    = in_array($task->status, $doneStatuses);
            $isOverdue = $task->deadline && $task->deadline->isPast() && ! $isDone;
            $isLate    = $isDone && $task->deadline && $completedAt && $completedAt->gt($task->deadline);
            $daysLate  = $isLate ? (int) $task->deadline->diffInDays($completedAt) : null;
            $daysEarly = ($isDone && $task->deadline && $completedAt && $completedAt->lte($task->deadline))
                ? (int) $completedAt->diffInDays($task->deadline) : null;

            return [
                'id'              => $task->id,
                'title'           => $task->title,
                'project'         => $task->project?->name ?? '—',
                'priority'        => $task->priority ?? 'medium',
                'status'          => $task->status,
                'deadline'        => $task->deadline?->format('M d, Y'),
                'deadline_raw'    => $task->deadline?->toDateString(),
                'started_at'      => $startedAt?->format('M d, Y'),
                'submitted_at'    => $submittedAt?->format('M d, Y'),
                'completed_at'    => $completedAt?->format('M d, Y'),
                'days_to_submit'  => $daysToSubmit,
                'days_to_complete'=> $daysToComplete,
                'is_done'         => $isDone,
                'is_overdue'      => $isOverdue,
                'is_late'         => $isLate,
                'days_late'       => $daysLate,
                'days_early'      => $daysEarly,
            ];
        });
    }

    public function exportTasks()
    {
        $user = auth()->user();

        if (!$user->hasPermission('export_data')) {
            abort(403);
        }

        $doneStatuses    = ['approved', 'delivered', 'archived'];
        $nonDoneStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];
        $details         = $this->buildTaskDetails($user, $doneStatuses, $nonDoneStatuses);

        $statusLabels = [
            'draft'              => 'Draft',
            'assigned'           => 'Assigned',
            'viewed'             => 'Viewed',
            'in_progress'        => 'In Progress',
            'submitted'          => 'In Review',
            'revision_requested' => 'Revision Requested',
            'approved'           => 'Approved',
            'delivered'          => 'Delivered',
            'archived'           => 'Archived',
        ];

        $filename = 'my-tasks-' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($details, $statusLabels, $user) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ["My Task Report — {$user->name}"]);
            fputcsv($h, ['Generated', now()->format('Y-m-d H:i')]);
            fputcsv($h, []);
            fputcsv($h, ['Task', 'Project', 'Priority', 'Status', 'Started', 'Submitted', 'Completed', 'Days to Submit', 'Days to Complete', 'Deadline', 'Result']);
            foreach ($details as $t) {
                if ($t['is_overdue'])        $result = 'Overdue';
                elseif ($t['is_late'])        $result = 'Late (' . $t['days_late'] . 'd)';
                elseif ($t['is_done'] && $t['days_early'] !== null) $result = 'On Time (' . $t['days_early'] . 'd early)';
                elseif ($t['is_done'])        $result = 'On Time';
                else                          $result = 'In Progress';

                fputcsv($h, [
                    $t['title'],
                    $t['project'],
                    ucfirst($t['priority']),
                    $statusLabels[$t['status']] ?? ucfirst($t['status']),
                    $t['started_at']    ?? '—',
                    $t['submitted_at']  ?? '—',
                    $t['completed_at']  ?? '—',
                    $t['days_to_submit']   !== null ? $t['days_to_submit']   . ' days' : '—',
                    $t['days_to_complete'] !== null ? $t['days_to_complete'] . ' days' : '—',
                    $t['deadline'] ?? '—',
                    $result,
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }
}
