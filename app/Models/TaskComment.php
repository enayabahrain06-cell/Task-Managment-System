<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TaskComment extends Model
{
    protected $fillable = ['task_id', 'user_id', 'body', 'file_path', 'original_filename'];

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    public function isImage(): bool
    {
        $ext = strtolower(pathinfo($this->original_filename ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    }

    public function isVideo(): bool
    {
        $ext = strtolower(pathinfo($this->original_filename ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'mov', 'avi', 'webm', 'mkv']);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function edits(): HasMany
    {
        return $this->hasMany(TaskCommentEdit::class)->latest('created_at');
    }

    public function isEdited(): bool
    {
        return $this->edits()->exists();
    }
}
