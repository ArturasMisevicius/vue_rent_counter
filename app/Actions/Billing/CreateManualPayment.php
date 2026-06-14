<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\AuditLogAction;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\Invoices\CreateManualPaymentRequest;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateManualPayment
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ConfirmInvoicePayment $confirmInvoicePayment,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Invoice $invoice, ?User $actor, array $attributes): InvoicePayment
    {
        if ($actor instanceof User) {
            Gate::forUser($actor)->authorize('update', $invoice);
        }

        $this->ensureInvoiceCanReceivePayment($invoice);

        $validated = (new CreateManualPaymentRequest)->validatePayload([
            ...$attributes,
            'invoice_id' => $invoice->id,
        ], $actor);
        $currency = strtoupper((string) ($validated['currency'] ?? $invoice->currency));

        if ($currency !== strtoupper((string) $invoice->currency)) {
            throw ValidationException::withMessages([
                'currency' => __('admin.payments.validation.currency_mismatch'),
            ]);
        }

        return DB::transaction(function () use ($invoice, $actor, $validated, $currency): InvoicePayment {
            $paymentMethod = $validated['payment_method'] instanceof PaymentMethod
                ? $validated['payment_method']
                : PaymentMethod::from((string) $validated['payment_method']);
            $payment = InvoicePayment::query()->create([
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'tenant_id' => $invoice->tenant_user_id,
                'property_id' => $invoice->property_id,
                'recorded_by_user_id' => $actor?->id,
                'submitted_by_user_id' => $actor?->id,
                'amount' => round((float) $validated['amount'], 2),
                'currency' => $currency,
                'method' => $paymentMethod,
                'payment_method' => $paymentMethod,
                'status' => PaymentStatus::PENDING,
                'payment_date' => $validated['payment_date'],
                'paid_at' => $validated['payment_date'],
                'reference' => $validated['reference'] ?? null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'internal_note' => $validated['internal_note'] ?? null,
                'tenant_comment' => $validated['tenant_comment'] ?? null,
                'notes' => $validated['internal_note'] ?? null,
            ]);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $payment,
                [
                    'workspace' => $this->workspaceContext($payment),
                    'context' => [
                        'mutation' => 'payment.manual_created',
                    ],
                    'after' => $this->paymentSnapshot($payment),
                ],
                $actor?->id,
                'Manual invoice payment created',
            );

            if ((bool) ($validated['confirm_immediately'] ?? false)) {
                return $this->confirmInvoicePayment->handle($payment->refresh(), $actor);
            }

            return $payment->refresh();
        });
    }

    private function ensureInvoiceCanReceivePayment(Invoice $invoice): void
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::tryFrom((string) $invoice->status);
        $paymentStatus = $invoice->payment_status instanceof InvoicePaymentStatus
            ? $invoice->payment_status
            : InvoicePaymentStatus::tryFrom((string) $invoice->payment_status);

        if ($status === InvoiceStatus::VOID || $status === InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.payments.validation.invoice_not_payable'),
            ]);
        }

        if (in_array($paymentStatus, [
            InvoicePaymentStatus::CANCELLED,
            InvoicePaymentStatus::VOIDED,
            InvoicePaymentStatus::REFUNDED,
        ], true)) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.payments.validation.invoice_not_payable'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentSnapshot(InvoicePayment $payment): array
    {
        return [
            'invoice_id' => $payment->invoice_id,
            'tenant_id' => $payment->tenant_id,
            'property_id' => $payment->property_id,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $payment->resolvedPaymentMethod() instanceof PaymentMethod
                ? $payment->resolvedPaymentMethod()->value
                : $payment->resolvedPaymentMethod(),
            'status' => $payment->status instanceof PaymentStatus ? $payment->status->value : $payment->status,
            'reference' => $payment->reference,
            'transaction_id' => $payment->transaction_id,
        ];
    }

    /**
     * @return array{organization_id: int, tenant_id: int|null, invoice_id: int, payment_id: int|null}
     */
    private function workspaceContext(InvoicePayment $payment): array
    {
        return [
            'organization_id' => $payment->organization_id,
            'tenant_id' => $payment->tenant_id,
            'invoice_id' => $payment->invoice_id,
            'payment_id' => $payment->id,
        ];
    }
}
