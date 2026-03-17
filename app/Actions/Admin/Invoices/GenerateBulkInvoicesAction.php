<?php

namespace App\Actions\Admin\Invoices;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Support\Admin\Invoices\InvoiceEligibilityWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GenerateBulkInvoicesAction
{
    public function __construct(
        protected InvoiceEligibilityWindow $invoiceEligibilityWindow,
        protected GenerateInvoiceLineItemsAction $generateInvoiceLineItemsAction,
    ) {}

    /**
     * @return Collection<int, Invoice>
     */
    public function handle(
        Organization $organization,
        CarbonInterface|string $billingPeriodStart,
        CarbonInterface|string $billingPeriodEnd,
    ): Collection {
        return PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'assigned_at',
                'unassigned_at',
            ])
            ->where('organization_id', $organization->id)
            ->whereNotNull('tenant_user_id')
            ->get()
            ->filter(fn (PropertyAssignment $assignment): bool => $this->invoiceEligibilityWindow->allows($assignment, $billingPeriodStart))
            ->map(function (PropertyAssignment $assignment) use ($billingPeriodStart, $billingPeriodEnd): Invoice {
                return Invoice::query()->create(
                    $this->generateInvoiceLineItemsAction->handle($assignment, $billingPeriodStart, $billingPeriodEnd),
                );
            })
            ->values();
    }
}
