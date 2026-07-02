<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeadlineExtensionRequest extends Model
{
    protected $fillable = ['task_id','user_id','reason','requested_deadline','status','admin_note','responded_by','responded_at'];

    protected $casts = ['requested_deadline' => 'date', 'responded_at' => 'datetime'];

    public function task() { return $this->belongsTo(Task::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function responder() { return $this->belongsTo(User::class, 'responded_by'); }
}
