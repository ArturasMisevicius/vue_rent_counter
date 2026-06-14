<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Enums\AuditLogAction;
use App\Enums\InvoiceItemSourceType;
use App\Filament\Support\Admin\Invoices\FinalizedInvoiceGuard;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Billing\InvoiceCalculationRows;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Validation\ValidationException;

final class AddManualInvoiceAdjustment
{
    public function __construct(
        private readonly InvoiceCalculationRows $calculationRows,
        private readonly InvoiceService $invoiceService,
        private readonly FinalizedInvoiceGuard $finalizedInvoiceGuard,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{amount?: string|int|float|null, description_for_tenant?: string|null, internal_note?: string|null, tenant_visible?: bool|null}  $attributes
     */
    public function handle(Invoice $invoice, array $attributes, ?User $actor = null): Invoice
    {
        if ($this->finalizedInvoiceGuard->isImmutable($invoice)) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.invoices.validation.manual_adjustment_locked'),
            ]);
        }

        $amount = $attributes['amount'] ?? null;
        $description = trim((string) ($attributes['description_for_tenant'] ?? ''));
        $internalNote = trim((string) ($attributes['internal_note'] ?? ''));

        if (! is_numeric($amount)) {
            throw ValidationException::withMessages([
                'amount' => __('admin.invoices.validation.empty_amount'),
            ]);
        }

        if ($description === '') {
            throw ValidationException::withMessages([
                'description_for_tenant' => __('admin.invoices.validation.tenant_description_required'),
            ]);
        }

        if ($internalNote === '') {
            throw ValidationException::withMessages([
                'internal_note' => __('admin.invoices.validation.internal_reason_required'),
            ]);
        }

        $beforeTotal = (string) $invoice->total_amount;
        $items = $this->calculationRows->forInvoice($invoice);
        $items[] = [
            'source_type' => InvoiceItemSourceType::MANUAL_ADJUSTMENT->value,
            'source_id' => null,
            'source_status' => 'approved',
            'title' => $description,
            'description' => $description,
            'description_for_tenant' => $description,
            'internal_note' => $internalNote,
            'quantity' => '1',
            'unit' => null,
            'unit_price' => $amount,
            'subtotal' => $amount,
            'tax_amount' => '0',
            'discount_amount' => '0',
            'total' => $amount,
            'currency' => (string) $invoice->currency,
            'formula_label' => __('admin.invoices.formulas.manual_amount'),
            'tenant_visible' => (bool) ($attributes['tenant_visible'] ?? true),
            'sort_order' => count($items) + 1,
            'is_adjustment' => true,
        ];

        $updatedInvoice = $this->invoiceService->updateDraft($invoice, [
            'items' => $items,
        ]);

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $updatedInvoice,
            [
                'context' => [
                    'mutation' => 'invoice.manual_adjustment_added',
                ],
                'before' => [
                    'total_amount' => $beforeTotal,
                ],
                'after' => [
                    'total_amount' => (string) $updatedInvoice->total_amount,
                    'manual_adjustment_amount' => (string) $amount,
                ],
            ],
            $actor?->id,
            'Manual invoice adjustment added',
        );

        return $updatedInvoice->fresh(['invoiceItems']);
    }

    /**
     * @param  array{amount?: string|int|float|null, description_for_tenant?: string|null, internal_note?: string|null, tenant_visible?: bool|null}  $attributes
     */
    public function __invoke(Invoice $invoice, array $attributes, ?User $actor = null): Invoice
    {
        return $this->handle($invoice, $attributes, $actor);
    }
}
