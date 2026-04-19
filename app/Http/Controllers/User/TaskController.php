<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskLog;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Notifications\TaskCommentPosted;
use App\Notifications\TaskCompleted;
use App\Notifications\TaskViewed;
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

        // Notify admins and log first view
        if (is_null($task->first_viewed_at)) {
            $task->update(['first_viewed_at' => now()]);

            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'first_viewed',
                'note'     => auth()->user()->name . ' opened this task for the first time.',
                'metadata' => [
                    'viewer_id'   => auth()->id(),
                    'viewer_name' => auth()->user()->name,
                ],
            ]);

            User::where('role', 'admin')->each(
                fn($admin) => $admin->notify(new TaskViewed($task, auth()->user()))
            );
        }

        $task->load('project', 'logs.user', 'submissions.user', 'submissions.reviewer', 'comments.user');
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

        $oldStatus = $task->status;
        $task->update(['status' => $request->status]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_' . $request->status,
            'note'     => $request->note,
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $request->status,
            ],
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
            'file' => 'nullable|file|max:20480',
        ]);

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
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_pending_approval',
            'note'     => 'Submitted version ' . $version . ($request->note ? ': ' . $request->note : ''),
            'metadata' => [
                'version'           => $version,
                'has_file'          => !is_null($filePath),
                'filename'          => $originalFilename,
                'submission_note'   => $request->note,
            ],
        ]);

        // Notify admins
        if (Setting::get('notify_on_complete', '1') === '1') {
            $task->load('assignee');
            $hasFile = !is_null($filePath);
            User::where('role', 'admin')->each(fn($admin) => $admin->notify(new TaskCompleted($task, $hasFile)));
        }

        return back()->with('success', 'Version ' . $version . ' submitted for review.');
    }

    public function addComment(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $request->validate(['body' => 'required|string|max:1000']);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'body'    => $request->body,
        ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'comment_added',
            'note'     => \Illuminate\Support\Str::limit($request->body, 120),
            'metadata' => ['comment_id' => $comment->id, 'author_role' => 'user'],
        ]);

        $comment->load('user');
        User::where('role', 'admin')->each(fn($admin) => $admin->notify(new TaskCommentPosted($task, $comment)));

        return back()->with('success', 'Comment posted.');
    }
}
