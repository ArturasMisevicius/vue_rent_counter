<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TenantReadingsSubmittedForInvoiceNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly User $tenant,
        private readonly int $readingCount,
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
            ->subject(__('admin.invoices.reading_submitted.subject', [
                'number' => $this->invoice->invoice_number,
            ]))
            ->greeting(__('admin.invoices.reading_submitted.greeting'))
            ->line(__('admin.invoices.reading_submitted.intro', [
                'tenant' => $this->tenant->name,
                'count' => $this->readingCount,
                'number' => $this->invoice->invoice_number,
            ]))
            ->line(__('admin.invoices.reading_submitted.period', [
                'from' => $this->periodStart(),
                'to' => $this->periodEnd(),
            ]))
            ->action(
                __('admin.invoices.reading_submitted.action'),
                route('filament.admin.resources.invoices.edit', ['record' => $this->invoice]),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('admin.invoices.reading_submitted.database_title'),
            'body' => __('admin.invoices.reading_submitted.database_body', [
                'tenant' => $this->tenant->name,
                'count' => $this->readingCount,
                'number' => $this->invoice->invoice_number,
            ]),
            'url' => route('filament.admin.resources.invoices.edit', ['record' => $this->invoice], false),
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'organization_id' => $this->invoice->organization_id,
            'property_id' => $this->invoice->property_id,
            'tenant_user_id' => $this->tenant->id,
            'submitted_reading_count' => $this->readingCount,
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
