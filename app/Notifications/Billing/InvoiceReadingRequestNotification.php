<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoiceReadingRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
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
        $periodStart = $this->periodStart();
        $periodEnd = $this->periodEnd();

        return (new MailMessage)
            ->subject(__('admin.invoices.reading_request.subject', [
                'number' => $this->invoice->invoice_number,
            ]))
            ->greeting(__('admin.invoices.reading_request.greeting'))
            ->line(__('admin.invoices.reading_request.intro', [
                'number' => $this->invoice->invoice_number,
            ]))
            ->line(__('admin.invoices.reading_request.period', [
                'from' => $periodStart,
                'to' => $periodEnd,
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
            'title' => __('admin.invoices.reading_request.database_title'),
            'body' => __('admin.invoices.reading_request.database_body', [
                'number' => $this->invoice->invoice_number,
                'from' => $this->periodStart(),
                'to' => $this->periodEnd(),
            ]),
            'url' => route('tenant.readings.create', ['invoice' => $this->invoice->id], false),
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'organization_id' => $this->invoice->organization_id,
            'property_id' => $this->invoice->property_id,
            'billing_period_start' => $this->periodStart(),
            'billing_period_end' => $this->periodEnd(),
        ];
    }

    private function periodStart(): string
    {
        return $this->invoice->billing_period_start?->toDateString() ?? __('dashboard.not_available');
    }

    private function periodEnd(): string
    {
        return $this->invoice->billing_period_end?->toDateString() ?? __('dashboard.not_available');
    }
}
