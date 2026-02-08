<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Property $property,
        protected string $temporaryPassword
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
        $propertyType = method_exists($this->property->type, 'label')
            ? $this->property->type->label()
            : $this->property->type->value;

        return (new MailMessage)
            ->subject(__('notifications.welcome.subject'))
            ->greeting(__('notifications.welcome.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.welcome.account_created'))
            ->line(__('notifications.welcome.address', ['address' => $this->property->address]))
            ->line(__('notifications.welcome.property_type', ['type' => $propertyType]))
            ->line('')
            ->line(__('notifications.welcome.credentials_heading'))
            ->line(__('notifications.welcome.email', ['email' => $notifiable->email]))
            ->line(__('notifications.welcome.temporary_password', ['password' => $this->temporaryPassword]))
            ->line('')
            ->line(__('notifications.welcome.password_reminder'))
            ->action(__('notifications.welcome.action'), url('/login'))
            ->line(__('notifications.welcome.support'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'property_id' => $this->property->id,
            'property_address' => $this->property->address,
        ];
    }
}
