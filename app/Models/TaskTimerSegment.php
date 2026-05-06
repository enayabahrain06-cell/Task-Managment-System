<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTimerSegment extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'phase',
        'started_at',
        'ended_at',
        'duration_seconds',
        'pause_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRunning(): bool
    {
        return is_null($this->ended_at);
    }

    public function elapsedSeconds(): int
    {
        if ($this->ended_at) {
            return $this->duration_seconds;
        }
        return (int) $this->started_at->diffInSeconds(now());
    }
}
