<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\ExtraCharges;

use App\Enums\AuditLogAction;
use App\Enums\ExtraChargeStatus;
use App\Enums\ExtraChargeTypeCode;
use App\Enums\InvoiceItemSourceType;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\BillingPeriod;
use App\Models\ExtraCharge;
use App\Models\Invoice;
use App\Models\PropertyAssignment;
use App\Services\Billing\UniversalBillingCalculator;
use Carbon\CarbonImmutable;

final class ExtraChargeInvoiceIntegrator
{
    public function __construct(
        private readonly UniversalBillingCalculator $calculator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lineItemsForAssignment(
        PropertyAssignment $assignment,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $billingPeriod = $this->billingPeriodFor($assignment, $periodStart, $periodEnd);

        return ExtraCharge::query()
            ->invoiceableForAssignment(
                organizationId: $assignment->organization_id,
                tenantId: $assignment->tenant_user_id,
                propertyId: $assignment->property_id,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                billingPeriodId: $billingPeriod->id,
            )
            ->get()
            ->map(fn (ExtraCharge $charge): array => $this->lineItemFor($charge, $periodStart, $periodEnd))
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function markIncluded(Invoice $invoice, array $items, ?int $actorUserId = null): void
    {
        $chargeIds = collect($items)
            ->filter(fn (array $item): bool => ($item['source_type'] ?? null) === InvoiceItemSourceType::EXTRA_CHARGE->value)
            ->pluck('source_id')
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($chargeIds->isEmpty()) {
            return;
        }

        ExtraCharge::query()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'property_id',
                'billing_period_id',
                'invoice_id',
                'extra_charge_type_id',
                'title',
                'description_for_tenant',
                'internal_note',
                'amount',
                'currency',
                'quantity',
                'unit_price',
                'tax_amount',
                'total_amount',
                'status',
                'is_recurring',
                'starts_at',
                'ends_at',
                'created_by_user_id',
                'approved_by_user_id',
                'approved_at',
                'created_at',
                'updated_at',
            ])
            ->whereKey($chargeIds->all())
            ->where('organization_id', $invoice->organization_id)
            ->whereIn('status', ExtraChargeStatus::invoiceableValues())
            ->get()
            ->each(fn (ExtraCharge $charge): mixed => $this->markChargeIncluded($charge, $invoice, $actorUserId));
    }

    private function billingPeriodFor(
        PropertyAssignment $assignment,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): BillingPeriod {
        return BillingPeriod::query()->firstOrCreate(
            [
                'organization_id' => $assignment->organization_id,
                'starts_at' => $periodStart->toDateString(),
                'ends_at' => $periodEnd->toDateString(),
            ],
            [
                'name' => $periodStart->format('F Y'),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function lineItemFor(ExtraCharge $charge, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $tenantVisible = $charge->isTenantVisible();
        $total = $this->calculator->money($charge->total_amount);
        $isDiscount = $charge->typeCode() === ExtraChargeTypeCode::DISCOUNT
            || $this->calculator->compare($total, '0', 2) < 0;

        return [
            'source_type' => InvoiceItemSourceType::EXTRA_CHARGE->value,
            'source_id' => $charge->id,
            'source_status' => $charge->status instanceof ExtraChargeStatus
                ? $charge->status->value
                : (string) $charge->status,
            'title' => $charge->title,
            'description' => $charge->title,
            'description_for_tenant' => $tenantVisible
                ? (string) ($charge->description_for_tenant ?: $charge->title)
                : '',
            'internal_note' => $charge->internal_note,
            'period' => $periodStart->format('F Y').' - '.$periodEnd->format('F Y'),
            'quantity' => $this->calculator->quantity($charge->quantity),
            'unit' => null,
            'unit_price' => $this->calculator->rate($charge->unit_price),
            'subtotal' => $this->calculator->money($charge->amount),
            'tax_amount' => $this->calculator->money($charge->tax_amount),
            'discount_amount' => $isDiscount ? $total : $this->calculator->money('0'),
            'total' => $total,
            'currency' => $charge->currency,
            'formula_label' => __('admin.invoices.formulas.manual_amount'),
            'calculation_snapshot' => [
                'source_type' => InvoiceItemSourceType::EXTRA_CHARGE->value,
                'source_id' => $charge->id,
                'source_status' => $charge->status instanceof ExtraChargeStatus
                    ? $charge->status->value
                    : (string) $charge->status,
                'charge_type' => $charge->typeCode()?->value,
                'quantity' => $this->calculator->quantity($charge->quantity),
                'unit_price' => $this->calculator->rate($charge->unit_price),
                'subtotal' => $this->calculator->money($charge->amount),
                'tax_amount' => $this->calculator->money($charge->tax_amount),
                'discount_amount' => $isDiscount ? $total : $this->calculator->money('0'),
                'total' => $total,
                'currency' => $charge->currency,
                'is_recurring' => $charge->is_recurring,
                'starts_at' => $charge->starts_at?->toDateString(),
                'ends_at' => $charge->ends_at?->toDateString(),
            ],
            'tenant_visible' => $tenantVisible,
            'sort_order' => 9000 + $charge->id,
            'consumption' => $this->calculator->quantity($charge->quantity),
            'rate' => $this->calculator->rate($charge->unit_price),
            'is_adjustment' => $this->calculator->compare($total, '0', 2) < 0,
            'meter_reading_snapshot' => null,
            'service_snapshot' => null,
            'tariff_snapshot' => null,
            'provider_snapshot' => null,
        ];
    }

    private function markChargeIncluded(ExtraCharge $charge, Invoice $invoice, ?int $actorUserId): void
    {
        $before = $charge->getAttributes();

        $charge->update([
            'status' => ExtraChargeStatus::INCLUDED_IN_INVOICE,
            'invoice_id' => $invoice->id,
        ]);

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $charge->fresh(),
            [
                'context' => [
                    'mutation' => 'extra_charge.included_in_invoice',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
                'before' => $before,
                'after' => $charge->fresh()?->getAttributes() ?? [],
            ],
            $actorUserId,
            'Extra charge included in invoice',
        );
    }
}
