<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use App\Notifications\UserReportSubmitted;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $allTasks = $user->tasks()->with('project')->get();

        $total      = $allTasks->count();
        $completed  = $allTasks->where('status', 'completed')->count();
        $inProgress = $allTasks->where('status', 'in_progress')->count();
        $pending    = $allTasks->where('status', 'pending')->count();
        $pendingApproval = $allTasks->where('status', 'pending_approval')->count();
        $overdue    = $allTasks->filter(fn($t) => $t->deadline->isPast() && !in_array($t->status, ['completed','pending_approval']))->count();
        $rate       = $total > 0 ? round($completed / $total * 100) : 0;

        // My tasks sorted: overdue → in_progress → pending_approval → upcoming → completed
        $tasks = $allTasks->sortBy(function ($t) {
            if ($t->status === 'completed')        return '5_' . $t->deadline->format('Y-m-d');
            if ($t->status === 'pending_approval') return '3_' . $t->deadline->format('Y-m-d');
            if ($t->deadline->isPast())            return '1_' . $t->deadline->format('Y-m-d');
            if ($t->status === 'in_progress')      return '2_' . $t->deadline->format('Y-m-d');
            return '4_' . $t->deadline->format('Y-m-d');
        })->values();

        // Next 4 upcoming (non-completed, future deadline)
        $upcomingTasks = $allTasks
            ->filter(fn($t) => $t->deadline->isFuture() && !in_array($t->status, ['completed','pending_approval']))
            ->sortBy('deadline')
            ->take(4);

        // Team tasks: all tasks in my projects, excluding my own
        $myProjectIds = $user->projects()->pluck('projects.id');
        $teamTasks = Task::whereIn('project_id', $myProjectIds)
            ->where('assigned_to', '!=', $user->id)
            ->with(['project', 'assignee'])
            ->orderByRaw("CASE WHEN status='completed' THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(20)
            ->get();

        // My projects with progress
        $myProjects = $user->projects()
            ->withCount(['tasks', 'tasks as completed_count' => fn($q) => $q->where('status','completed')])
            ->orderByRaw("CASE WHEN status='completed' THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(6)
            ->get();

        // Recent activity (this user's task logs)
        $recentActivity = TaskLog::where('user_id', $user->id)
            ->with('task')
            ->latest()
            ->take(8)
            ->get();

        // Last 7 days activity for bar chart
        $weekActivity = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = now()->subDays($daysAgo)->toDateString();
            return [
                'label' => now()->subDays($daysAgo)->format('D'),
                'count' => TaskLog::where('user_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->count(),
            ];
        });

        return view('user.dashboard', compact(
            'total', 'completed', 'inProgress', 'pending', 'pendingApproval', 'overdue', 'rate',
            'tasks', 'upcomingTasks', 'recentActivity', 'weekActivity',
            'teamTasks', 'myProjects'
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
}
