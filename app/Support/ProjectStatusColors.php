<?php

namespace App\Support;

class ProjectStatusColors
{
    /**
     * Canonical text/background/label per project status (distinct from task status —
     * see TaskStatusColors). Single source of truth — do not duplicate this map elsewhere.
     *
     * `class` is the Tailwind-utility equivalent of the same text/bg pair, for views
     * (e.g. the desktop projects table) that use utility classes instead of inline
     * hex styles — keep both in sync here rather than re-deriving colors locally.
     */
    public const MAP = [
        'active'    => ['text' => '#4F46E5', 'bg' => '#EEF2FF', 'label' => 'Active', 'class' => 'bg-indigo-100 text-indigo-700'],
        'completed' => ['text' => '#047857', 'bg' => '#ECFDF5', 'label' => 'Completed', 'class' => 'bg-emerald-100 text-emerald-700'],
    ];

    public static function for(?string $status): array
    {
        return self::MAP[$status] ?? ['text' => '#6B7280', 'bg' => '#F3F4F6', 'label' => $status ? ucfirst($status) : 'Unknown', 'class' => 'bg-gray-100 text-gray-600'];
    }

    public static function tailwindClass(?string $status): string
    {
        return self::for($status)['class'];
    }

    public static function label(?string $status): string
    {
        return self::for($status)['label'];
    }

    public static function text(?string $status): string
    {
        return self::for($status)['text'];
    }

    public static function bg(?string $status): string
    {
        return self::for($status)['bg'];
    }
}
