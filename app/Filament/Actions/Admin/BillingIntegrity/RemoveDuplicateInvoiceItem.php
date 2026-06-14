<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingIntegrity;

use App\Enums\AuditLogAction;
use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\BillingIntegrity\BillingIntegrityActionGuard;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RemoveDuplicateInvoiceItem
{
    public function __construct(
        private BillingIntegrityActionGuard $guard,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(InvoiceItem $keepItem, InvoiceItem $duplicateItem, string $reason, ?User $actor = null): Invoice
    {
        $invoice = $duplicateItem->invoice()->firstOrFail();
        $actor = $this->guard->ensureCanManage($actor ?? auth()->user(), (int) $invoice->organization_id);
        $reason = $this->guard->ensureReason($reason);

        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'invoice_item' => __('admin.billing_cleanup.errors.invoice_item_draft_only'),
            ]);
        }

        if (! $this->sameInvoiceItemSource($keepItem, $duplicateItem)) {
            throw ValidationException::withMessages([
                'invoice_item' => __('admin.billing_cleanup.errors.invoice_item_group_mismatch'),
            ]);
        }

        $beforeItem = $duplicateItem->getAttributes();
        $beforeInvoice = $invoice->getAttributes();

        return DB::transaction(function () use ($duplicateItem, $invoice, $reason, $actor, $beforeItem, $beforeInvoice): Invoice {
            $duplicateItem->forceFill([
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            $updatedInvoice = $this->recalculateDraftInvoiceTotals($invoice);
            $freshItem = $duplicateItem->fresh();

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $freshItem,
                [
                    'context' => [
                        'mutation' => 'billing_integrity.invoice_item_duplicate_voided',
                        'invoice_id' => $updatedInvoice->id,
                    ],
                    'before' => $beforeItem,
                    'after' => $freshItem->getAttributes(),
                    'reason' => $reason,
                ],
                $actor->id,
                'Duplicate invoice item voided',
            );

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $updatedInvoice,
                [
                    'context' => [
                        'mutation' => 'billing_integrity.invoice_recalculated_after_item_void',
                    ],
                    'before' => $beforeInvoice,
                    'after' => $updatedInvoice->getAttributes(),
                    'reason' => $reason,
                ],
                $actor->id,
                'Invoice recalculated after duplicate item cleanup',
            );

            return $updatedInvoice;
        });
    }

    private function sameInvoiceItemSource(InvoiceItem $keepItem, InvoiceItem $duplicateItem): bool
    {
        if ((int) $keepItem->invoice_id !== (int) $duplicateItem->invoice_id) {
            return false;
        }

        if (
            (string) $keepItem->source_type?->value !== (string) $duplicateItem->source_type?->value
        ) {
            return false;
        }

        if ($keepItem->source_id !== null || $duplicateItem->source_id !== null) {
            return (int) $keepItem->source_id === (int) $duplicateItem->source_id;
        }

        return (int) $keepItem->service_configuration_id === (int) $duplicateItem->service_configuration_id
            && (int) $keepItem->utility_service_id === (int) $duplicateItem->utility_service_id
            && ($keepItem->service_configuration_id !== null || $keepItem->utility_service_id !== null);
    }

    private function recalculateDraftInvoiceTotals(Invoice $invoice): Invoice
    {
        $items = $invoice->invoiceItems()
            ->select([
                'id',
                'invoice_id',
                'source_type',
                'source_id',
                'service_configuration_id',
                'utility_service_id',
                'tariff_id',
                'provider_id',
                'project_id',
                'title',
                'description',
                'description_for_tenant',
                'internal_note',
                'quantity',
                'unit',
                'unit_price',
                'subtotal',
                'tax_amount',
                'discount_amount',
                'total',
                'currency',
                'formula_label',
                'calculation_snapshot',
                'tenant_visible',
                'sort_order',
                'meter_reading_snapshot',
                'service_snapshot',
                'tariff_snapshot',
                'provider_snapshot',
                'metadata',
                'voided_at',
                'void_reason',
            ])
            ->whereNull('voided_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $payload = $items
            ->map(fn (InvoiceItem $item): array => $this->invoiceItemPayload($item))
            ->values()
            ->all();

        $invoice->forceFill([
            'total_amount' => $this->sumItemTotals($items),
            'items' => $payload,
            'snapshot_data' => $payload,
            'snapshot_created_at' => now(),
        ])->save();

        return $invoice->fresh(['invoiceItems']);
    }

    /**
     * @param  Collection<int, InvoiceItem>  $items
     */
    private function sumItemTotals(Collection $items): string
    {
        $total = $items->reduce(
            fn (float $carry, InvoiceItem $item): float => $carry + (float) $item->total,
            0.0,
        );

        return number_format($total, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceItemPayload(InvoiceItem $item): array
    {
        return [
            'source_type' => $item->source_type?->value,
            'source_id' => $item->source_id,
            'service_configuration_id' => $item->service_configuration_id,
            'utility_service_id' => $item->utility_service_id,
            'tariff_id' => $item->tariff_id,
            'provider_id' => $item->provider_id,
            'project_id' => $item->project_id,
            'title' => $item->title,
            'description' => $item->description,
            'description_for_tenant' => $item->description_for_tenant,
            'internal_note' => $item->internal_note,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'unit_price' => $item->unit_price,
            'subtotal' => $item->subtotal,
            'tax_amount' => $item->tax_amount,
            'discount_amount' => $item->discount_amount,
            'total' => $item->total,
            'currency' => $item->currency,
            'formula_label' => $item->formula_label,
            'calculation_snapshot' => $item->calculation_snapshot,
            'tenant_visible' => $item->tenant_visible,
            'sort_order' => $item->sort_order,
            'meter_reading_snapshot' => $item->meter_reading_snapshot,
            'service_snapshot' => $item->service_snapshot,
            'tariff_snapshot' => $item->tariff_snapshot,
            'provider_snapshot' => $item->provider_snapshot,
            'metadata' => $item->metadata,
        ];
    }
}
