<?php

namespace App\Observers;

use App\Services\MqttService;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationObserver
{
    public function created(DatabaseNotification $notification): void
    {
        $userId = $notification->notifiable_id;
        $data   = $notification->data;

        // Count AFTER this insert so the bell always shows the exact DB value.
        $unreadCount = DatabaseNotification::where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();

        MqttService::notifyUser((int) $userId, [
            'id'           => $notification->id,
            'title'        => $data['title']      ?? 'New Notification',
            'message'      => $data['message']    ?? '',
            'url'          => $data['url']        ?? '#',
            'icon'         => $data['icon']       ?? 'fa-bell',
            'color'        => $data['color']      ?? 'gray',
            'notif_type'   => $data['notif_type'] ?? null,
            'unread_count' => $unreadCount,
            'created_at'   => now()->toISOString(),
        ]);
    }
}
