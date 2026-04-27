<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageGroupUser extends Model
{
    protected $fillable = ['group_id', 'user_id', 'last_read_at'];
    protected $casts    = ['last_read_at' => 'datetime'];
}
