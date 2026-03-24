<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\Notifications\DatabaseNotificationPresenter;
use App\Livewire\Concerns\AppliesShellLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
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
        $notification = $this->findNotification($notificationId);

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
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

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

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        $user = auth()->user();

        if ($user === null) {
            return collect();
        }

        /** @var Collection<int, DatabaseNotification> $notifications */
        $notifications = $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'updated_at', 'notifiable_type', 'notifiable_id'])
            ->latest()
            ->limit((int) config('tenanto.shell.notifications.limit', 8))
            ->get();

        return $notifications;
    }

    /**
     * @return array<int, array{id: string, title: string, preview: string, relative_time: string, unread: bool, url: ?string}>
     */
    #[Computed]
    public function presentedNotifications(): array
    {
        $presenter = app(DatabaseNotificationPresenter::class);

        return $this->notifications
            ->map(fn (DatabaseNotification $notification): array => $presenter->present($notification))
            ->all();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    #[Computed]
    public function pollSeconds(): int
    {
        return max(5, (int) config('tenanto.shell.polling.notifications', 30));
    }

    protected function findNotification(string $notificationId): ?DatabaseNotification
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        return $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'updated_at', 'notifiable_type', 'notifiable_id'])
            ->whereKey($notificationId)
            ->first();
    }
}
