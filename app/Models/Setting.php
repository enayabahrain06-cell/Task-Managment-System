<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    // Values under these keys are transparently encrypted at rest (Laravel APP_KEY).
    // They still round-trip as plain strings through get()/set() — only the DB column is ciphertext.
    private const ENCRYPTED_KEYS = ['wa_token', 'storage_omv_password'];

    // Request-scoped in-memory cache — avoids repeated DB hits for the same key within a single request.
    private static array $cache = [];

    /** Get a setting value by key, with optional default. */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = static::decryptIfNeeded($key, static::where('key', $key)->value('value'));
        }
        return static::$cache[$key] ?? $default;
    }

    /** Set (upsert) a setting value and invalidate the in-memory cache for that key. */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => static::encryptIfNeeded($key, $value)]);
        static::$cache[$key] = $value;
    }

    /** Get many settings at once as key→value array (populates cache for each key). */
    public static function getMany(array $keys): array
    {
        $missing = array_values(array_filter($keys, fn($k) => !array_key_exists($k, static::$cache)));

        if ($missing) {
            $rows = static::whereIn('key', $missing)->pluck('value', 'key')->toArray();
            foreach ($missing as $k) {
                static::$cache[$k] = static::decryptIfNeeded($k, $rows[$k] ?? null);
            }
        }

        $result = [];
        foreach ($keys as $k) {
            if (static::$cache[$k] !== null) {
                $result[$k] = static::$cache[$k];
            }
        }
        return $result;
    }

    /** Upsert multiple settings at once. */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }

    /** Encrypt a value before it hits the DB, only for keys flagged as secret. */
    private static function encryptIfNeeded(string $key, mixed $value): mixed
    {
        if (!in_array($key, self::ENCRYPTED_KEYS, true) || $value === null || $value === '') {
            return $value;
        }
        return Crypt::encryptString((string) $value);
    }

    /**
     * Decrypt a value read from the DB, only for keys flagged as secret.
     * Falls back to the raw value on failure — covers rows saved before encryption was added.
     */
    private static function decryptIfNeeded(string $key, mixed $value): mixed
    {
        if (!in_array($key, self::ENCRYPTED_KEYS, true) || $value === null || $value === '') {
            return $value;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
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
