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

        // IDs of tasks that were transferred TO this user (inherited)
        $inheritedIds = TaskTransfer::where('to_user_id', $user->id)
            ->pluck('task_id')
            ->unique();

        // Native = assigned to this user and NOT inherited
        $nativeTasks    = $allTasks->whereNotIn('id', $inheritedIds);
        $inheritedTasks = $allTasks->whereIn('id', $inheritedIds);

        $doneStatuses   = ['approved', 'delivered', 'archived'];
        $activeStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];

        $total      = $allTasks->count();
        $completed  = $allTasks->whereIn('status', $doneStatuses)->count();
        $inProgress = $allTasks->where('status', 'in_progress')->count();
        $pending    = $allTasks->whereIn('status', ['draft', 'assigned', 'viewed'])->count();
        $inReview   = $allTasks->where('status', 'submitted')->count();
        $overdue    = $allTasks->filter(
            fn($t) => $t->deadline && $t->deadline->isPast() && in_array($t->status, $activeStatuses)
        )->count();

        // Completion rate based on native tasks only (excludes inherited)
        $nativeTotal     = $nativeTasks->count();
        $nativeCompleted = $nativeTasks->whereIn('status', $doneStatuses)->count();
        $rate            = $nativeTotal > 0 ? round($nativeCompleted / $nativeTotal * 100) : 0;

        // My tasks sorted: overdue → in_progress → submitted → upcoming → done
        $tasks = $allTasks->sortBy(function ($t) use ($doneStatuses) {
            if (in_array($t->status, $doneStatuses))  return '5_' . ($t->deadline?->format('Y-m-d') ?? '9999');
            if ($t->status === 'submitted')            return '3_' . ($t->deadline?->format('Y-m-d') ?? '9999');
            if ($t->deadline && $t->deadline->isPast()) return '1_' . $t->deadline->format('Y-m-d');
            if ($t->status === 'in_progress')          return '2_' . ($t->deadline?->format('Y-m-d') ?? '9999');
            return '4_' . ($t->deadline?->format('Y-m-d') ?? '9999');
        })->values();

        // Tag each task as inherited or not for the view
        $tasks = $tasks->map(function ($t) use ($inheritedIds) {
            $t->is_inherited = $inheritedIds->contains($t->id);
            return $t;
        });

        // Next 4 upcoming (non-done, future deadline)
        $upcomingTasks = $allTasks
            ->filter(fn($t) => $t->deadline && $t->deadline->isFuture() && !in_array($t->status, $doneStatuses))
            ->sortBy('deadline')
            ->take(4);

        // Team tasks: tasks in my projects not assigned to me
        $myProjectIds = $user->projects()->pluck('projects.id');
        $teamTasks = Task::whereIn('project_id', $myProjectIds)
            ->where('assigned_to', '!=', $user->id)
            ->with(['project', 'assignee'])
            ->orderByRaw("CASE WHEN status IN ('approved','delivered','archived') THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(20)
            ->get();

        // My projects with progress
        $myProjects = $user->projects()
            ->withCount([
                'tasks',
                'tasks as completed_count' => fn($q) => $q->whereIn('status', $doneStatuses),
            ])
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

        $inheritedCount = $inheritedTasks->count();

        $pendingApproval = $inReview; // view uses old name, maps to 'submitted' status

        return view('user.dashboard', compact(
            'total', 'completed', 'inProgress', 'pending', 'pendingApproval', 'overdue', 'rate',
            'tasks', 'upcomingTasks', 'recentActivity', 'weekActivity',
            'teamTasks', 'myProjects',
            'inheritedCount', 'nativeTotal', 'nativeCompleted'
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
