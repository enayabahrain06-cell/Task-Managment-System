<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubmissionEdit extends Model {
    public $timestamps = false;
    protected $fillable = ['task_submission_id','old_note','edited_by_id','created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function submission(): BelongsTo { return $this->belongsTo(TaskSubmission::class, 'task_submission_id'); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'edited_by_id'); }
}
