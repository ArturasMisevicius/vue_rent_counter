<?php

namespace App\Support\Admin\Invoices;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
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
            ->where('organization_id', $organization->id)
            ->whereNull('unassigned_at')
            ->orderBy('property_id')
            ->get();

        $valid = [];
        $skipped = [];

        foreach ($assignments as $assignment) {
            $exists = Invoice::query()
                ->where('organization_id', $organization->id)
                ->where('property_id', $assignment->property_id)
                ->where('tenant_user_id', $assignment->tenant_user_id)
                ->whereDate('billing_period_start', $periodStart->toDateString())
                ->whereDate('billing_period_end', $periodEnd->toDateString())
                ->exists();

            if ($exists) {
                $skipped[] = [
                    'tenant_id' => $assignment->tenant_user_id,
                    'property_id' => $assignment->property_id,
                    'reason' => 'already_billed',
                ];

                continue;
            }

            $property = Property::query()
                ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number'])
                ->findOrFail($assignment->property_id);

            $items = $this->calculator->handle($property, $periodStart, $periodEnd);

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
