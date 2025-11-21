<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantReassignedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Property $newProperty,
        protected ?Property $previousProperty = null
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
        $message = (new MailMessage)
            ->subject('Property Assignment Updated')
            ->greeting("Hello {$notifiable->name}!");

        if ($this->previousProperty) {
            $message->line('Your property assignment has been updated.')
                ->line("**Previous Property:** {$this->previousProperty->address}")
                ->line("**New Property:** {$this->newProperty->address}");
        } else {
            $message->line('You have been assigned to a property:')
                ->line("**Property:** {$this->newProperty->address}");
        }

        return $message
            ->line("**Property Type:** {$this->newProperty->type->value}")
            ->line('')
            ->line('You can now view your utility information for this property.')
            ->action('View Dashboard', url('/tenant/dashboard'))
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
            'new_property_id' => $this->newProperty->id,
            'new_property_address' => $this->newProperty->address,
            'previous_property_id' => $this->previousProperty?->id,
            'previous_property_address' => $this->previousProperty?->address,
        ];
    }
}
