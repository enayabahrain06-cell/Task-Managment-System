<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Notifications\TaskCompleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = auth()->user()->tasks()->with('project')->latest()->paginate(15);
        return view('user.tasks.index', compact('tasks'));
    }

    public function show(Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $task->load('project', 'logs.user', 'submissions.user', 'submissions.reviewer');
        return view('user.tasks.show', compact('task'));
    }

    public function updateStatus(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress',
            'note'   => 'nullable|string|max:500',
        ]);

        $task->update(['status' => $request->status]);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action'  => 'status_updated_' . $request->status,
            'note'    => $request->note,
        ]);

        return back()->with('success', 'Status updated.');
    }

    public function submitVersion(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        if ($task->status === 'completed') {
            return back()->with('error', 'This task is already completed.');
        }

        $request->validate([
            'note' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:20480', // 20 MB
        ]);

        // Must have at least a note or a file
        if (!$request->filled('note') && !$request->hasFile('file')) {
            return back()->withErrors(['note' => 'Please add a note or attach a file.']);
        }

        $version = TaskSubmission::where('task_id', $task->id)->max('version') + 1;

        $filePath         = null;
        $originalFilename = null;
        if ($request->hasFile('file')) {
            $file             = $request->file('file');
            $originalFilename = $file->getClientOriginalName();
            $filePath         = $file->store('task-submissions/' . $task->id, 'public');
        }

        TaskSubmission::create([
            'task_id'           => $task->id,
            'user_id'           => auth()->id(),
            'version'           => $version,
            'note'              => $request->note,
            'file_path'         => $filePath,
            'original_filename' => $originalFilename,
            'status'            => 'submitted',
        ]);

        $task->update(['status' => 'pending_approval']);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action'  => 'status_updated_pending_approval',
            'note'    => 'Submitted version ' . $version . ($request->note ? ': ' . $request->note : ''),
        ]);

        // Notify admins
        if (Setting::get('notify_on_complete', '1') === '1') {
            $task->load('assignee');
            User::where('role', 'admin')->each(fn($admin) => $admin->notify(new TaskCompleted($task)));
        }

        return back()->with('success', 'Version ' . $version . ' submitted for review.');
    }
}
