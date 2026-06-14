<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\AuditLogAction;
use App\Enums\PaymentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VoidInvoicePayment
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RecalculateInvoicePaymentStatus $recalculateInvoicePaymentStatus,
    ) {}

    public function handle(InvoicePayment $payment, User $actor, string $reason): InvoicePayment
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'void_reason' => __('admin.payments.validation.void_reason_required'),
            ]);
        }

        $payment->loadMissing('invoice:id,organization_id,property_id,tenant_user_id,status,currency,total_amount,amount_paid,paid_amount,balance_amount,payment_status,due_date,paid_at,payment_reference,overdue_at');
        Gate::forUser($actor)->authorize('update', $payment->invoice);

        if (! $payment->canBeVoided()) {
            throw ValidationException::withMessages([
                'payment' => __('admin.payments.validation.void_pending_or_confirmed_only'),
            ]);
        }

        return DB::transaction(function () use ($payment, $actor, $reason): InvoicePayment {
            $before = $this->paymentSnapshot($payment);
            $payment->forceFill([
                'status' => PaymentStatus::VOIDED,
                'voided_by_user_id' => $actor->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            $freshPayment = $payment->fresh(['invoice']);

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $freshPayment,
                [
                    'workspace' => $this->workspaceContext($freshPayment),
                    'context' => [
                        'mutation' => 'payment.voided',
                    ],
                    'before' => $before,
                    'after' => $this->paymentSnapshot($freshPayment),
                    'reason' => $reason,
                ],
                $actor->id,
                'Invoice payment voided',
            );

            $this->recalculateInvoicePaymentStatus->handle(
                $freshPayment->invoice,
                $actor,
                'invoice.payment_voided',
            );

            return $freshPayment->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentSnapshot(InvoicePayment $payment): array
    {
        return [
            'status' => $payment->status instanceof PaymentStatus ? $payment->status->value : $payment->status,
            'voided_by_user_id' => $payment->voided_by_user_id,
            'voided_at' => $payment->voided_at?->toISOString(),
            'void_reason' => $payment->void_reason,
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
