<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectAttachment extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'name',
        'path',
        'size',
        'uploaded_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }

    public function url(): string
    {
        return $this->type === 'link'
            ? $this->path
            : Storage::disk('public')->url($this->path);
    }

    public function humanSize(): string
    {
        if (!$this->size) return '';
        if ($this->size < 1024)        return $this->size . ' B';
        if ($this->size < 1048576)     return round($this->size / 1024, 1) . ' KB';
        return round($this->size / 1048576, 1) . ' MB';
    }

    public function iconClass(): string
    {
        if ($this->type === 'link') return 'fa-link';
        $ext = strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
        return match(true) {
            in_array($ext, ['pdf'])                        => 'fa-file-pdf',
            in_array($ext, ['doc', 'docx'])                => 'fa-file-word',
            in_array($ext, ['xls', 'xlsx'])                => 'fa-file-excel',
            in_array($ext, ['ppt', 'pptx'])                => 'fa-file-powerpoint',
            in_array($ext, ['zip', 'rar', '7z'])           => 'fa-file-zipper',
            in_array($ext, ['jpg','jpeg','png','gif','webp','svg']) => 'fa-file-image',
            in_array($ext, ['mp4','mov','avi','mkv'])      => 'fa-file-video',
            in_array($ext, ['mp3','wav','aac'])             => 'fa-file-audio',
            default                                        => 'fa-file',
        };
    }
}
