<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use App\Notifications\TaskTransferred;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('tasks');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'    => User::count(),
            'active'   => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'archived' => User::where('status', 'archived')->count(),
            'admins'   => User::where('role', 'admin')->count(),
            'managers' => User::where('role', 'manager')->count(),
        ];

        $allRoles = Role::ordered();

        return view('admin.users.index', compact('users', 'stats', 'allRoles'));
    }

    public function create()
    {
        return redirect()->route('team.index', ['view' => 'manage']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users',
            'password'  => 'required|string|min:8|confirmed',
            'role'      => ['required', Rule::in(Role::pluck('name'))],
            'phone'     => 'nullable|string|max:30',
            'job_title' => 'nullable|string|max:80',
            'status'    => 'nullable|in:active,inactive',
            'avatar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $allKeys = array_keys(User::ALL_PERMISSIONS);
        $isPrivileged = in_array($request->role, ['admin', 'manager']);
        if ($request->has('_perms_sent') && !$isPrivileged) {
            $submitted = $request->input('permissions', []);
            $perms = empty($submitted) ? null : array_values(array_intersect($submitted, $allKeys));
        } else {
            $perms = null;
        }

        $data = [
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            'phone'       => $request->phone,
            'job_title'   => $request->job_title,
            'status'      => $request->status ?? 'active',
            'permissions' => $perms,
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create($data);

        AuditLogger::log(
            'user.created',
            $user,
            'Account created for ' . $user->name . ' (' . $user->email . ') with role: ' . $user->role,
            [
                'name'      => $user->name,
                'email'     => $user->email,
                'role'      => $user->role,
                'job_title' => $user->job_title,
                'status'    => $user->status,
            ]
        );

        return back()->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return redirect()->route('team.index', ['view' => 'manage']);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,' . $user->id,
            'role'      => ['required', Rule::in(Role::pluck('name'))],
            'password'  => 'nullable|string|min:8|confirmed',
            'phone'     => 'nullable|string|max:30',
            'job_title' => 'nullable|string|max:80',
            'status'    => 'nullable|in:active,inactive',
            'avatar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Capture before-state for audit diff
        $changes  = [];
        $oldRole   = $user->role;
        $oldStatus = $user->status;

        $allKeys = array_keys(User::ALL_PERMISSIONS);
        $isPrivileged = in_array($request->role, ['admin', 'manager']);
        if ($request->has('_perms_sent') && !$isPrivileged) {
            $submitted = $request->input('permissions', []);
            $perms = empty($submitted) ? null : array_values(array_intersect($submitted, $allKeys));
        } else {
            $perms = null;
        }

        $data = [
            'name'        => $request->name,
            'email'       => $request->email,
            'role'        => $request->role,
            'phone'       => $request->phone,
            'job_title'   => $request->job_title,
            'status'      => $request->status ?? 'active',
            'permissions' => $perms,
        ];

        foreach (['name', 'email', 'role', 'phone', 'job_title'] as $field) {
            if ($user->$field !== $data[$field]) {
                $changes[$field] = ['from' => $user->$field, 'to' => $data[$field]];
            }
        }
        $newStatus = $data['status'];
        if ($oldStatus !== $newStatus) {
            $changes['status'] = ['from' => $oldStatus, 'to' => $newStatus];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
            $changes['avatar'] = ['from' => 'previous', 'to' => 'updated'];
        }

        $passwordChanged = false;
        if ($request->filled('password')) {
            $data['password']  = Hash::make($request->password);
            $passwordChanged   = true;
        }

        $user->update($data);

        // Role change audit
        if ($oldRole !== $request->role) {
            AuditLogger::log(
                'user.role_changed',
                $user,
                $user->name . '\'s role changed from ' . $oldRole . ' to ' . $request->role,
                ['from_role' => $oldRole, 'to_role' => $request->role, 'user_name' => $user->name]
            );
        }

        // Status change audit
        if ($oldStatus !== $newStatus) {
            $action = $newStatus === 'inactive' ? 'user.deactivated' : 'user.reactivated';
            AuditLogger::log(
                $action,
                $user,
                $user->name . '\'s account was ' . ($newStatus === 'inactive' ? 'deactivated' : 'reactivated'),
                ['user_name' => $user->name, 'user_email' => $user->email, 'new_status' => $newStatus]
            );
        }

        // Password change audit
        if ($passwordChanged) {
            AuditLogger::log(
                'user.password_changed',
                $user,
                'Password changed for ' . $user->name,
                ['user_name' => $user->name, 'user_email' => $user->email]
            );
        }

        // General update audit (if other fields changed)
        $nonSensitiveChanges = array_diff_key($changes, array_flip(['status', 'avatar']));
        if (!empty($nonSensitiveChanges) || isset($changes['avatar'])) {
            AuditLogger::log(
                'user.updated',
                $user,
                'Profile updated for ' . $user->name,
                ['changes' => $changes, 'user_name' => $user->name]
            );
        }

        return back()->with('success', 'User updated successfully.');
    }

    public function updatePermissions(Request $request, User $user)
    {
        $allKeys = array_keys(User::ALL_PERMISSIONS);
        $submitted = $request->input('permissions', []);

        // null = unrestricted (all access), array = specific allowed list
        $perms = $request->boolean('unrestricted')
            ? null
            : array_values(array_intersect((array) $submitted, $allKeys));

        $user->update(['permissions' => $perms]);

        return response()->json(['ok' => true, 'permissions' => $perms]);
    }

    public function destroy(User $user)
    {
        $taskCount = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['completed', 'delivered'])
            ->count();

        AuditLogger::log(
            'user.deleted',
            null,
            'Account deleted for ' . $user->name . ' (' . $user->email . ')',
            [
                'user_name'            => $user->name,
                'user_email'           => $user->email,
                'role'                 => $user->role,
                'unfinished_tasks'     => $taskCount,
            ]
        );

        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    public function transferTasks(Request $request, User $user)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id|not_in:' . $user->id,
            'reason'     => 'nullable|string|max:500',
        ]);

        $toUser = User::findOrFail($request->to_user_id);

        $doneStatuses = ['approved', 'delivered', 'archived'];

        // Collect from both assigned_to and pivot
        $assignedToIds = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', $doneStatuses)
            ->pluck('id');

        $pivotIds = \Illuminate\Support\Facades\DB::table('task_assignees')
            ->where('user_id', $user->id)
            ->pluck('task_id');

        $taskIds = $assignedToIds->merge($pivotIds)->unique();

        $tasks = Task::whereIn('id', $taskIds)
            ->whereNotIn('status', $doneStatuses)
            ->get();

        if ($tasks->isEmpty()) {
            return back()->with('error', 'No unfinished tasks to transfer from ' . $user->name . '.');
        }

        $reason = $request->input('reason', 'Bulk task transfer by admin.');
        $now    = now();

        foreach ($tasks as $task) {
            if ($task->assigned_to === $user->id) {
                $task->update(['assigned_to' => $toUser->id]);
            }

            // Move pivot entry
            $existingRole = \Illuminate\Support\Facades\DB::table('task_assignees')
                ->where('task_id', $task->id)
                ->where('user_id', $user->id)
                ->value('role_in_task');

            \Illuminate\Support\Facades\DB::table('task_assignees')
                ->where('task_id', $task->id)
                ->where('user_id', $user->id)
                ->delete();

            $alreadyAssigned = \Illuminate\Support\Facades\DB::table('task_assignees')
                ->where('task_id', $task->id)
                ->where('user_id', $toUser->id)
                ->exists();

            if (!$alreadyAssigned) {
                \Illuminate\Support\Facades\DB::table('task_assignees')->insert([
                    'task_id'      => $task->id,
                    'user_id'      => $toUser->id,
                    'role_in_task' => $existingRole,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }

            \App\Models\TaskTransfer::create([
                'task_id'        => $task->id,
                'from_user_id'   => $user->id,
                'to_user_id'     => $toUser->id,
                'transferred_by' => auth()->id(),
                'reason'         => $reason,
                'transferred_at' => $now,
            ]);

            TaskLog::create([
                'task_id'  => $task->id,
                'user_id'  => auth()->id(),
                'action'   => 'task_transferred',
                'note'     => 'Transferred from ' . $user->name . ' → ' . $toUser->name . '.',
                'metadata' => [
                    'from_user_id'   => $user->id,
                    'from_user_name' => $user->name,
                    'to_user_id'     => $toUser->id,
                    'to_user_name'   => $toUser->name,
                    'performed_by'   => auth()->user()->name,
                    'reason'         => $reason,
                    'is_bulk'        => true,
                ],
            ]);
        }

        AuditLogger::log(
            'tasks.bulk_transferred',
            $user,
            $tasks->count() . ' tasks transferred from ' . $user->name . ' to ' . $toUser->name,
            [
                'from_user_id'   => $user->id,
                'from_user_name' => $user->name,
                'to_user_id'     => $toUser->id,
                'to_user_name'   => $toUser->name,
                'task_count'     => $tasks->count(),
                'task_ids'       => $tasks->pluck('id')->toArray(),
                'reason'         => $reason,
            ]
        );

        $toUser->notify(new TaskTransferred($tasks->count(), $user));

        return back()->with('success', $tasks->count() . ' task(s) transferred from ' . $user->name . ' to ' . $toUser->name . '.');
    }
}
