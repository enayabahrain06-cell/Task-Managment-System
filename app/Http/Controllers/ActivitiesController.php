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

        $query = TaskLog::with(['user', 'task.project'])->latest();

        $selectedUserId = $request->input('user_id');
        $selectedUser   = null;
        if ($selectedUserId) {
            $query->where('user_id', $selectedUserId);
            $selectedUser = User::find($selectedUserId);
        }

        $activities = $query->paginate(20)->withQueryString();

        return view('activities.index', compact('teams', 'activities', 'selectedUser'));
    }
}
