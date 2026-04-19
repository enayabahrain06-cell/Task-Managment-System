<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskTransfer;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OffboardingController extends Controller
{
    private const DONE_STATUSES = ['approved', 'delivered', 'archived'];

    public function show(User $user)
    {
        abort_if($user->status === 'archived', 422, 'User is already archived.');
        abort_if($user->id === auth()->id(), 422, 'You cannot offboard yourself.');

        // Collect unfinished tasks from both assigned_to and pivot
        $assignedToIds = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', self::DONE_STATUSES)
            ->pluck('id');

        $pivotIds = DB::table('task_assignees')
            ->where('user_id', $user->id)
            ->pluck('task_id');

        $allIds = $assignedToIds->merge($pivotIds)->unique();

        $unfinishedTasks = Task::whereIn('id', $allIds)
            ->whereNotIn('status', self::DONE_STATUSES)
            ->with(['project', 'assignees'])
            ->orderBy('deadline')
            ->get();

        $recipients = User::where('id', '!=', $user->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'job_title']);

        return view('admin.users.offboard', compact('user', 'unfinishedTasks', 'recipients'));
    }

    public function process(Request $request, User $user)
    {
        abort_if($user->status === 'archived', 422, 'User is already archived.');
        abort_if($user->id === auth()->id(), 422, 'You cannot offboard yourself.');

        $data = $request->validate([
            'action'     => 'required|in:archive,deactivate',
            'reason'     => 'required|string|min:10|max:1000',
            'to_user_id' => ['nullable', 'exists:users,id', function ($attr, $val, $fail) use ($user) {
                if ($val == $user->id) {
                    $fail('Cannot transfer tasks back to the same user.');
                }
            }],
        ]);

        $toUser           = null;
        $transferredCount = 0;

        if (!empty($data['to_user_id'])) {
            $toUser = User::findOrFail($data['to_user_id']);

            $assignedToIds = Task::where('assigned_to', $user->id)
                ->whereNotIn('status', self::DONE_STATUSES)
                ->pluck('id');

            $pivotIds = DB::table('task_assignees')
                ->where('user_id', $user->id)
                ->pluck('task_id');

            $taskIds = $assignedToIds->merge($pivotIds)->unique();

            $tasks = Task::whereIn('id', $taskIds)
                ->whereNotIn('status', self::DONE_STATUSES)
                ->get();

            $now = now();

            foreach ($tasks as $task) {
                // Reassign primary assignee
                if ($task->assigned_to === $user->id) {
                    $task->update(['assigned_to' => $toUser->id]);
                }

                // Move pivot entry: preserve role, swap user
                $existingRole = DB::table('task_assignees')
                    ->where('task_id', $task->id)
                    ->where('user_id', $user->id)
                    ->value('role_in_task');

                DB::table('task_assignees')
                    ->where('task_id', $task->id)
                    ->where('user_id', $user->id)
                    ->delete();

                $alreadyAssigned = DB::table('task_assignees')
                    ->where('task_id', $task->id)
                    ->where('user_id', $toUser->id)
                    ->exists();

                if (!$alreadyAssigned) {
                    DB::table('task_assignees')->insert([
                        'task_id'      => $task->id,
                        'user_id'      => $toUser->id,
                        'role_in_task' => $existingRole,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }

                // Permanent transfer record
                TaskTransfer::create([
                    'task_id'        => $task->id,
                    'from_user_id'   => $user->id,
                    'to_user_id'     => $toUser->id,
                    'transferred_by' => auth()->id(),
                    'reason'         => $data['reason'],
                    'transferred_at' => $now,
                ]);

                // Task-level log entry (visible in task history)
                TaskLog::create([
                    'task_id'  => $task->id,
                    'user_id'  => auth()->id(),
                    'action'   => 'task_transferred',
                    'note'     => 'Transferred from ' . $user->name . ' → ' . $toUser->name . ' (offboarding).',
                    'metadata' => [
                        'from_user_id'   => $user->id,
                        'from_user_name' => $user->name,
                        'to_user_id'     => $toUser->id,
                        'to_user_name'   => $toUser->name,
                        'performed_by'   => auth()->user()->name,
                        'reason'         => $data['reason'],
                        'offboarding'    => true,
                    ],
                ]);

                $transferredCount++;
            }

            // Auto-add replacement as project member
            $projectIds = Task::whereIn('id', $taskIds)->pluck('project_id')->unique()->filter();
            foreach ($projectIds as $pid) {
                DB::table('project_user')->insertOrIgnore([
                    'project_id' => $pid,
                    'user_id'    => $toUser->id,
                ]);
            }

            if ($transferredCount > 0) {
                $toUser->notify(new \App\Notifications\TaskTransferred($toUser, $user, $transferredCount));
            }
        }

        // Archive or deactivate
        $newStatus  = $data['action'] === 'archive' ? 'archived' : 'inactive';
        $updateData = ['status' => $newStatus];

        if ($newStatus === 'archived') {
            $updateData['archived_at'] = now();
            $updateData['archived_by'] = auth()->id();
        }

        $user->update($updateData);

        // Invalidate active sessions
        DB::table('users')->where('id', $user->id)->update(['remember_token' => null]);

        AuditLogger::log(
            'user.offboarded',
            $user,
            $user->name . ' was ' . ($newStatus === 'archived' ? 'archived' : 'deactivated')
                . ' by ' . auth()->user()->name
                . ($toUser ? ' — ' . $transferredCount . ' task(s) transferred to ' . $toUser->name : ''),
            [
                'action'            => $data['action'],
                'reason'            => $data['reason'],
                'offboarded_name'   => $user->name,
                'offboarded_email'  => $user->email,
                'offboarded_role'   => $user->role,
                'tasks_transferred' => $transferredCount,
                'transferred_to'    => $toUser?->name,
                'performed_by'      => auth()->user()->name,
            ]
        );

        $statusLabel = $newStatus === 'archived' ? 'archived' : 'deactivated';
        $msg = $user->name . ' has been ' . $statusLabel . '.';
        if ($transferredCount > 0) {
            $msg .= ' ' . $transferredCount . ' unfinished task(s) transferred to ' . $toUser->name . '.';
        }

        return redirect()->route('admin.users.index')->with('success', $msg);
    }
}
