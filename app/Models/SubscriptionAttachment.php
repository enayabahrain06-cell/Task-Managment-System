<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubscriptionAttachment extends Model
{
    protected $fillable = ['subscription_id', 'uploaded_by', 'filename', 'path', 'size', 'mime_type', 'comment'];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
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
        if (str_contains($mime, 'word') || str_contains($mime, 'document')) return 'fa-file-word';
        if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) return 'fa-file-excel';
        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) return 'fa-file-zipper';
        return 'fa-file';
    }
}
