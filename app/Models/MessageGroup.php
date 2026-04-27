<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageGroup extends Model
{
    protected $fillable = ['name', 'created_by'];

    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function members()  { return $this->belongsToMany(User::class, 'message_group_users', 'group_id', 'user_id')->withPivot('last_read_at')->withTimestamps(); }
    public function messages() { return $this->hasMany(Message::class, 'group_id')->orderBy('created_at'); }

    public function unreadCountFor(int $userId): int
    {
        $pivot = $this->members()->where('user_id', $userId)->first()?->pivot;
        $since = $pivot?->last_read_at;
        return $this->messages()->where('sender_id', '!=', $userId)
            ->when($since, fn($q) => $q->where('created_at', '>', $since))
            ->count();
    }
}
