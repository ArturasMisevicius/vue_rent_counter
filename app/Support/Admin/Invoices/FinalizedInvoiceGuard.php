<?php

namespace App\Support\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class FinalizedInvoiceGuard
{
    /**
     * @var array<int, string>
     */
    private const MUTABLE_FIELDS = [
        'status',
        'amount_paid',
        'paid_amount',
        'paid_at',
        'payment_reference',
    ];

    public function canMutateField(string $field): bool
    {
        return in_array($field, self::MUTABLE_FIELDS, true);
    }

    public function isImmutable(?Invoice $invoice): bool
    {
        if (! $invoice instanceof Invoice) {
            return false;
        }

        if ($invoice->finalized_at !== null) {
            return true;
        }

        return in_array($invoice->status, [
            InvoiceStatus::FINALIZED,
            InvoiceStatus::PARTIALLY_PAID,
            InvoiceStatus::PAID,
            InvoiceStatus::OVERDUE,
            InvoiceStatus::VOID,
        ], true);
    }

    public function ensureCanMutate(Invoice $invoice, array $attributes): void
    {
        if (! $this->isImmutable($invoice)) {
            return;
        }

        $lockedFields = collect(array_keys($attributes))
            ->reject(fn (string $field): bool => $this->canMutateField($field))
            ->values()
            ->all();

        if ($lockedFields === []) {
            return;
        }

        throw ValidationException::withMessages([
            'invoice' => __('admin.invoices.messages.finalized_locked'),
        ]);
    }
}
