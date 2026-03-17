<?php

namespace App\Support\Shell\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class DatabaseNotificationPresenter
{
    /**
     * @return array{title: string, preview: string, relative_time: string, target_url: ?string, is_read: bool}
     */
    public function present(DatabaseNotification $notification): array
    {
        $title = (string) data_get($notification->data, 'title', __('shell.notifications'));
        $message = (string) data_get($notification->data, 'message', data_get($notification->data, 'body', ''));

        return [
            'title' => $title,
            'preview' => Str::limit($message, 96),
            'relative_time' => $notification->created_at?->diffForHumans() ?? '',
            'target_url' => data_get($notification->data, 'url'),
            'is_read' => filled($notification->read_at),
        ];
    }
}
