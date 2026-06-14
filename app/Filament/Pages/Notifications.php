<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Notifications\DomainNotificationCatalog;
use App\Filament\Support\Shell\Notifications\DatabaseNotificationFeed;
use App\Filament\Support\Shell\Notifications\DatabaseNotificationPresenter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Notifications extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'notifications';

    protected string $view = 'filament.pages.notifications';

    public ?string $typeFilter = null;

    public string $statusFilter = 'all';

    public ?string $statusMessage = null;

    public function getTitle(): string
    {
        return __('notifications.center.title');
    }

    protected function getViewData(): array
    {
        $feed = app(DatabaseNotificationFeed::class);
        $user = auth()->user();

        return [
            'notifications' => $feed->presentFor(
                user: $user,
                presenter: app(DatabaseNotificationPresenter::class),
                limit: 50,
                type: $this->normalizedTypeFilter(),
                status: $this->normalizedStatusFilter(),
            ),
            'typeOptions' => DomainNotificationCatalog::options(),
            'statusOptions' => [
                'all' => __('notifications.center.filters.all_statuses'),
                'unread' => __('shell.notifications.status.unread'),
                'read' => __('shell.notifications.status.read'),
            ],
            'unreadCount' => $feed->unreadCount($user),
            'totalCount' => $feed->totalCount($user),
        ];
    }

    public function openNotification(string $notificationId): void
    {
        abort_unless(static::canAccess(), 403);

        $notification = app(DatabaseNotificationFeed::class)->findNotification(auth()->user(), $notificationId);

        if ($notification === null) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $destination = app(DatabaseNotificationPresenter::class)->present($notification)['url'];

        if ($destination !== null) {
            $this->redirect($destination, navigate: str_starts_with($destination, '/'));
        }
    }

    public function markAllAsRead(): void
    {
        abort_unless(static::canAccess(), 403);

        app(DatabaseNotificationFeed::class)->markAllAsRead(auth()->user());
        $this->statusMessage = __('shell.notifications.page.messages.marked_all_read');

        Notification::make()
            ->title($this->statusMessage)
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    private function normalizedTypeFilter(): ?string
    {
        return DomainNotificationCatalog::isSupported((string) $this->typeFilter)
            ? $this->typeFilter
            : null;
    }

    private function normalizedStatusFilter(): ?string
    {
        return in_array($this->statusFilter, ['read', 'unread'], true)
            ? $this->statusFilter
            : null;
    }
}
