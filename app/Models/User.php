<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Task;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'job_title',
        'nationality',
        'status',
        'permissions',
        'archived_at',
        'archived_by',
    ];

    public const ALL_PERMISSIONS = [
        // Tasks & Work
        'view_tasks'           => 'View Tasks',
        'submit_work'          => 'Submit Work',
        'manage_tasks'         => 'Create & Assign Tasks',
        'approve_tasks'        => 'Approve Submissions',
        'view_activity_log'    => 'Task Activity Log',
        'view_version_history' => 'Version History',
        'view_comments'        => 'Comments & Updates',

        // Projects & Team
        'view_projects'        => 'View Projects',
        'manage_projects'      => 'Create & Manage Projects',
        'view_team_tasks'      => 'View Team Tasks',
        'view_team'            => 'Team Directory',

        // Communication
        'view_messages'        => 'Messages',
        'view_calendar'        => 'Calendar & Schedule',

        // Reports & Data
        'view_reports'         => 'Reports & Analytics',
        'export_data'          => 'Export & Download Data',
        'view_audit_log'       => 'Audit Log',

        // Administration
        'manage_users'         => 'Manage Users',
        'manage_roles'         => 'Manage Roles & Permissions',
        'manage_settings'      => 'System Settings',
        'view_approvals'       => 'Task Approvals',
    ];

    /** Returns true if the user has the given permission (null = all allowed). */
    public function hasPermission(string $key): bool
    {
        if (in_array($this->role, ['admin', 'manager'])) return true;

        // User-level explicit permissions take priority over role defaults
        if (!is_null($this->permissions)) {
            return in_array($key, $this->permissions);
        }

        // Fall back to role-level default permissions
        $role = $this->roleModel;
        if ($role && !is_null($role->permissions)) {
            return in_array($key, $role->permissions);
        }

        return true;
    }

    public function roleModel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    /** Returns the public URL of the avatar, or null. */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? \Illuminate\Support\Facades\Storage::url($this->avatar) : null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'archived_at'       => 'datetime',
            'password'          => 'hashed',
            'role'              => 'string',
            'permissions'       => 'array',
        ];
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function archivedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignees', 'user_id', 'task_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user');
    }
}

