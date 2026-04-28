<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSocialPost;
use App\Models\TaskSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function analyticsTasks(Request $request)
    {
        $filter = $request->input('filter', 'pending');

        $query = Task::with(['project:id,name', 'assignee:id,name'])
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline');

        match ($filter) {
            'pending'       => $query->whereIn('status', ['draft', 'assigned', 'viewed']),
            'in_progress'   => $query->where('status', 'in_progress'),
            'in_review'     => $query->where('status', 'submitted'),
            'done'          => $query->whereIn('status', ['approved', 'delivered', 'archived']),
            'overdue'       => $query->whereNotNull('deadline')
                                     ->where('deadline', '<', now())
                                     ->whereNotIn('status', ['approved', 'delivered', 'archived']),
            'due_this_week' => $query->whereNotNull('deadline')
                                     ->whereBetween('deadline', [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)])
                                     ->whereNotIn('status', ['approved', 'delivered', 'archived']),
            'reopened'      => $query->whereHas('logs', fn($q) => $q->where('action', 'status_updated_reopened')),
            'reassigned'    => $query->whereHas('logs', fn($q) => $q->whereIn('action', ['task_reassigned', 'task_transferred'])),
            default         => $query->whereIn('status', ['draft', 'assigned', 'viewed']),
        };

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
            'high'   => ['label' => 'High',   'color' => '#EF4444'],
            'medium' => ['label' => 'Med',    'color' => '#F59E0B'],
            'low'    => ['label' => 'Low',    'color' => '#10B981'],
        ];

        $tasks = $query->get()->map(function ($task) use ($statusMeta, $priorityMeta) {
            $sm = $statusMeta[$task->status]    ?? ['label' => ucfirst($task->status), 'color' => '#6B7280', 'bg' => '#F3F4F6'];
            $pm = $priorityMeta[$task->priority] ?? null;
            $initials = $task->assignee
                ? collect(explode(' ', $task->assignee->name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('')
                : null;
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
                'assignee'    => $task->assignee?->name,
                'initials'    => $initials,
                'url'         => route('admin.tasks.show', $task->id),
            ];
        });

        return response()->json(['tasks' => $tasks, 'count' => $tasks->count(), 'filter' => $filter]);
    }

    public function socialPosts(Request $request)
    {
        $platform = $request->input('platform');

        $query = \App\Models\TaskSocialPost::with(['task:id,title', 'user:id,name'])
            ->latest();

        if ($platform) {
            $query->where('platform', $platform);
        }

        $posts = $query->get()->map(fn($sp) => [
            'id'        => $sp->id,
            'platform'  => $sp->platform,
            'label'     => $sp->platformLabel(),
            'icon'      => $sp->platformIcon(),
            'color'     => $sp->platformColor(),
            'postUrl'   => $sp->post_url,
            'note'      => $sp->note,
            'task'      => $sp->task?->title,
            'taskId'    => $sp->task_id,
            'taskUrl'   => $sp->task_id ? route('admin.tasks.show', $sp->task_id) : null,
            'postedBy'  => $sp->user?->name,
            'postedAt'  => $sp->created_at->format('M d, Y · H:i'),
            'diffHumans'=> $sp->created_at->diffForHumans(),
        ]);

        return response()->json(['posts' => $posts, 'platform' => $platform]);
    }

    public function workloadTasks(Request $request)
    {
        $index = (int) $request->input('index', 0);

        $workloadUsers = User::withCount([
            'tasks as open_tasks' => fn($q) => $q->where('status', '!=', 'completed'),
        ])->where('role', 'user')->orderByDesc('open_tasks')->take(6)->get();

        $user = $workloadUsers->values()->get($index);

        if (!$user) {
            return response()->json(['tasks' => [], 'user' => null]);
        }

        $tasks = Task::with(['project:id,name'])
            ->where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'submitted' THEN 1 WHEN 'revision_requested' THEN 2 WHEN 'assigned' THEN 3 WHEN 'viewed' THEN 4 ELSE 5 END")
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->take(25)
            ->get()
            ->map(fn($task) => [
                'id'       => $task->id,
                'title'    => $task->title,
                'status'   => $task->status,
                'priority' => $task->priority,
                'deadline' => $task->deadline?->format('M d, Y'),
                'project'  => $task->project?->name,
                'url'      => route('admin.tasks.show', $task->id),
            ]);

        return response()->json([
            'tasks' => $tasks,
            'user'  => [
                'name'       => $user->name,
                'open_tasks' => $user->open_tasks,
                'initials'   => collect(explode(' ', $user->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join(''),
            ],
        ]);
    }

    public function chartTasks(Request $request)
    {
        $period       = $request->input('period', 'week');
        $index        = (int) $request->input('index', 0);
        $doneStatuses = ['approved', 'delivered'];
        $bucket       = null;

        switch ($period) {
            case 'today':
                if ($index >= 0 && $index < 24) {
                    $h      = now()->subHours(23 - $index)->startOfHour();
                    $bucket = ['type' => 'hour', 'start' => $h, 'end' => $h->copy()->endOfHour()];
                }
                break;
            case 'week':
                if ($index >= 0 && $index < 7) {
                    $bucket = ['type' => 'day', 'date' => now()->subDays(6 - $index)];
                }
                break;
            case 'last_week':
                $lw = now()->subWeek()->startOfWeek(Carbon::MONDAY);
                if ($index >= 0 && $index < 7) {
                    $bucket = ['type' => 'day', 'date' => $lw->copy()->addDays($index)];
                }
                break;
            case 'month':
                if ($index >= 0 && $index < 30) {
                    $bucket = ['type' => 'day', 'date' => now()->subDays(29 - $index)];
                }
                break;
            case 'last_month':
                $lmStart = now()->subMonth()->startOfMonth();
                if ($index >= 0 && $index < $lmStart->daysInMonth) {
                    $bucket = ['type' => 'day', 'date' => $lmStart->copy()->addDays($index)];
                }
                break;
            case 'year':
                if ($index >= 0 && $index < 12) {
                    $month  = now()->subMonths(11 - $index);
                    $bucket = ['type' => 'month', 'year' => $month->year, 'month' => $month->month, 'label' => $month->format('F Y')];
                }
                break;
        }

        if (!$bucket) {
            return response()->json(['tasks' => [], 'label' => '']);
        }

        $query = Task::with(['project:id,name', 'assignee:id,name,avatar']);

        if ($bucket['type'] === 'hour') {
            $s = $bucket['start']; $e = $bucket['end'];
            $query->where(fn($q) => $q
                ->whereBetween('created_at', [$s, $e])
                ->orWhere(fn($q2) => $q2->whereBetween('updated_at', [$s, $e])->whereIn('status', $doneStatuses))
            );
            $label = $s->format('H:i') . '–' . $e->format('H:i') . ', ' . $s->format('M d');
        } elseif ($bucket['type'] === 'day') {
            $day = $bucket['date'];
            $query->where(fn($q) => $q
                ->whereDate('created_at', $day)
                ->orWhere(fn($q2) => $q2->whereDate('updated_at', $day)->whereIn('status', $doneStatuses))
            );
            $label = $day->format('l, M d Y');
        } else {
            $y = $bucket['year']; $m = $bucket['month'];
            $query->where(fn($q) => $q
                ->where(fn($q2) => $q2->whereYear('created_at', $y)->whereMonth('created_at', $m))
                ->orWhere(fn($q2) => $q2->whereYear('updated_at', $y)->whereMonth('updated_at', $m)->whereIn('status', $doneStatuses))
            );
            $label = $bucket['label'];
        }

        $tasks = $query->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->take(25)
            ->get()
            ->map(fn($task) => [
                'id'       => $task->id,
                'title'    => $task->title,
                'status'   => $task->status,
                'priority' => $task->priority,
                'deadline' => $task->deadline?->format('M d, Y'),
                'project'  => $task->project?->name,
                'assignee' => $task->assignee?->name,
                'url'      => route('admin.tasks.show', $task->id),
            ]);

        return response()->json(['tasks' => $tasks, 'label' => $label]);
    }

    public function workingHours(Request $request)
    {
        $period       = $request->input('period', 'week');
        $labels       = [];
        $data         = [];
        $doneStatuses = ['approved', 'delivered'];

        switch ($period) {
            case 'today':
                for ($i = 23; $i >= 0; $i--) {
                    $h        = now()->subHours($i)->startOfHour();
                    $labels[] = $h->format('H:i');
                    $data[]   = Task::whereBetween('created_at', [$h, $h->copy()->endOfHour()])->count()
                              + Task::whereBetween('updated_at', [$h, $h->copy()->endOfHour()])->whereIn('status', $doneStatuses)->count();
                }
                break;

            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $day      = now()->subDays($i);
                    $labels[] = $day->format('D');
                    $data[]   = Task::whereDate('created_at', $day)->count()
                              + Task::whereDate('updated_at', $day)->whereIn('status', $doneStatuses)->count();
                }
                break;

            case 'last_week':
                $start = now()->subWeek()->startOfWeek(Carbon::MONDAY);
                for ($i = 0; $i < 7; $i++) {
                    $day      = $start->copy()->addDays($i);
                    $labels[] = $day->format('D d');
                    $data[]   = Task::whereDate('created_at', $day)->count()
                              + Task::whereDate('updated_at', $day)->whereIn('status', $doneStatuses)->count();
                }
                break;

            case 'month':
                for ($i = 29; $i >= 0; $i--) {
                    $day      = now()->subDays($i);
                    $labels[] = $day->format('d M');
                    $data[]   = Task::whereDate('created_at', $day)->count()
                              + Task::whereDate('updated_at', $day)->whereIn('status', $doneStatuses)->count();
                }
                break;

            case 'last_month':
                $start = now()->subMonth()->startOfMonth();
                $days  = $start->daysInMonth;
                for ($i = 0; $i < $days; $i++) {
                    $day      = $start->copy()->addDays($i);
                    $labels[] = $day->format('d');
                    $data[]   = Task::whereDate('created_at', $day)->count()
                              + Task::whereDate('updated_at', $day)->whereIn('status', $doneStatuses)->count();
                }
                break;

            case 'year':
                for ($i = 11; $i >= 0; $i--) {
                    $month    = now()->subMonths($i);
                    $labels[] = $month->format('M');
                    $data[]   = Task::whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->count()
                              + Task::whereYear('updated_at', $month->year)->whereMonth('updated_at', $month->month)->whereIn('status', $doneStatuses)->count();
                }
                break;
        }

        return response()->json(['labels' => $labels, 'data' => $data]);
    }

    public function refresh()
    {
        $doneStatuses     = ['approved', 'delivered', 'archived'];
        $analyticsNonDone = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        $totalTasks     = Task::count();
        $activeProjects = Project::where('status', 'active')->where('is_quick', false)->count();
        $overdueTasks   = Task::where('deadline', '<', now())->whereNotIn('status', $doneStatuses)->count();

        $taskOverview = [
            'total'         => $totalTasks,
            'assigned'      => Task::whereNotNull('assigned_to')->count(),
            'pending'       => Task::whereIn('status', ['draft', 'assigned', 'viewed'])->count(),
            'in_progress'   => Task::where('status', 'in_progress')->count(),
            'in_review'     => Task::where('status', 'submitted')->count(),
            'completed'     => Task::where('status', 'approved')->count(),
            'delivered'     => Task::where('status', 'delivered')->count(),
            'archived'      => Task::where('status', 'archived')->count(),
            'overdue'       => $overdueTasks,
            'due_today'     => Task::whereDate('deadline', today())->whereIn('status', $analyticsNonDone)->count(),
            'due_this_week' => Task::whereBetween('deadline', [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)])->whereIn('status', $analyticsNonDone)->count(),
            'reopened'      => \App\Models\TaskLog::where('action', 'status_updated_reopened')->distinct('task_id')->count('task_id'),
            'reassigned'    => \App\Models\TaskLog::whereIn('action', ['task_reassigned', 'task_transferred'])->distinct('task_id')->count('task_id'),
        ];

        $totalDone      = $taskOverview['completed'] + $taskOverview['delivered'] + $taskOverview['archived'];
        $completionRate = $totalTasks > 0 ? round($totalDone / $totalTasks * 100) : 0;

        $onTimeCount = Task::whereIn('status', ['approved', 'delivered'])
            ->whereHas('logs', function ($q) {
                $q->whereIn('action', ['status_updated_approved', 'status_updated_delivered', 'status_updated_completed'])
                  ->whereColumn('task_logs.created_at', '<=', 'tasks.deadline');
            })->count();

        $onTimeRate   = $totalDone > 0 ? round($onTimeCount / $totalDone * 100) : 0;
        $reviewCycles = TaskSubmission::count();

        $weekLabels = [];
        $weekData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day          = Carbon::now()->subDays($i);
            $weekLabels[] = $day->format('D');
            $weekData[]   = Task::whereDate('created_at', $day)->count()
                          + Task::whereDate('updated_at', $day)->whereIn('status', $doneStatuses)->count();
        }

        $taskStats = [
            'completed'   => Project::where('is_quick', false)->where('status', 'completed')->count(),
            'in_progress' => Project::where('is_quick', false)->where('status', 'active')->count(),
            'pending'     => Project::where('is_quick', false)->where('status', 'pending')->count(),
            'overdue'     => Project::where('is_quick', false)->where('status', 'active')
                                ->whereNotNull('deadline')->where('deadline', '<', now())->count(),
        ];

        $workloadUsers  = User::withCount(['tasks as open_tasks' => fn($q) => $q->where('status', '!=', 'completed')])->where('role', 'user')->orderByDesc('open_tasks')->take(6)->get();
        $workloadLabels = $workloadUsers->pluck('name')->map(fn($n) => explode(' ', $n)[0])->toArray();
        $workloadData   = $workloadUsers->pluck('open_tasks')->toArray();

        return response()->json([
            'totalTasks'        => $totalTasks,
            'activeProjects'    => $activeProjects,
            'scheduledMeetings' => CalendarEvent::where('start_date', '>=', now())->count(),
            'totalMembers'      => User::count(),
            'activeMembers'     => User::where('role', '!=', 'admin')->count(),
            'managerCount'      => User::where('role', 'manager')->count(),
            'userCount'         => User::where('role', 'user')->count(),
            'taskOverview'      => $taskOverview,
            'completionRate'    => $completionRate,
            'onTimeRate'        => $onTimeRate,
            'reviewCycles'      => $reviewCycles,
            'weekLabels'        => $weekLabels,
            'weekData'          => $weekData,
            'taskStats'         => $taskStats,
            'workloadLabels'    => $workloadLabels,
            'workloadData'      => $workloadData,
            'socialPostsTotal'  => TaskSocialPost::count(),
            'socialPostsMonth'  => TaskSocialPost::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'socialPending'     => Task::where('social_required', true)->whereNull('social_posted_at')->count(),
            'refreshedAt'       => now()->format('H:i:s'),
        ]);
    }

    public function index()
    {
        $users      = User::paginate(10);
        $projects   = Project::where('is_quick', false)->withCount('tasks')->paginate(10);
        $allUsers   = User::where('role', 'user')->orderBy('name')->get();
        $allProjects = Project::where('status', 'active')->where('is_quick', false)->orderBy('name')->get();

        $doneStatuses   = ['approved', 'delivered', 'archived'];
        $overdueTasks   = Task::where('deadline', '<', now())->whereNotIn('status', $doneStatuses)->count();
        $completedTasks = Task::where('status', 'approved')->count();
        $totalTasks     = Task::count();
        $activeProjects = Project::where('status', 'active')->where('is_quick', false)->count();

        // --- Task Analytics ---
        $analyticsNonDone = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        $taskOverview = [
            'total'              => $totalTasks,
            'assigned'           => Task::whereNotNull('assigned_to')->count(),
            'pending'            => Task::whereIn('status', ['draft', 'assigned', 'viewed'])->count(),
            'in_progress'        => Task::where('status', 'in_progress')->count(),
            'in_review'          => Task::where('status', 'submitted')->count(),
            'revision_requested' => Task::where('status', 'revision_requested')->count(),
            'completed'          => Task::where('status', 'approved')->count(),
            'delivered'          => Task::where('status', 'delivered')->count(),
            'archived'           => Task::where('status', 'archived')->count(),
            'overdue'            => Task::where('deadline', '<', now())->whereIn('status', $analyticsNonDone)->count(),
            'due_today'          => Task::whereDate('deadline', today())->whereIn('status', $analyticsNonDone)->count(),
            'due_this_week'      => Task::whereBetween('deadline', [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)])->whereIn('status', $analyticsNonDone)->count(),
            'reopened'           => \App\Models\TaskLog::where('action', 'status_updated_reopened')->distinct('task_id')->count('task_id'),
            'reassigned'         => \App\Models\TaskLog::whereIn('action', ['task_reassigned', 'task_transferred'])->distinct('task_id')->count('task_id'),
        ];

        $totalDone = $taskOverview['completed'] + $taskOverview['delivered'] + $taskOverview['archived'];

        $completionRate = $taskOverview['total'] > 0
            ? round($totalDone / $taskOverview['total'] * 100)
            : 0;

        // On-time: approved/delivered tasks that have a log entry before the deadline
        $onTimeCount = Task::whereIn('status', ['approved', 'delivered'])
            ->whereHas('logs', function ($q) {
                $q->whereIn('action', ['status_updated_approved', 'status_updated_delivered', 'status_updated_completed'])
                  ->whereColumn('task_logs.created_at', '<=', 'tasks.deadline');
            })
            ->count();

        $onTimeRate = $totalDone > 0 ? round($onTimeCount / $totalDone * 100) : 0;

        // Review cycles = total number of task submissions (each submit = 1 cycle)
        $reviewCycles = TaskSubmission::count();

        // --- Chart: Working Hours / Daily Task Activity (last 7 days) ---
        $weekLabels = [];
        $weekData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day          = Carbon::now()->subDays($i);
            $weekLabels[] = $day->format('D');
            $weekData[]   = Task::whereDate('created_at', $day)->count()
                          + Task::whereDate('updated_at', $day)->whereIn('status', $doneStatuses)->count();
        }

        // --- Chart: Project Statistics (donut) ---
        $taskStats = [
            'completed'   => Project::where('is_quick', false)->where('status', 'completed')->count(),
            'in_progress' => Project::where('is_quick', false)->where('status', 'active')->count(),
            'pending'     => Project::where('is_quick', false)->where('status', 'pending')->count(),
            'overdue'     => Project::where('is_quick', false)->where('status', 'active')
                                ->whereNotNull('deadline')->where('deadline', '<', now())->count(),
        ];

        // --- Social Media Stats ---
        $socialPostsTotal    = TaskSocialPost::count();
        $socialPostsMonth    = TaskSocialPost::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count();
        $socialPending       = Task::where('social_required', true)->whereNull('social_posted_at')->count();
        $socialRequired      = Task::where('social_required', true)->count();
        $socialPlatformStats = TaskSocialPost::selectRaw('platform, count(*) as total')
                                ->groupBy('platform')->orderByDesc('total')->get();

        // --- Chart: Task Workload (bar) per user ---
        $workloadUsers = User::withCount([
            'tasks as open_tasks' => fn($q) => $q->where('status', '!=', 'completed'),
        ])->where('role', 'user')->orderByDesc('open_tasks')->take(6)->get();

        $workloadLabels = $workloadUsers->pluck('name')->map(fn($n) => explode(' ', $n)[0])->toArray();
        $workloadData   = $workloadUsers->pluck('open_tasks')->toArray();

        // --- Meetings count ---
        $scheduledMeetings = CalendarEvent::where('start_date', '>=', now())->count();

        // --- Member status stats ---
        $totalMembers    = User::count();
        $activeMembers   = User::where('role', '!=', 'admin')->count();
        $managerCount    = User::where('role', 'manager')->count();
        $userCount       = User::where('role', 'user')->count();

        // --- Calendar & Meetings ---
        $calNow        = Carbon::now();
        $firstOfMonth  = $calNow->copy()->startOfMonth();
        $lastOfMonth   = $calNow->copy()->endOfMonth();

        // Build calendar grid (Mon–Sun weeks)
        $calStart = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $calEnd   = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);
        $calWeeks = [];
        $cur = $calStart->copy();
        while ($cur <= $calEnd) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $week[] = $cur->copy();
                $cur->addDay();
            }
            $calWeeks[] = $week;
        }

        // Task deadlines this month → dot colors per date
        $monthTasks = Task::whereBetween('deadline', [$firstOfMonth, $lastOfMonth])
            ->get()
            ->groupBy(fn($t) => $t->deadline->format('Y-m-d'));

        $taskDotMap = [];
        foreach ($monthTasks as $dateKey => $tasks) {
            $colors = [];
            if ($tasks->where('status', 'completed')->count())   $colors[] = '#10B981';
            if ($tasks->where('status', 'in_progress')->count()) $colors[] = '#F59E0B';
            if ($tasks->where('status', 'pending')->count())     $colors[] = '#6366F1';
            if ($tasks->where('deadline', '<', now())->where('status', '!=', 'completed')->count()) $colors[] = '#EF4444';
            $taskDotMap[$dateKey] = array_unique($colors);
        }

        // Today's meetings (CalendarEvents first, fall back to tasks due today)
        $todayMeetings = CalendarEvent::with('user')
            ->whereDate('start_date', today())
            ->orderBy('start_date')
            ->take(6)
            ->get();

        $todayTaskEvents = Task::with(['assignee', 'project'])
            ->whereDate('deadline', today())
            ->take(6)
            ->get();

        $calMonthLabel  = $calNow->format('F Y');
        $calTodayKey    = $calNow->format('Y-m-d');
        $calWeekCount   = count($calWeeks);

        // ── Extra charts (opt-in via developer mode) ──
        $priorityCounts = Task::selectRaw("priority, count(*) as total")
            ->groupBy('priority')
            ->pluck('total', 'priority');
        $priorityData = [
            'labels' => ['High', 'Medium', 'Low'],
            'data'   => [
                (int)($priorityCounts['high']   ?? 0),
                (int)($priorityCounts['medium'] ?? 0),
                (int)($priorityCounts['low']    ?? 0),
            ],
        ];

        $perfUsers = User::withCount([
            'tasks as completed' => fn($q) => $q->whereIn('status', ['approved', 'delivered']),
            'tasks as in_progress_count' => fn($q) => $q->where('status', 'in_progress'),
            'tasks as total_assigned',
        ])->where('role', 'user')->orderByDesc('completed')->take(8)->get();
        $teamPerfData = [
            'labels'      => $perfUsers->map(fn($u) => explode(' ', $u->name)[0])->toArray(),
            'completed'   => $perfUsers->pluck('completed')->map(fn($v) => (int)$v)->toArray(),
            'in_progress' => $perfUsers->pluck('in_progress_count')->map(fn($v) => (int)$v)->toArray(),
        ];

        $projectProgressData = $projects->take(8)->map(function ($p) {
            $total     = $p->tasks_count ?: 0;
            $done      = Task::where('project_id', $p->id)->whereIn('status', ['approved', 'delivered', 'archived'])->count();
            return [
                'name'    => $p->name,
                'percent' => $total > 0 ? round($done / $total * 100) : 0,
                'done'    => $done,
                'total'   => $total,
            ];
        })->values();

        $dashRefreshUrl      = route('admin.dashboard.refresh');
        $dashHomeUrl         = route('admin.dashboard');
        $dashProjectsUrl     = route('admin.projects.index');
        $dashProjectStoreUrl = route('admin.projects.store');
        $dashQuickTaskUrl    = route('admin.tasks.quick');
        $customers    = Customer::orderBy('name')->get();

        // Customer task distribution for donut chart (all statuses)
        $customerTaskDist = Customer::withCount('tasks')
            ->orderBy('name')
            ->get(['id', 'name', 'tasks_count']);

        $unassignedTaskCount = Task::whereNull('customer_id')->count();

        $recentTasks  = Task::with(['project:id,name', 'assignee:id,name,avatar'])
            ->orderByDesc('updated_at')
            ->take(12)
            ->get();

        return view('admin.dashboard', compact(
            'users', 'projects', 'allUsers', 'allProjects', 'customers', 'recentTasks', 'customerTaskDist', 'unassignedTaskCount',
            'overdueTasks', 'completedTasks', 'totalTasks', 'activeProjects',
            'scheduledMeetings',
            'weekLabels', 'weekData',
            'taskStats',
            'workloadLabels', 'workloadData',
            'totalMembers', 'activeMembers', 'managerCount', 'userCount',
            'calWeeks', 'taskDotMap', 'todayMeetings', 'todayTaskEvents',
            'calMonthLabel', 'calTodayKey', 'calWeekCount', 'firstOfMonth',
            'taskOverview', 'completionRate', 'onTimeRate', 'reviewCycles',
            'priorityData', 'teamPerfData', 'projectProgressData',
            'socialPostsTotal', 'socialPostsMonth', 'socialPending', 'socialRequired', 'socialPlatformStats',
            'dashRefreshUrl', 'dashHomeUrl', 'dashProjectsUrl', 'dashProjectStoreUrl', 'dashQuickTaskUrl'
        ));
    }
}
