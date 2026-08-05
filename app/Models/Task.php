<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'customer_id',
        'title',
        'description',
        'assigned_to',
        'social_assigned_to',
        'is_recurring',
        'recurring_type',
        'recurring_end_date',
        'recurring_max',
        'recurring_count',
        'recurring_parent_id',
        'social_posted_at',
        'social_required',
        'social_description',
        'social_caption',
        'social_budget',
        'social_platforms',
        'status',
        'priority',
        'deadline',
        'first_viewed_at',
        'design_sent_at',
        'customer_approved_at',
        'customer_decision_deferred_at',
        'created_by',
        'reviewer_id',
        'task_type',
        'tags',
        'delivered_at',
    ];

    protected $casts = [
        'deadline'         => 'date',
        'delivered_at'     => 'datetime',
        'first_viewed_at'      => 'datetime',
        'design_sent_at'                  => 'datetime',
        'customer_approved_at'            => 'datetime',
        'customer_decision_deferred_at'   => 'datetime',
        'social_posted_at'     => 'datetime',
        'social_required'  => 'boolean',
        'social_platforms' => 'array',
        'tags'                => 'array',
        'is_recurring'        => 'boolean',
        'recurring_end_date'  => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    /** Tasks that THIS task is waiting for (must complete first). */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
                    ->withTimestamps();
    }

    /** Tasks that are blocked waiting for THIS task to complete. */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
                    ->withTimestamps();
    }

    public function recurringParent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'recurring_parent_id');
    }

    public function recurringChildren(): HasMany
    {
        return $this->hasMany(Task::class, 'recurring_parent_id');
    }

    /** Calculate the deadline for the next occurrence. */
    public function nextRecurringDeadline(): ?\Carbon\Carbon
    {
        if (!$this->deadline || !$this->recurring_type) return null;
        $base = $this->recurring_parent_id
            ? ($this->recurringParent?->deadline ?? $this->deadline)
            : $this->deadline;
        $count = ($this->recurring_count ?? 0) + 1;
        return match ($this->recurring_type) {
            'daily'   => $this->deadline->copy()->addDays(1),
            'weekly'  => $this->deadline->copy()->addWeeks(1),
            'monthly' => $this->deadline->copy()->addMonthsNoOverflow(1),
            default   => null,
        };
    }

    /** Spawn the next recurring copy. Returns the new Task or null if limit reached. */
    public function createNextRecurrence(): ?self
    {
        if (!$this->is_recurring || !$this->recurring_type) return null;

        $nextDeadline = $this->nextRecurringDeadline();
        if (!$nextDeadline) return null;

        // Check end date
        if ($this->recurring_end_date && $nextDeadline->gt($this->recurring_end_date)) return null;

        // Check max count
        $parentId = $this->recurring_parent_id ?? $this->id;
        $totalCreated = self::where('recurring_parent_id', $parentId)->count() + 1;
        if ($this->recurring_max && $totalCreated > $this->recurring_max) return null;

        $next = self::create([
            'project_id'          => $this->project_id,
            'customer_id'         => $this->customer_id,
            'title'               => $this->title,
            'description'         => $this->description,
            'assigned_to'         => $this->assigned_to,
            'social_assigned_to'  => $this->social_assigned_to,
            'social_required'     => $this->social_required,
            'social_description'  => $this->social_description,
            'social_caption'      => $this->social_caption,
            'social_budget'       => $this->social_budget,
            'social_platforms'    => $this->social_platforms,
            'priority'            => $this->priority,
            'deadline'            => $nextDeadline->toDateString(),
            'task_type'           => $this->task_type,
            'tags'                => $this->tags,
            'reviewer_id'         => $this->reviewer_id,
            'created_by'          => $this->created_by,
            'status'              => 'assigned',
            'is_recurring'        => true,
            'recurring_type'      => $this->recurring_type,
            'recurring_end_date'  => $this->recurring_end_date,
            'recurring_max'       => $this->recurring_max,
            'recurring_count'     => $totalCreated,
            'recurring_parent_id' => $parentId,
        ]);

        return $next;
    }

    /** Returns true if all blocking tasks are in a done state. */
    public function dependenciesResolved(): bool
    {
        $doneStatuses = ['approved', 'delivered', 'archived'];
        return $this->dependencies()->whereNotIn('status', $doneStatuses)->doesntExist();
    }

    /** Past its deadline and not yet in a done state. */
    public function isOverdue(): bool
    {
        $doneStatuses = ['approved', 'delivered', 'archived'];
        return $this->deadline
            && $this->deadline->isPast()
            && !in_array($this->status, $doneStatuses);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(TaskTransfer::class)->orderBy('transferred_at');
    }

    public function latestTransfer(): HasOne
    {
        return $this->hasOne(TaskTransfer::class)->latestOfMany('transferred_at');
    }

    public function timerSegments(): HasMany
    {
        return $this->hasMany(TaskTimerSegment::class)->orderBy('started_at');
    }

    public function activeTimerSegment(): HasOne
    {
        return $this->hasOne(TaskTimerSegment::class)->whereNull('ended_at')->latestOfMany('started_at');
    }

    public function deadlineExtensionRequests(): HasMany
    {
        return $this->hasMany(\App\Models\DeadlineExtensionRequest::class);
    }

    /** Total completed seconds for a user (or all users) across all phases. */
    public function totalTimerSeconds(?int $userId = null, ?string $phase = null): int
    {
        $query = $this->timerSegments()->whereNotNull('ended_at');
        if ($userId) $query->where('user_id', $userId);
        if ($phase)  $query->where('phase', $phase);
        return (int) $query->sum('duration_seconds');
    }

    /** Seconds broken down by user_id for billing reports. */
    public function timerSecondsByUser(): Collection
    {
        return $this->timerSegments()
            ->selectRaw('user_id, phase, SUM(duration_seconds) as total_seconds')
            ->whereNotNull('ended_at')
            ->groupBy('user_id', 'phase')
            ->get();
    }
}

