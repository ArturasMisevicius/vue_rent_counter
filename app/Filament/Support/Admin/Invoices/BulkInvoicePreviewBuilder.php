<?php

namespace App\Filament\Support\Admin\Invoices;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use Illuminate\Support\Carbon;

class BulkInvoicePreviewBuilder
{
    public function __construct(
        private readonly InvoiceLineItemCalculator $calculator,
    ) {}

    /**
     * @param  array{billing_period_start: string, billing_period_end: string}  $attributes
     * @return array{valid: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>}
     */
    public function handle(Organization $organization, array $attributes): array
    {
        $periodStart = Carbon::parse($attributes['billing_period_start'])->startOfDay();
        $periodEnd = Carbon::parse($attributes['billing_period_end'])->endOfDay();

        $assignments = PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'assigned_at',
                'unassigned_at',
            ])
            ->with([
                'property:id,organization_id,building_id,name,unit_number',
                'tenant:id,name,email',
            ])
            ->forOrganization($organization->id)
            ->current()
            ->orderBy('property_id')
            ->get();

        $valid = [];
        $skipped = [];

        foreach ($assignments as $assignment) {
            $exists = Invoice::query()
                ->forOrganization($organization->id)
                ->forProperty($assignment->property_id)
                ->forTenant($assignment->tenant_user_id)
                ->forBillingPeriod($periodStart, $periodEnd)
                ->exists();

            if ($exists) {
                $skipped[] = [
                    'tenant_id' => $assignment->tenant_user_id,
                    'property_id' => $assignment->property_id,
                    'reason' => 'already_billed',
                ];

                continue;
            }

            $items = $this->calculator->handle($assignment->property, $periodStart, $periodEnd);

            $valid[] = [
                'property_id' => $assignment->property_id,
                'tenant_user_id' => $assignment->tenant_user_id,
                'items' => $items,
                'total' => collect($items)->sum('total'),
            ];
        }

        return [
            'valid' => $valid,
            'skipped' => $skipped,
        ];
    }
}
