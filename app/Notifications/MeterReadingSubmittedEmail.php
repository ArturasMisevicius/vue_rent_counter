<?php

namespace App\Notifications;

use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeterReadingSubmittedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected MeterReading $meterReading,
        protected User $tenant
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
        $meter = $this->meterReading->meter;
        $property = $meter->property;
        $consumption = $this->meterReading->getConsumption();
        $meterTypeLabel = method_exists($meter->type, 'label')
            ? $meter->type->label()
            : $meter->type->value;

        $message = (new MailMessage)
            ->subject(__('notifications.meter_reading_submitted.subject'))
            ->greeting(__('notifications.meter_reading_submitted.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.meter_reading_submitted.submitted_by', ['tenant' => $this->tenant->name]))
            ->line('')
            ->line(__('notifications.meter_reading_submitted.details'))
            ->line(__('notifications.meter_reading_submitted.property', ['address' => $property->address]))
            ->line(__('notifications.meter_reading_submitted.meter_type', ['type' => $meterTypeLabel]))
            ->line(__('notifications.meter_reading_submitted.serial', ['serial' => $meter->serial_number]))
            ->line(__('notifications.meter_reading_submitted.reading_date', [
                'date' => $this->meterReading->reading_date->format('F j, Y'),
            ]))
            ->line(__('notifications.meter_reading_submitted.reading_value', ['value' => $this->meterReading->value]));

        if ($this->meterReading->zone) {
            $message->line(__('notifications.meter_reading_submitted.zone', ['zone' => $this->meterReading->zone]));
        }

        if ($consumption !== null) {
            $message->line(__('notifications.meter_reading_submitted.consumption', ['consumption' => $consumption]));
        }

        return $message
            ->line('')
            ->action(__('notifications.meter_reading_submitted.view'), url('/manager/meter-readings'))
            ->line(__('notifications.meter_reading_submitted.manage_hint'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'meter_reading_id' => $this->meterReading->id,
            'meter_id' => $this->meterReading->meter_id,
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'reading_date' => $this->meterReading->reading_date->toDateTimeString(),
            'value' => $this->meterReading->value,
            'zone' => $this->meterReading->zone,
        ];
    }
}
