<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;

class ActivitiesController extends Controller
{
    public function index()
    {
        $teams = User::withCount('tasks')
            ->where('role', '!=', 'admin')
            ->orderBy('role')
            ->get()
            ->groupBy('role');

        $activities = TaskLog::with(['user', 'task.project'])
            ->latest()
            ->paginate(20);

        return view('activities.index', compact('teams', 'activities'));
    }
}
