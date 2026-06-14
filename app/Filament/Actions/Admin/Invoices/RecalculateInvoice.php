<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Enums\AuditLogAction;
use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\Invoices\FinalizedInvoiceGuard;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\InvoiceGenerationAudit;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Validation\ValidationException;

final class RecalculateInvoice
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
        private readonly InvoiceService $invoiceService,
        private readonly FinalizedInvoiceGuard $finalizedInvoiceGuard,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(Invoice $invoice, ?User $actor = null): Invoice
    {
        if ($this->finalizedInvoiceGuard->isImmutable($invoice)) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.invoices.validation.recalculate_locked'),
            ]);
        }

        if ($invoice->tenant_user_id === null || $invoice->billing_period_start === null || $invoice->billing_period_end === null) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.invoices.validation.recalculate_missing_context'),
            ]);
        }

        $organization = Organization::query()
            ->select(['id'])
            ->findOrFail($invoice->organization_id);
        $before = $this->auditSnapshot($invoice);
        $preview = $this->billingService->previewInvoiceDraft($organization, [
            'tenant_user_id' => $invoice->tenant_user_id,
            'billing_period_start' => $invoice->billing_period_start->toDateString(),
            'billing_period_end' => $invoice->billing_period_end->toDateString(),
        ]);

        $updatedInvoice = $this->invoiceService->updateDraft($invoice, [
            'items' => $preview['items'],
            'total_amount' => $preview['total_amount'],
        ]);

        InvoiceGenerationAudit::query()->create([
            'invoice_id' => $updatedInvoice->id,
            'organization_id' => $updatedInvoice->organization_id,
            'tenant_user_id' => $updatedInvoice->tenant_user_id,
            'user_id' => $actor?->id,
            'period_start' => $updatedInvoice->billing_period_start?->toDateString(),
            'period_end' => $updatedInvoice->billing_period_end?->toDateString(),
            'total_amount' => $updatedInvoice->total_amount,
            'items_count' => $updatedInvoice->invoiceItems()->count(),
            'metadata' => [
                'context' => [
                    'mutation' => 'invoice.recalculated',
                ],
                'before' => $before,
                'after' => $this->auditSnapshot($updatedInvoice),
            ],
            'created_at' => now(),
        ]);

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $updatedInvoice,
            [
                'context' => [
                    'mutation' => 'invoice.recalculated',
                ],
                'before' => $before,
                'after' => $this->auditSnapshot($updatedInvoice),
            ],
            $actor?->id,
            'Invoice recalculated',
        );

        return $updatedInvoice->fresh(['invoiceItems']);
    }

    public function __invoke(Invoice $invoice, ?User $actor = null): Invoice
    {
        return $this->handle($invoice, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditSnapshot(Invoice $invoice): array
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status->value
            : (string) $invoice->status;

        return [
            'status' => $status,
            'total_amount' => (string) $invoice->total_amount,
            'items_count' => $invoice->invoiceItems()->count(),
            'snapshot_created_at' => $invoice->snapshot_created_at?->toISOString(),
        ];
    }
}
