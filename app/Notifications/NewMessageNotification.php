<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewMessageNotification extends Notification
{
    public function __construct(private Message $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isGroup  = $this->message->group_id !== null;
        $sender   = $this->message->sender->name ?? 'Someone';
        $body     = $this->message->deleted_at
                        ? ''
                        : ($this->message->body ?: ($this->message->file_name ? '📎 '.$this->message->file_name : '📎 File'));

        if ($isGroup) {
            $groupName = optional($this->message->group)->name ?? 'Group';
            return [
                'title'      => '💬 '.$sender.' in '.$groupName,
                'message'    => Str::limit($body, 80),
                'url'        => route('messages.index').'?group='.$this->message->group_id,
                'icon'       => 'fa-comments',
                'color'      => 'indigo',
                'notif_type' => 'new_message',
                'sender_id'  => $this->message->sender_id,
                'group_id'   => $this->message->group_id,
            ];
        }

        return [
            'title'      => '💬 New message from '.$sender,
            'message'    => Str::limit($body, 80),
            'url'        => route('messages.index').'?user='.$this->message->sender_id,
            'icon'       => 'fa-comment',
            'color'      => 'indigo',
            'notif_type' => 'new_message',
            'sender_id'  => $this->message->sender_id,
            'group_id'   => null,
        ];
    }
}
