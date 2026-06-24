<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Get a setting value by key, with optional default. */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /** Set (upsert) a setting value. */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Get many settings at once as key→value array. */
    public static function getMany(array $keys): array
    {
        return static::whereIn('key', $keys)->pluck('value', 'key')->toArray();
    }

    /** Upsert multiple settings at once. */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }

    /**
     * Return the effective "end of deadline day" Carbon for a given deadline.
     * Uses the admin-configured deadline_end_time (default 23:59).
     */
    public static function deadlineEOD(\Illuminate\Support\Carbon $deadline): \Illuminate\Support\Carbon
    {
        $time = static::get('deadline_end_time', '23:59');
        [$h, $m] = array_map('intval', explode(':', $time . ':00'));
        return $deadline->copy()->setTime($h, $m, 0);
    }
}
