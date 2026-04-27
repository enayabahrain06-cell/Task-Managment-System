<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('view_reports')) {
            abort(403, 'You do not have permission to view Reports.');
        }

        $range     = $request->input('range', '30');
        $projectId = $request->input('project_id');

        $from = match ($range) {
            '7'   => now()->subDays(7)->startOfDay(),
            '30'  => now()->subDays(30)->startOfDay(),
            '90'  => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => null,  // 'all'
        };

        $doneStatuses     = ['approved', 'delivered'];
        $nonDoneStatuses  = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        // ── Base scoped query helper ───────────────────────────────────────────
        $scoped = function () use ($from, $projectId) {
            return Task::when($from, fn($q) => $q->where('tasks.created_at', '>=', $from))
                       ->when($projectId, fn($q) => $q->where('tasks.project_id', $projectId));
        };

        // ── Summary KPIs ───────────────────────────────────────────────────────
        $totalTasks     = $scoped()->count();
        $completedTasks = $scoped()->whereIn('status', $doneStatuses)->count();
        // Overdue is a current state — do not filter by created_at, only by project
        $overdueTasks   = Task::where('deadline', '<', now())
                              ->whereIn('status', $nonDoneStatuses)
                              ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                              ->count();
        $completionRate = $totalTasks > 0 ? round($completedTasks / $totalTasks * 100) : 0;

        $onTimeCount = $scoped()
            ->whereIn('status', $doneStatuses)
            ->whereHas('logs', function ($q) {
                $q->whereIn('action', ['status_updated_approved', 'status_updated_delivered', 'status_updated_completed'])
                  ->whereColumn('task_logs.created_at', '<=', 'tasks.deadline');
            })->count();
        $onTimeRate = $completedTasks > 0 ? round($onTimeCount / $completedTasks * 100) : 0;

        $activeProjects = Project::where('status', 'active')
            ->when($projectId, fn($q) => $q->where('id', $projectId))
            ->count();

        // Pending review is a current queue — do not filter by created_at, only by project
        $pendingReview = Task::where('status', 'submitted')
                             ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                             ->count();

        // All active non-admin users regardless of task count in period
        $teamMemberCount = User::whereNotIn('role', ['admin'])
            ->where('status', 'active')
            ->count();

        // ── Status Breakdown ──────────────────────────────────────────────────
        $statusGroups = [
            'pending'    => ['label' => 'Pending',        'statuses' => ['draft', 'assigned', 'viewed'],             'color' => '#6B7280', 'bg' => '#F3F4F6'],
            'in_progress'=> ['label' => 'In Progress',    'statuses' => ['in_progress'],                             'color' => '#F59E0B', 'bg' => '#FEF3C7'],
            'in_review'  => ['label' => 'In Review',      'statuses' => ['submitted', 'revision_requested'],         'color' => '#8B5CF6', 'bg' => '#EDE9FE'],
            'completed'  => ['label' => 'Completed',      'statuses' => ['approved'],                                'color' => '#10B981', 'bg' => '#D1FAE5'],
            'delivered'  => ['label' => 'Delivered',      'statuses' => ['delivered', 'archived'],                   'color' => '#047857', 'bg' => '#ECFDF5'],
            'overdue'    => ['label' => 'Overdue',        'statuses' => $nonDoneStatuses, 'extra' => ['deadline' => ['<', now()]], 'color' => '#EF4444', 'bg' => '#FEE2E2'],
        ];

        $statusBreakdown = [];
        foreach ($statusGroups as $key => $group) {
            $q = $scoped()->whereIn('status', $group['statuses']);
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
        foreach (['low' => ['#10B981','#D1FAE5'], 'medium' => ['#F59E0B','#FEF3C7'], 'high' => ['#EF4444','#FEE2E2']] as $p => [$color, $bg]) {
            $count = $scoped()->where('priority', $p)->count();
            $priorityBreakdown[$p] = [
                'label' => ucfirst($p),
                'count' => $count,
                'color' => $color,
                'bg'    => $bg,
                'pct'   => $totalTasks > 0 ? round($count / $totalTasks * 100) : 0,
            ];
        }

        // ── Project Performance ───────────────────────────────────────────────
        $projects = Project::with('tasks')
            ->when($projectId, fn($q) => $q->where('id', $projectId))
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($proj) use ($from, $doneStatuses, $nonDoneStatuses) {
                $tasks    = $proj->tasks()->when($from, fn($q) => $q->where('tasks.created_at', '>=', $from))->get();
                $total    = $tasks->count();
                $done     = $tasks->whereIn('status', $doneStatuses)->count();
                $overdue  = $tasks->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses)->count();
                $inProg   = $tasks->where('status', 'in_progress')->count();

                return [
                    'id'          => $proj->id,
                    'name'        => $proj->name,
                    'status'      => $proj->status,
                    'total'       => $total,
                    'completed'   => $done,
                    'in_progress' => $inProg,
                    'overdue'     => $overdue,
                    'rate'        => $total > 0 ? round($done / $total * 100) : 0,
                    'deadline'    => $proj->deadline,
                    'days_left'   => $proj->deadline ? now()->diffInDays($proj->deadline, false) : null,
                ];
            });

        // ── Team Productivity ─────────────────────────────────────────────────
        // Regular users: metrics based on assigned tasks
        $userMembers = User::where('role', 'user')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($from, $doneStatuses, $nonDoneStatuses) {
                $base = Task::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                          ->from('task_assignees')
                          ->whereColumn('task_assignees.task_id', 'tasks.id')
                          ->where('task_assignees.user_id', $user->id));
                })->when($from, fn($q) => $q->where('tasks.updated_at', '>=', $from));

                $total    = (clone $base)->count();
                $done     = (clone $base)->whereIn('status', $doneStatuses)->count();
                $inProg   = (clone $base)->where('status', 'in_progress')->count();
                $overdue  = (clone $base)->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses)->count();
                $inReview = (clone $base)->whereIn('status', ['submitted', 'revision_requested'])->count();

                $socialBase = Task::where('social_assigned_to', $user->id)
                    ->where(function ($q) use ($user) {
                        $q->whereNull('assigned_to')
                          ->orWhere('assigned_to', '!=', $user->id);
                    })
                    ->whereNotExists(fn($sub) => $sub->selectRaw('1')
                        ->from('task_assignees')
                        ->whereColumn('task_assignees.task_id', 'tasks.id')
                        ->where('task_assignees.user_id', $user->id))
                    ->when($from, fn($q) => $q->where('tasks.updated_at', '>=', $from));

                $socialTotal  = (clone $socialBase)->count();
                $socialDone   = (clone $socialBase)->whereNotNull('social_posted_at')->count();
                $socialInProg = (clone $socialBase)->whereNull('social_posted_at')->count();

                $grandTotal = $total + $socialTotal;
                $grandDone  = $done + $socialDone;

                return [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'role'             => ucfirst($user->role),
                    'member_type'      => 'user',
                    'total'            => $grandTotal,
                    'completed'        => $grandDone,
                    'in_progress'      => $inProg + $socialInProg,
                    'in_review'        => $inReview,
                    'overdue'          => $overdue,
                    'rate'             => $grandTotal > 0 ? round($grandDone / $grandTotal * 100) : 0,
                    'projects_created' => 0,
                ];
            });

        // Admin/Manager: metrics based on tasks they created + tasks they approved
        $adminManagerMembers = User::whereIn('role', ['admin', 'manager'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($from, $nonDoneStatuses) {
                $createdBase = Task::where('created_by', $user->id)
                    ->when($from, fn($q) => $q->where('tasks.created_at', '>=', $from));

                $totalCreated = (clone $createdBase)->count();
                $inProg       = (clone $createdBase)->where('status', 'in_progress')->count();
                $inReview     = (clone $createdBase)->whereIn('status', ['submitted', 'revision_requested'])->count();
                $overdue      = (clone $createdBase)->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses)->count();

                // Count distinct tasks this user has approved or delivered
                $approved = TaskLog::where('user_id', $user->id)
                    ->whereIn('action', ['status_updated_approved', 'status_updated_delivered', 'status_updated_completed'])
                    ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
                    ->distinct('task_id')
                    ->count('task_id');

                $projectsCreated = Project::where('created_by', $user->id)->count();

                return [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'role'             => ucfirst($user->role),
                    'member_type'      => 'admin',
                    'total'            => $totalCreated,
                    'completed'        => $approved,
                    'in_progress'      => $inProg,
                    'in_review'        => $inReview,
                    'overdue'          => $overdue,
                    'rate'             => $totalCreated > 0 ? round($approved / $totalCreated * 100) : ($approved > 0 ? 100 : 0),
                    'projects_created' => $projectsCreated,
                ];
            });

        $teamMembers = $adminManagerMembers->merge($userMembers)->values();

        // ── Monthly Trend (last 6 months, always full window) ─────────────────
        $monthlyCreated   = [];
        $monthlyCompleted = [];
        $monthLabels      = [];
        for ($i = 5; $i >= 0; $i--) {
            $month        = now()->subMonths($i);
            $monthLabels[]      = $month->format('M Y');
            $monthlyCreated[]   = Task::whereYear('created_at', $month->year)
                                      ->whereMonth('created_at', $month->month)
                                      ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                                      ->count();
            $monthlyCompleted[] = Task::whereYear('updated_at', $month->year)
                                      ->whereMonth('updated_at', $month->month)
                                      ->whereIn('status', $doneStatuses)
                                      ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                                      ->count();
        }

        // ── Overdue Task List ─────────────────────────────────────────────────
        $overdueList = Task::with(['project', 'assignee'])
            ->where('deadline', '<', now())
            ->whereIn('status', $nonDoneStatuses)
            ->when($from, fn($q) => $q->where('tasks.created_at', '>=', $from))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderBy('deadline')
            ->take(50)
            ->get()
            ->map(fn($t) => [
                'title'       => $t->title,
                'project'     => $t->project->name ?? '—',
                'assignee'    => $t->assignee->name ?? 'Unassigned',
                'deadline'    => $t->deadline->format('M d, Y'),
                'days_late'   => abs(now()->diffInDays($t->deadline)),
                'priority'    => $t->priority ?? 'medium',
                'status'      => $t->status,
            ]);

        // ── Reassigned Task List ──────────────────────────────────────────────
        $reassignedList = TaskLog::with(['task.project', 'user'])
            ->whereIn('action', ['task_reassigned', 'task_transferred'])
            ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
            ->when($projectId, fn($q) => $q->whereHas('task', fn($tq) => $tq->where('project_id', $projectId)))
            ->orderByDesc('task_logs.created_at')
            ->take(100)
            ->get()
            ->map(fn($log) => [
                'task'          => $log->task?->title ?? '—',
                'task_id'       => $log->task_id,
                'project'       => $log->task?->project?->name ?? '—',
                'from_user'     => $log->metadata['from_user_name'] ?? '—',
                'to_user'       => $log->metadata['to_user_name']   ?? '—',
                'by'            => $log->metadata['reassigned_by'] ?? $log->user?->name ?? '—',
                'reason'        => $log->metadata['reason'] ?? null,
                'date'          => $log->created_at->format('M d, Y'),
                'time'          => $log->created_at->format('H:i'),
            ]);

        // ── Reopened Task List ────────────────────────────────────────────────
        $reopenedList = TaskLog::with(['task.project', 'user'])
            ->where('action', 'status_updated_reopened')
            ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
            ->when($projectId, fn($q) => $q->whereHas('task', fn($tq) => $tq->where('project_id', $projectId)))
            ->orderByDesc('task_logs.created_at')
            ->take(100)
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

        // ── Project list for filter dropdown ─────────────────────────────────
        $allProjects = Project::orderBy('name')->get(['id', 'name']);

        return view('admin.reports.index', compact(
            'range', 'projectId',
            'totalTasks', 'completedTasks', 'overdueTasks', 'completionRate',
            'onTimeRate', 'activeProjects', 'pendingReview', 'teamMemberCount',
            'statusBreakdown', 'priorityBreakdown',
            'projects', 'teamMembers',
            'monthLabels', 'monthlyCreated', 'monthlyCompleted',
            'overdueList', 'reassignedList', 'reopenedList', 'allProjects', 'from'
        ));
    }

    public function exportUsers(Request $request)
    {
        if (!auth()->user()->hasPermission('view_reports')) {
            abort(403);
        }

        $range     = $request->input('range', '30');
        $userIds   = $request->input('user_ids', []);

        $from = match ($range) {
            '7'   => now()->subDays(7)->startOfDay(),
            '30'  => now()->subDays(30)->startOfDay(),
            '90'  => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => null,
        };

        $doneStatuses    = ['approved', 'delivered', 'archived'];
        $nonDoneStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        $usersQuery = User::where('status', 'active')->orderBy('name');
        if (!empty($userIds)) {
            $usersQuery->whereIn('id', $userIds);
        }
        $users = $usersQuery->get();

        $rows = [];
        foreach ($users as $user) {
            $isAdminOrManager = in_array($user->role, ['admin', 'manager']);

            if ($isAdminOrManager) {
                // Admin/Manager: metrics by tasks they created and tasks they approved
                $tasks = Task::where('created_by', $user->id)
                    ->with('project')
                    ->when($from, fn($q) => $q->where('created_at', '>=', $from))
                    ->get();

                $now           = now();
                $totalCreated  = $tasks->count();
                $inProgCount   = $tasks->where('status', 'in_progress')->count();
                $inReviewCount = $tasks->whereIn('status', ['submitted', 'revision_requested'])->count();
                $overdueCount  = $tasks->filter(
                    fn($t) => $t->deadline && $t->deadline->lt($now) && in_array($t->status, $nonDoneStatuses)
                )->count();
                $approvedCount = TaskLog::where('user_id', $user->id)
                    ->whereIn('action', ['status_updated_approved', 'status_updated_delivered', 'status_updated_completed'])
                    ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
                    ->distinct('task_id')->count('task_id');
                $projectsCreated = Project::where('created_by', $user->id)->count();
                $rateStr = ($totalCreated > 0 ? round($approvedCount / $totalCreated * 100) : ($approvedCount > 0 ? 100 : 0)) . '%';

                if ($tasks->isEmpty()) {
                    $rows[] = [
                        $user->name, ucfirst($user->role), 'Admin/Manager',
                        0, $approvedCount, 0, 0, 0, $rateStr, $projectsCreated,
                        '—', '—', '—', '—', '—', '—',
                    ];
                    continue;
                }

                foreach ($tasks as $task) {
                    $rows[] = [
                        $user->name, ucfirst($user->role), 'Admin/Manager',
                        $totalCreated, $approvedCount, $inProgCount, $inReviewCount, $overdueCount, $rateStr, $projectsCreated,
                        $task->title,
                        $task->project->name ?? '—',
                        ucfirst($task->status ?? '—'),
                        ucfirst($task->priority ?? '—'),
                        $task->deadline ? $task->deadline->format('Y-m-d') : '—',
                        $task->created_at->format('Y-m-d'),
                    ];
                }
            } else {
                // Regular user: metrics by assigned tasks
                $tasks = Task::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                          ->from('task_assignees')
                          ->whereColumn('task_assignees.task_id', 'tasks.id')
                          ->where('task_assignees.user_id', $user->id));
                })->with('project')
                  ->when($from, fn($q) => $q->where('created_at', '>=', $from))
                  ->get();

                if ($tasks->isEmpty()) {
                    $rows[] = [
                        $user->name, ucfirst($user->role), 'User',
                        0, 0, 0, 0, 0, '0%', '—',
                        '—', '—', '—', '—', '—', '—',
                    ];
                    continue;
                }

                $totalCount    = $tasks->count();
                $doneCount     = $tasks->whereIn('status', $doneStatuses)->count();
                $inProgCount   = $tasks->where('status', 'in_progress')->count();
                $inReviewCount = $tasks->whereIn('status', ['submitted', 'revision_requested'])->count();
                $now           = now();
                $overdueCount  = $tasks->filter(
                    fn($t) => $t->deadline && $t->deadline->lt($now) && in_array($t->status, $nonDoneStatuses)
                )->count();
                $rateStr = ($totalCount > 0 ? round($doneCount / $totalCount * 100) : 0) . '%';

                foreach ($tasks as $task) {
                    $rows[] = [
                        $user->name, ucfirst($user->role), 'User',
                        $totalCount, $doneCount, $inProgCount, $inReviewCount, $overdueCount, $rateStr, '—',
                        $task->title,
                        $task->project->name ?? '—',
                        ucfirst($task->status ?? '—'),
                        ucfirst($task->priority ?? '—'),
                        $task->deadline ? $task->deadline->format('Y-m-d') : '—',
                        $task->created_at->format('Y-m-d'),
                    ];
                }
            }
        }

        $periodLabel = match($range) {
            '7'   => 'Last 7 Days',
            '30'  => 'Last 30 Days',
            '90'  => 'Last 90 Days',
            '365' => 'Last Year',
            default => 'All Time',
        };

        $filename = 'user-performance-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows, $periodLabel, $range) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ["User Performance Report — {$periodLabel}"]);
            fputcsv($handle, ['Generated', now()->format('Y-m-d H:i')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'User', 'Role', 'Type',
                'Created/Total Tasks', 'Done/Approved', 'In Progress', 'In Review', 'Overdue', 'Rate', 'Projects Created',
                'Task Title', 'Project', 'Status', 'Priority', 'Deadline', 'Task Created',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
