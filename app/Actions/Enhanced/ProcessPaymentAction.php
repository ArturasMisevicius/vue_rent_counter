<?php

declare(strict_types=1);

namespace App\Actions\Enhanced;

use App\DTOs\PaymentProcessingDTO;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Process Payment Action
 * 
 * Single responsibility: Process a payment for an invoice.
 * Handles payment validation, recording, and invoice status updates.
 * 
 * @package App\Actions\Enhanced
 */
final class ProcessPaymentAction
{
    /**
     * Execute the payment processing action.
     *
     * @param PaymentProcessingDTO $dto Payment processing data
     * @return Payment The created payment record
     * @throws \InvalidArgumentException If payment data is invalid
     * @throws \RuntimeException If payment processing fails
     */
    public function execute(PaymentProcessingDTO $dto): Payment
    {
        return DB::transaction(function () use ($dto) {
            // Validate invoice
            $invoice = Invoice::findOrFail($dto->invoiceId);
            $this->validateInvoiceForPayment($invoice, $dto->amount);

            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id,
                'amount' => $dto->amount,
                'payment_method' => $dto->paymentMethod,
                'payment_reference' => $dto->paymentReference,
                'payment_date' => $dto->paymentDate,
                'processed_by' => auth()->id(),
                'notes' => $dto->notes,
            ]);

            // Update invoice status if fully paid
            $this->updateInvoiceStatus($invoice, $payment);

            // Log payment processing
            Log::info('Payment processed successfully', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $dto->amount,
                'payment_method' => $dto->paymentMethod->value,
                'processed_by' => auth()->id(),
            ]);

            return $payment;
        });
    }

    /**
     * Validate invoice for payment processing.
     */
    private function validateInvoiceForPayment(Invoice $invoice, float $amount): void
    {
        if ($invoice->status !== InvoiceStatus::FINALIZED) {
            throw new \InvalidArgumentException('Invoice must be finalized before payment can be processed');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero');
        }

        $remainingAmount = $invoice->total_amount - $invoice->payments()->sum('amount');
        
        if ($amount > $remainingAmount) {
            throw new \InvalidArgumentException('Payment amount exceeds remaining invoice balance');
        }
    }

    /**
     * Update invoice status based on payment.
     */
    private function updateInvoiceStatus(Invoice $invoice, Payment $payment): void
    {
        $totalPaid = $invoice->payments()->sum('amount');
        
        if ($totalPaid >= $invoice->total_amount) {
            $invoice->update([
                'status' => InvoiceStatus::PAID,
                'paid_at' => now(),
            ]);
        } elseif ($totalPaid > 0) {
            $invoice->update([
                'status' => InvoiceStatus::PARTIALLY_PAID,
            ]);
        }
    }
}