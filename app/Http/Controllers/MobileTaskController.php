<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class MobileTaskController extends Controller
{
    private const FILTERS = [
        ['key' => 'all', 'label' => 'All'],
        ['key' => 'pending', 'label' => 'Pending'],
        ['key' => 'in_progress', 'label' => 'In Progress'],
        ['key' => 'overdue', 'label' => 'Overdue'],
        ['key' => 'completed', 'label' => 'Completed'],
    ];

    private const PENDING_STATUSES = ['draft', 'assigned', 'viewed', 'revision_requested'];
    private const IN_PROGRESS_STATUSES = ['in_progress', 'paused', 'submitted', 'pending_customer'];
    private const COMPLETED_STATUSES = ['approved', 'delivered', 'archived'];

    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = in_array($user->role, ['admin', 'manager']);

        if ($isAdmin && !$user->hasPermission('manage_tasks')) {
            abort(403, 'You do not have permission to manage Tasks.');
        }

        $filterKeys = array_column(self::FILTERS, 'key');
        $activeFilter = in_array($request->input('filter'), $filterKeys) ? $request->input('filter') : 'all';

        $query = Task::with(['project:id,name', 'assignee:id,name']);

        if ($request->filled('project')) {
            $query->where('project_id', $request->project);
        }

        if (!$isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('social_assigned_to', $user->id)
                    ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                        ->from('task_assignees')
                        ->whereColumn('task_assignees.task_id', 'tasks.id')
                        ->where('task_assignees.user_id', $user->id));
            });
        }

        match ($activeFilter) {
            'pending' => $query->whereIn('status', self::PENDING_STATUSES),
            'in_progress' => $query->whereIn('status', self::IN_PROGRESS_STATUSES),
            'completed' => $query->whereIn('status', self::COMPLETED_STATUSES),
            'overdue' => $query->whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->whereNotIn('status', self::COMPLETED_STATUSES),
            default => null,
        };

        $totalCount = (clone $query)->count();

        $tasks = $query
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->orderByDesc('created_at')
            ->take(50)
            ->get();

        $activeLabel = collect(self::FILTERS)->firstWhere('key', $activeFilter)['label'];

        return view('mobile.tasks.index', [
            'filters' => self::FILTERS,
            'activeFilter' => $activeFilter,
            'activeLabel' => $activeLabel,
            'tasks' => $tasks,
            'totalCount' => $totalCount,
        ]);
    }

    public function show(Task $task)
    {
        $role = auth()->user()->role;
        $routeName = $role === 'user' ? 'user.tasks.show' : 'admin.tasks.show';

        return redirect()->route($routeName, $task);
    }
}
