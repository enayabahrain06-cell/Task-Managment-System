<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = auth()->user()->tasks()->latest()->paginate(15);
        return view('user.tasks.index', compact('tasks'));
    }

    public function show(Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $task->load('project', 'logs.user');
        return view('user.tasks.show', compact('task'));
    }

    public function updateStatus(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'note' => 'nullable|string',
        ]);

        $task->update($request->only('status'));

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'status_updated_' . $request->status,
            'note' => $request->note,
        ]);

        return redirect()->back()->with('success', 'Task status updated.');
    }
}

