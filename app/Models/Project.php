<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'deadline',
        'first_review_date',
        'created_by',
        'status',
        'customer_id',
        'is_quick',
    ];

    protected $casts = [
        'deadline'          => 'date',
        'first_review_date' => 'date',
        'is_quick'          => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectAttachment::class)->orderBy('created_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    public function completionRate(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;
        return (int) round($this->tasks()->whereIn('status', ['delivered', 'archived'])->count() / $total * 100);
    }

    public function autoComplete(): bool
    {
        if ($this->status === 'completed') return false;
        $total = $this->tasks()->count();
        if ($total === 0) return false;
        $done = $this->tasks()->whereIn('status', ['approved', 'delivered', 'archived'])->count();
        if ($total === $done) {
            $this->update(['status' => 'completed']);
            return true;
        }
        return false;
    }
}

