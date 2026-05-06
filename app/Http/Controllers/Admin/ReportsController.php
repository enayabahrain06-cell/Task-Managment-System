<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskTimerSegment;
use App\Models\TaskTransfer;
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

        $range      = $request->input('range', '30');
        $projectId  = $request->input('project_id');
        $customerId = $request->input('customer_id');
        $userId     = $request->input('user_id') ?: null;

        $from = match ($range) {
            '7'   => now()->subDays(7)->startOfDay(),
            '30'  => now()->subDays(30)->startOfDay(),
            '90'  => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => null,  // 'all'
        };

        $doneStatuses     = ['approved', 'delivered'];
        $nonDoneStatuses  = ['draft', 'assigned', 'viewed', 'in_progress', 'paused', 'submitted', 'revision_requested'];

        // ── Base scoped query helper ───────────────────────────────────────────
        $scoped = function () use ($from, $projectId, $customerId, $userId) {
            return Task::when($from, fn($q) => $q->where('tasks.created_at', '>=', $from))
                       ->when($projectId, fn($q) => $q->where('tasks.project_id', $projectId))
                       ->when($customerId, fn($q) => $q->where('tasks.customer_id', $customerId))
                       ->when($userId, fn($q) => $q->where(function ($uq) use ($userId) {
                           $uq->where('tasks.assigned_to', $userId)
                              ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                                  ->from('task_assignees')
                                  ->whereColumn('task_assignees.task_id', 'tasks.id')
                                  ->where('task_assignees.user_id', $userId));
                       }));
        };

        // ── Summary KPIs ───────────────────────────────────────────────────────
        $totalTasks     = $scoped()->count();
        $completedTasks = $scoped()->whereIn('status', $doneStatuses)->count();
        // Overdue is a current state — do not filter by created_at, only by project/customer
        $overdueTasks   = Task::where('deadline', '<', now())
                              ->whereIn('status', $nonDoneStatuses)
                              ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                              ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                              ->when($userId, fn($q) => $q->where(function ($uq) use ($userId) {
                                  $uq->where('assigned_to', $userId)
                                     ->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
                              }))
                              ->count();
        $completionRate = $totalTasks > 0 ? round($completedTasks / $totalTasks * 100) : 0;

        $onTimeCount = $scoped()
            ->whereIn('status', $doneStatuses)
            ->whereHas('logs', function ($q) {
                $q->whereIn('action', ['status_updated_approved', 'status_updated_delivered', 'status_updated_completed'])
                  ->whereColumn('task_logs.created_at', '<=', 'tasks.deadline');
            })->count();
        $onTimeRate = $completedTasks > 0 ? round($onTimeCount / $completedTasks * 100) : 0;

        $activeProjects = Project::where('status', 'active')->where('is_quick', false)
            ->when($projectId, fn($q) => $q->where('id', $projectId))
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($userId, fn($q) => $q->whereHas('tasks', fn($tq) => $tq->where(function ($uq) use ($userId) {
                $uq->where('assigned_to', $userId)
                   ->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
            })))
            ->count();

        // Pending review is a current queue — do not filter by created_at, only by project/customer
        $pendingReview = Task::where('status', 'submitted')
                             ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                             ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                             ->when($userId, fn($q) => $q->where(function ($uq) use ($userId) {
                                 $uq->where('assigned_to', $userId)
                                    ->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
                             }))
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
            ->where('is_quick', false)
            ->when($projectId, fn($q) => $q->where('id', $projectId))
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($userId, fn($q) => $q->whereHas('tasks', fn($tq) => $tq->where(function ($uq) use ($userId) {
                $uq->where('assigned_to', $userId)
                   ->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
            })))
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
            ->when($userId, fn($q) => $q->where('id', $userId))
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
                    'tasks_reopened'   => 0,
                    'tasks_reassigned' => 0,
                ];
            });

        // Admin/Manager: metrics based on tasks they created + tasks they approved
        $adminManagerMembers = User::whereIn('role', ['admin', 'manager'])
            ->where('status', 'active')
            ->when($userId, fn($q) => $q->where('id', $userId))
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

                $tasksReopened = TaskLog::where('user_id', $user->id)
                    ->where('action', 'status_updated_reopened')
                    ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
                    ->count();

                $tasksReassigned = TaskTransfer::where('transferred_by', $user->id)
                    ->when($from, fn($q) => $q->where('transferred_at', '>=', $from))
                    ->count();

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
                    'tasks_reopened'   => $tasksReopened,
                    'tasks_reassigned' => $tasksReassigned,
                ];
            });

        $teamMembers = $adminManagerMembers->toBase()->merge($userMembers->toBase())->values();

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
                                      ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                                      ->when($userId, fn($q) => $q->where(function ($uq) use ($userId) {
                                          $uq->where('assigned_to', $userId)->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
                                      }))
                                      ->count();
            $monthlyCompleted[] = Task::whereYear('updated_at', $month->year)
                                      ->whereMonth('updated_at', $month->month)
                                      ->whereIn('status', $doneStatuses)
                                      ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                                      ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                                      ->when($userId, fn($q) => $q->where(function ($uq) use ($userId) {
                                          $uq->where('assigned_to', $userId)->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
                                      }))
                                      ->count();
        }

        // ── Monthly Balance (last 12 months) for diverging bar chart ──────────
        $balanceLabels  = [];
        $balanceCreated = [];
        $balanceDone    = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $balanceLabels[]  = $month->format('M');
            $balanceCreated[] = Task::whereYear('created_at', $month->year)
                                    ->whereMonth('created_at', $month->month)
                                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                                    ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                                    ->when($userId, fn($q) => $q->where(function ($uq) use ($userId) {
                                        $uq->where('assigned_to', $userId)->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
                                    }))
                                    ->count();
            $balanceDone[]    = Task::whereYear('updated_at', $month->year)
                                    ->whereMonth('updated_at', $month->month)
                                    ->whereIn('status', $doneStatuses)
                                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                                    ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                                    ->when($userId, fn($q) => $q->where(function ($uq) use ($userId) {
                                        $uq->where('assigned_to', $userId)->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
                                    }))
                                    ->count();
        }

        // ── Overdue Task List ─────────────────────────────────────────────────
        $overdueList = Task::with(['project', 'assignee'])
            ->where('deadline', '<', now())
            ->whereIn('status', $nonDoneStatuses)
            ->when($from, fn($q) => $q->where('tasks.created_at', '>=', $from))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($userId, fn($q) => $q->where(function ($uq) use ($userId) {
                $uq->where('assigned_to', $userId)->orWhereExists(fn($sub) => $sub->selectRaw('1')->from('task_assignees')->whereColumn('task_assignees.task_id','tasks.id')->where('task_assignees.user_id',$userId));
            }))
            ->orderBy('deadline')
            ->take(50)
            ->get()
            ->map(fn($t) => [
                'title'       => $t->title,
                'project'     => $t->project->name ?? '—',
                'assignee'    => $t->assignee->name ?? 'Unassigned',
                'deadline'    => $t->deadline->format('M d, Y'),
                'days_late'   => (int) abs(now()->diffInDays($t->deadline)),
                'priority'    => $t->priority ?? 'medium',
                'status'      => $t->status,
            ]);

        // ── Reassigned Task List ──────────────────────────────────────────────
        $reassignedList = TaskTransfer::with(['task.project', 'fromUser', 'toUser', 'transferredBy'])
            ->when($from, fn($q) => $q->where('transferred_at', '>=', $from))
            ->when($projectId, fn($q) => $q->whereHas('task', fn($tq) => $tq->where('project_id', $projectId)))
            ->when($customerId, fn($q) => $q->whereHas('task', fn($tq) => $tq->where('customer_id', $customerId)))
            ->when($userId, fn($q) => $q->where(fn($uq) => $uq->where('from_user_id', $userId)->orWhere('to_user_id', $userId)))
            ->orderByDesc('transferred_at')
            ->take(100)
            ->get()
            ->map(fn($t) => [
                'task'      => $t->task?->title ?? '—',
                'task_id'   => $t->task_id,
                'project'   => $t->task?->project?->name ?? '—',
                'from_user' => $t->fromUser?->name ?? '—',
                'to_user'   => $t->toUser?->name ?? '—',
                'by'        => $t->transferredBy?->name ?? '—',
                'reason'    => $t->reason,
                'date'      => $t->transferred_at->format('M d, Y'),
                'time'      => $t->transferred_at->format('H:i'),
            ]);

        // ── Reopened Task List ────────────────────────────────────────────────
        $reopenedList = TaskLog::with(['task.project', 'user'])
            ->where('action', 'status_updated_reopened')
            ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
            ->when($projectId, fn($q) => $q->whereHas('task', fn($tq) => $tq->where('project_id', $projectId)))
            ->when($customerId, fn($q) => $q->whereHas('task', fn($tq) => $tq->where('customer_id', $customerId)))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
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

        // ── Ad Budget Monitor ─────────────────────────────────────────────────
        $adBudgetTasks = Task::with(['project.customer', 'customer', 'socialAssignee'])
            ->where('social_required', true)
            ->when($from, fn($q) => $q->where('tasks.created_at', '>=', $from))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($userId, fn($q) => $q->where('social_assigned_to', $userId))
            ->orderByDesc('tasks.created_at')
            ->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'project'     => $t->project->name ?? '—',
                'project_id'  => $t->project_id,
                'customer'    => $t->customer->name ?? $t->project?->customer?->name ?? '—',
                'social_user' => $t->socialAssignee?->name ?? '—',
                'budget'      => $t->social_budget,
                'caption'     => $t->social_caption,
                'posted'      => (bool) $t->social_posted_at,
                'posted_at'   => $t->social_posted_at ? $t->social_posted_at->format('M d, Y') : null,
                'status'      => $t->status,
            ]);

        // ── Project list for filter dropdown ─────────────────────────────────
        $allProjects = Project::orderBy('name')->get(['id', 'name']);

        // ── Customer list for filter dropdown ────────────────────────────────
        $allCustomers = Customer::orderBy('name')->get(['id', 'name', 'company']);

        // ── User list for filter dropdown ─────────────────────────────────────
        $allUsers     = User::where('status', 'active')->orderBy('name')->get(['id', 'name', 'role']);
        $selectedUser = $userId ? $allUsers->firstWhere('id', $userId) : null;

        // ── Customer Performance ──────────────────────────────────────────────
        $customerStats = Customer::withCount([
                'projects',
                'tasks',
                'tasks as completed_tasks_count' => fn($q) => $q->whereIn('status', $doneStatuses),
                'tasks as overdue_tasks_count'   => fn($q) => $q->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses),
                'tasks as active_tasks_count'    => fn($q) => $q->whereIn('status', ['assigned', 'viewed', 'in_progress']),
            ])
            ->when($from, fn($q) => $q->whereHas('tasks', fn($tq) => $tq->where('tasks.created_at', '>=', $from)))
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'company'    => $c->company,
                'projects'   => $c->projects_count,
                'total'      => $c->tasks_count,
                'completed'  => $c->completed_tasks_count,
                'active'     => $c->active_tasks_count,
                'overdue'    => $c->overdue_tasks_count,
                'rate'       => $c->tasks_count > 0 ? round($c->completed_tasks_count / $c->tasks_count * 100) : 0,
            ]);

        // ── Billing / Time Tracking ───────────────────────────────────────────
        $phaseLabels = ['work' => 'Work', 'revision' => 'Revision', 'review' => 'Admin Review', 'social' => 'Social Media'];

        // Per-user tracked seconds with hourly rate, broken out by phase
        $billingUsers = DB::table('task_timer_segments as tts')
            ->join('users', 'users.id', '=', 'tts.user_id')
            ->whereNotNull('tts.ended_at')
            ->when($from, fn($q) => $q->where('tts.started_at', '>=', $from))
            ->when($userId, fn($q) => $q->where('tts.user_id', $userId))
            ->when($projectId, fn($q) => $q->join('tasks as bt', 'bt.id', '=', 'tts.task_id')->where('bt.project_id', $projectId))
            ->when($customerId, fn($q) => $q->join('tasks as bct', 'bct.id', '=', 'tts.task_id')->where(function ($sq) use ($customerId) {
                $sq->where('bct.customer_id', $customerId)
                   ->orWhereExists(fn($s2) => $s2->selectRaw('1')->from('projects')->whereColumn('projects.id','bct.project_id')->where('projects.customer_id', $customerId));
            }))
            ->selectRaw('tts.user_id, users.name as user_name, users.role as user_role, users.hourly_rate, tts.phase, SUM(tts.duration_seconds) as total_seconds')
            ->groupBy('tts.user_id', 'users.name', 'users.role', 'users.hourly_rate', 'tts.phase')
            ->orderBy('users.name')
            ->get()
            ->groupBy('user_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $totalSeconds = $rows->sum('total_seconds');
                $hours = round($totalSeconds / 3600, 2);
                $rate  = (float) ($first->hourly_rate ?? 0);
                return [
                    'user_id'       => $first->user_id,
                    'name'          => $first->user_name,
                    'role'          => $first->user_role,
                    'hourly_rate'   => $rate,
                    'total_seconds' => $totalSeconds,
                    'hours'         => $hours,
                    'estimated_pay' => $rate > 0 ? round($hours * $rate, 2) : null,
                    'phases'        => $rows->pluck('total_seconds', 'phase'),
                ];
            })
            ->values();

        // Per-customer billing summary
        $billingCustomers = DB::table('task_timer_segments as tts')
            ->join('tasks', 'tasks.id', '=', 'tts.task_id')
            ->join('users', 'users.id', '=', 'tts.user_id')
            ->leftJoin('customers as tc', 'tc.id', '=', 'tasks.customer_id')
            ->leftJoin('projects as tp', 'tp.id', '=', 'tasks.project_id')
            ->leftJoin('customers as pc', 'pc.id', '=', 'tp.customer_id')
            ->whereNotNull('tts.ended_at')
            ->when($from, fn($q) => $q->where('tts.started_at', '>=', $from))
            ->when($userId, fn($q) => $q->where('tts.user_id', $userId))
            ->when($projectId, fn($q) => $q->where('tasks.project_id', $projectId))
            ->when($customerId, fn($q) => $q->where(function ($sq) use ($customerId) {
                $sq->where('tc.id', $customerId)->orWhere('pc.id', $customerId);
            }))
            ->selectRaw("
                COALESCE(tc.id, pc.id) as customer_id,
                COALESCE(tc.name, pc.name, 'No Customer') as customer_name,
                tts.user_id,
                users.name as user_name,
                users.role as user_role,
                users.hourly_rate,
                tts.phase,
                SUM(tts.duration_seconds) as total_seconds
            ")
            ->groupByRaw("COALESCE(tc.id, pc.id), COALESCE(tc.name, pc.name, 'No Customer'), tts.user_id, users.name, users.role, users.hourly_rate, tts.phase")
            ->get()
            ->groupBy('customer_name')
            ->map(function ($rows, $customerName) {
                $totalSeconds = $rows->sum('total_seconds');
                $hours = round($totalSeconds / 3600, 2);
                $totalCost = $rows->sum(function ($row) {
                    $rate  = (float) ($row->hourly_rate ?? 0);
                    $hrs   = $row->total_seconds / 3600;
                    return $rate > 0 ? $hrs * $rate : 0;
                });
                return [
                    'customer_name'  => $customerName,
                    'total_seconds'  => $totalSeconds,
                    'hours'          => $hours,
                    'estimated_cost' => $totalCost > 0 ? round($totalCost, 2) : null,
                    'phases'         => $rows->groupBy('phase')->map(fn($r) => $r->sum('total_seconds')),
                    'by_user'        => $rows->groupBy('user_name')->map(fn($r) => [
                        'seconds' => $r->sum('total_seconds'),
                        'phases'  => $r->pluck('total_seconds', 'phase'),
                        'role'    => $r->first()->user_role,
                        'rate'    => (float) ($r->first()->hourly_rate ?? 0),
                    ]),
                ];
            })
            ->values();

        return view('admin.reports.index', compact(
            'range', 'projectId', 'customerId', 'userId', 'selectedUser',
            'totalTasks', 'completedTasks', 'overdueTasks', 'completionRate',
            'onTimeRate', 'activeProjects', 'pendingReview', 'teamMemberCount',
            'statusBreakdown', 'priorityBreakdown',
            'projects', 'teamMembers',
            'monthLabels', 'monthlyCreated', 'monthlyCompleted',
            'balanceLabels', 'balanceCreated', 'balanceDone',
            'overdueList', 'reassignedList', 'reopenedList',
            'allProjects', 'allCustomers', 'allUsers', 'customerStats',
            'billingUsers', 'billingCustomers', 'phaseLabels', 'from',
            'adBudgetTasks'
        ));
    }

    public function userDetail(Request $request)
    {
        if (!auth()->user()->hasPermission('view_reports')) abort(403);

        $userIds = array_filter(array_map('intval', (array) $request->input('user_ids', [])));
        if (empty($userIds)) return response()->json([]);

        $range = $request->input('range', '30');
        $from  = match ($range) {
            '7'   => now()->subDays(7)->startOfDay(),
            '30'  => now()->subDays(30)->startOfDay(),
            '90'  => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => null,
        };

        $doneStatuses    = ['approved', 'delivered', 'archived'];
        $nonDoneStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];
        $now             = now();

        $result = [];

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            $isAdm = in_array($user->role, ['admin', 'manager']);

            $tasks = $isAdm
                ? Task::where('created_by', $user->id)
                    ->with(['project.customer', 'customer'])
                    ->when($from, fn($q) => $q->where('created_at', '>=', $from))
                    ->orderBy('created_at')->get()
                : Task::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                          ->from('task_assignees')
                          ->whereColumn('task_assignees.task_id', 'tasks.id')
                          ->where('task_assignees.user_id', $user->id));
                })->with(['project.customer', 'customer'])
                  ->when($from, fn($q) => $q->where('created_at', '>=', $from))
                  ->orderBy('created_at')->get();

            $timeByTask   = $tasks->isNotEmpty() ? self::timeSpentByTask($tasks->pluck('id')) : collect();
            $totalMinutes = $timeByTask->sum();

            $totals = [
                'total'     => $tasks->count(),
                'pending'   => $tasks->whereIn('status', ['draft','assigned','viewed'])->count(),
                'in_prog'   => $tasks->where('status', 'in_progress')->count(),
                'in_review' => $tasks->whereIn('status', ['submitted','revision_requested'])->count(),
                'approved'  => $tasks->where('status', 'approved')->count(),
                'delivered' => $tasks->whereIn('status', ['delivered','archived'])->count(),
                'overdue'   => $tasks->filter(fn($t) => $t->deadline && $t->deadline->lt($now) && in_array($t->status, $nonDoneStatuses))->count(),
                'done'      => $tasks->whereIn('status', $doneStatuses)->count(),
                'p_low'     => $tasks->where('priority', 'low')->count(),
                'p_medium'  => $tasks->where('priority', 'medium')->count(),
                'p_high'    => $tasks->where('priority', 'high')->count(),
            ];

            // Projects (skip Quick Tasks)
            $projects = [];
            foreach ($tasks->filter(fn($t) => !($t->project?->is_quick ?? false))->groupBy('project_id') as $projTasks) {
                $proj     = $projTasks->first()->project;
                $customer = $proj?->customer?->name ?? $projTasks->first()->customer?->name ?? '—';
                $first    = $projTasks->min('created_at');
                $pDone    = $projTasks->whereIn('status', $doneStatuses)->count();
                $pTotal   = $projTasks->count();
                $projects[] = [
                    'name'        => $proj?->name ?? '—',
                    'customer'    => $customer,
                    'proj_status' => $proj ? $proj->status : '—',
                    'first_date'  => $first ? Carbon::parse($first)->format('M d, Y') : '—',
                    'days_active' => $first ? (int) Carbon::parse($first)->diffInDays($now) : 0,
                    'total'       => $pTotal,
                    'done'        => $pDone,
                    'in_progress' => $projTasks->where('status', 'in_progress')->count(),
                    'overdue'     => $projTasks->filter(fn($t) => $t->deadline && $t->deadline->lt($now) && in_array($t->status, $nonDoneStatuses))->count(),
                    'rate'        => $pTotal > 0 ? round($pDone / $pTotal * 100) : 0,
                    'time_spent'  => self::fmtMinutes($projTasks->sum(fn($t) => $timeByTask->get($t->id, 0))),
                    'is_active'   => $projTasks->whereNotIn('status', $doneStatuses)->isNotEmpty(),
                ];
            }
            usort($projects, fn($a, $b) => strcmp($a['first_date'], $b['first_date']));

            // Active tasks
            $active = $tasks->whereNotIn('status', $doneStatuses)->sortBy('deadline')->values()
                ->map(fn($t) => [
                    'title'     => $t->title,
                    'project'   => $t->project?->name ?? '—',
                    'customer'  => $t->customer?->name ?? $t->project?->customer?->name ?? '—',
                    'status'    => $t->status,
                    'priority'  => $t->priority ?? 'medium',
                    'deadline'  => $t->deadline ? $t->deadline->format('M d, Y') : null,
                    'days_left' => $t->deadline ? (int) $now->diffInDays($t->deadline, false) : null,
                ])->values()->all();

            // Monthly trend (last 6 months from tasks collection)
            $monthlyLabels    = [];
            $monthlyCreated   = [];
            $monthlyCompleted = [];
            for ($i = 5; $i >= 0; $i--) {
                $month              = $now->copy()->subMonths($i);
                $monthlyLabels[]    = $month->format('M Y');
                $monthlyCreated[]   = $tasks->filter(fn($t) => $t->created_at->year === $month->year && $t->created_at->month === $month->month)->count();
                $monthlyCompleted[] = $tasks->filter(fn($t) => in_array($t->status, $doneStatuses) && $t->updated_at->year === $month->year && $t->updated_at->month === $month->month)->count();
            }
            $bestMonthCount = !empty($monthlyCompleted) ? max($monthlyCompleted) : 0;
            $bestMonthIdx   = $bestMonthCount > 0 ? array_search($bestMonthCount, $monthlyCompleted) : null;
            $bestMonthLabel = $bestMonthIdx !== null ? explode(' ', $monthlyLabels[$bestMonthIdx])[0] : '—';

            // On-time rate (done tasks where deadline was not missed at completion)
            $onTimeDone = $tasks->filter(fn($t) =>
                in_array($t->status, $doneStatuses) &&
                $t->deadline &&
                $t->updated_at->lte($t->deadline)
            )->count();
            $onTimeRate = $totals['done'] > 0 ? round($onTimeDone / $totals['done'] * 100) : 0;

            $result[] = [
                'id'                 => $user->id,
                'name'               => $user->name,
                'role'               => ucfirst($user->role),
                'job_title'          => $user->job_title ?: null,
                'email'              => $user->email,
                'member_since'       => $user->created_at->format('M d, Y'),
                'totals'             => $totals,
                'time_spent'         => self::fmtMinutes($totalMinutes),
                'rate'               => $totals['total'] > 0 ? round($totals['done'] / $totals['total'] * 100) : 0,
                'on_time_rate'       => $onTimeRate,
                'monthly_labels'     => $monthlyLabels,
                'monthly_created'    => $monthlyCreated,
                'monthly_completed'  => $monthlyCompleted,
                'best_month'         => $bestMonthLabel,
                'best_month_count'   => $bestMonthCount,
                'projects'           => $projects,
                'active_tasks'       => $active,
            ];
        }

        return response()->json($result);
    }

    private static function fmtMinutes(int $minutes): string
    {
        if ($minutes <= 0) return '—';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    private static function timeSpentByTask(\Illuminate\Support\Collection $taskIds, string $currentStatus = ''): \Illuminate\Support\Collection
    {
        if ($taskIds->isEmpty()) {
            return collect();
        }

        // Primary: use timer segments (accurate, user-controlled)
        $segmentTotals = DB::table('task_timer_segments')
            ->whereIn('task_id', $taskIds)
            ->whereNotNull('ended_at')
            ->selectRaw('task_id, SUM(duration_seconds) as total_seconds')
            ->groupBy('task_id')
            ->get()
            ->keyBy('task_id');

        // For tasks with no segments: fall back to log-based estimation
        $tasksWithoutSegments = $taskIds->filter(fn($id) => !$segmentTotals->has($id));

        $logMinutes = collect();
        if ($tasksWithoutSegments->isNotEmpty()) {
            $endActions = ['auto_paused', 'status_updated_paused', 'status_updated_submitted', 'status_updated_approved', 'status_updated_delivered', 'status_updated_completed'];
            $logsByTask = TaskLog::whereIn('task_id', $tasksWithoutSegments)
                ->whereIn('action', array_merge(['status_updated_in_progress', 'timer_started'], $endActions))
                ->orderBy('created_at')
                ->get()
                ->groupBy('task_id');

            foreach ($tasksWithoutSegments as $taskId) {
                $logs         = $logsByTask->get($taskId, collect());
                $totalMinutes = 0;
                $segStart     = null;
                foreach ($logs as $log) {
                    if (in_array($log->action, ['status_updated_in_progress', 'timer_started'])) {
                        $segStart = $log->created_at;
                    } elseif ($segStart !== null && in_array($log->action, $endActions)) {
                        $totalMinutes += (int) $segStart->diffInMinutes($log->created_at);
                        $segStart = null;
                    }
                }
                $logMinutes[$taskId] = $totalMinutes;
            }
        }

        return $taskIds->mapWithKeys(function ($taskId) use ($segmentTotals, $logMinutes) {
            if ($segmentTotals->has($taskId)) {
                return [$taskId => (int) ceil($segmentTotals->get($taskId)->total_seconds / 60)];
            }
            return [$taskId => $logMinutes[$taskId] ?? 0];
        });
    }

    /** Time in seconds grouped by [task_id][user_id][phase] — for billing reports. */
    private static function billingByTask(\Illuminate\Support\Collection $taskIds): \Illuminate\Support\Collection
    {
        if ($taskIds->isEmpty()) return collect();

        return DB::table('task_timer_segments')
            ->whereIn('task_id', $taskIds)
            ->whereNotNull('ended_at')
            ->selectRaw('task_id, user_id, phase, SUM(duration_seconds) as total_seconds')
            ->groupBy('task_id', 'user_id', 'phase')
            ->get()
            ->groupBy('task_id');
    }

    public function exportUsers(Request $request)
    {
        if (!auth()->user()->hasPermission('view_reports')) {
            abort(403);
        }

        $range   = $request->input('range', '30');
        $userIds = $request->input('user_ids', []);

        $from = match ($range) {
            '7'   => now()->subDays(7)->startOfDay(),
            '30'  => now()->subDays(30)->startOfDay(),
            '90'  => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => null,
        };

        $doneStatuses    = ['approved', 'delivered', 'archived'];
        $nonDoneStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        $periodLabel = match($range) {
            '7'   => 'Last 7 Days',
            '30'  => 'Last 30 Days',
            '90'  => 'Last 90 Days',
            '365' => 'Last Year',
            default => 'All Time',
        };

        $usersQuery = User::where('status', 'active')->orderBy('name');
        if (!empty($userIds)) {
            $usersQuery->whereIn('id', $userIds);
        }
        $users = $usersQuery->get();

        $filename = 'user-performance-' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($users, $from, $doneStatuses, $nonDoneStatuses, $periodLabel) {
            $handle = fopen('php://output', 'w');
            $now    = now();

            fputcsv($handle, ["USER PERFORMANCE REPORT  |  Period: {$periodLabel}"]);
            fputcsv($handle, ['Generated:', $now->format('Y-m-d H:i'), '', 'Total Employees:', $users->count()]);
            fputcsv($handle, []);

            // ── OVERVIEW TABLE (matches report page layout) ───────────────────
            fputcsv($handle, ['-- PERFORMANCE OVERVIEW --']);
            fputcsv($handle, [
                'Member', 'Role', 'Type',
                'Created', 'Done', 'Active', 'Overdue',
                'Projects', 'Reopened', 'Reassigned', 'Rate',
                'Active Time', 'Active Projects', 'Customers Served',
            ]);

            // Pre-collect overview rows so we can write them before drilling into detail
            $overviewRows = [];

            foreach ($users as $user) {
                $isAdminOrManager = in_array($user->role, ['admin', 'manager']);

                if ($isAdminOrManager) {
                    $tasks = Task::where('created_by', $user->id)
                        ->with(['project.customer', 'customer'])
                        ->when($from, fn($q) => $q->where('created_at', '>=', $from))
                        ->orderBy('created_at')->get();
                } else {
                    $tasks = Task::where(function ($q) use ($user) {
                        $q->where('assigned_to', $user->id)
                          ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                              ->from('task_assignees')
                              ->whereColumn('task_assignees.task_id', 'tasks.id')
                              ->where('task_assignees.user_id', $user->id));
                    })->with(['project.customer', 'customer'])
                      ->when($from, fn($q) => $q->where('created_at', '>=', $from))
                      ->orderBy('created_at')->get();
                }

                $timeByTask   = $tasks->isNotEmpty() ? self::timeSpentByTask($tasks->pluck('id')) : collect();
                $totalMinutes = $timeByTask->sum();
                $totalTasks   = $tasks->count();
                $doneTasks    = $tasks->whereIn('status', $doneStatuses)->count();
                $inProgTasks  = $tasks->where('status', 'in_progress')->count();
                $overdueTasks = $tasks->filter(
                    fn($t) => $t->deadline && $t->deadline->lt($now) && in_array($t->status, $nonDoneStatuses)
                )->count();

                $approvedByUser  = 0;
                $projectsCreated = 0;
                $tasksReopened   = 0;
                $tasksReassigned = 0;
                if ($isAdminOrManager) {
                    $approvedByUser = TaskLog::where('user_id', $user->id)
                        ->whereIn('action', ['status_updated_approved', 'status_updated_delivered', 'status_updated_completed'])
                        ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
                        ->distinct('task_id')->count('task_id');
                    $projectsCreated = Project::where('created_by', $user->id)->where('is_quick', false)->count();
                    $tasksReopened   = TaskLog::where('user_id', $user->id)
                        ->where('action', 'status_updated_reopened')
                        ->when($from, fn($q) => $q->where('task_logs.created_at', '>=', $from))
                        ->count();
                    $tasksReassigned = TaskTransfer::where('transferred_by', $user->id)
                        ->when($from, fn($q) => $q->where('transferred_at', '>=', $from))
                        ->count();
                }

                $customerNames = $tasks->map(
                    fn($t) => $t->customer?->name ?? $t->project?->customer?->name
                )->filter()->unique()->sort()->values()->implode(' | ');

                $realTasks       = $tasks->filter(fn($t) => !($t->project?->is_quick ?? false));
                $byProject       = $realTasks->groupBy('project_id');
                $activeProjCount = $byProject->filter(
                    fn($pt) => $pt->whereNotIn('status', $doneStatuses)->isNotEmpty()
                )->count();

                $done = $isAdminOrManager ? $approvedByUser : $doneTasks;
                $rate = $totalTasks > 0 ? round($done / $totalTasks * 100) . '%' : '0%';

                fputcsv($handle, [
                    $user->name,
                    ucfirst($user->role),
                    $isAdminOrManager ? 'Admin / Manager' : 'User',
                    $totalTasks,
                    $done,
                    $inProgTasks,
                    $overdueTasks,
                    $isAdminOrManager ? $projectsCreated : '—',
                    $isAdminOrManager ? $tasksReopened   : '—',
                    $isAdminOrManager ? $tasksReassigned : '—',
                    $rate,
                    self::fmtMinutes($totalMinutes),
                    $activeProjCount,
                    $customerNames ?: '—',
                ]);

                // Save for per-user detail sections below
                $overviewRows[$user->id] = compact(
                    'tasks', 'timeByTask', 'totalMinutes', 'totalTasks', 'doneTasks',
                    'inProgTasks', 'overdueTasks', 'approvedByUser', 'projectsCreated',
                    'tasksReopened', 'tasksReassigned', 'customerNames', 'byProject',
                    'activeProjCount', 'isAdminOrManager', 'rate'
                );
            }

            fputcsv($handle, []);
            fputcsv($handle, []);

            // ── PER-USER DETAIL SECTIONS ──────────────────────────────────────
            foreach ($users as $user) {
                $d = $overviewRows[$user->id];
                extract($d); // expands all saved vars into local scope

                $inRevTasks = $tasks->whereIn('status', ['submitted', 'revision_requested'])->count();

                // ── EMPLOYEE SECTION HEADER ───────────────────────────────────
                fputcsv($handle, ['================================================================================']);
                fputcsv($handle, [
                    "EMPLOYEE: {$user->name}",
                    "Role: " . ucfirst($user->role),
                    "Job Title: " . ($user->job_title ?: '—'),
                    "Email: {$user->email}",
                    "Member Since: " . $user->created_at->format('Y-m-d'),
                ]);
                fputcsv($handle, []);

                // ── PROFILE ───────────────────────────────────────────────────
                fputcsv($handle, ['-- PROFILE --']);
                fputcsv($handle, ['Name', 'Role', 'Job Title', 'Email', 'Phone', 'Member Since', 'Account Status']);
                fputcsv($handle, [
                    $user->name,
                    ucfirst($user->role),
                    $user->job_title ?: '—',
                    $user->email,
                    $user->phone ?: '—',
                    $user->created_at->format('Y-m-d'),
                    ucfirst($user->status),
                ]);
                fputcsv($handle, []);

                // ── PERFORMANCE SUMMARY (columns match report page table) ─────
                fputcsv($handle, ['-- PERFORMANCE SUMMARY --']);
                fputcsv($handle, [
                    'Created', 'Done', 'Active', 'Overdue',
                    'Projects', 'Reopened', 'Reassigned', 'Rate',
                    'In Review', 'Total Active Time', 'Active Projects', 'Customers Served',
                ]);
                fputcsv($handle, [
                    $totalTasks,
                    $isAdminOrManager ? $approvedByUser : $doneTasks,
                    $inProgTasks,
                    $overdueTasks,
                    $isAdminOrManager ? $projectsCreated : '—',
                    $isAdminOrManager ? $tasksReopened   : '—',
                    $isAdminOrManager ? $tasksReassigned : '—',
                    $rate,
                    $inRevTasks,
                    self::fmtMinutes($totalMinutes),
                    $activeProjCount,
                    $customerNames ?: '—',
                ]);
                fputcsv($handle, []);

                // ── PROJECT ENGAGEMENTS ───────────────────────────────────────
                fputcsv($handle, ['-- PROJECT ENGAGEMENTS --']);
                fputcsv($handle, [
                    'Project Name', 'Customer', 'Project Status',
                    'First Involvement', 'Days Active',
                    'My Tasks', 'Completed', 'In Progress', 'Overdue',
                    'My Completion Rate', 'Time Spent on Project',
                ]);

                if ($byProject->isEmpty()) {
                    fputcsv($handle, ['No project tasks in this period.']);
                } else {
                    foreach ($byProject->sortBy(fn($pt) => $pt->min('created_at')) as $projectId => $projTasks) {
                        $project  = $projTasks->first()->project;
                        $customer = $project?->customer?->name
                            ?? $projTasks->first()->customer?->name
                            ?? '—';

                        $firstDate   = $projTasks->min('created_at');
                        $daysActive  = $firstDate ? (int) Carbon::parse($firstDate)->diffInDays($now) : '—';
                        $projTotal   = $projTasks->count();
                        $projDone    = $projTasks->whereIn('status', $doneStatuses)->count();
                        $projInProg  = $projTasks->where('status', 'in_progress')->count();
                        $projOverdue = $projTasks->filter(
                            fn($t) => $t->deadline && $t->deadline->lt($now) && in_array($t->status, $nonDoneStatuses)
                        )->count();
                        $projRate    = $projTotal > 0 ? round($projDone / $projTotal * 100) . '%' : '0%';
                        $projMinutes = $projTasks->sum(fn($t) => $timeByTask->get($t->id, 0));

                        fputcsv($handle, [
                            $project?->name ?? '—',
                            $customer,
                            $project ? ucfirst($project->status) : '—',
                            $firstDate ? Carbon::parse($firstDate)->format('Y-m-d') : '—',
                            $daysActive . ($daysActive !== '—' ? ' days' : ''),
                            $projTotal, $projDone, $projInProg, $projOverdue,
                            $projRate,
                            self::fmtMinutes($projMinutes),
                        ]);
                    }
                }
                fputcsv($handle, []);

                // ── CURRENT WORKLOAD (active/pending tasks only) ───────────────
                $activeTasks = $tasks->whereNotIn('status', $doneStatuses)->sortBy('deadline');
                fputcsv($handle, ['-- CURRENT WORKLOAD (' . $activeTasks->count() . ' active tasks) --']);
                if ($activeTasks->isNotEmpty()) {
                    fputcsv($handle, ['Task Title', 'Project', 'Customer', 'Status', 'Priority', 'Deadline', 'Days Left', 'Time Spent']);
                    foreach ($activeTasks as $task) {
                        $customer = $task->customer?->name ?? $task->project?->customer?->name ?? '—';
                        $daysLeft = $task->deadline ? (int) $now->diffInDays($task->deadline, false) : '—';
                        $daysLeftStr = $daysLeft === '—' ? '—'
                            : ($daysLeft < 0 ? abs($daysLeft) . ' days overdue' : $daysLeft . ' days left');
                        fputcsv($handle, [
                            $task->title,
                            $task->project?->name ?? '—',
                            $customer,
                            ucfirst(str_replace('_', ' ', $task->status)),
                            ucfirst($task->priority ?? '—'),
                            $task->deadline ? $task->deadline->format('Y-m-d') : '—',
                            $daysLeftStr,
                            self::fmtMinutes($timeByTask->get($task->id, 0)),
                        ]);
                    }
                } else {
                    fputcsv($handle, ['No active tasks in this period.']);
                }
                fputcsv($handle, []);

                // ── FULL TASK HISTORY ─────────────────────────────────────────
                fputcsv($handle, ['-- FULL TASK HISTORY (' . $totalTasks . ' tasks) --']);
                if ($tasks->isNotEmpty()) {
                    fputcsv($handle, [
                        'Task Title', 'Project', 'Customer', 'Status', 'Priority',
                        'Start Date', 'Deadline', 'Days to Submit', 'Time Spent',
                    ]);
                    foreach ($tasks as $task) {
                        $customer = $task->customer?->name ?? $task->project?->customer?->name ?? '—';
                        $daysToSubmit = ($task->created_at && $task->deadline)
                            ? (int) $task->created_at->diffInDays($task->deadline)
                            : '—';
                        fputcsv($handle, [
                            $task->title,
                            $task->project?->name ?? '—',
                            $customer,
                            ucfirst(str_replace('_', ' ', $task->status)),
                            ucfirst($task->priority ?? '—'),
                            $task->created_at->format('Y-m-d'),
                            $task->deadline ? $task->deadline->format('Y-m-d') : '—',
                            $daysToSubmit,
                            self::fmtMinutes($timeByTask->get($task->id, 0)),
                        ]);
                    }
                } else {
                    fputcsv($handle, ['No tasks found for this period.']);
                }

                fputcsv($handle, []);
                fputcsv($handle, []);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
