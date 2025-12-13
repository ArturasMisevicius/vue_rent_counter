<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use App\Models\PlatformNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformNotificationEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private PlatformNotification $platformNotification,
        private Organization $organization
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $trackingUrl = route('platform-notification.track', [
            'notification' => $this->platformNotification->id,
            'organization' => $this->organization->id,
        ]);

        return (new MailMessage)
            ->subject($this->platformNotification->title)
            ->greeting("Hello {$this->organization->name},")
            ->line($this->platformNotification->message)
            ->line('This is an important notification from the Vilnius Utilities Billing Platform.')
            ->action('View in Dashboard', route('filament.admin.pages.dashboard'))
            ->line('If you have any questions, please contact our support team.')
            ->line("Best regards,\nThe Platform Team")
            ->line('<img src="' . $trackingUrl . '" width="1" height="1" style="display:none;" alt="" />');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'notification_id' => $this->platformNotification->id,
            'title' => $this->platformNotification->title,
            'message' => $this->platformNotification->message,
            'organization_id' => $this->organization->id,
        ];
    }
}
