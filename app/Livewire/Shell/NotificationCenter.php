<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\Notifications\DatabaseNotificationPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Component;

class NotificationCenter extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refreshNotifications();
    }

    public function refreshNotifications(): void
    {
        $user = auth()->user();

        $this->unreadCount = $user?->unreadNotifications()->count() ?? 0;
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

    public function render(DatabaseNotificationPresenter $presenter): View
    {
        return view('livewire.shell.notification-center', [
            'notifications' => $this->notifications()
                ->map(fn (DatabaseNotification $notification): array => $presenter->present($notification))
                ->all(),
            'pollSeconds' => max(5, (int) config('tenanto.shell.polling.notifications', 30)),
        ]);
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    protected function notifications(): Collection
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
