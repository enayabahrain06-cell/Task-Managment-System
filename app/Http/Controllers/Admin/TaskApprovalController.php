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
        $tasks = Task::where('status', 'pending_approval')
            ->with(['project', 'assignee', 'submissions' => fn($q) => $q->latest()])
            ->latest()
            ->paginate(20);

        return view('admin.approvals.index', compact('tasks'));
    }

    public function approve(Request $request, Task $task)
    {
        $request->validate(['note' => 'nullable|string|max:500']);

        $task->update(['status' => 'completed']);

        TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->update([
                'status'      => 'approved',
                'admin_note'  => $request->note,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action'  => 'status_updated_completed',
            'note'    => $request->note ? 'Approved: ' . $request->note : 'Approved by admin',
        ]);

        if ($task->assignee) {
            $task->assignee->notify(new TaskApproved($task, $request->note));
        }

        return back()->with('success', 'Task approved and marked as completed.');
    }

    public function reject(Request $request, Task $task)
    {
        $request->validate(['note' => 'required|string|max:500']);

        $task->update(['status' => 'in_progress']);

        TaskSubmission::where('task_id', $task->id)
            ->where('status', 'submitted')
            ->update([
                'status'      => 'rejected',
                'admin_note'  => $request->note,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action'  => 'status_updated_in_progress',
            'note'    => 'Rejected: ' . $request->note,
        ]);

        if ($task->assignee) {
            $task->assignee->notify(new TaskRejected($task, $request->note));
        }

        return back()->with('success', 'Task rejected — user notified to revise.');
    }
}
