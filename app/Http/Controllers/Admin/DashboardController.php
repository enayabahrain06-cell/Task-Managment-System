<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function refresh()
    {
        $doneStatuses     = ['approved', 'delivered', 'archived'];
        $analyticsNonDone = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        $totalTasks     = Task::count();
        $activeProjects = Project::where('status', 'active')->count();
        $overdueTasks   = Task::where('deadline', '<', now())->whereNotIn('status', $doneStatuses)->count();

        $taskOverview = [
            'total'         => $totalTasks,
            'assigned'      => Task::whereNotNull('assigned_to')->count(),
            'pending'       => Task::whereIn('status', ['draft', 'assigned', 'viewed'])->count(),
            'in_progress'   => Task::where('status', 'in_progress')->count(),
            'in_review'     => Task::where('status', 'submitted')->count(),
            'completed'     => Task::where('status', 'approved')->count(),
            'delivered'     => Task::where('status', 'delivered')->count(),
            'overdue'       => $overdueTasks,
            'due_today'     => Task::whereDate('deadline', today())->whereIn('status', $analyticsNonDone)->count(),
            'due_this_week' => Task::whereBetween('deadline', [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)])->whereIn('status', $analyticsNonDone)->count(),
        ];

        $totalDone      = $taskOverview['completed'] + $taskOverview['delivered'];
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
                          + Task::whereDate('updated_at', $day)->where('status', 'completed')->count();
        }

        $taskStats = [
            'completed'   => $taskOverview['completed'],
            'in_progress' => $taskOverview['in_progress'],
            'pending'     => $taskOverview['pending'],
            'overdue'     => $overdueTasks,
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
            'refreshedAt'       => now()->format('H:i:s'),
        ]);
    }

    public function index()
    {
        $users      = User::paginate(10);
        $projects   = Project::withCount('tasks')->paginate(10);
        $allUsers   = User::where('role', 'user')->orderBy('name')->get();
        $allProjects = Project::where('status', 'active')->orderBy('name')->get();

        $doneStatuses   = ['approved', 'delivered', 'archived'];
        $overdueTasks   = Task::where('deadline', '<', now())->whereNotIn('status', $doneStatuses)->count();
        $completedTasks = Task::where('status', 'approved')->count();
        $totalTasks     = Task::count();
        $activeProjects = Project::where('status', 'active')->count();

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
            'overdue'            => Task::where('deadline', '<', now())->whereIn('status', $analyticsNonDone)->count(),
            'due_today'          => Task::whereDate('deadline', today())->whereIn('status', $analyticsNonDone)->count(),
            'due_this_week'      => Task::whereBetween('deadline', [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)])->whereIn('status', $analyticsNonDone)->count(),
        ];

        $totalDone = $taskOverview['completed'] + $taskOverview['delivered'];

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
                          + Task::whereDate('updated_at', $day)->where('status', 'completed')->count();
        }

        // --- Chart: Project Statistics (donut) ---
        $taskStats = [
            'completed'   => Task::where('status', 'approved')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'pending'     => Task::whereIn('status', ['draft', 'assigned', 'viewed'])->count(),
            'overdue'     => $overdueTasks,
        ];

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

        return view('admin.dashboard', compact(
            'users', 'projects', 'allUsers', 'allProjects',
            'overdueTasks', 'completedTasks', 'totalTasks', 'activeProjects',
            'scheduledMeetings',
            'weekLabels', 'weekData',
            'taskStats',
            'workloadLabels', 'workloadData',
            'totalMembers', 'activeMembers', 'managerCount', 'userCount',
            'calWeeks', 'taskDotMap', 'todayMeetings', 'todayTaskEvents',
            'calMonthLabel', 'calTodayKey', 'calWeekCount', 'firstOfMonth',
            'taskOverview', 'completionRate', 'onTimeRate', 'reviewCycles'
        ));
    }
}
