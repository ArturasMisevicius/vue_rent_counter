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
        return (new MailMessage)
            ->subject('Welcome to Vilnius Utilities Billing System')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your tenant account has been created for the following property:')
            ->line("**Address:** {$this->property->address}")
            ->line("**Property Type:** {$this->property->type->value}")
            ->line('')
            ->line('**Login Credentials:**')
            ->line("Email: {$notifiable->email}")
            ->line("Temporary Password: {$this->temporaryPassword}")
            ->line('')
            ->line('Please log in and change your password immediately.')
            ->action('Log In', url('/login'))
            ->line('If you have any questions, please contact your property administrator.');
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
