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
            ->subject(__('notifications.subscription_expiry.subject'))
            ->greeting(__('notifications.subscription_expiry.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.subscription_expiry.intro', [
                'days' => $daysRemaining,
                'date' => $expiryDate,
            ]))
            ->line('')
            ->line(__('notifications.subscription_expiry.plan', [
                'plan' => enum_label($this->subscription->plan_type, SubscriptionPlanType::class),
            ]))
            ->line(__('notifications.subscription_expiry.properties', [
                'used' => $notifiable->properties()->count(),
                'max' => $this->subscription->max_properties,
            ]))
            ->line(__('notifications.subscription_expiry.tenants', [
                'used' => $notifiable->childUsers()->where('role', 'tenant')->count(),
                'max' => $this->subscription->max_tenants,
            ]))
            ->line('')
            ->line(__('notifications.subscription_expiry.cta_intro'))
            ->line(__('notifications.subscription_expiry.cta_notice'))
            ->action(__('notifications.subscription_expiry.action'), url('/admin/profile'))
            ->line(__('notifications.subscription_expiry.support'));
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
