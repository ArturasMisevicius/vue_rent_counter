<?php

namespace App\Filament\Pages;

use App\Filament\Support\Shell\Notifications\DatabaseNotificationFeed;
use App\Filament\Support\Shell\Notifications\DatabaseNotificationPresenter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PlatformNotifications extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'platform-notifications';

    protected string $view = 'filament.pages.platform-notifications';

    public ?string $statusMessage = null;

    public function getTitle(): string
    {
        return __('shell.notifications.page.title');
    }

    protected function getViewData(): array
    {
        $feed = app(DatabaseNotificationFeed::class);
        $user = auth()->user();

        return [
            'notifications' => $feed->presentFor($user, app(DatabaseNotificationPresenter::class), 25),
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

        $this->statusMessage = __('shell.notifications.page.messages.opened');

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
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
