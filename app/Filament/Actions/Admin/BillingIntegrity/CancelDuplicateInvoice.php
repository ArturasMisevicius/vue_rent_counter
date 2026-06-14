<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingIntegrity;

use App\Enums\AuditLogAction;
use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\BillingIntegrity\BillingIntegrityActionGuard;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CancelDuplicateInvoice
{
    public function __construct(
        private BillingIntegrityActionGuard $guard,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(Invoice $keepInvoice, Invoice $duplicateInvoice, string $reason, ?User $actor = null): Invoice
    {
        $actor = $this->guard->ensureCanManage($actor ?? auth()->user(), (int) $duplicateInvoice->organization_id);
        $reason = $this->guard->ensureReason($reason);

        if (! $this->sameInvoiceGroup($keepInvoice, $duplicateInvoice)) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.billing_cleanup.errors.invoice_group_mismatch'),
            ]);
        }

        if ($duplicateInvoice->status !== InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.billing_cleanup.errors.cancel_draft_only'),
            ]);
        }

        $before = $duplicateInvoice->getAttributes();

        return DB::transaction(function () use ($duplicateInvoice, $keepInvoice, $reason, $actor, $before): Invoice {
            $duplicateInvoice->forceFill([
                'status' => InvoiceStatus::VOID,
                'approval_status' => 'voided_duplicate',
                'approval_metadata' => [
                    ...($duplicateInvoice->approval_metadata ?? []),
                    'duplicate_cleanup' => [
                        'kept_invoice_id' => $keepInvoice->id,
                        'reason' => $reason,
                        'voided_by_user_id' => $actor->id,
                        'voided_at' => now()->toISOString(),
                    ],
                ],
                'notes' => trim((string) $duplicateInvoice->notes."\n\n".__('admin.billing_cleanup.audit_notes.invoice_cancelled', [
                    'reason' => $reason,
                    'kept_invoice' => $keepInvoice->invoice_number,
                ])),
            ])->save();

            $fresh = $duplicateInvoice->fresh();

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $fresh,
                [
                    'context' => [
                        'mutation' => 'billing_integrity.invoice_duplicate_cancelled',
                        'kept_invoice_id' => $keepInvoice->id,
                    ],
                    'before' => $before,
                    'after' => $fresh->getAttributes(),
                    'reason' => $reason,
                ],
                $actor->id,
                'Duplicate invoice cancelled',
            );

            return $fresh;
        });
    }

    private function sameInvoiceGroup(Invoice $keepInvoice, Invoice $duplicateInvoice): bool
    {
        return (int) $keepInvoice->organization_id === (int) $duplicateInvoice->organization_id
            && (int) $keepInvoice->property_id === (int) $duplicateInvoice->property_id
            && (int) $keepInvoice->tenant_user_id === (int) $duplicateInvoice->tenant_user_id
            && $keepInvoice->billing_period_start?->toDateString() === $duplicateInvoice->billing_period_start?->toDateString()
            && $keepInvoice->billing_period_end?->toDateString() === $duplicateInvoice->billing_period_end?->toDateString();
    }
}
