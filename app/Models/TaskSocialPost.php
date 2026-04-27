<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSocialPost extends Model
{
    protected $fillable = ['task_id', 'user_id', 'platform', 'post_url', 'note'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platformLabel(): string
    {
        return match($this->platform) {
            'facebook'  => 'Facebook',
            'instagram' => 'Instagram',
            'twitter'   => 'Twitter / X',
            'linkedin'  => 'LinkedIn',
            'tiktok'    => 'TikTok',
            'youtube'   => 'YouTube',
            'snapchat'  => 'Snapchat',
            default     => 'Other',
        };
    }

    public function platformIcon(): string
    {
        return match($this->platform) {
            'facebook'  => 'fa-facebook',
            'instagram' => 'fa-instagram',
            'twitter'   => 'fa-x-twitter',
            'linkedin'  => 'fa-linkedin',
            'tiktok'    => 'fa-tiktok',
            'youtube'   => 'fa-youtube',
            'snapchat'  => 'fa-snapchat',
            default     => 'fa-share-nodes',
        };
    }

    public function platformColor(): string
    {
        return match($this->platform) {
            'facebook'  => '#1877F2',
            'instagram' => '#E1306C',
            'twitter'   => '#000000',
            'linkedin'  => '#0A66C2',
            'tiktok'    => '#010101',
            'youtube'   => '#FF0000',
            'snapchat'  => '#F7CA00',
            default     => '#6366F1',
        };
    }
}
