<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AutoRenewalFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Subscription $subscription,
        protected string $failureReason
    ) {}

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
        return (new MailMessage)
            ->subject('Auto-Renewal Failed for Your Subscription')
            ->greeting('Hello ' . $notifiable->name)
            ->line('We attempted to automatically renew your subscription, but the renewal failed.')
            ->line('**Failure Reason:** ' . $this->failureReason)
            ->line('')
            ->line('**Subscription Details:**')
            ->line('Plan: ' . ucfirst($this->subscription->plan_type))
            ->line('Expiry Date: ' . $this->subscription->expires_at->format('F j, Y'))
            ->line('')
            ->line('Please contact support or manually renew your subscription to avoid service interruption.')
            ->action('Renew Subscription', url('/admin/profile'))
            ->line('If you need assistance, please contact our support team.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'failure_reason' => $this->failureReason,
            'expires_at' => $this->subscription->expires_at->toDateTimeString(),
        ];
    }
}