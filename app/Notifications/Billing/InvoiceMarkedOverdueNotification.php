<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class InvoiceMarkedOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
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
        $this->invoice->loadMissing([
            'tenant:id,organization_id,name,email',
        ]);

        return [
            'title' => __('admin.payments.notifications.overdue_title'),
            'body' => __('admin.payments.notifications.overdue_body', [
                'invoice' => $this->invoice->invoice_number,
                'tenant' => $this->invoice->tenant?->name ?? __('dashboard.not_available'),
                'days' => $this->invoice->overdueDays(),
                'amount' => EuMoneyFormatter::format($this->invoice->outstanding_balance, $this->invoice->currency),
            ]),
            'url' => route('filament.admin.resources.invoices.view', ['record' => $this->invoice], false),
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'tenant_id' => $this->invoice->tenant_user_id,
            'organization_id' => $this->invoice->organization_id,
            'property_id' => $this->invoice->property_id,
            'balance_amount' => $this->invoice->outstanding_balance,
            'currency' => $this->invoice->currency,
            'payment_status' => $this->invoice->payment_status?->value,
            'overdue_days' => $this->invoice->overdueDays(),
        ];
    }
}
