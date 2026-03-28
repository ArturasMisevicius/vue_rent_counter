<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationInvoiceWriteOff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WriteOffOrganizationInvoicesAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(Organization $organization, string $reason): int
    {
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => __('superadmin.organizations.validation.write_off_reason_required'),
            ]);
        }

        $invoices = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'invoice_number',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'due_date',
                'billing_period_end',
            ])
            ->forOrganization($organization->id)
            ->outstanding()
            ->get();

        if ($invoices->isEmpty()) {
            return 0;
        }

        $writtenOffAt = now();
        $writtenOffCount = 0;
        $invoiceIds = [];
        $totalWrittenOffAmount = 0.0;

        DB::transaction(function () use (
            $organization,
            $invoices,
            $reason,
            $writtenOffAt,
            &$writtenOffCount,
            &$invoiceIds,
            &$totalWrittenOffAmount,
        ): void {
            foreach ($invoices as $invoice) {
                OrganizationInvoiceWriteOff::query()->create([
                    'organization_id' => $organization->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->outstanding_balance,
                    'reason' => $reason,
                    'written_off_at' => $writtenOffAt,
                    'created_by' => auth()->id(),
                ]);

                $writtenOffCount++;
                $invoiceIds[] = $invoice->id;
                $totalWrittenOffAmount += $invoice->outstanding_balance;
            }

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $organization,
                [
                    'reason' => $reason,
                    'written_off_invoice_count' => $writtenOffCount,
                    'written_off_invoice_ids' => $invoiceIds,
                    'total_written_off_amount' => number_format($totalWrittenOffAmount, 2, '.', ''),
                ],
                description: 'Organization invoices written off',
            );
        });

        return $writtenOffCount;
    }
}
