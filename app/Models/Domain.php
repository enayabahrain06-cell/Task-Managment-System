<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Domain extends Model
{
    protected $fillable = [
        'domain', 'registrar', 'customer_id', 'responsible_user_id', 'billing_to',
        'cost', 'currency', 'billing_cycle', 'auto_renew',
        'registered_at', 'expires_at', 'notify_days',
        'nameservers', 'hosting_provider', 'login_url', 'username', 'password',
        'notes', 'created_by',
    ];

    protected $casts = [
        'notify_days'   => 'array',
        'nameservers'   => 'array',
        'registered_at' => 'date',
        'expires_at'    => 'date',
        'cost'          => 'decimal:3',
        'auto_renew'    => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function attachments()
    {
        return $this->hasMany(\App\Models\DomainAttachment::class)->latest();
    }

    public function getStatusAttribute(): string
    {
        if (! $this->expires_at) return 'active';
        $days = (int) now()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay(), false);
        if ($days < 0)  return 'expired';
        if ($days <= 30) return 'expiring_soon';
        return 'active';
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expires_at) return null;
        return (int) now()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay(), false);
    }

    public function getNotifyDaysAttribute($value): array
    {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [60, 30, 14, 7, 1];
    }

    public function getAnnualCostAttribute(): float
    {
        return match ($this->billing_cycle) {
            'annual'    => (float) $this->cost,
            'biennial'  => round((float) $this->cost / 2, 3),
            'triennial' => round((float) $this->cost / 3, 3),
            default     => (float) $this->cost,
        };
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

    public static function registrarOptions(): array
    {
        return [
            'GoDaddy', 'Namecheap', 'Google Domains', 'Cloudflare', 'Name.com',
            'Dynadot', 'Hover', 'SiteGround', 'HostGator', 'Bluehost',
            'Network Solutions', 'Register.com', 'Domain.com', 'Gandi', 'Other',
        ];
    }

    public static function billingCycleOptions(): array
    {
        return [
            'annual'    => 'Annual (1 year)',
            'biennial'  => 'Biennial (2 years)',
            'triennial' => 'Triennial (3 years)',
            'one_time'  => 'One-time',
        ];
    }
}
