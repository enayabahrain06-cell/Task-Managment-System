<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;

class TeamController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermission('view_team')) {
            return redirect()->route('user.dashboard')->with('error', "You don't have permission to access Team Members.");
        }

        $doneStatuses = ['delivered', 'approved', 'archived'];

        $members = User::withCount([
            'assignedTasks as total_tasks',
            'assignedTasks as completed_tasks' => fn($q) => $q->whereIn('status', $doneStatuses),
            'assignedTasks as pending_tasks'   => fn($q) => $q->whereNotIn('status', $doneStatuses),
        ])->orderBy('role')->get();

        $totalMembers    = $members->count();
        $activeMembers   = $members->where('role', '!=', 'admin')->count();
        $totalCompleted  = Task::where('status', 'completed')->count();
        $totalPending    = Task::where('status', '!=', 'completed')->count();

        return view('team.index', compact('members', 'totalMembers', 'activeMembers', 'totalCompleted', 'totalPending'));
    }
}
