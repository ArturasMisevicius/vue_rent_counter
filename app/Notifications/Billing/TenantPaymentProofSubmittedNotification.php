<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\InvoicePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class TenantPaymentProofSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly InvoicePayment $payment,
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
        $this->payment->loadMissing([
            'invoice:id,organization_id,property_id,tenant_user_id,invoice_number,currency,total_amount,paid_amount,balance_amount,payment_status,due_date',
            'tenant:id,organization_id,name,email',
        ]);

        return [
            'title' => __('admin.payments.notifications.proof_submitted_title'),
            'body' => __('admin.payments.notifications.proof_submitted_body', [
                'tenant' => $this->payment->tenant?->name ?? __('dashboard.not_available'),
                'amount' => $this->amount(),
                'invoice' => $this->invoiceNumber(),
            ]),
            'url' => route('filament.admin.resources.payments.view', ['record' => $this->payment], false),
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'invoice_number' => $this->invoiceNumber(),
            'tenant_id' => $this->payment->tenant_id,
            'organization_id' => $this->payment->organization_id,
            'property_id' => $this->payment->property_id,
            'amount' => (float) $this->payment->amount,
            'currency' => $this->payment->currency,
            'reference' => $this->payment->reference,
            'status' => $this->payment->status?->value,
        ];
    }

    private function amount(): string
    {
        return EuMoneyFormatter::format($this->payment->amount, $this->payment->currency);
    }

    private function invoiceNumber(): string
    {
        return $this->payment->invoice?->invoice_number ?? (string) $this->payment->invoice_id;
    }
}
