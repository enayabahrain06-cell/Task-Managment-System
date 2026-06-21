<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAttachment extends Model
{
    protected $fillable = ['domain_id', 'uploaded_by', 'original_name', 'path', 'size', 'mime_type'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function getIconClassAttribute(): string
    {
        $mime = $this->mime_type ?? '';
        if (str_contains($mime, 'pdf'))   return 'fa-file-pdf';
        if (str_contains($mime, 'image')) return 'fa-file-image';
        if (str_contains($mime, 'word') || str_ends_with($this->original_name, '.docx') || str_ends_with($this->original_name, '.doc')) return 'fa-file-word';
        if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet') || str_ends_with($this->original_name, '.xlsx')) return 'fa-file-excel';
        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) return 'fa-file-zipper';
        return 'fa-file';
    }
}
