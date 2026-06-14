<?php

namespace App\Filament\Support\Shell\Notifications;

use App\Filament\Support\Notifications\DomainNotificationCatalog;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class DatabaseNotificationPresenter
{
    /**
     * @return array{id: string, type: string|null, type_label: string, title: string, preview: string, relative_time: string, unread: bool, url: ?string}
     */
    public function present(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        $title = is_string($notification->title ?? null) && filled($notification->title)
            ? $notification->title
            : null;
        $title ??= is_string($data['title'] ?? null) && filled($data['title'])
            ? $data['title']
            : __('shell.notifications.defaults.title');

        $body = is_string($notification->message ?? null) && filled($notification->message)
            ? $notification->message
            : null;
        $body ??= is_string($data['body'] ?? null) && filled($data['body'])
            ? $data['body']
            : __('shell.notifications.defaults.body');

        $url = is_string($notification->action_url ?? null) && filled($notification->action_url)
            ? $notification->action_url
            : (is_string($data['url'] ?? null) ? $data['url'] : null);
        $type = $this->businessType($notification, $data);

        return [
            'id' => $notification->id,
            'type' => $type,
            'type_label' => $this->typeLabel($type),
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function businessType(DatabaseNotification $notification, array $data): ?string
    {
        $dataType = $data['business_type'] ?? null;

        if (is_string($dataType) && DomainNotificationCatalog::isSupported($dataType)) {
            return $dataType;
        }

        return DomainNotificationCatalog::isSupported($notification->type)
            ? $notification->type
            : null;
    }

    private function typeLabel(?string $type): string
    {
        return $type !== null && DomainNotificationCatalog::isSupported($type)
            ? __("notifications.types.{$type}")
            : __('shell.notifications.defaults.title');
    }
}
