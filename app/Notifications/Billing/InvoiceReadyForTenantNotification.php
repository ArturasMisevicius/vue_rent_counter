<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class InvoiceReadyForTenantNotification extends Notification
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
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('admin.invoices.invoice_ready.database_title'),
            'body' => __('admin.invoices.invoice_ready.database_body', [
                'number' => $this->invoice->invoice_number,
            ]),
            'url' => $this->invoiceHistoryUrl(),
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'organization_id' => $this->invoice->organization_id,
            'property_id' => $this->invoice->property_id,
            'billing_period_start' => $this->periodStart(),
            'billing_period_end' => $this->periodEnd(),
        ];
    }

    private function invoiceHistoryUrl(): string
    {
        return route('filament.admin.pages.tenant-invoice-history', [], false).'#tenant-invoice-'.$this->invoice->id;
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
