<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvoiceAlreadyFinalizedException;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing invoice operations.
 *
 * Handles invoice finalization with validation, ensuring invoices
 * meet all requirements before becoming immutable.
 */
final class InvoiceService
{
    /**
     * Finalize an invoice, making it immutable.
     *
     * Validates that the invoice can be finalized and sets the
     * finalized_at timestamp and status to FINALIZED.
     *
     * @param  Invoice  $invoice  The invoice to finalize
     *
     * @throws ValidationException If invoice cannot be finalized
     * @throws InvoiceAlreadyFinalizedException If invoice is already finalized
     */
    public function finalize(Invoice $invoice): void
    {
        // Early return if already finalized
        if ($invoice->isFinalized()) {
            throw new InvoiceAlreadyFinalizedException($invoice->id);
        }

        // Validate invoice can be finalized
        $this->validateCanFinalize($invoice);

        // Finalize in transaction
        DB::transaction(function () use ($invoice) {
            $invoice->finalize();
        });
    }

    /**
     * Validate that an invoice can be finalized.
     *
     * @throws ValidationException
     */
    private function validateCanFinalize(Invoice $invoice): void
    {
        $errors = [];

        // Eager load items if not already loaded to prevent N+1 queries
        if (! $invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        // Check if invoice has items (use loaded collection instead of count query)
        if ($invoice->items->isEmpty()) {
            $errors['invoice'] = 'Cannot finalize invoice: invoice has no items';
        }

        // Check if invoice has a valid total amount
        if ($invoice->total_amount <= 0) {
            $errors['total_amount'] = 'Cannot finalize invoice: total amount must be greater than zero';
        }

        // Check if all invoice items have valid data
        foreach ($invoice->items as $item) {
            if (empty($item->description) || $item->unit_price < 0 || $item->quantity < 0) {
                $errors['items'] = 'Cannot finalize invoice: all items must have valid description, unit price, and quantity';
                break;
            }
        }

        // Check if billing period is valid
        if ($invoice->billing_period_start >= $invoice->billing_period_end) {
            $errors['billing_period'] = 'Cannot finalize invoice: billing period start must be before billing period end';
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Check if an invoice can be finalized.
     */
    public function canFinalize(Invoice $invoice): bool
    {
        if (! $invoice->isDraft()) {
            return false;
        }

        try {
            $this->validateCanFinalize($invoice);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }
}
