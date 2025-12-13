<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuperadminRenewalFailureNotification extends Notification implements ShouldQueue
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
        $organizationName = $this->subscription->user->organization_name ?? $this->subscription->user->name;

        return (new MailMessage)
            ->subject('Auto-Renewal Failed - Action Required')
            ->greeting('Hello Superadmin')
            ->line('An automatic subscription renewal has failed and requires your attention.')
            ->line('')
            ->line('**Organization:** ' . $organizationName)
            ->line('**Subscription ID:** ' . $this->subscription->id)
            ->line('**Plan:** ' . ucfirst($this->subscription->plan_type))
            ->line('**Expiry Date:** ' . $this->subscription->expires_at->format('F j, Y'))
            ->line('**Failure Reason:** ' . $this->failureReason)
            ->line('')
            ->line('Please review this subscription and take appropriate action to prevent service disruption.')
            ->action('View Subscription', url('/admin/subscriptions/' . $this->subscription->id))
            ->line('The organization admin has been notified of this failure.');
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
            'organization_name' => $this->subscription->user->organization_name ?? $this->subscription->user->name,
            'failure_reason' => $this->failureReason,
            'expires_at' => $this->subscription->expires_at->toDateTimeString(),
        ];
    }
}