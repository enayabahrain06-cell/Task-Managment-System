<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageGroup extends Model
{
    protected $fillable = ['name', 'created_by'];

    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function members()  { return $this->belongsToMany(User::class, 'message_group_users', 'group_id', 'user_id')->withPivot('last_read_at', 'cleared_at')->withTimestamps(); }
    public function messages() { return $this->hasMany(Message::class, 'group_id')->orderBy('created_at'); }

    public function unreadCountFor(int $userId): int
    {
        $pivot     = $this->members()->where('user_id', $userId)->first()?->pivot;
        $since     = $pivot?->last_read_at;
        $clearedAt = $pivot?->cleared_at;
        $floor     = collect(array_filter([$since, $clearedAt]))->map(fn($d) => \Carbon\Carbon::parse($d))->max();
        return $this->messages()->where('sender_id', '!=', $userId)
            ->when($floor, fn($q) => $q->where('created_at', '>', $floor))
            ->count();
    }
}
