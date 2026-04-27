<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
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
        return redirect()->route('team.index', ['view' => 'manage']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'username'    => 'nullable|string|max:60|unique:users|alpha_dash',
            'email'       => 'required|email|max:255|unique:users',
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
            'name'        => $request->name,
            'username'    => $request->username ?: null,
            'email'       => $request->email,
            'password'    => $request->password,
            'role'        => $request->role,
            'phone'       => $request->phone,
            'job_title'   => $request->job_title,
            'nationality' => $request->nationality,
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
            'name'        => 'required|string|max:255',
            'username'    => 'nullable|string|max:60|alpha_dash|unique:users,username,' . $user->id,
            'email'       => 'required|email|max:255|unique:users,email,' . $user->id,
            'role'        => ['required', Rule::in(Role::pluck('name'))],
            'password'    => 'nullable|string|min:8|confirmed',
            'phone'       => 'nullable|string|max:30',
            'job_title'   => 'nullable|string|max:80',
            'nationality' => 'nullable|string|max:80',
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
            'email'       => $request->email,
            'role'        => $request->role,
            'phone'       => $request->phone,
            'job_title'   => $request->job_title,
            'nationality' => $request->nationality,
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

    public function hold(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot hold your own account.');
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

        $toUser->notify(new TaskTransferred($tasks->count(), $user));

        return back()->with('success', $tasks->count() . ' task(s) transferred from ' . $user->name . ' to ' . $toUser->name . '.');
    }

    public function viewDashboard(User $user)
    {
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
        } else {
            // Regular user: stats based on tasks assigned to them
            $allTasks = $user->tasks()->with('project')->get();

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

            $tasks = $allTasks->sortBy(function ($t) use ($doneStatuses) {
                if (in_array($t->status, $doneStatuses))    return '5_' . ($t->deadline?->format('Y-m-d') ?? '9999');
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

            $upcomingTasks = $allTasks
                ->filter(fn($t) => $t->deadline && $t->deadline->isFuture() && !in_array($t->status, $doneStatuses))
                ->sortBy('deadline')
                ->take(4);

            $involvedProjectIds = $user->projects()->pluck('projects.id')
                ->merge(Task::where('assigned_to', $user->id)->whereNotNull('project_id')->pluck('project_id'))
                ->merge(Task::where('social_assigned_to', $user->id)->whereNotNull('project_id')->pluck('project_id'))
                ->unique()->values();
        }

        $teamTasks = Task::whereIn('project_id', $involvedProjectIds)
            ->where('assigned_to', '!=', $user->id)
            ->with(['project', 'assignee'])
            ->orderByRaw("CASE WHEN status IN ('approved','delivered','archived') THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(20)
            ->get();

        $myProjects = \App\Models\Project::whereIn('id', $involvedProjectIds)
            ->withCount([
                'tasks',
                'tasks as completed_count' => fn($q) => $q->whereIn('status', $doneStatuses),
            ])
            ->orderByRaw("CASE WHEN status='completed' THEN 1 ELSE 0 END")
            ->orderBy('deadline')
            ->take(6)
            ->get();

        $myProjectStats = [
            'total'     => \App\Models\Project::whereIn('id', $involvedProjectIds)->count(),
            'active'    => \App\Models\Project::whereIn('id', $involvedProjectIds)->where('status', 'active')->count(),
            'completed' => \App\Models\Project::whereIn('id', $involvedProjectIds)->where('status', 'completed')->count(),
            'overdue'   => \App\Models\Project::whereIn('id', $involvedProjectIds)
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

        return view('user.dashboard', compact(
            'total', 'completed', 'inProgress', 'pending', 'pendingApproval', 'overdue', 'rate',
            'tasks', 'upcomingTasks', 'recentActivity', 'weekActivity',
            'teamTasks', 'myProjects', 'myProjectStats', 'socialTasks',
            'inheritedCount', 'nativeTotal', 'nativeCompleted', 'pendingSocialPosts', 'completedSocialPosts',
            'receivedTotal', 'receivedCompleted',
            'previewUser'
        ));
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
                'joined_at'   => $user->created_at?->format('M d, Y') ?? '—',
                'archived_at' => $user->archived_at?->format('M d, Y') ?? '—',
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
                'deadline'   => $t->deadline?->format('M d, Y') ?? '—',
                'created_at' => $t->created_at?->format('M d, Y') ?? '—',
            ])->values(),
        ]);
    }
}
