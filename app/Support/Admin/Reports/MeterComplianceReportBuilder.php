<?php

namespace App\Support\Admin\Reports;

use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Support\Carbon;

class MeterComplianceReportBuilder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(int $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        return Meter::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'name',
                'status',
                'type',
            ])
            ->with([
                'property:id,name,unit_number',
                'latestReading:id,meter_id,reading_value,reading_date,validation_status',
            ])
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get()
            ->map(function (Meter $meter) use ($startDate, $endDate): array {
                $latestReading = MeterReading::query()
                    ->select(['id', 'meter_id', 'reading_date', 'validation_status'])
                    ->where('meter_id', $meter->id)
                    ->whereDate('reading_date', '>=', $startDate->toDateString())
                    ->whereDate('reading_date', '<=', $endDate->toDateString())
                    ->orderByDesc('reading_date')
                    ->orderByDesc('id')
                    ->first();

                return [
                    'meter' => $meter->name,
                    'property' => (string) ($meter->property?->name ?? '—'),
                    'latest_reading_date' => $latestReading?->reading_date?->toDateString(),
                    'validation_status' => $latestReading?->validation_status?->value ?? 'missing',
                ];
            })
            ->all();
    }
}
