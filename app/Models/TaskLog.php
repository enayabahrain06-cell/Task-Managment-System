<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'action',
        'note',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions()
    {
        return $this->hasMany(ActivityReaction::class, 'task_log_id');
    }

    public function replies()
    {
        return $this->hasMany(ActivityReply::class, 'task_log_id')->with('user')->oldest();
    }

    public function isComment(): bool
    {
        return $this->action === 'comment_added';
    }

    public function actionLabel(): string
    {
        return match($this->action) {
            'task_created'                        => 'Task Created',
            'status_updated_draft'                => 'Set to Draft',
            'status_updated_assigned'             => 'Assigned',
            'status_updated_viewed'               => 'Viewed by Assignee',
            'status_updated_in_progress'          => 'Work Started',
            'status_updated_submitted'            => 'Submitted for Review',
            'status_updated_revision_requested'   => 'Revision Requested',
            'status_updated_approved'             => 'Approved',
            'status_updated_delivered'            => 'Delivered',
            'status_updated_archived'             => 'Archived',
            'status_updated_reopened'             => 'Reopened',
            // legacy labels (backward compat)
            'status_updated_pending'              => 'Set to Pending',
            'status_updated_pending_approval'     => 'Submitted for Review',
            'status_updated_completed'            => 'Approved',
            'status_updated_paused'               => 'Timer Paused',
            'auto_paused'                         => 'Auto-Paused (Task Switch)',
            'timer_started'                       => 'Timer Started',
            'timer_paused'                        => 'Timer Paused',
            'revision_acknowledged'               => 'Revision Acknowledged',
            'task_reassigned'                     => 'Reassigned',
            'task_transferred'                    => 'Transferred',
            'deadline_updated'                    => 'Deadline Changed',
            'first_viewed'                        => 'First Opened by Assignee',
            'comment_added'                       => 'Comment Added',
            'customer_notified'                   => 'Customer Notified',
            'social_assigned'                     => 'Assigned for Social Media',
            'social_posted'                       => 'Posted to Social Media',
            'social_post_edited'                  => 'Social Post Edited',
            'social_post_deleted'                 => 'Social Post Deleted',
            'social_reopened'                     => 'Social Post Reopened',
            'task_updated'                        => 'Task Details Updated',
            'release_published'                   => 'Release Published',
            default => ucwords(str_replace(['status_updated_', '_'], ['', ' '], $this->action)),
        };
    }

    /** Returns [icon, fg_color, bg_color] */
    public function actionStyle(): array
    {
        return match($this->action) {
            'task_created'                        => ['fa-plus-circle',          '#4F46E5', '#EEF2FF'],
            'status_updated_draft'                => ['fa-file-pen',             '#6B7280', '#F3F4F6'],
            'status_updated_assigned'             => ['fa-user-check',           '#0284C7', '#E0F2FE'],
            'status_updated_viewed'               => ['fa-eye',                  '#0EA5E9', '#E0F2FE'],
            'status_updated_in_progress'          => ['fa-circle-play',          '#D97706', '#FEF3C7'],
            'status_updated_submitted'            => ['fa-hourglass-half',       '#7C3AED', '#EDE9FE'],
            'status_updated_revision_requested'   => ['fa-rotate-left',          '#DC2626', '#FEE2E2'],
            'status_updated_approved'             => ['fa-circle-check',         '#059669', '#D1FAE5'],
            'status_updated_delivered'            => ['fa-truck',                '#047857', '#ECFDF5'],
            'status_updated_archived'             => ['fa-box-archive',          '#6B7280', '#F3F4F6'],
            'status_updated_reopened'             => ['fa-rotate-right',         '#D97706', '#FEF3C7'],
            // legacy
            'status_updated_pending'              => ['fa-circle-pause',         '#6B7280', '#F3F4F6'],
            'status_updated_pending_approval'     => ['fa-hourglass-half',       '#7C3AED', '#EDE9FE'],
            'status_updated_completed'            => ['fa-circle-check',         '#059669', '#D1FAE5'],
            'status_updated_paused'               => ['fa-circle-pause',         '#6B7280', '#F3F4F6'],
            'auto_paused'                         => ['fa-circle-pause',         '#9CA3AF', '#F3F4F6'],
            'timer_started'                       => ['fa-circle-play',          '#059669', '#D1FAE5'],
            'timer_paused'                        => ['fa-circle-pause',         '#6B7280', '#F3F4F6'],
            'revision_acknowledged'               => ['fa-circle-check',         '#7C3AED', '#EDE9FE'],
            'task_reassigned'                     => ['fa-arrows-rotate',        '#D97706', '#FEF3C7'],
            'task_transferred'                    => ['fa-arrow-right-arrow-left', '#7C3AED', '#EDE9FE'],
            'deadline_updated'                    => ['fa-calendar-xmark',       '#DC2626', '#FEE2E2'],
            'first_viewed'                        => ['fa-eye',                  '#0EA5E9', '#E0F2FE'],
            'comment_added'                       => ['fa-comment',              '#6366F1', '#EEF2FF'],
            'customer_notified'                   => ['fa-paper-plane',          '#0284C7', '#E0F2FE'],
            'social_assigned'                     => ['fa-share-nodes',          '#7C3AED', '#EDE9FE'],
            'social_posted'                       => ['fa-share-nodes',          '#059669', '#D1FAE5'],
            'social_post_edited'                  => ['fa-pen-to-square',        '#D97706', '#FEF3C7'],
            'social_post_deleted'                 => ['fa-trash',                '#DC2626', '#FEE2E2'],
            'social_reopened'                     => ['fa-rotate-right',         '#6366F1', '#EEF2FF'],
            'task_updated'                        => ['fa-pen',                  '#6B7280', '#F3F4F6'],
            'release_published'                   => ['fa-rocket',               '#6366F1', '#EEF2FF'],
            default                               => ['fa-circle-dot',           '#6366F1', '#EEF2FF'],
        };
    }
}
