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
        $typeLabel = method_exists($this->newProperty->type, 'label')
            ? $this->newProperty->type->label()
            : $this->newProperty->type->value;

        $message = (new MailMessage)
            ->subject(__('notifications.tenant_reassigned.subject'))
            ->greeting(__('notifications.tenant_reassigned.greeting', ['name' => $notifiable->name]));

        if ($this->previousProperty) {
            $message->line(__('notifications.tenant_reassigned.updated'))
                ->line(__('notifications.tenant_reassigned.previous', ['address' => $this->previousProperty->address]))
                ->line(__('notifications.tenant_reassigned.new', ['address' => $this->newProperty->address]));
        } else {
            $message->line(__('notifications.tenant_reassigned.assigned'))
                ->line(__('notifications.tenant_reassigned.property', ['address' => $this->newProperty->address]));
        }

        return $message
            ->line(__('notifications.tenant_reassigned.property_type', ['type' => $typeLabel]))
            ->line('')
            ->line(__('notifications.tenant_reassigned.info'))
            ->action(__('notifications.tenant_reassigned.view_dashboard'), url('/tenant/dashboard'))
            ->line(__('notifications.tenant_reassigned.support'));
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
