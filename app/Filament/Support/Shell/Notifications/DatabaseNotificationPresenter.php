<?php

namespace App\Filament\Support\Shell\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class DatabaseNotificationPresenter
{
    /**
     * @return array{id: string, title: string, preview: string, relative_time: string, unread: bool, url: ?string}
     */
    public function present(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        $title = is_string($data['title'] ?? null) && filled($data['title'])
            ? $data['title']
            : __('shell.notifications.defaults.title');

        $body = is_string($data['body'] ?? null) && filled($data['body'])
            ? $data['body']
            : __('shell.notifications.defaults.body');

        $url = is_string($data['url'] ?? null) ? $data['url'] : null;

        return [
            'id' => $notification->id,
            'title' => $title,
            'preview' => Str::limit($body, (int) config('tenanto.shell.notifications.preview_length', 120)),
            'relative_time' => $notification->created_at?->diffForHumans() ?? __('shell.notifications.defaults.just_now'),
            'unread' => $notification->read_at === null,
            'url' => $this->normalizeUrl($url),
        ];
    }

    protected function normalizeUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
