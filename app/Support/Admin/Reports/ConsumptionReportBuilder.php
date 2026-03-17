<?php

namespace App\Support\Admin\Reports;

use App\Models\BillingRecord;
use Illuminate\Support\Carbon;

class ConsumptionReportBuilder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(int $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        return BillingRecord::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'utility_service_id',
                'consumption',
                'amount',
                'billing_period_start',
                'billing_period_end',
            ])
            ->with([
                'property:id,name,unit_number',
                'utilityService:id,name,unit_of_measurement',
            ])
            ->where('organization_id', $organizationId)
            ->whereDate('billing_period_start', '>=', $startDate->toDateString())
            ->whereDate('billing_period_end', '<=', $endDate->toDateString())
            ->orderBy('billing_period_start')
            ->get()
            ->map(fn (BillingRecord $record): array => [
                'property' => (string) ($record->property?->name ?? '—'),
                'service' => (string) ($record->utilityService?->name ?? '—'),
                'consumption' => (float) ($record->consumption ?? 0),
                'amount' => (float) $record->amount,
                'unit' => (string) ($record->utilityService?->unit_of_measurement ?? ''),
            ])
            ->all();
    }
}
