<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'group_id', 'body', 'file_path', 'file_name', 'file_type', 'reply_to_id', 'read_at', 'deleted_at'];

    protected $casts = ['read_at' => 'datetime', 'deleted_at' => 'datetime'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->with('sender:id,name');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    /** All messages in a conversation between two users, respecting per-user clear timestamps. */
    public static function conversation(int $userA, int $userB, ?\Carbon\Carbon $clearedAt = null)
    {
        $q = static::where(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userA)->where('receiver_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userB)->where('receiver_id', $userA);
        });

        if ($clearedAt) {
            $q->where('created_at', '>', $clearedAt);
        }

        return $q->orderBy('created_at');
    }
}
