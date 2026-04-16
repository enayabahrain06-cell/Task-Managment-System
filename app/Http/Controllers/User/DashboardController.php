<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $tasks = auth()->user()->tasks()->latest()->paginate(10);
        $overdueTasks = auth()->user()->tasks()->where('deadline', '<', now())->where('status', '!=', 'completed')->count();

        return view('user.dashboard', compact('tasks', 'overdueTasks'));
    }
}

