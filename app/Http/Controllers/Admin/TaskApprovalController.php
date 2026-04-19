<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskSubmission;
use App\Notifications\TaskApproved;
use App\Notifications\TaskRejected;
use Illuminate\Http\Request;

class TaskApprovalController extends Controller
{
    public function index()
    {
        $tasks = Task::where('status', 'submitted')
            ->with(['project', 'assignee', 'assignees', 'submissions' => fn($q) => $q->latest()])
            ->latest()
            ->paginate(20);

        return view('admin.approvals.index', compact('tasks'));
    }

    public function approve(Request $request, Task $task)
    {
        $request->validate(['note' => 'nullable|string|max:500']);

        $latestSub = TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->orderByDesc('version')
            ->first();

        $task->update(['status' => 'approved']);

        TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->update([
                'status'      => 'approved',
                'admin_note'  => $request->note,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_approved',
            'note'     => $request->note ? 'Approved: ' . $request->note : 'Approved by admin',
            'metadata' => [
                'old_status'         => 'submitted',
                'new_status'         => 'approved',
                'reviewer_id'        => auth()->id(),
                'reviewer_name'      => auth()->user()->name,
                'submission_version' => $latestSub?->version,
                'approval_note'      => $request->note,
            ],
        ]);

        if ($task->assignee) {
            $task->assignee->notify(new TaskApproved($task, $request->note));
        }

        return back()->with('success', 'Task approved.');
    }

    public function reject(Request $request, Task $task)
    {
        $request->validate(['note' => 'required|string|max:500']);

        $latestSub = TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->orderByDesc('version')
            ->first();

        $task->update(['status' => 'revision_requested']);

        TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->update([
                'status'      => 'rejected',
                'admin_note'  => $request->note,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        TaskLog::create([
            'task_id'  => $task->id,
            'user_id'  => auth()->id(),
            'action'   => 'status_updated_revision_requested',
            'note'     => 'Revision requested: ' . $request->note,
            'metadata' => [
                'old_status'         => 'submitted',
                'new_status'         => 'revision_requested',
                'reviewer_id'        => auth()->id(),
                'reviewer_name'      => auth()->user()->name,
                'submission_version' => $latestSub?->version,
                'rejection_reason'   => $request->note,
            ],
        ]);

        if ($task->assignee) {
            $task->assignee->notify(new TaskRejected($task, $request->note));
        }

        return back()->with('success', 'Revision requested — assignee has been notified.');
    }
}
