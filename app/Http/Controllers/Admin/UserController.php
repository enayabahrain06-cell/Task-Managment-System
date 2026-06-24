<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskTransfer;
use App\Models\User;
use App\Notifications\TaskTransferred;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
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
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(name) = ?', [strtolower($value)])->exists()) {
                        $fail('A user with this name already exists.');
                    }
                },
            ],
            'username'    => 'nullable|string|max:60|unique:users|alpha_dash',
            'email' => [
                'required', 'email', 'max:255',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(email) = ?', [strtolower($value)])->exists()) {
                        $fail('A user with this email address already exists.');
                    }
                },
            ],
            'password'    => 'required|string|min:8|confirmed',
            'role'        => ['required', Rule::in(Role::pluck('name'))],
            'phone'       => 'nullable|string|max:30',
            'job_title'   => 'nullable|string|max:80',
            'nationality' => 'nullable|string|max:80',
            'status'      => 'nullable|in:active,inactive',
            'avatar'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
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
            'name'         => $request->name,
            'username'     => $request->username ?: null,
            'email'        => strtolower($request->email),
            'password'     => $request->password,
            'role'         => $request->role,
            'phone'        => $request->phone,
            'job_title'    => $request->job_title,
            'nationality'  => $request->nationality,
            'status'       => $request->status ?? 'active',
            'permissions'  => $perms,
            'mfa_required' => $request->boolean('mfa_required'),
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
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if (auth()->user()->role === 'manager' && $user->role === 'admin' && Setting::get('manager_can_edit_admin', '0') !== '1') {
            return back()->with('error', 'Managers cannot edit administrator accounts.');
        }

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($user) {
                    if (User::whereRaw('LOWER(name) = ?', [strtolower($value)])->where('id', '!=', $user->id)->exists()) {
                        $fail('A user with this name already exists.');
                    }
                },
            ],
            'username'    => 'nullable|string|max:60|alpha_dash|unique:users,username,' . $user->id,
            'email' => [
                'required', 'email', 'max:255',
                function ($attribute, $value, $fail) use ($user) {
                    if (User::whereRaw('LOWER(email) = ?', [strtolower($value)])->where('id', '!=', $user->id)->exists()) {
                        $fail('A user with this email address already exists.');
                    }
                },
            ],
            'role'        => ['required', Rule::in(Role::pluck('name'))],
            'password'    => 'nullable|string|min:8|confirmed',
            'phone'       => 'nullable|string|max:30',
            'job_title'   => 'nullable|string|max:80',
            'nationality' => 'nullable|string|max:80',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
            'status'      => 'nullable|in:active,inactive',
            'avatar'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
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
            'username'    => $request->username ?: null,
            'email'       => strtolower($request->email),
            'role'        => $request->role,
            'phone'       => $request->phone,
            'job_title'   => $request->job_title,
            'nationality' => $request->nationality,
            'hourly_rate' => $request->hourly_rate ?: null,
            'status'      => $request->status ?? 'active',
            'permissions' => $perms,
        ];

        foreach (['name', 'username', 'email', 'role', 'phone', 'job_title', 'nationality'] as $field) {
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
            $data['password'] = $request->password;
            $passwordChanged  = true;
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
        if (auth()->user()->role === 'manager' && $user->role === 'admin' && Setting::get('manager_can_edit_admin', '0') !== '1') {
            return response()->json(['error' => 'Managers cannot edit administrator permissions.'], 403);
        }

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
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot archive your own account.');
        }

        if (auth()->user()->role === 'manager' && $user->role === 'admin' && Setting::get('manager_can_edit_admin', '0') !== '1') {
            return back()->with('error', 'Managers cannot archive administrator accounts.');
        }

        $user->update([
            'status'      => 'archived',
            'archived_at' => now(),
            'archived_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'user.archived',
            $user,
            'Account archived for ' . $user->name . ' (' . $user->email . ')',
            ['user_name' => $user->name, 'user_email' => $user->email, 'role' => $user->role]
        );

        return back()->with('success', $user->name . ' has been moved to Former Employees.');
    }

    public function restore(User $user)
    {
        $user->update([
            'status'      => 'active',
            'archived_at' => null,
            'archived_by' => null,
        ]);

        AuditLogger::log(
            'user.restored',
            $user,
            'Account restored for ' . $user->name . ' (' . $user->email . ')',
            ['user_name' => $user->name, 'user_email' => $user->email]
        );

        return back()->with('success', $user->name . ' has been restored to the team.');
    }

    public function permanentDelete(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if (auth()->user()->role === 'manager' && $user->role === 'admin' && Setting::get('manager_can_edit_admin', '0') !== '1') {
            return back()->with('error', 'Managers cannot delete administrator accounts.');
        }

        $name  = $user->name;
        $email = $user->email;

        // Null out task primary assignments so tasks aren't orphaned
        \Illuminate\Support\Facades\DB::table('tasks')
            ->where('assigned_to', $user->id)
            ->update(['assigned_to' => null, 'status' => 'draft']);

        \Illuminate\Support\Facades\DB::table('tasks')
            ->where('social_assigned_to', $user->id)
            ->update(['social_assigned_to' => null]);

        // Remove pivot entries not covered by cascades
        \Illuminate\Support\Facades\DB::table('project_user')->where('user_id', $user->id)->delete();
        \Illuminate\Support\Facades\DB::table('message_group_users')->where('user_id', $user->id)->delete();

        // Delete avatar file
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        AuditLogger::log(
            'user.permanently_deleted',
            null,
            'Account permanently deleted for ' . $name . ' (' . $email . ')',
            ['user_name' => $name, 'user_email' => $email]
        );

        $user->delete();

        return redirect()->route('team.index')->with('success', $name . ' has been permanently deleted from the system.');
    }

    public function requireMfa(User $user)
    {
        if ($user->mfa_enabled) {
            if (request()->expectsJson()) {
                return response()->json(['ok' => false, 'message' => "{$user->name} already has MFA enabled."]);
            }
            return back()->with('info', "{$user->name} already has MFA enabled.");
        }

        $user->update(['mfa_required' => true]);

        AuditLogger::log(
            'user.mfa_required',
            $user,
            "MFA setup required for {$user->name} by admin",
            ['admin_id' => auth()->id()]
        );

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'message' => "MFA setup required — {$user->name} will be prompted on next login.", 'mfa_enabled' => false, 'mfa_required' => true]);
        }
        return back()->with('success', "MFA setup is now required for {$user->name}. They will be prompted on next login.");
    }

    public function cancelMfaRequirement(User $user)
    {
        $user->update(['mfa_required' => false]);

        AuditLogger::log(
            'user.mfa_requirement_cancelled',
            $user,
            "MFA requirement removed for {$user->name} by admin",
            ['admin_id' => auth()->id()]
        );

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'message' => "MFA requirement removed for {$user->name}.", 'mfa_enabled' => false, 'mfa_required' => false]);
        }
        return back()->with('success', "MFA requirement removed for {$user->name}.");
    }

    public function resetMfa(User $user)
    {
        $user->update([
            'mfa_enabled'        => false,
            'mfa_secret'         => null,
            'mfa_recovery_codes' => null,
            'mfa_required'       => true,
        ]);

        AuditLogger::log(
            'user.mfa_reset',
            $user,
            "MFA reset for {$user->name} — required to re-enroll",
            ['admin_id' => auth()->id()]
        );

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'message' => "MFA reset — {$user->name} must re-enroll on next login.", 'mfa_enabled' => false, 'mfa_required' => true]);
        }
        return back()->with('success', "MFA has been reset for {$user->name}. They must re-enroll on next login.");
    }

    public function disableMfa(User $user)
    {
        if (! $user->mfa_enabled) {
            if (request()->expectsJson()) {
                return response()->json(['ok' => false, 'message' => "{$user->name} does not have MFA enabled."]);
            }
            return back()->with('info', "{$user->name} does not have MFA enabled.");
        }

        $user->update([
            'mfa_enabled'        => false,
            'mfa_secret'         => null,
            'mfa_recovery_codes' => null,
        ]);

        AuditLogger::log(
            'user.mfa_disabled',
            $user,
            "MFA disabled for {$user->name} by admin",
            ['admin_id' => auth()->id()]
        );

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'message' => "MFA disabled for {$user->name}. They can now log in with password only.", 'mfa_enabled' => false, 'mfa_required' => false]);
        }
        return back()->with('success', "MFA has been disabled for {$user->name}. They can now log in with their password only.");
    }

    public function hold(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot hold your own account.');
        }

        if (auth()->user()->role === 'manager' && $user->role === 'admin' && Setting::get('manager_can_edit_admin', '0') !== '1') {
            return back()->with('error', 'Managers cannot put administrator accounts on hold.');
        }

        $wasHeld = $user->status === 'inactive';
        $user->update(['status' => $wasHeld ? 'active' : 'inactive']);

        AuditLogger::log(
            $wasHeld ? 'user.released' : 'user.held',
            $user,
            ($wasHeld ? 'Account released for ' : 'Account put on hold for ') . $user->name,
            ['status' => $user->status]
        );

        return back()->with('success', $wasHeld
            ? $user->name . ' account has been released.'
            : $user->name . ' account is now on hold. They cannot log in.'
        );
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

        if (Setting::get('notify_on_transfer', '1') === '1') {
            $toUser->notify(new TaskTransferred($tasks->count(), $user));
        }

        return back()->with('success', $tasks->count() . ' task(s) transferred from ' . $user->name . ' to ' . $toUser->name . '.');
    }

    public function viewDashboard(User $user)
    {
        if (auth()->user()->role === 'manager' && $user->role === 'admin') {
            return redirect()->route('team.index')->with('error', 'You do not have permission to view an administrator\'s dashboard.');
        }

        $doneStatuses   = ['approved', 'delivered', 'archived'];
        $activeStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'submitted', 'revision_requested'];
        $isAdminOrManager = in_array($user->role, ['admin', 'manager']);

        if ($isAdminOrManager) {
            // Admin/Manager: stats based on tasks they created
            $allTasks = Task::where('created_by', $user->id)->with('project')->get();

            $total      = $allTasks->count();
            $completed  = $allTasks->whereIn('status', $doneStatuses)->count();
            $inProgress = $allTasks->where('status', 'in_progress')->count();
            $pending    = $allTasks->whereIn('status', ['draft', 'assigned', 'viewed'])->count();
            $inReview   = $allTasks->where('status', 'submitted')->count();
            $overdue    = $allTasks->filter(
                fn($t) => $t->deadline && $t->deadline->isPast() && in_array($t->status, $activeStatuses)
            )->count();

            $nativeTotal      = $total;
            $nativeCompleted  = $completed;
            $rate             = $total > 0 ? round($completed / $total * 100) : 0;
            $inheritedCount   = 0;
            $receivedTotal    = 0;
            $receivedCompleted = 0;

            $tasks = $allTasks->sortBy(function ($t) use ($doneStatuses) {
                if (in_array($t->status, $doneStatuses))    return '5_' . ($t->deadline?->format('Y-m-d') ?? '9999');
                if ($t->status === 'submitted')              return '3_' . ($t->deadline?->format('Y-m-d') ?? '9999');
                if ($t->deadline && $t->deadline->isPast()) return '1_' . $t->deadline->format('Y-m-d');
                if ($t->status === 'in_progress')            return '2_' . ($t->deadline?->format('Y-m-d') ?? '9999');
                return '4_' . ($t->deadline?->format('Y-m-d') ?? '9999');
            })->values()->map(function ($t) {
                $t->is_inherited  = false;
                $t->is_reassigned = false;
                $t->is_received   = false;
                $t->from_user     = null;
                $t->is_social     = false;
                return $t;
            });

            $upcomingTasks = $allTasks
                ->filter(fn($t) => $t->deadline && $t->deadline->isFuture() && !in_array($t->status, $doneStatuses))
                ->sortBy('deadline')
                ->take(4);

            $involvedProjectIds = \App\Models\Project::where('created_by', $user->id)->pluck('id');
            $completedTasks = $allTasks->whereIn('status', $doneStatuses)->sortByDesc('updated_at')->values();
        } else {
            // Regular user: mirrors User DashboardController exactly
            $allTasks = Task::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                      ->from('task_assignees')
                      ->whereColumn('task_assignees.task_id', 'tasks.id')
                      ->where('task_assignees.user_id', $user->id));
            })->with('project')->get();

            $inheritedIds = TaskTransfer::where('to_user_id', $user->id)->pluck('task_id')->unique();

            $reassignedLogsToUser = TaskLog::where('action', 'task_reassigned')
                ->whereIn('task_id', $allTasks->pluck('id'))
                ->get()
                ->filter(fn($log) => ($log->metadata['to_user_id'] ?? null) == $user->id)
                ->keyBy('task_id');

            $receivedFromOthersIds = $inheritedIds->merge($reassignedLogsToUser->keys())->unique();
            $nativeTasks   = $allTasks->whereNotIn('id', $receivedFromOthersIds->toArray());
            $receivedTasks = $allTasks->whereIn('id', $receivedFromOthersIds->toArray());

            $total      = $allTasks->count();
            $completed  = $allTasks->whereIn('status', $doneStatuses)->count();
            $inProgress = $allTasks->where('status', 'in_progress')->count();
            $pending    = $allTasks->whereIn('status', ['draft', 'assigned', 'viewed'])->count();
            $inReview   = $allTasks->where('status', 'submitted')->count();
            $overdue    = $allTasks->filter(
                fn($t) => $t->deadline && $t->deadline->isPast() && in_array($t->status, $activeStatuses)
            )->count();

            $nativeTotal       = $nativeTasks->count();
            $nativeCompleted   = $nativeTasks->whereIn('status', $doneStatuses)->count();
            $rate              = $nativeTotal > 0 ? round($nativeCompleted / $nativeTotal * 100) : 0;
            $inheritedCount    = $receivedFromOthersIds->count();
            $receivedTotal     = $receivedTasks->count();
            $receivedCompleted = $receivedTasks->whereIn('status', $doneStatuses)->count();

            // Active (non-done) tasks only — matches User DashboardController
            $tasks = $allTasks->filter(fn($t) => !in_array($t->status, $doneStatuses))
                ->sortBy(function ($t) {
                    if ($t->status === 'submitted')              return '3_' . ($t->deadline?->format('Y-m-d') ?? '9999');
                    if ($t->deadline && $t->deadline->isPast()) return '1_' . $t->deadline->format('Y-m-d');
                    if ($t->status === 'in_progress')            return '2_' . ($t->deadline?->format('Y-m-d') ?? '9999');
                    return '4_' . ($t->deadline?->format('Y-m-d') ?? '9999');
                })->values()->map(function ($t) use ($inheritedIds, $reassignedLogsToUser, $receivedFromOthersIds) {
                    $t->is_inherited  = $inheritedIds->contains($t->id);
                    $t->is_reassigned = $reassignedLogsToUser->has($t->id);
                    $t->from_user     = $reassignedLogsToUser->get($t->id)?->metadata['from_user_name'] ?? null;
                    $t->is_received   = $receivedFromOthersIds->contains($t->id);
                    $t->is_social     = false;
                    return $t;
                });

            $pendingSocialTasks = Task::where('social_assigned_to', $user->id)
                ->whereNull('social_posted_at')
                ->with('project')
                ->get()
                ->map(function ($t) {
                    $t->is_inherited  = false;
                    $t->is_reassigned = false;
                    $t->is_received   = false;
                    $t->from_user     = null;
                    $t->is_social     = true;
                    return $t;
                });
            $tasks = $tasks->merge($pendingSocialTasks)->values();

            // Completed tasks with social-completed included
            $completedTasks = $allTasks->filter(fn($t) => in_array($t->status, $doneStatuses))
                ->map(function ($t) use ($inheritedIds, $reassignedLogsToUser, $receivedFromOthersIds) {
                    $t->is_inherited  = $inheritedIds->contains($t->id);
                    $t->is_reassigned = $reassignedLogsToUser->has($t->id);
                    $t->from_user     = $reassignedLogsToUser->get($t->id)?->metadata['from_user_name'] ?? null;
                    $t->is_received   = $receivedFromOthersIds->contains($t->id);
                    $t->is_social     = false;
                    return $t;
                });

            $existingIds = $allTasks->pluck('id');
            $socialCompletedTasks = Task::where('social_assigned_to', $user->id)
                ->whereNotNull('social_posted_at')
                ->whereNotIn('id', $existingIds)
                ->with('project')
                ->get()
                ->map(function ($t) {
                    $t->is_inherited  = false;
                    $t->is_reassigned = false;
                    $t->from_user     = null;
                    $t->is_received   = false;
                    $t->is_social     = true;
                    return $t;
                });

            $completedTasks = $completedTasks->merge($socialCompletedTasks)
                ->sortByDesc('updated_at')
                ->values();

            $upcomingTasks = $allTasks
                ->filter(fn($t) => $t->deadline && $t->deadline->isFuture() && !in_array($t->status, $doneStatuses))
                ->sortBy('deadline')
                ->take(4);

            $pivotProjectIds = Task::whereExists(fn($sub) => $sub->selectRaw('1')
                    ->from('task_assignees')
                    ->whereColumn('task_assignees.task_id', 'tasks.id')
                    ->where('task_assignees.user_id', $user->id))
                ->whereNotNull('project_id')
                ->pluck('project_id');

            $involvedProjectIds = $user->projects()->pluck('projects.id')
                ->merge(Task::where('assigned_to', $user->id)->whereNotNull('project_id')->pluck('project_id'))
                ->merge(Task::where('social_assigned_to', $user->id)->whereNotNull('project_id')->pluck('project_id'))
                ->merge($pivotProjectIds)
                ->unique()->values();
        }

        $nonDoneStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'paused', 'submitted', 'revision_requested'];

        if ($isAdminOrManager) {
            $cardTotal      = $allTasks->whereIn('status', $nonDoneStatuses)->count();
            $cardCompleted  = $completed;
            $cardInProgress = $allTasks->whereIn('status', ['in_progress', 'paused'])->count();
            $cardInReview   = $allTasks->whereIn('status', ['submitted', 'revision_requested'])->count();
            $cardOverdue    = $overdue;
        } else {
            $userScope = fn($q) => $q->where('assigned_to', $user->id)
                ->orWhere('social_assigned_to', $user->id)
                ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                    ->from('task_assignees')
                    ->whereColumn('task_assignees.task_id', 'tasks.id')
                    ->where('task_assignees.user_id', $user->id));
            $cardTotal      = Task::where($userScope)->whereIn('status', $nonDoneStatuses)->count();
            $cardCompleted  = Task::where($userScope)->whereIn('status', $doneStatuses)->count();
            $cardInProgress = Task::where($userScope)->whereIn('status', ['in_progress', 'paused'])->count();
            $cardInReview   = Task::where($userScope)->whereIn('status', ['submitted', 'revision_requested'])->count();
            $cardOverdue    = Task::where($userScope)->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses)->count();
        }

        $teamTasks = Task::whereIn('project_id', $involvedProjectIds)
            ->where('assigned_to', '!=', $user->id)
            ->with(['project', 'assignee'])
            ->orderByRaw("CASE WHEN status IN ('approved','delivered','archived') THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(20)
            ->get();

        $myProjects = \App\Models\Project::whereIn('id', $involvedProjectIds)
            ->where('is_quick', false)
            ->withCount([
                'tasks',
                'tasks as completed_count' => fn($q) => $q->whereIn('status', $doneStatuses),
            ])
            ->orderByRaw("CASE WHEN status='completed' THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(6)
            ->get();

        $realProjects = \App\Models\Project::whereIn('id', $involvedProjectIds)->where('is_quick', false);
        $myProjectStats = [
            'total'     => (clone $realProjects)->count(),
            'active'    => (clone $realProjects)->where('status', 'active')->count(),
            'completed' => (clone $realProjects)->where('status', 'completed')->count(),
            'overdue'   => (clone $realProjects)
                ->whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->where('status', '!=', 'completed')
                ->count(),
        ];

        $recentActivity = TaskLog::where('user_id', $user->id)
            ->with('task')
            ->latest()
            ->take(8)
            ->get();

        $weekActivity = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = now()->subDays($daysAgo)->toDateString();
            return [
                'label' => now()->subDays($daysAgo)->format('D'),
                'count' => TaskLog::where('user_id', $user->id)->whereDate('created_at', $date)->count(),
            ];
        });

        $pendingApproval = $inReview;
        $previewUser     = $user;

        $socialTasks = Task::where('social_assigned_to', $user->id)
            ->with(['project', 'socialPosts'])
            ->orderByRaw('social_posted_at IS NOT NULL')
            ->orderBy('deadline')
            ->get();

        $pendingSocialPosts   = $socialTasks->whereNull('social_posted_at')->count();
        $completedSocialPosts = $socialTasks->whereNotNull('social_posted_at')->count();

        $myLicenses = Subscription::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('renewal_date')
            ->get();

        return view('user.dashboard', compact(
            'total', 'completed', 'inProgress', 'pending', 'pendingApproval', 'overdue', 'rate',
            'cardTotal', 'cardCompleted', 'cardInProgress', 'cardInReview', 'cardOverdue',
            'tasks', 'completedTasks', 'upcomingTasks', 'recentActivity', 'weekActivity',
            'teamTasks', 'myProjects', 'myProjectStats', 'socialTasks',
            'inheritedCount', 'nativeTotal', 'nativeCompleted', 'pendingSocialPosts', 'completedSocialPosts',
            'receivedTotal', 'receivedCompleted', 'myLicenses',
            'previewUser'
        ));
    }

    public function taskModal(User $user, Request $request)
    {
        $doneStatuses    = ['approved', 'delivered', 'archived'];
        $nonDoneStatuses = ['draft', 'assigned', 'viewed', 'in_progress', 'paused', 'submitted', 'revision_requested'];
        $isAdminOrManager = in_array($user->role, ['admin', 'manager']);

        $filter = $request->input('filter', 'total');

        // Admin/manager dashboards are built from tasks they *created*, not assigned to them
        if ($isAdminOrManager) {
            $base = Task::where('created_by', $user->id)->with(['project:id,name']);

            if ($filter === 'date') {
                $date    = $request->input('date');
                $taskIds = TaskLog::where('user_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->pluck('task_id')->unique();
                $base = Task::whereIn('id', $taskIds)->with(['project:id,name']);
            } elseif ($filter === 'social') {
                $base = Task::where('social_assigned_to', $user->id)
                    ->whereNull('social_posted_at')
                    ->with(['project:id,name']);
            } else {
                match ($filter) {
                    'completed'   => $base->whereIn('status', $doneStatuses),
                    'in_progress' => $base->where('status', 'in_progress'),
                    'in_review'   => $base->whereIn('status', ['submitted', 'revision_requested']),
                    'overdue'     => $base->whereNotNull('deadline')->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses),
                    default       => $base->whereNotIn('status', $doneStatuses),
                };
            }
        } else {
            // Regular users: scoped to tasks assigned to them (assigned_to or task_assignees)
            $base = Task::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                      ->from('task_assignees')
                      ->whereColumn('task_assignees.task_id', 'tasks.id')
                      ->where('task_assignees.user_id', $user->id));
            })->with(['project:id,name']);

            if ($filter === 'social') {
                $base = Task::where('social_assigned_to', $user->id)
                    ->whereNull('social_posted_at')
                    ->with(['project:id,name']);
            } elseif ($filter === 'date') {
                $date    = $request->input('date');
                $taskIds = TaskLog::where('user_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->pluck('task_id')->unique();
                $base = Task::whereIn('id', $taskIds)->with(['project:id,name']);
            } elseif ($filter === 'total') {
                $base = Task::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhere('social_assigned_to', $user->id)
                      ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                          ->from('task_assignees')
                          ->whereColumn('task_assignees.task_id', 'tasks.id')
                          ->where('task_assignees.user_id', $user->id));
                })->whereIn('status', $nonDoneStatuses)->with(['project:id,name']);
            } elseif ($filter === 'completed') {
                $base = Task::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhere('social_assigned_to', $user->id)
                      ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                          ->from('task_assignees')
                          ->whereColumn('task_assignees.task_id', 'tasks.id')
                          ->where('task_assignees.user_id', $user->id));
                })->whereIn('status', $doneStatuses)->with(['project:id,name']);
            } elseif ($filter === 'received') {
                $inheritedIds  = \App\Models\TaskTransfer::where('to_user_id', $user->id)->pluck('task_id');
                $reassignedIds = TaskLog::where('action', 'task_reassigned')
                    ->whereIn('task_id', Task::where(function ($q) use ($user) {
                        $q->where('assigned_to', $user->id)
                          ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                              ->from('task_assignees')
                              ->whereColumn('task_assignees.task_id', 'tasks.id')
                              ->where('task_assignees.user_id', $user->id));
                    })->pluck('id'))
                    ->get()
                    ->filter(fn($log) => ($log->metadata['to_user_id'] ?? null) == $user->id)
                    ->pluck('task_id');
                $base->whereIn('id', $inheritedIds->merge($reassignedIds)->unique());
            } else {
                match ($filter) {
                    'in_progress' => $base->whereIn('status', ['in_progress', 'paused']),
                    'in_review'   => $base->whereIn('status', ['submitted', 'revision_requested']),
                    'overdue'     => $base->where('deadline', '<', now())->whereIn('status', $nonDoneStatuses),
                    default       => $base->whereIn('status', $nonDoneStatuses),
                };
            }
        }

        $statusMeta = [
            'draft'              => ['label' => 'Draft',       'color' => '#6B7280', 'bg' => '#F3F4F6'],
            'assigned'           => ['label' => 'Assigned',    'color' => '#4F46E5', 'bg' => '#EEF2FF'],
            'viewed'             => ['label' => 'Viewed',      'color' => '#0369A1', 'bg' => '#E0F2FE'],
            'in_progress'        => ['label' => 'In Progress', 'color' => '#D97706', 'bg' => '#FEF3C7'],
            'paused'             => ['label' => 'Paused',      'color' => '#92400E', 'bg' => '#FEF3C7'],
            'submitted'          => ['label' => 'In Review',   'color' => '#7C3AED', 'bg' => '#EDE9FE'],
            'revision_requested' => ['label' => 'Revision',    'color' => '#DC2626', 'bg' => '#FEE2E2'],
            'approved'           => ['label' => 'Approved',    'color' => '#059669', 'bg' => '#D1FAE5'],
            'delivered'          => ['label' => 'Delivered',   'color' => '#047857', 'bg' => '#ECFDF5'],
            'archived'           => ['label' => 'Archived',    'color' => '#6B7280', 'bg' => '#F3F4F6'],
        ];
        $priorityMeta = [
            'high'   => ['label' => 'High', 'color' => '#EF4444'],
            'medium' => ['label' => 'Med',  'color' => '#F59E0B'],
            'low'    => ['label' => 'Low',  'color' => '#10B981'],
        ];

        $totalCount = $base->count();

        $tasks = $base->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->take(50)
            ->get()
            ->map(function ($task) use ($statusMeta, $priorityMeta) {
                $sm = $statusMeta[$task->status] ?? ['label' => ucfirst($task->status ?? ''), 'color' => '#6B7280', 'bg' => '#F3F4F6'];
                $pm = $priorityMeta[$task->priority] ?? null;
                return [
                    'id'           => $task->id,
                    'title'        => $task->title,
                    'status'       => $task->status,
                    'statusLabel'  => $sm['label'],
                    'statusColor'  => $sm['color'],
                    'statusBg'     => $sm['bg'],
                    'priority'     => $task->priority,
                    'priorityMeta' => $pm,
                    'deadline'     => $task->deadline?->format(config('app.date_format', 'M d, Y')),
                    'isOverdue'    => $task->deadline && $task->deadline->isPast() && !in_array($task->status, ['approved', 'delivered', 'archived']),
                    'project'      => $task->project?->name,
                    'url'          => route('admin.tasks.show', $task->id),
                ];
            });

        return response()->json(['tasks' => $tasks, 'filter' => $filter, 'total' => $totalCount]);
    }

    public function taskHistory(User $user, Request $request)
    {
        $filter = $request->input('filter', 'all');

        $pendingStatuses    = ['draft', 'assigned', 'viewed', 'revision_requested'];
        $inProgressStatuses = ['in_progress', 'paused', 'submitted'];
        $completedStatuses  = ['approved', 'delivered', 'archived'];

        $isAdminOrManager = in_array($user->role, ['admin', 'manager']);

        if ($isAdminOrManager) {
            $freshBase = fn() => Task::where('created_by', $user->id);
        } else {
            // Match the dashboard scope: assigned_to + social_assigned_to + task_assignees pivot
            $userScope = fn($q) => $q->where('assigned_to', $user->id)
                ->orWhere('social_assigned_to', $user->id)
                ->orWhereExists(fn($sub) => $sub->selectRaw('1')
                    ->from('task_assignees')
                    ->whereColumn('task_assignees.task_id', 'tasks.id')
                    ->where('task_assignees.user_id', $user->id));
            $freshBase = fn() => Task::where($userScope);
        }

        $base = $freshBase()->with('project');

        match ($filter) {
            'pending'     => $base->whereIn('status', $pendingStatuses),
            'in_progress' => $base->whereIn('status', $inProgressStatuses),
            'completed'   => $base->whereIn('status', $completedStatuses),
            default       => null,
        };

        $tasks = $base->latest()->paginate(15)->withQueryString();

        $counts = [
            'all'         => $freshBase()->count(),
            'pending'     => $freshBase()->whereIn('status', $pendingStatuses)->count(),
            'in_progress' => $freshBase()->whereIn('status', $inProgressStatuses)->count(),
            'completed'   => $freshBase()->whereIn('status', $completedStatuses)->count(),
        ];

        return view('admin.users.task_history', compact('user', 'tasks', 'filter', 'counts'));
    }

    public function performance(User $user)
    {
        $doneStatuses = ['delivered', 'approved', 'archived'];

        $tasks = Task::where('assigned_to', $user->id)
            ->with('project:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'status', 'priority', 'deadline', 'created_at', 'project_id']);

        $total     = $tasks->count();
        $completed = $tasks->whereIn('status', $doneStatuses)->count();
        $pending   = $tasks->whereNotIn('status', $doneStatuses)->count();

        return response()->json([
            'user' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'job_title'   => $user->job_title ?? '',
                'nationality' => $user->nationality ?? '',
                'phone'       => $user->phone ?? '',
                'joined_at'   => $user->created_at?->format(config('app.date_format', 'M d, Y')) ?? '—',
                'archived_at' => $user->archived_at?->format(config('app.date_format', 'M d, Y')) ?? '—',
                'archived_by' => $user->archivedBy?->name ?? '—',
            ],
            'stats' => [
                'total'     => $total,
                'completed' => $completed,
                'pending'   => $pending,
                'rate'      => $total > 0 ? round(($completed / $total) * 100) : 0,
            ],
            'tasks' => $tasks->map(fn($t) => [
                'id'         => $t->id,
                'title'      => $t->title,
                'status'     => $t->status,
                'priority'   => $t->priority,
                'project'    => $t->project?->name ?? '—',
                'deadline'   => $t->deadline?->format(config('app.date_format', 'M d, Y')) ?? '—',
                'created_at' => $t->created_at?->format(config('app.date_format', 'M d, Y')) ?? '—',
            ])->values(),
        ]);
    }

    public function cloneUser(Request $request, User $user)
    {
        $request->validate([
            'name'     => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(name) = ?', [strtolower($value)])->exists()) {
                        $fail('A user with this name already exists.');
                    }
                },
            ],
            'email'    => [
                'required', 'email', 'max:255',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(email) = ?', [strtolower($value)])->exists()) {
                        $fail('A user with this email address already exists.');
                    }
                },
            ],
            'username' => 'nullable|string|max:60|unique:users|alpha_dash',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $clone = User::create([
            'name'        => $request->name,
            'email'       => strtolower($request->email),
            'username'    => $request->username ?: null,
            'password'    => $request->password,
            'role'        => $user->role,
            'phone'       => $user->phone,
            'job_title'   => $user->job_title,
            'nationality' => $user->nationality,
            'hourly_rate' => $user->hourly_rate,
            'permissions' => $user->permissions,
            'status'      => 'active',
        ]);

        AuditLogger::log(
            'user.cloned',
            $clone,
            'Account cloned from ' . $user->name . ' → ' . $clone->name . ' (no tasks transferred)',
            ['source_user_id' => $user->id, 'source_name' => $user->name, 'role' => $clone->role]
        );

        return redirect()->route('team.index')->with('success', '"' . $clone->name . '" created as a clone of ' . $user->name . ' (no tasks assigned).');
    }
}
