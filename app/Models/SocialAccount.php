<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    protected $fillable = [
        'created_by', 'customer_id', 'name', 'platform', 'username', 'email',
        'password', 'account_id', 'page_url', 'status', 'notes',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'social_account_users')->withTimestamps();
    }

    public function getDecryptedPasswordAttribute(): ?string
    {
        if (! $this->password) return null;
        try { return Crypt::decryptString($this->password); }
        catch (\Exception $e) { return null; }
    }

    public static function platforms(): array
    {
        return [
            'facebook'  => ['label' => 'Facebook',    'icon' => 'fa-facebook',  'color' => '#1877F2', 'bg' => '#EBF3FE'],
            'instagram' => ['label' => 'Instagram',   'icon' => 'fa-instagram', 'color' => '#E1306C', 'bg' => '#FDE8EF'],
            'tiktok'    => ['label' => 'TikTok',      'icon' => 'fa-tiktok',    'color' => '#010101', 'bg' => '#F3F4F6'],
            'linkedin'  => ['label' => 'LinkedIn',    'icon' => 'fa-linkedin',  'color' => '#0A66C2', 'bg' => '#E8F0FB'],
            'twitter'   => ['label' => 'X / Twitter', 'icon' => 'fa-x-twitter', 'color' => '#000000', 'bg' => '#F3F4F6'],
            'snapchat'  => ['label' => 'Snapchat',    'icon' => 'fa-snapchat',  'color' => '#F7B500', 'bg' => '#FEF3C7'],
            'youtube'   => ['label' => 'YouTube',     'icon' => 'fa-youtube',   'color' => '#FF0000', 'bg' => '#FFEBEE'],
        ];
    }

    public function getPlatformInfoAttribute(): array
    {
        return static::platforms()[$this->platform] ?? ['label' => ucfirst($this->platform), 'icon' => 'fa-globe', 'color' => '#6B7280', 'bg' => '#F3F4F6'];
    }

    public function getStatusColorAttribute(): array
    {
        return match ($this->status) {
            'active'    => ['bg' => '#ECFDF5', 'color' => '#16A34A'],
            'inactive'  => ['bg' => '#F3F4F6', 'color' => '#6B7280'],
            'suspended' => ['bg' => '#FEE2E2', 'color' => '#DC2626'],
            default     => ['bg' => '#F3F4F6', 'color' => '#6B7280'],
        };
    }
}
