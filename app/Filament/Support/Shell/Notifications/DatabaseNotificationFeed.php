<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Notifications;

use App\Filament\Support\Notifications\DomainNotificationCatalog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final class DatabaseNotificationFeed
{
    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function notificationsFor(
        ?Authenticatable $user,
        ?int $limit = null,
        ?string $type = null,
        ?string $status = null,
    ): Collection {
        if ($user === null) {
            return collect();
        }

        $query = $user->notifications()
            ->select([
                'id',
                'type',
                'title',
                'message',
                'action_url',
                'organization_id',
                'recipient_user_id',
                'data',
                'read_at',
                'sent_email_at',
                'created_at',
                'updated_at',
                'notifiable_type',
                'notifiable_id',
            ])
            ->where(function ($query) use ($user): void {
                $query
                    ->whereNull('recipient_user_id')
                    ->orWhere('recipient_user_id', $user->getAuthIdentifier());
            })
            ->latest();

        $organizationId = $user->organization_id ?? null;

        if (! method_exists($user, 'isSuperadmin') || ! $user->isSuperadmin()) {
            $query->where(function ($query) use ($organizationId): void {
                $query
                    ->whereNull('organization_id')
                    ->orWhere('organization_id', $organizationId);
            });
        }

        if ($type !== null && DomainNotificationCatalog::isSupported($type)) {
            $query->where(function ($query) use ($type): void {
                $query
                    ->where('type', $type)
                    ->orWhere('data->business_type', $type);
            });
        }

        match ($status) {
            'read' => $query->whereNotNull('read_at'),
            'unread' => $query->whereNull('read_at'),
            default => null,
        };

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
        ?string $type = null,
        ?string $status = null,
    ): array {
        return $this->notificationsFor($user, $limit, $type, $status)
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
            ->select([
                'id',
                'type',
                'title',
                'message',
                'action_url',
                'organization_id',
                'recipient_user_id',
                'data',
                'read_at',
                'sent_email_at',
                'created_at',
                'updated_at',
                'notifiable_type',
                'notifiable_id',
            ])
            ->where(function ($query) use ($user): void {
                $query
                    ->whereNull('recipient_user_id')
                    ->orWhere('recipient_user_id', $user->getAuthIdentifier());
            })
            ->when(
                ! method_exists($user, 'isSuperadmin') || ! $user->isSuperadmin(),
                fn ($query) => $query->where(function ($query) use ($user): void {
                    $query
                        ->whereNull('organization_id')
                        ->orWhere('organization_id', $user->organization_id);
                }),
            )
            ->whereKey($notificationId)
            ->first();
    }
}
