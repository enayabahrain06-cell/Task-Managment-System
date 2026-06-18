<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Subscription extends Model
{
    protected $fillable = [
        'name', 'vendor', 'category', 'type', 'billing_cycle',
        'cost', 'currency', 'max_seats', 'website',
        'purchase_date', 'renewal_date', 'notify_days', 'notes', 'created_by',
        'logo_path', 'username', 'password',
    ];

    protected $casts = [
        'notify_days'   => 'array',
        'purchase_date' => 'date',
        'renewal_date'  => 'date',
        'cost'          => 'decimal:3',
        'max_seats'     => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SubscriptionAttachment::class)->latest();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscription_users', 'subscription_id', 'user_id')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->orderByPivot('assigned_at');
    }

    public function getStatusAttribute(): string
    {
        if (! $this->renewal_date) return 'active';
        $days = (int) now()->startOfDay()->diffInDays($this->renewal_date->copy()->startOfDay(), false);
        if ($days < 0) return 'expired';
        if ($days <= 30) return 'expiring_soon';
        return 'active';
    }

    public function getDaysUntilRenewalAttribute(): ?int
    {
        if (! $this->renewal_date) return null;
        return (int) now()->startOfDay()->diffInDays($this->renewal_date->copy()->startOfDay(), false);
    }

    public function getNotifyDaysAttribute($value): array
    {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [30, 14, 7, 1];
    }

    public function getMonthlyCostAttribute(): float
    {
        return match ($this->billing_cycle) {
            'monthly'   => (float) $this->cost,
            'annual'    => round((float) $this->cost / 12, 3),
            'quarterly' => round((float) $this->cost / 3, 3),
            default     => 0,
        };
    }

    public function getAnnualCostAttribute(): float
    {
        return match ($this->billing_cycle) {
            'monthly'   => round((float) $this->cost * 12, 3),
            'annual'    => (float) $this->cost,
            'quarterly' => round((float) $this->cost * 4, 3),
            default     => 0,
        };
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) return null;
        return Storage::disk('public')->url($this->logo_path);
    }

    public function getDecryptedPasswordAttribute(): ?string
    {
        if (! $this->password) return null;
        try {
            return Crypt::decryptString($this->password);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function categoryOptions(): array
    {
        return [
            'design'        => 'Design',
            'development'   => 'Development',
            'communication' => 'Communication',
            'marketing'     => 'Marketing',
            'productivity'  => 'Productivity',
            'security'      => 'Security',
            'finance'       => 'Finance',
            'other'         => 'Other',
        ];
    }

    public static function categoryColors(): array
    {
        return [
            'design'        => ['bg' => '#EDE9FE', 'color' => '#7C3AED'],
            'development'   => ['bg' => '#DBEAFE', 'color' => '#2563EB'],
            'communication' => ['bg' => '#D1FAE5', 'color' => '#059669'],
            'marketing'     => ['bg' => '#FEF3C7', 'color' => '#D97706'],
            'productivity'  => ['bg' => '#E0F2FE', 'color' => '#0284C7'],
            'security'      => ['bg' => '#FEE2E2', 'color' => '#DC2626'],
            'finance'       => ['bg' => '#ECFDF5', 'color' => '#16A34A'],
            'other'         => ['bg' => '#F3F4F6', 'color' => '#6B7280'],
        ];
    }
}
