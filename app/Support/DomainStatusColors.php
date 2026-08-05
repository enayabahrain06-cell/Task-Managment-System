<?php

namespace App\Support;

class DomainStatusColors
{
    /**
     * Canonical text/background/label per domain status (Domain::getStatusAttribute()
     * only ever returns one of these three). Single source of truth — do not duplicate
     * this map elsewhere.
     */
    public const MAP = [
        'active'         => ['text' => '#16A34A', 'bg' => '#ECFDF5', 'label' => 'Active'],
        'expiring_soon'  => ['text' => '#D97706', 'bg' => '#FEF3C7', 'label' => 'Expiring Soon'],
        'expired'        => ['text' => '#DC2626', 'bg' => '#FEE2E2', 'label' => 'Expired'],
    ];

    public static function for(?string $status): array
    {
        return self::MAP[$status] ?? ['text' => '#6B7280', 'bg' => '#F3F4F6', 'label' => 'Unknown'];
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
