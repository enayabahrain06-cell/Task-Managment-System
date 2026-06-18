<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'notes',
        'logo',
        'created_by',
    ];

    public function whatsappUrl(): ?string
    {
        if (!$this->phone) return null;
        $digits = preg_replace('/\D/', '', $this->phone);
        return $digits ? 'https://wa.me/' . $digits : null;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(\App\Models\SocialAccount::class);
    }
}
