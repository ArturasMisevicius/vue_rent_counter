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

class RejectInvoicePayment
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(InvoicePayment $payment, User $actor, string $reason): InvoicePayment
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => __('admin.payments.validation.rejection_reason_required'),
            ]);
        }

        $payment->loadMissing('invoice:id,organization_id');
        Gate::forUser($actor)->authorize('update', $payment->invoice);

        if ($payment->status !== PaymentStatus::PENDING) {
            throw ValidationException::withMessages([
                'payment' => __('admin.payments.validation.reject_pending_only'),
            ]);
        }

        return DB::transaction(function () use ($payment, $actor, $reason): InvoicePayment {
            $before = $this->paymentSnapshot($payment);
            $payment->forceFill([
                'status' => PaymentStatus::FAILED,
                'rejected_by_user_id' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $freshPayment = $payment->refresh();

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $freshPayment,
                [
                    'workspace' => $this->workspaceContext($freshPayment),
                    'context' => [
                        'mutation' => 'payment.rejected',
                    ],
                    'before' => $before,
                    'after' => $this->paymentSnapshot($freshPayment),
                    'reason' => $reason,
                ],
                $actor->id,
                'Invoice payment rejected',
            );

            return $freshPayment;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentSnapshot(InvoicePayment $payment): array
    {
        return [
            'status' => $payment->status instanceof PaymentStatus ? $payment->status->value : $payment->status,
            'rejected_by_user_id' => $payment->rejected_by_user_id,
            'rejected_at' => $payment->rejected_at?->toISOString(),
            'rejection_reason' => $payment->rejection_reason,
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
