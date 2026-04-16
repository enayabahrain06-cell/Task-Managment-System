<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = ['title', 'description', 'meeting_date', 'start_time', 'duration_minutes', 'location', 'color', 'created_by'];

    protected $casts = ['meeting_date' => 'date'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'meeting_user');
    }

    public function isToday(): bool
    {
        return $this->meeting_date->isToday();
    }

    public function endTime(): string
    {
        return \Carbon\Carbon::createFromTimeString($this->start_time)
            ->addMinutes($this->duration_minutes)
            ->format('H:i');
    }
}
