<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MissingReadingReminderNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $missingReadings
     */
    public function __construct(
        private readonly Invoice $invoice,
        private readonly array $missingReadings,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admin.billing_review.notifications.missing_reading_reminder.subject', [
                'number' => $this->invoice->invoice_number,
            ]))
            ->line(__('admin.billing_review.notifications.missing_reading_reminder.body', [
                'count' => count($this->missingReadings),
            ]))
            ->action(
                __('admin.invoices.reading_request.action'),
                route('tenant.readings.create', ['invoice' => $this->invoice->id]),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('admin.billing_review.notifications.missing_reading_reminder.title'),
            'body' => __('admin.billing_review.notifications.missing_reading_reminder.body', [
                'count' => count($this->missingReadings),
            ]),
            'url' => route('tenant.readings.create', ['invoice' => $this->invoice->id], false),
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'organization_id' => $this->invoice->organization_id,
            'property_id' => $this->invoice->property_id,
            'missing_readings' => $this->missingReadings,
        ];
    }
}
