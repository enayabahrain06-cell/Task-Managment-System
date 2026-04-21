<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskLog;
use App\Models\User;
use App\Notifications\TaskCommentPosted;
use App\Notifications\TaskDelivered;
use App\Notifications\TaskReassigned;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
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
        if ($request->boolean('overdue')) {
            $query->whereNotNull('deadline')
                  ->where('deadline', '<', now())
                  ->whereNotIn('status', ['approved','delivered','archived']);
        }

        $tasks = $query->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->paginate(24)
            ->withQueryString();

        $projects = \App\Models\Project::orderBy('name')->get(['id','name']);

        $stats = [
            'total'       => Task::count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'overdue'     => Task::whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->whereNotIn('status', ['approved','delivered','archived'])
                ->count(),
            'done'        => Task::whereIn('status', ['approved','delivered'])->count(),
        ];

        return view('admin.tasks.index', compact('tasks', 'projects', 'stats'));
    }

    public function show(Task $task)
    {
        $task->load('project', 'assignee', 'assignees', 'reviewer', 'creator', 'logs.user', 'submissions.user', 'submissions.reviewer', 'comments.user', 'transfers.fromUser', 'transfers.toUser', 'transfers.transferredBy');
        $users = User::whereIn('role', ['user', 'manager'])->orderBy('name')->get();
        return view('admin.tasks.show', compact('task', 'users'));
    }

    public function comment(Request $request, Task $task)
    {
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
            'note'     => Str::limit($request->body, 120),
            'metadata' => ['comment_id' => $comment->id, 'author_role' => 'admin'],
        ]);

        $comment->load('user');

        if ($task->assignee) {
            $task->assignee->notify(new TaskCommentPosted($task, $comment));
        }

        return back()->with('success', 'Comment posted.');
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

        if ($task->assignee) {
            $task->assignee->notify(new TaskDelivered($task, $request->note));
        }

        return back()->with('success', 'Task marked as delivered — ' . ($task->assignee->name ?? 'assignee') . ' has been notified.');
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

        return back()->with('success', 'Task archived.');
    }

    public function reassign(Request $request, Task $task)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ((int) $request->assigned_to === (int) $task->assigned_to) {
            return back()->with('error', 'Task is already assigned to that user.');
        }

        $oldAssignee = $task->assignee;
        $task->update(['assigned_to' => $request->assigned_to]);
        $newAssignee = User::find($request->assigned_to);

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
                'is_bulk'        => false,
            ],
        ]);

        if ($newAssignee) {
            $newAssignee->notify(new TaskReassigned($task, true));
        }

        if ($oldAssignee && $oldAssignee->id !== (int) $request->assigned_to) {
            $oldAssignee->notify(new TaskReassigned($task, false));
        }

        return back()->with('success', 'Task reassigned to ' . ($newAssignee->name ?? 'user') . '.');
    }
}
