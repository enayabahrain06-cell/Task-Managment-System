<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskCommentEdit;
use App\Models\TaskLog;
use App\Models\TaskSubmission;
use App\Models\TaskSubmissionEdit;
use App\Models\TaskTransfer;
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

        // Auto-advance to "viewed" on first open
        if (is_null($task->first_viewed_at)) {
            $updates = ['first_viewed_at' => now()];
            if ($task->status === 'assigned') {
                $updates['status'] = 'viewed';
            }
            $task->update($updates);

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

            if (Setting::get('notify_on_viewed', '0') === '1') {
                User::where('role', 'admin')->each(
                    fn($admin) => $admin->notify(new TaskViewed($task, auth()->user()))
                );
            }
        }

        $task->load('project.attachments', 'project.customer', 'assignees', 'reviewer', 'creator', 'customer', 'logs.user', 'submissions.user', 'submissions.reviewer', 'submissions.noteEdits.editor', 'comments.user', 'comments.edits.editor', 'transfers.fromUser', 'transfers.transferredBy');

        // Find the transfer that handed this task TO the current user
        $incomingTransfer = $task->transfers
            ->where('to_user_id', auth()->id())
            ->sortByDesc('transferred_at')
            ->first();

        return view('user.tasks.show', compact('task', 'incomingTransfer'));
    }

    public function updateStatus(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $allowed = match($task->status) {
            'viewed', 'revision_requested' => ['in_progress'],
            'in_progress'                  => [],
            default                        => [],
        };

        $request->validate([
            'status' => 'required|in:' . implode(',', $allowed ?: ['in_progress']),
            'note'   => 'nullable|string|max:500',
        ]);

        if (!in_array($request->status, $allowed)) {
            return back()->with('error', 'This status transition is not allowed.');
        }

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

        return back()->with('success', 'Status updated to ' . ucfirst(str_replace('_', ' ', $request->status)) . '.');
    }

    public function submitVersion(Request $request, Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $submittable = ['viewed', 'in_progress', 'revision_requested'];
        if (!in_array($task->status, $submittable)) {
            return back()->with('error', 'You cannot submit at this stage.');
        }

        // Auto-advance from viewed → in_progress on first submission
        if ($task->status === 'viewed') {
            $task->update(['status' => 'in_progress']);
            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'status_updated_in_progress',
                'note'     => 'Started working (triggered by first submission)',
                'metadata' => ['old_status' => 'viewed', 'new_status' => 'in_progress'],
            ]);
        }

        $request->validate([
            'note' => 'nullable|string|max:1000',
            'body' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:' . ((int) Setting::get('max_upload_mb', 20) * 1024),
        ]);

        $note = $request->body ?? $request->note;

        if (!$request->filled('note') && !$request->filled('body') && !$request->hasFile('file')) {
            return back()->withErrors(['body' => 'Please add a note or attach a file.']);
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
            'note'              => $note,
            'file_path'         => $filePath,
            'original_filename' => $originalFilename,
            'status'            => 'submitted',
        ]);

        $oldStatus = $task->status;
        $task->update(['status' => 'submitted']);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_submitted',
            'note'     => 'Submitted version ' . $version . ($note ? ': ' . $note : ''),
            'metadata' => [
                'old_status'      => $oldStatus,
                'new_status'      => 'submitted',
                'version'         => $version,
                'has_file'        => !is_null($filePath),
                'filename'        => $originalFilename,
                'submission_note' => $note,
            ],
        ]);

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

        $request->validate([
            'body' => 'required|string|max:1000',
            'file' => 'nullable|file|max:' . ((int) Setting::get('max_upload_mb', 20) * 1024),
        ]);

        $filePath = null;
        $originalFilename = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();
            $filePath = $file->store("task-comment-files/{$task->id}", 'public');
        }

        // Auto-advance from viewed → in_progress on first comment
        if ($task->status === 'viewed') {
            $task->update(['status' => 'in_progress']);
            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'status_updated_in_progress',
                'note'     => 'Started working (triggered by first comment)',
                'metadata' => ['old_status' => 'viewed', 'new_status' => 'in_progress'],
            ]);
        }

        $comment = TaskComment::create([
            'task_id'           => $task->id,
            'user_id'           => auth()->id(),
            'body'              => $request->body,
            'file_path'         => $filePath,
            'original_filename' => $originalFilename,
        ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'comment_added',
            'note'     => \Illuminate\Support\Str::limit($request->body, 120),
            'metadata' => ['comment_id' => $comment->id, 'author_role' => 'user'],
        ]);

        $comment->load('user');
        if (Setting::get('notify_on_comment', '1') === '1') {
            User::where('role', 'admin')->each(fn($admin) => $admin->notify(new TaskCommentPosted($task, $comment)));
        }

        return back()->with('success', 'Comment posted.');
    }

    public function editComment(Request $request, Task $task, TaskComment $comment)
    {
        if ($comment->task_id !== $task->id || $comment->user_id !== auth()->id()) {
            abort(403);
        }
        $request->validate(['body' => 'required|string|max:1000']);
        TaskCommentEdit::create([
            'task_comment_id'       => $comment->id,
            'old_body'              => $comment->body,
            'old_file_path'         => $comment->file_path,
            'old_original_filename' => $comment->original_filename,
            'edited_by_id'          => auth()->id(),
            'created_at'            => now(),
        ]);
        $comment->update(['body' => $request->body]);
        return back()->with('success', 'Comment updated.');
    }

    public function editSubmissionNote(Request $request, Task $task, TaskSubmission $submission)
    {
        if ($submission->task_id !== $task->id || $submission->user_id !== auth()->id()) {
            abort(403);
        }
        $request->validate(['note' => 'nullable|string|max:1000']);
        TaskSubmissionEdit::create([
            'task_submission_id' => $submission->id,
            'old_note'           => $submission->note,
            'edited_by_id'       => auth()->id(),
            'created_at'         => now(),
        ]);
        $submission->update(['note' => $request->note]);
        return back()->with('success', 'Submission note updated.');
    }
}
