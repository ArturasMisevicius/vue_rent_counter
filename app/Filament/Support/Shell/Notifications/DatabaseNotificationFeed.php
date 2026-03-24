<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Notifications;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final class DatabaseNotificationFeed
{
    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function notificationsFor(?Authenticatable $user, ?int $limit = null): Collection
    {
        if ($user === null) {
            return collect();
        }

        $query = $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'updated_at', 'notifiable_type', 'notifiable_id'])
            ->latest();

        if ($limit !== null) {
            $query->limit($limit);
        }

        /** @var Collection<int, DatabaseNotification> $notifications */
        $notifications = $query->get();

        return $notifications;
    }

    /**
     * @return array<int, array{id: string, title: string, preview: string, relative_time: string, unread: bool, url: ?string}>
     */
    public function presentFor(
        ?Authenticatable $user,
        DatabaseNotificationPresenter $presenter,
        ?int $limit = null,
    ): array {
        return $this->notificationsFor($user, $limit)
            ->map(fn (DatabaseNotification $notification): array => $presenter->present($notification))
            ->all();
    }

    public function unreadCount(?Authenticatable $user): int
    {
        return $user?->unreadNotifications()->count() ?? 0;
    }

    public function totalCount(?Authenticatable $user): int
    {
        return $user?->notifications()->count() ?? 0;
    }

    public function markAllAsRead(?Authenticatable $user): void
    {
        $user?->unreadNotifications()->update([
            'read_at' => now(),
        ]);
    }

    public function findNotification(?Authenticatable $user, string $notificationId): ?DatabaseNotification
    {
        if ($user === null) {
            return null;
        }

        return $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'updated_at', 'notifiable_type', 'notifiable_id'])
            ->whereKey($notificationId)
            ->first();
    }
}
