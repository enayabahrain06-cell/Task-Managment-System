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
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'job_title',
        'status',
        'permissions',
        'archived_at',
        'archived_by',
    ];

    public const ALL_PERMISSIONS = [
        'view_activity_log'   => 'Activity Log',
        'view_version_history'=> 'Version History',
        'view_comments'       => 'Comments & Updates',
        'view_team_tasks'     => 'Team Tasks Tab',
        'view_projects'       => 'Projects Section',
        'view_messages'       => 'Messages',
        'view_team'           => 'Team Page',
        'view_calendar'       => 'Calendar',
        'submit_work'         => 'Submit Work',
    ];

    /** Returns true if the user has the given permission (null = all allowed). */
    public function hasPermission(string $key): bool
    {
        if (in_array($this->role, ['admin', 'manager'])) return true;
        if (is_null($this->permissions)) return true;
        return in_array($key, $this->permissions);
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

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user');
    }
}

