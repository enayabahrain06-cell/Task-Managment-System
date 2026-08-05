<?php

namespace App\Support;

class TaskStatusColors
{
    /**
     * Canonical text/background/label per task status. Single source of truth —
     * do not duplicate this map anywhere else in the codebase.
     */
    public const MAP = [
        'draft' => ['text' => '#6B7280', 'bg' => '#F3F4F6', 'label' => 'Draft'],
        'assigned' => ['text' => '#4F46E5', 'bg' => '#EEF2FF', 'label' => 'Assigned'],
        'viewed' => ['text' => '#0369A1', 'bg' => '#E0F2FE', 'label' => 'Viewed'],
        'in_progress' => ['text' => '#D97706', 'bg' => '#FEF3C7', 'label' => 'In Progress'],
        'paused' => ['text' => '#92400E', 'bg' => '#FEF3C7', 'label' => 'Paused'],
        'submitted' => ['text' => '#7C3AED', 'bg' => '#EDE9FE', 'label' => 'In Review'],
        'revision_requested' => ['text' => '#DC2626', 'bg' => '#FEE2E2', 'label' => 'Revision Requested'],
        'pending_customer' => ['text' => '#C2410C', 'bg' => '#FFF7ED', 'label' => 'Awaiting Client'],
        'approved' => ['text' => '#059669', 'bg' => '#D1FAE5', 'label' => 'Approved'],
        'delivered' => ['text' => '#047857', 'bg' => '#ECFDF5', 'label' => 'Delivered'],
        'archived' => ['text' => '#6B7280', 'bg' => '#F3F4F6', 'label' => 'Archived'],
    ];

    public static function for(?string $status): array
    {
        return self::MAP[$status] ?? self::MAP['draft'];
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
