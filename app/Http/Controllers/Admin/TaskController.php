<?php

namespace App\Http\Controllers\Admin;

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
use App\Notifications\TaskDelivered;
use App\Notifications\TaskReassigned;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_tasks')) {
            abort(403, 'You do not have permission to manage Tasks.');
        }

        $query = Task::with(['project:id,name', 'assignee:id,name,avatar'])
            ->withCount('assignees');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('project')) {
            $query->where('project_id', $request->project);
        }
        $doneStatuses   = ['approved', 'delivered', 'archived'];
        $isDoneTab      = ($request->get('tab') === 'done');

        if ($request->boolean('overdue')) {
            $query->whereNotNull('deadline')
                  ->where('deadline', '<', now())
                  ->whereNotIn('status', $doneStatuses);
        }
        if ($request->filled('filter')) {
            match($request->filter) {
                'pending'       => $query->whereIn('status', ['draft','assigned','viewed']),
                'due_this_week' => $query->whereNotNull('deadline')
                                         ->whereBetween('deadline', [now()->startOfWeek(\Carbon\Carbon::MONDAY), now()->endOfWeek(\Carbon\Carbon::SUNDAY)])
                                         ->whereNotIn('status', $doneStatuses),
                default         => null,
            };
        }

        // Tab-based separation: active tab hides done, done tab shows only done
        if (!$request->filled('status')) {
            if ($isDoneTab) {
                $query->whereIn('status', $doneStatuses);
            } else {
                $query->whereNotIn('status', $doneStatuses);
            }
        }

        $tasks = $query->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->paginate(24)
            ->withQueryString();

        $projects = \App\Models\Project::orderBy('name')->get(['id','name']);

        $stats = [
            'total'       => Task::count(),
            'active'      => Task::whereNotIn('status', $doneStatuses)->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'overdue'     => Task::whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->whereNotIn('status', $doneStatuses)
                ->count(),
            'done'        => Task::whereIn('status', $doneStatuses)->count(),
            'approved'    => Task::where('status', 'approved')->count(),
            'delivered'   => Task::where('status', 'delivered')->count(),
            'archived'    => Task::where('status', 'archived')->count(),
        ];

        return view('admin.tasks.index', compact('tasks', 'projects', 'stats'));
    }

    public function show(Task $task)
    {
        $task->load('project.attachments', 'project.members', 'project.customer', 'assignee', 'assignees', 'reviewer', 'creator', 'customer', 'logs.user', 'submissions.user', 'submissions.reviewer', 'submissions.noteEdits.editor', 'comments.user', 'comments.edits.editor', 'transfers.fromUser', 'transfers.toUser', 'transfers.transferredBy');
        $users       = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        $socialUsers = User::where('role', 'user')->orderBy('name')->get();
        return view('admin.tasks.show', compact('task', 'users', 'socialUsers'));
    }

    public function comment(Request $request, Task $task)
    {
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
            'note'     => Str::limit($request->body, 120),
            'metadata' => ['comment_id' => $comment->id, 'author_role' => 'admin'],
        ]);

        $comment->load('user');

        if ($task->assignee && Setting::get('notify_on_comment', '1') === '1') {
            $task->assignee->notify(new TaskCommentPosted($task, $comment));
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
        if ($submission->task_id !== $task->id) {
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

    public function deliver(Request $request, Task $task)
    {
        $request->validate(['note' => 'nullable|string|max:500']);

        if ($task->status !== 'approved') {
            return back()->with('error', 'Only approved tasks can be marked as delivered.');
        }

        $task->update(['status' => 'delivered']);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_delivered',
            'note'     => $request->note ? 'Delivered: ' . $request->note : 'Marked as delivered',
            'metadata' => [
                'old_status'        => 'approved',
                'new_status'        => 'delivered',
                'delivered_by_id'   => auth()->id(),
                'delivered_by_name' => auth()->user()->name,
                'delivery_note'     => $request->note,
            ],
        ]);

        AuditLogger::log(
            'task.delivered',
            $task,
            'Task "' . $task->title . '" marked as delivered',
            ['task_id' => $task->id, 'task_title' => $task->title, 'note' => $request->note]
        );

        if ($task->assignee && Setting::get('notify_on_deliver', '1') === '1') {
            $task->assignee->notify(new TaskDelivered($task, $request->note));
        }

        $task->project?->autoComplete();

        return back()->with('success', 'Task marked as delivered — ' . ($task->assignee->name ?? 'assignee') . ' has been notified.');
    }

    public function forceClose(Request $request, Task $task)
    {
        $closeable = ['assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested', 'approved'];
        if (!in_array($task->status, $closeable)) {
            return back()->with('error', 'This task is already closed or archived.');
        }

        $oldStatus = $task->status;
        $task->update(['status' => 'delivered']);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_delivered',
            'note'     => 'Task force-closed by ' . auth()->user()->name . ($request->note ? ': ' . $request->note : ''),
            'metadata' => [
                'old_status'        => $oldStatus,
                'new_status'        => 'delivered',
                'delivered_by_id'   => auth()->id(),
                'delivered_by_name' => auth()->user()->name,
                'force_closed'      => true,
            ],
        ]);

        AuditLogger::log(
            'task.delivered',
            $task,
            'Task "' . $task->title . '" force-closed by admin (was ' . $oldStatus . ')',
            ['task_id' => $task->id, 'task_title' => $task->title, 'old_status' => $oldStatus]
        );

        if ($task->assignee && Setting::get('notify_on_deliver', '1') === '1') {
            $task->assignee->notify(new TaskDelivered($task, $request->note));
        }

        $task->project?->autoComplete();

        return back()->with('success', 'Task closed successfully.');
    }

    public function destroy(Task $task)
    {
        $title = $task->title;
        AuditLogger::log(
            'task.deleted',
            $task,
            'Task "' . $title . '" moved to recycle bin',
            ['task_id' => $task->id, 'task_title' => $title]
        );
        $task->delete();

        return redirect()->route('admin.tasks.index')
            ->with('success', '"' . $title . '" moved to the Recycle Bin.');
    }

    public function trash(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_tasks')) {
            abort(403);
        }

        $query = Task::onlyTrashed()->with(['project:id,name', 'assignee:id,name']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $tasks = $query->latest('deleted_at')->paginate(24)->withQueryString();

        return view('admin.tasks.trash', compact('tasks'));
    }

    public function restore(int $id)
    {
        $task = Task::onlyTrashed()->findOrFail($id);
        $task->restore();

        return back()->with('success', '"' . $task->title . '" has been restored.');
    }

    public function forceDelete(int $id)
    {
        $task = Task::onlyTrashed()->findOrFail($id);
        $title = $task->title;
        AuditLogger::log(
            'task.force_deleted',
            $task,
            'Task "' . $title . '" permanently deleted',
            ['task_id' => $task->id, 'task_title' => $title]
        );
        $task->forceDelete();

        return back()->with('success', '"' . $title . '" has been permanently deleted.');
    }

    public function reopen(Request $request, Task $task)
    {
        if (!in_array($task->status, ['approved', 'delivered', 'archived'])) {
            return back()->with('error', 'Only approved, delivered, or archived tasks can be reopened.');
        }

        $oldStatus = $task->status;
        $task->update(['status' => 'in_progress']);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_reopened',
            'note'     => 'Task reopened by ' . auth()->user()->name,
            'metadata' => [
                'old_status'       => $oldStatus,
                'new_status'       => 'in_progress',
                'reopened_by_id'   => auth()->id(),
                'reopened_by_name' => auth()->user()->name,
            ],
        ]);

        AuditLogger::log(
            'task.reopened',
            $task,
            'Task "' . $task->title . '" reopened (was ' . $oldStatus . ')',
            ['task_id' => $task->id, 'task_title' => $task->title, 'old_status' => $oldStatus]
        );

        if ($task->assignee && Setting::get('notify_on_reassign', '1') === '1') {
            $task->assignee->notify(new \App\Notifications\TaskReassigned($task, true));
        }

        // If the project was auto-completed, reopen it
        if ($task->project && $task->project->status === 'completed') {
            $task->project->update(['status' => 'active']);
        }

        return back()->with('success', 'Task "' . $task->title . '" has been reopened and is now In Progress.');
    }

    public function archive(Request $request, Task $task)
    {
        $task->update(['status' => 'archived']);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_archived',
            'note'     => 'Task archived by ' . auth()->user()->name,
            'metadata' => [
                'old_status'       => $task->getOriginal('status'),
                'new_status'       => 'archived',
                'archived_by_id'   => auth()->id(),
                'archived_by_name' => auth()->user()->name,
            ],
        ]);

        AuditLogger::log(
            'task.archived',
            $task,
            'Task "' . $task->title . '" archived',
            ['task_id' => $task->id, 'task_title' => $task->title]
        );

        $task->project?->autoComplete();

        return back()->with('success', 'Task archived.');
    }

    public function panel(Task $task): \Illuminate\Http\JsonResponse
    {
        $task->load([
            'project:id,name',
            'assignee:id,name,avatar',
            'creator:id,name',
            'reviewer:id,name',
            'logs' => fn($q) => $q->with('user:id,name')->orderBy('created_at'),
            'submissions' => fn($q) => $q->with(['user:id,name', 'reviewer:id,name'])->orderBy('version', 'desc'),
            'comments' => fn($q) => $q->with('user:id,name')->latest(),
            'socialPosts',
        ]);

        $statusMeta = [
            'draft'              => ['label'=>'Draft',            'color'=>'#6B7280','bg'=>'#F3F4F6'],
            'assigned'           => ['label'=>'Assigned',         'color'=>'#4F46E5','bg'=>'#EEF2FF'],
            'viewed'             => ['label'=>'Viewed',           'color'=>'#0369A1','bg'=>'#E0F2FE'],
            'in_progress'        => ['label'=>'In Progress',      'color'=>'#D97706','bg'=>'#FEF3C7'],
            'submitted'          => ['label'=>'In Review',        'color'=>'#7C3AED','bg'=>'#EDE9FE'],
            'revision_requested' => ['label'=>'Revision Requested','color'=>'#DC2626','bg'=>'#FEE2E2'],
            'approved'           => ['label'=>'Approved',         'color'=>'#059669','bg'=>'#D1FAE5'],
            'delivered'          => ['label'=>'Delivered',        'color'=>'#047857','bg'=>'#ECFDF5'],
            'archived'           => ['label'=>'Archived',         'color'=>'#6B7280','bg'=>'#F3F4F6'],
        ];
        $priorityMeta = [
            'high'   => ['label'=>'High',   'color'=>'#EF4444','bg'=>'#FEF2F2'],
            'medium' => ['label'=>'Medium', 'color'=>'#F59E0B','bg'=>'#FFFBEB'],
            'low'    => ['label'=>'Low',    'color'=>'#10B981','bg'=>'#ECFDF5'],
        ];
        $sm = $statusMeta[$task->status]   ?? ['label'=>ucfirst($task->status),'color'=>'#6B7280','bg'=>'#F3F4F6'];
        $pm = $priorityMeta[$task->priority] ?? null;

        $isOverdue = $task->deadline && $task->deadline->isPast()
            && !in_array($task->status, ['approved','delivered','archived']);

        return response()->json([
            'id'          => $task->id,
            'title'       => $task->title,
            'description' => $task->description,
            'status'      => $task->status,
            'statusLabel' => $sm['label'],
            'statusColor' => $sm['color'],
            'statusBg'    => $sm['bg'],
            'priority'    => $task->priority,
            'priorityMeta'=> $pm,
            'deadline'    => $task->deadline?->format('M d, Y'),
            'isOverdue'   => $isOverdue,
            'createdAt'   => $task->created_at->format('M d, Y · H:i'),
            'updatedAt'   => $task->updated_at->format('M d, Y · H:i'),
            'project'     => $task->project  ? ['name' => $task->project->name]  : null,
            'assignee'    => $task->assignee ? ['name' => $task->assignee->name, 'initials' => $this->initials($task->assignee->name)] : null,
            'creator'     => $task->creator  ? ['name' => $task->creator->name,  'initials' => $this->initials($task->creator->name)]  : null,
            'reviewer'    => $task->reviewer ? ['name' => $task->reviewer->name, 'initials' => $this->initials($task->reviewer->name)] : null,
            'taskUrl'     => route('admin.tasks.show', $task->id),
            'logs'        => $task->logs->sortByDesc('created_at')->values()->map(fn($l) => [
                'label'     => $l->actionLabel(),
                'style'     => $l->actionStyle(),
                'note'      => $l->note,
                'user'      => $l->user?->name,
                'createdAt' => $l->created_at->format('M d, Y · H:i'),
                'diffHumans'=> $l->created_at->diffForHumans(),
            ]),
            'submissions' => $task->submissions->map(fn($s) => [
                'version'    => $s->version,
                'status'     => $s->status,
                'note'       => $s->note,
                'adminNote'  => $s->admin_note,
                'fileUrl'    => $s->fileUrl(),
                'filename'   => $s->original_filename,
                'fileType'   => $this->fileType($s->original_filename),
                'user'       => $s->user?->name,
                'reviewer'   => $s->reviewer?->name,
                'reviewedAt' => $s->reviewed_at?->format('M d, Y · H:i'),
                'submittedAt'=> $s->created_at->format('M d, Y · H:i'),
            ]),
            'comments'    => $task->comments->map(fn($c) => [
                'body'      => $c->body,
                'user'      => $c->user?->name,
                'initials'  => $this->initials($c->user?->name ?? 'U'),
                'createdAt' => $c->created_at->format('M d, Y · H:i'),
                'diffHumans'=> $c->created_at->diffForHumans(),
            ]),
            'socialPosts' => $task->socialPosts->map(fn($sp) => [
                'platform' => $sp->platform,
                'postUrl'  => $sp->post_url,
                'caption'  => $sp->caption ?? null,
                'postedAt' => $sp->created_at->format('M d, Y · H:i'),
            ]),
        ]);
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
    }

    private function fileType(?string $filename): string
    {
        if (!$filename) return 'file';
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp'])) return 'image';
        if (in_array($ext, ['mp4','mov','avi','webm','mkv'])) return 'video';
        if ($ext === 'pdf') return 'pdf';
        return 'file';
    }

    public function reassign(Request $request, Task $task)
    {
        if (in_array($task->status, ['approved', 'delivered', 'archived'])) {
            return back()->with('error', 'This task is closed. Reopen it before reassigning.');
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ((int) $request->assigned_to === (int) $task->assigned_to) {
            return back()->with('error', 'Task is already assigned to that user.');
        }

        $oldAssignee = $task->assignee;
        $task->update(['assigned_to' => $request->assigned_to]);
        $newAssignee = User::find($request->assigned_to);

        $reason = trim($request->input('reason', ''));
        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'task_reassigned',
            'note'     => 'Reassigned from ' . ($oldAssignee->name ?? 'unknown') . ' to ' . ($newAssignee->name ?? 'unknown'),
            'metadata' => [
                'from_user_id'   => $oldAssignee?->id,
                'from_user_name' => $oldAssignee?->name,
                'to_user_id'     => $newAssignee?->id,
                'to_user_name'   => $newAssignee?->name,
                'reassigned_by'  => auth()->user()->name,
                'reason'         => $reason ?: null,
                'is_bulk'        => false,
            ],
        ]);

        TaskTransfer::create([
            'task_id'        => $task->id,
            'from_user_id'   => $oldAssignee?->id,
            'to_user_id'     => $newAssignee?->id,
            'transferred_by' => auth()->id(),
            'reason'         => $reason ?: null,
            'transferred_at' => now(),
        ]);

        AuditLogger::log(
            'task.reassigned',
            $task,
            'Task "' . $task->title . '" reassigned from ' . ($oldAssignee->name ?? 'unknown') . ' to ' . ($newAssignee->name ?? 'unknown'),
            [
                'task_id'        => $task->id,
                'task_title'     => $task->title,
                'from_user'      => $oldAssignee?->name,
                'to_user'        => $newAssignee?->name,
                'reason'         => $reason ?: null,
            ]
        );

        if ($newAssignee && Setting::get('notify_on_reassign', '1') === '1') {
            $newAssignee->notify(new TaskReassigned($task, true));
        }

        if ($oldAssignee && $oldAssignee->id !== (int) $request->assigned_to && Setting::get('notify_on_reassign', '1') === '1') {
            $oldAssignee->notify(new TaskReassigned($task, false));
        }

        return back()->with('success', 'Task reassigned to ' . ($newAssignee->name ?? 'user') . '.');
    }

    public function updateDeadline(Request $request, Task $task)
    {
        if (in_array($task->status, ['approved', 'delivered', 'archived'])) {
            return back()->with('error', 'Cannot change the deadline of a closed task.');
        }

        if ($task->project && $task->project->status === 'completed') {
            return back()->with('error', 'Cannot change the deadline — the project is already completed.');
        }

        $request->validate([
            'deadline' => 'required|date',
            'reason'   => 'nullable|string|max:500',
        ]);

        $oldDeadline = $task->deadline->format('M d, Y');
        $newDeadline = \Illuminate\Support\Carbon::parse($request->deadline);

        if ($newDeadline->toDateString() === $task->deadline->toDateString()) {
            return back()->with('error', 'The new deadline is the same as the current one.');
        }

        $task->update(['deadline' => $newDeadline]);

        $reason = trim($request->input('reason', ''));
        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'deadline_updated',
            'note'     => $reason ?: null,
            'metadata' => [
                'old_deadline'    => $oldDeadline,
                'new_deadline'    => $newDeadline->format('M d, Y'),
                'changed_by_name' => auth()->user()->name,
                'reason'          => $reason ?: null,
            ],
        ]);

        AuditLogger::log(
            'task.deadline_updated',
            $task,
            'Deadline changed from ' . $oldDeadline . ' to ' . $newDeadline->format('M d, Y'),
            ['task_id' => $task->id, 'old_deadline' => $oldDeadline, 'new_deadline' => $newDeadline->format('M d, Y')]
        );

        if ($task->assignee && Setting::get('notify_on_reassign', '1') === '1') {
            $task->assignee->notify(new \App\Notifications\TaskReassigned($task, true));
        }

        return back()->with('success', 'Deadline updated to ' . $newDeadline->format('M d, Y') . '.');
    }
}
