<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;

class ActivitiesController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $teams = User::withCount('tasks')
            ->where('role', '!=', 'admin')
            ->orderBy('role')
            ->get()
            ->groupBy('role');

        $query = TaskLog::with(['user', 'task.project']);

        // User filter (sidebar)
        $selectedUserId = $request->input('user_id');
        $selectedUser   = null;
        if ($selectedUserId) {
            $query->where('user_id', $selectedUserId);
            $selectedUser = User::find($selectedUserId);
        }

        // Action type filter
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Date range filter
        switch ($request->input('date_range')) {
            case 'today':     $query->whereDate('created_at', today()); break;
            case 'yesterday': $query->whereDate('created_at', today()->subDay()); break;
            case 'week':      $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]); break;
            case 'month':     $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year); break;
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        $sort === 'oldest' ? $query->oldest() : $query->latest();

        $activities = $query->paginate(20)->withQueryString();

        // Distinct action types for filter dropdown
        $actionTypes = TaskLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('activities.index', compact('teams', 'activities', 'selectedUser', 'actionTypes'));
    }
}
