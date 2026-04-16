<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $teamUsers = User::whereIn('role', ['manager', 'user'])->get();
        $projects = Project::withCount(['tasks' => function ($query) {
            $query->where('status', 'completed');
        }])->get();
        $overdueTasks = Task::where('deadline', '<', now())->where('status', '!=', 'completed')->get();
        $workload = [];
        foreach ($teamUsers as $user) {
            $workload[$user->id] = $user->tasks()->where('status', '!=', 'completed')->count();
        }

        return view('manager.dashboard', compact('teamUsers', 'projects', 'overdueTasks', 'workload'));
    }
}

