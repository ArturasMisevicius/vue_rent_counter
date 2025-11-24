<?php

namespace App\Notifications;

use App\Enums\SubscriptionPlanType;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiryWarningEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Subscription $subscription
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
        $daysRemaining = $this->subscription->daysUntilExpiry();
        $expiryDate = $this->subscription->expires_at->format('F j, Y');

        return (new MailMessage)
            ->subject('Subscription Expiry Warning')
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your subscription to the Vilnius Utilities Billing System will expire in **{$daysRemaining} days** on **{$expiryDate}**.")
            ->line('')
            ->line('**Current Plan:** ' . enum_label($this->subscription->plan_type, SubscriptionPlanType::class))
            ->line("**Properties:** {$notifiable->properties()->count()} / {$this->subscription->max_properties}")
            ->line("**Tenants:** {$notifiable->childUsers()->where('role', 'tenant')->count()} / {$this->subscription->max_tenants}")
            ->line('')
            ->line('To avoid interruption of service, please renew your subscription before it expires.')
            ->line('After expiry, your account will be restricted to read-only access until renewal.')
            ->action('Renew Subscription', url('/admin/profile'))
            ->line('If you have any questions about renewal, please contact support.');
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
            'expires_at' => $this->subscription->expires_at->toDateTimeString(),
            'days_remaining' => $this->subscription->daysUntilExpiry(),
            'plan_type' => $this->subscription->plan_type,
        ];
    }
}
