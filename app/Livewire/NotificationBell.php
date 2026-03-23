<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Actions\Notifications\MarkPlatformNotificationReadAction;
use App\Http\Controllers\NotificationTrackingController;
use App\Livewire\Concerns\SupportsEchoListeners;
use App\Models\PlatformNotificationRecipient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class NotificationBell extends Component
{
    use SupportsEchoListeners;

    public ?int $organizationId = null;

    public function mount(): void
    {
        $this->organizationId = auth()->user()?->organization_id;
    }

    public function refreshNotifications(): void
    {
        unset(
            $this->recipientRecords,
            $this->notifications,
            $this->unreadCount,
            $this->pollSeconds,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $listeners = [
            'platform-notification.sent' => 'refreshNotifications',
        ];

        if ($this->shouldUseEchoListeners() && $this->organizationId !== null) {
            $listeners['echo-private:org.'.$this->organizationId.',.platform-notification.sent'] = 'refreshNotifications';
        }

        return $listeners;
    }

    public function trackNotification(
        int $recipientId,
        NotificationTrackingController $notificationTrackingController,
        MarkPlatformNotificationReadAction $markPlatformNotificationReadAction,
    ): void {
        $recipient = $this->findRecipient($recipientId);

        if ($recipient === null) {
            return;
        }

        $notificationTrackingController($recipient, $markPlatformNotificationReadAction);

        $this->refreshNotifications();
    }

    public function render(): View
    {
        return view('livewire.notification-bell', [
            'notifications' => $this->notifications,
            'pollSeconds' => $this->pollSeconds,
            'unreadCount' => $this->unreadCount,
        ]);
    }

    /**
     * @return Collection<int, PlatformNotificationRecipient>
     */
    #[Computed]
    public function recipientRecords(): Collection
    {
        if ($this->organizationId === null) {
            return collect();
        }

        /** @var Collection<int, PlatformNotificationRecipient> $recipients */
        $recipients = PlatformNotificationRecipient::query()
            ->select([
                'id',
                'platform_notification_id',
                'organization_id',
                'delivery_status',
                'sent_at',
                'read_at',
                'created_at',
                'updated_at',
            ])
            ->forOrganization($this->organizationId)
            ->sent()
            ->with([
                'notification:id,title,body,severity,status,scheduled_for,sent_at,created_at,updated_at',
            ])
            ->latestSentFirst()
            ->limit(10)
            ->get();

        return $recipients;
    }

    /**
     * @return array<int, array{recipient_id: int, title: string, preview: string, relative_time: string, unread: bool}>
     */
    #[Computed]
    public function notifications(): array
    {
        $previewLength = max(24, (int) config('tenanto.shell.notifications.preview_length', 120));

        return $this->recipientRecords
            ->map(function (PlatformNotificationRecipient $recipient) use ($previewLength): array {
                $notification = $recipient->notification;
                $body = trim((string) ($notification?->body ?? ''));

                return [
                    'recipient_id' => $recipient->id,
                    'title' => trim((string) ($notification?->title ?? __('shell.notifications.defaults.title'))),
                    'preview' => str($body !== '' ? $body : __('shell.notifications.defaults.body'))
                        ->limit($previewLength)
                        ->value(),
                    'relative_time' => $recipient->sent_at?->diffForHumans() ?? __('shell.notifications.defaults.just_now'),
                    'unread' => $recipient->read_at === null,
                ];
            })
            ->all();
    }

    #[Computed]
    public function unreadCount(): int
    {
        if ($this->organizationId === null) {
            return 0;
        }

        return PlatformNotificationRecipient::query()
            ->forOrganization($this->organizationId)
            ->sent()
            ->unread()
            ->count();
    }

    #[Computed]
    public function pollSeconds(): int
    {
        return max(5, (int) config('tenanto.shell.polling.notifications', 30));
    }

    protected function findRecipient(int $recipientId): ?PlatformNotificationRecipient
    {
        if ($this->organizationId === null) {
            return null;
        }

        return PlatformNotificationRecipient::query()
            ->select([
                'id',
                'platform_notification_id',
                'organization_id',
                'delivery_status',
                'sent_at',
                'read_at',
                'created_at',
                'updated_at',
            ])
            ->forOrganization($this->organizationId)
            ->whereKey($recipientId)
            ->first();
    }
}
