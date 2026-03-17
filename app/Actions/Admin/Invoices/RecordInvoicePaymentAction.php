<?php

namespace App\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;

class RecordInvoicePaymentAction
{
    /**
     * @param  array{amount: float|int|string, method: mixed, reference?: string|null, paid_at?: mixed, notes?: string|null}  $attributes
     */
    public function handle(Invoice $invoice, array $attributes): Invoice
    {
        return DB::transaction(function () use ($invoice, $attributes): Invoice {
            InvoicePayment::query()->create([
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'amount' => $attributes['amount'],
                'method' => $attributes['method'],
                'reference' => $attributes['reference'] ?? null,
                'paid_at' => $attributes['paid_at'] ?? now(),
                'notes' => $attributes['notes'] ?? null,
            ]);

            $amountPaid = (float) $invoice->payments()->sum('amount');
            $totalAmount = (float) $invoice->total_amount;
            $status = $amountPaid >= $totalAmount
                ? InvoiceStatus::PAID
                : InvoiceStatus::PARTIALLY_PAID;

            $invoice->forceFill([
                'amount_paid' => $amountPaid,
                'paid_amount' => $amountPaid,
                'payment_reference' => $attributes['reference'] ?? $invoice->payment_reference,
                'status' => $status,
                'paid_at' => $status === InvoiceStatus::PAID ? ($attributes['paid_at'] ?? now()) : null,
            ])->save();

            return $invoice->fresh(['payments']);
        });
    }
}
