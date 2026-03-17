<?php

namespace App\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Support\Admin\Invoices\InvoiceEligibilityWindow;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GenerateBulkInvoicesAction
{
    public function __construct(
        protected GenerateInvoiceLineItemsAction $generateInvoiceLineItemsAction,
        protected InvoiceEligibilityWindow $invoiceEligibilityWindow,
    ) {}

    /**
     * @return Collection<int, Invoice>
     */
    public function handle(
        Organization $organization,
        CarbonInterface|string $billingPeriodStart,
        CarbonInterface|string $billingPeriodEnd,
    ): Collection {
        $periodStart = $this->normalizeDate($billingPeriodStart)->startOfDay();
        $periodEnd = $this->normalizeDate($billingPeriodEnd)->endOfDay();

        return PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'unit_area_sqm',
                'assigned_at',
                'unassigned_at',
            ])
            ->where('organization_id', $organization->id)
            ->where('assigned_at', '<=', $periodEnd)
            ->where(function ($query) use ($periodStart): void {
                $query
                    ->whereNull('unassigned_at')
                    ->orWhere('unassigned_at', '>=', $periodStart);
            })
            ->with([
                'property:id,organization_id,building_id,name,unit_number',
                'tenant:id,organization_id,name,email',
            ])
            ->get()
            ->reduce(function (Collection $generatedInvoices, PropertyAssignment $assignment) use ($organization, $periodStart, $periodEnd): Collection {
                if (! $this->invoiceEligibilityWindow->allows($assignment, $periodStart, $periodEnd)) {
                    return $generatedInvoices;
                }

                $alreadyGenerated = Invoice::query()
                    ->select(['id'])
                    ->where('organization_id', $organization->id)
                    ->where('property_id', $assignment->property_id)
                    ->where('tenant_user_id', $assignment->tenant_user_id)
                    ->whereDate('billing_period_start', $periodStart->toDateString())
                    ->whereDate('billing_period_end', $periodEnd->toDateString())
                    ->exists();

                if ($alreadyGenerated) {
                    return $generatedInvoices;
                }

                $lineItems = $this->generateInvoiceLineItemsAction->handle($assignment, $periodStart, $periodEnd);

                $invoice = Invoice::query()->create([
                    'organization_id' => $organization->id,
                    'property_id' => $assignment->property_id,
                    'tenant_user_id' => $assignment->tenant_user_id,
                    'invoice_number' => $this->invoiceNumberFor($assignment, $periodStart),
                    'billing_period_start' => $periodStart->toDateString(),
                    'billing_period_end' => $periodEnd->toDateString(),
                    'status' => InvoiceStatus::FINALIZED,
                    'currency' => 'EUR',
                    'total_amount' => $lineItems['total_amount'],
                    'amount_paid' => 0,
                    'paid_amount' => 0,
                    'due_date' => $periodEnd->copy()->addDays(14)->toDateString(),
                    'finalized_at' => now(),
                    'items' => $lineItems['items'],
                    'snapshot_data' => $lineItems['items'],
                    'snapshot_created_at' => now(),
                    'generated_at' => now(),
                    'generated_by' => 'bulk_invoices_action',
                    'approval_status' => 'approved',
                    'automation_level' => 'manual',
                ]);

                return $generatedInvoices->push($invoice);
            }, collect());
    }

    protected function invoiceNumberFor(PropertyAssignment $assignment, CarbonInterface $billingPeriodStart): string
    {
        return sprintf(
            'INV-%s-%d-%d',
            $billingPeriodStart->format('Ym'),
            $assignment->property_id,
            $assignment->tenant_user_id,
        );
    }

    protected function normalizeDate(CarbonInterface|string $value): CarbonImmutable
    {
        return $value instanceof CarbonInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value);
    }
}
