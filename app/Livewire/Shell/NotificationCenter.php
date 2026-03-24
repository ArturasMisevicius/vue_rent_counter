<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\Notifications\DatabaseNotificationFeed;
use App\Filament\Support\Shell\Notifications\DatabaseNotificationPresenter;
use App\Livewire\Concerns\AppliesShellLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationCenter extends Component
{
    use AppliesShellLocale;

    public function refreshNotifications(): void
    {
        unset(
            $this->unreadCount,
            $this->notifications,
            $this->presentedNotifications,
        );
    }

    public function openNotification(string $notificationId): void
    {
        $notification = app(DatabaseNotificationFeed::class)->findNotification(auth()->user(), $notificationId);

        if ($notification === null) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $this->refreshNotifications();

        $destination = app(DatabaseNotificationPresenter::class)->present($notification)['url'];

        if ($destination !== null) {
            $this->redirect($destination, navigate: str_starts_with($destination, '/'));
        }
    }

    public function markAllAsRead(): void
    {
        app(DatabaseNotificationFeed::class)->markAllAsRead(auth()->user());
        $this->refreshNotifications();
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();
        $this->refreshNotifications();
    }

    public function render(DatabaseNotificationPresenter $presenter): View
    {
        return view('livewire.shell.notification-center', [
            'notifications' => $this->presentedNotifications,
            'pollSeconds' => $this->pollSeconds,
            'unreadCount' => $this->unreadCount,
        ]);
    }

    #[Computed]
    public function notifications(): Collection
    {
        return app(DatabaseNotificationFeed::class)->notificationsFor(
            auth()->user(),
            (int) config('tenanto.shell.notifications.limit', 8),
        );
    }

    /**
     * @return array<int, array{id: string, title: string, preview: string, relative_time: string, unread: bool, url: ?string}>
     */
    #[Computed]
    public function presentedNotifications(): array
    {
        return app(DatabaseNotificationFeed::class)->presentFor(
            auth()->user(),
            app(DatabaseNotificationPresenter::class),
            (int) config('tenanto.shell.notifications.limit', 8),
        );
    }

    #[Computed]
    public function unreadCount(): int
    {
        return app(DatabaseNotificationFeed::class)->unreadCount(auth()->user());
    }

    #[Computed]
    public function pollSeconds(): int
    {
        return max(5, (int) config('tenanto.shell.polling.notifications', 30));
    }
}
