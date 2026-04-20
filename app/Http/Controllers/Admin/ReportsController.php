<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
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
        $overdueTasks   = $scoped()->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses)->count();
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

        $pendingReview = $scoped()->where('status', 'submitted')->count();

        // ── Status Breakdown ──────────────────────────────────────────────────
        $statusGroups = [
            'pending'    => ['label' => 'Pending',        'statuses' => ['draft', 'assigned', 'viewed'],             'color' => '#6B7280', 'bg' => '#F3F4F6'],
            'in_progress'=> ['label' => 'In Progress',    'statuses' => ['in_progress'],                             'color' => '#F59E0B', 'bg' => '#FEF3C7'],
            'in_review'  => ['label' => 'In Review',      'statuses' => ['submitted', 'revision_requested'],         'color' => '#8B5CF6', 'bg' => '#EDE9FE'],
            'completed'  => ['label' => 'Completed',      'statuses' => ['approved'],                                'color' => '#10B981', 'bg' => '#D1FAE5'],
            'delivered'  => ['label' => 'Delivered',      'statuses' => ['delivered'],                               'color' => '#047857', 'bg' => '#ECFDF5'],
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
        $teamMembers = User::whereNotIn('role', ['admin'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($from, $doneStatuses, $nonDoneStatuses) {
                $q = Task::where('assigned_to', $user->id)
                          ->when($from, fn($q) => $q->where('tasks.created_at', '>=', $from));

                $total    = (clone $q)->count();
                $done     = (clone $q)->whereIn('status', $doneStatuses)->count();
                $inProg   = (clone $q)->where('status', 'in_progress')->count();
                $overdue  = (clone $q)->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses)->count();
                $inReview = (clone $q)->whereIn('status', ['submitted', 'revision_requested'])->count();

                return [
                    'name'        => $user->name,
                    'role'        => ucfirst($user->role),
                    'total'       => $total,
                    'completed'   => $done,
                    'in_progress' => $inProg,
                    'in_review'   => $inReview,
                    'overdue'     => $overdue,
                    'rate'        => $total > 0 ? round($done / $total * 100) : 0,
                ];
            })->filter(fn($m) => $m['total'] > 0)->values();

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

        // ── Project list for filter dropdown ─────────────────────────────────
        $allProjects = Project::orderBy('name')->get(['id', 'name']);

        return view('admin.reports.index', compact(
            'range', 'projectId',
            'totalTasks', 'completedTasks', 'overdueTasks', 'completionRate',
            'onTimeRate', 'activeProjects', 'pendingReview',
            'statusBreakdown', 'priorityBreakdown',
            'projects', 'teamMembers',
            'monthLabels', 'monthlyCreated', 'monthlyCompleted',
            'overdueList', 'allProjects', 'from'
        ));
    }
}
