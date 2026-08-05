<?php

namespace App\Support;

use App\Models\SocialAccount;

class SocialAccountColors
{
    /**
     * Canonical text/background/label per social-account status. Single source of
     * truth — do not duplicate this map elsewhere. Platform colors/icons stay on
     * SocialAccount::platforms() (already the single source for that data); this
     * class only re-exposes them under the same naming convention as the status
     * helpers so callers don't need to know two different APIs.
     */
    public const MAP = [
        'active'    => ['text' => '#16A34A', 'bg' => '#ECFDF5', 'label' => 'Active'],
        'inactive'  => ['text' => '#6B7280', 'bg' => '#F3F4F6', 'label' => 'Inactive'],
        'suspended' => ['text' => '#DC2626', 'bg' => '#FEE2E2', 'label' => 'Suspended'],
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

    public static function platform(?string $platform): array
    {
        $info = SocialAccount::platforms()[$platform] ?? [
            'label' => $platform ? ucfirst($platform) : 'Unknown',
            'icon' => 'fa-globe',
            'color' => '#6B7280',
            'bg' => '#F3F4F6',
        ];

        return $info;
    }
}
