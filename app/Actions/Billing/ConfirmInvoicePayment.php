<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\AuditLogAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\InvoicePayment;
use App\Models\User;
use App\Notifications\Billing\InvoicePaymentConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ConfirmInvoicePayment
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RecalculateInvoicePaymentStatus $recalculateInvoicePaymentStatus,
    ) {}

    public function handle(InvoicePayment $payment, ?User $actor): InvoicePayment
    {
        $payment->loadMissing('invoice:id,organization_id,property_id,tenant_user_id,status,currency,total_amount,amount_paid,paid_amount,balance_amount,payment_status,due_date,paid_at,payment_reference,overdue_at');
        $invoice = $payment->invoice;

        if ($invoice === null) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.payments.validation.invoice_required'),
            ]);
        }

        if ($actor instanceof User) {
            Gate::forUser($actor)->authorize('update', $invoice);
        }

        if ($payment->status !== PaymentStatus::PENDING) {
            throw ValidationException::withMessages([
                'payment' => __('admin.payments.validation.confirm_pending_only'),
            ]);
        }

        if (strtoupper((string) $payment->currency) !== strtoupper((string) $invoice->currency)) {
            throw ValidationException::withMessages([
                'currency' => __('admin.payments.validation.currency_mismatch'),
            ]);
        }

        $confirmedPayment = DB::transaction(function () use ($payment, $actor): InvoicePayment {
            $before = $this->paymentSnapshot($payment);
            $payment->forceFill([
                'status' => PaymentStatus::CONFIRMED,
                'confirmed_by_user_id' => $actor?->id,
                'confirmed_at' => now(),
                'paid_at' => $payment->payment_date?->startOfDay() ?? now(),
            ])->save();

            $freshPayment = $payment->fresh(['invoice']);

            if (blank($freshPayment->invoice?->payment_reference) && filled($freshPayment->reference)) {
                $freshPayment->invoice?->forceFill([
                    'payment_reference' => $freshPayment->reference,
                ])->save();
            }

            $this->auditLogger->record(
                AuditLogAction::APPROVED,
                $freshPayment,
                [
                    'workspace' => $this->workspaceContext($freshPayment),
                    'context' => [
                        'mutation' => 'payment.confirmed',
                    ],
                    'before' => $before,
                    'after' => $this->paymentSnapshot($freshPayment),
                ],
                $actor?->id,
                'Invoice payment confirmed',
            );

            $this->recalculateInvoicePaymentStatus->handle(
                $freshPayment->invoice,
                $actor,
                'invoice.payment_confirmed',
            );

            return $freshPayment->refresh();
        });

        $this->notifyTenant($confirmedPayment);

        return $confirmedPayment;
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentSnapshot(InvoicePayment $payment): array
    {
        return [
            'status' => $payment->status instanceof PaymentStatus ? $payment->status->value : $payment->status,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $payment->resolvedPaymentMethod() instanceof PaymentMethod
                ? $payment->resolvedPaymentMethod()->value
                : $payment->resolvedPaymentMethod(),
            'confirmed_by_user_id' => $payment->confirmed_by_user_id,
            'confirmed_at' => $payment->confirmed_at?->toISOString(),
            'paid_at' => $payment->paid_at?->toISOString(),
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

    private function notifyTenant(InvoicePayment $payment): void
    {
        $payment->loadMissing('tenant:id,organization_id,name,email');

        $payment->tenant?->notify(new InvoicePaymentConfirmedNotification($payment));
    }
}
