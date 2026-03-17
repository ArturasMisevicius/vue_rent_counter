<?php

namespace App\Livewire\Shell;

use App\Models\User;
use App\Support\Shell\Notifications\DatabaseNotificationPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationCenter extends Component
{
    public bool $isOpen = false;

    public function togglePanel(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);
    }

    public function openNotification(string $notificationId)
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $notification = $user->notifications()->findOrFail($notificationId);

        if (blank($notification->read_at)) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        $url = data_get($notification->data, 'url');

        if (filled($url)) {
            return $this->redirect($url, navigate: true);
        }

        $this->isOpen = false;

        return null;
    }

    /**
     * @return Collection<int, array{id: string, title: string, preview: string, relative_time: string, target_url: ?string, is_read: bool}>
     */
    public function getNotificationsProperty(): Collection
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return collect();
        }

        $presenter = app(DatabaseNotificationPresenter::class);

        return $user->notifications()
            ->latest()
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->getKey(),
                ...$presenter->present($notification),
            ]);
    }

    public function getUnreadCountProperty(): int
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    #[On('shell-locale-updated')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('livewire.shell.notification-center');
    }
}
