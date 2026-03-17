<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class RecordInvoicePaymentAction
{
    public function __construct(
        private readonly SaveInvoiceDraftAction $saveInvoiceDraftAction,
    ) {}

    public function handle(Invoice $invoice, array $attributes): Invoice
    {
        $validated = $this->validate($attributes);

        $amountPaid = (float) ($validated['amount_paid'] ?? $validated['paid_amount'] ?? $invoice->amount_paid);

        $invoice = $this->saveInvoiceDraftAction->handle($invoice, [
            'amount_paid' => $amountPaid,
            'paid_amount' => $amountPaid,
            'payment_reference' => $validated['payment_reference'] ?? $invoice->payment_reference,
            'paid_at' => $validated['paid_at'] ?? $invoice->paid_at,
            'status' => $amountPaid >= (float) $invoice->total_amount
                ? InvoiceStatus::PAID
                : InvoiceStatus::PARTIALLY_PAID,
        ]);

        return $invoice->fresh();
    }

    private function validate(array $attributes): array
    {
        /** @var array<string, mixed> $validated */
        $validated = Validator::make(
            Arr::only($attributes, ['amount_paid', 'paid_amount', 'payment_reference', 'paid_at']),
            [
                'amount_paid' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'paid_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'payment_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
                'paid_at' => ['sometimes', 'nullable', 'date'],
            ],
        )->validate();

        return $validated;
    }
}
