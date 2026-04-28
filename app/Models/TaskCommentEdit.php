<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCommentEdit extends Model {
    public $timestamps = false;
    protected $fillable = ['task_comment_id','old_body','old_file_path','old_original_filename','edited_by_id','created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function comment(): BelongsTo { return $this->belongsTo(TaskComment::class, 'task_comment_id'); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'edited_by_id'); }
}
