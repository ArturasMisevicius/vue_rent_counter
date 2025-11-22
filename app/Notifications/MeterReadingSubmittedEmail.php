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

        $message = (new MailMessage)
            ->subject('New Meter Reading Submitted')
            ->greeting("Hello {$notifiable->name}!")
            ->line("A new meter reading has been submitted by **{$this->tenant->name}**.")
            ->line('')
            ->line('**Reading Details:**')
            ->line("Property: {$property->address}")
            ->line("Meter Type: {$meter->type->value}")
            ->line("Serial Number: {$meter->serial_number}")
            ->line("Reading Date: {$this->meterReading->reading_date->format('F j, Y')}")
            ->line("Reading Value: {$this->meterReading->value}");

        if ($this->meterReading->zone) {
            $message->line("Zone: {$this->meterReading->zone}");
        }

        if ($consumption !== null) {
            $message->line("Consumption: {$consumption}");
        }

        return $message
            ->line('')
            ->action('View Meter Readings', url('/manager/meter-readings'))
            ->line('You can review and manage all meter readings from your dashboard.');
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
