<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'assigned_to',
        'social_assigned_to',
        'social_posted_at',
        'social_required',
        'status',
        'priority',
        'deadline',
        'first_viewed_at',
        'created_by',
        'reviewer_id',
        'task_type',
        'tags',
    ];

    protected $casts = [
        'deadline'         => 'date',
        'first_viewed_at'  => 'datetime',
        'social_posted_at' => 'datetime',
        'social_required'  => 'boolean',
        'tags'             => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')
            ->withPivot('role_in_task')
            ->withTimestamps();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function socialAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'social_assigned_to');
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(TaskSocialPost::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class)->orderBy('version', 'desc');
    }

    public function calendarEvent(): HasOne
    {
        return $this->hasOne(CalendarEvent::class, 'related_task_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(TaskTransfer::class)->orderBy('transferred_at');
    }

    public function latestTransfer(): HasOne
    {
        return $this->hasOne(TaskTransfer::class)->latestOfMany('transferred_at');
    }
}

