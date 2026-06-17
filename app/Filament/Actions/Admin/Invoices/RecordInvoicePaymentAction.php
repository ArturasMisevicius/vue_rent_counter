<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Actions\Billing\CreateManualPayment;
use App\Enums\PaymentMethod;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RecordInvoicePaymentAction
{
    public function __construct(
        private readonly CreateManualPayment $createManualPayment,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Invoice $invoice, array $attributes, ?User $actor = null): Invoice
    {
        $this->subscriptionLimitGuard->ensureCanWrite($invoice->organization_id);

        $payment = $this->createManualPayment->handle($invoice, $actor, [
            'amount' => $this->paymentAmount($attributes),
            'payment_method' => $attributes['method'] ?? PaymentMethod::BANK_TRANSFER,
            'payment_date' => $attributes['paid_at'] ?? now()->toDateString(),
            'reference' => $attributes['payment_reference'] ?? null,
            'internal_note' => $attributes['notes'] ?? null,
            'confirm_immediately' => true,
        ]);

        return $payment->invoice?->refresh() ?? $invoice->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function paymentAmount(array $attributes): mixed
    {
        if (array_key_exists('amount_paid', $attributes)) {
            return $attributes['amount_paid'];
        }

        if (array_key_exists('paid_amount', $attributes)) {
            return $attributes['paid_amount'];
        }

        throw ValidationException::withMessages([
            'amount_paid' => __('validation.required', [
                'attribute' => __('requests.attributes.amount_paid'),
            ]),
        ]);
    }
}
