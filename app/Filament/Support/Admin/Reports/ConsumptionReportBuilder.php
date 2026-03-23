<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Models\MeterReading;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

final class ConsumptionReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{building_id: int|null, property_id: int|null, tenant_id: int|null, meter_type: string|null}  $filters
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: array<int, array{label: string, value: string}>,
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, mixed>>,
     *     empty_state: string
     * }
     */
    public function build(int $organizationId, CarbonInterface $startDate, CarbonInterface $endDate, array $filters): array
    {
        $query = MeterReading::query()
            ->from('meter_readings')
            ->join('meters', function (JoinClause $join): void {
                $join->on('meters.id', '=', 'meter_readings.meter_id');
                $join->on('meters.organization_id', '=', 'meter_readings.organization_id');
            })
            ->join('properties', 'properties.id', '=', 'meters.property_id')
            ->leftJoin('buildings', 'buildings.id', '=', 'properties.building_id')
            ->join('property_assignments', function (JoinClause $join): void {
                $join->on('property_assignments.property_id', '=', 'properties.id');
                $join->on('property_assignments.organization_id', '=', 'meter_readings.organization_id');
                $join->whereNull('property_assignments.unassigned_at');
            })
            ->join('users as tenants', function (JoinClause $join): void {
                $join->on('tenants.id', '=', 'property_assignments.tenant_user_id');
                $join->on('tenants.organization_id', '=', 'meter_readings.organization_id');
            })
            ->where('meter_readings.organization_id', $organizationId)
            ->whereBetween('meter_readings.reading_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->whereIn('meter_readings.validation_status', MeterReadingValidationStatus::comparableValues())
            ->when(
                filled($filters['building_id'] ?? null),
                fn ($builder) => $builder->where('properties.building_id', (int) $filters['building_id']),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn ($builder) => $builder->where('properties.id', (int) $filters['property_id']),
            )
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn ($builder) => $builder->where('property_assignments.tenant_user_id', (int) $filters['tenant_id']),
            )
            ->when(
                filled($filters['meter_type'] ?? null),
                fn ($builder) => $builder->where('meters.type', (string) $filters['meter_type']),
            )
            ->select([
                'property_assignments.tenant_user_id as tenant_id',
                'tenants.name as tenant_name',
                'tenants.email as tenant_email',
                'meters.type as meter_type',
            ])
            ->addSelect(new Expression('COUNT(meter_readings.id) AS readings_count'))
            ->addSelect(new Expression('MIN(meter_readings.reading_value) AS first_reading_value'))
            ->addSelect(new Expression('MAX(meter_readings.reading_value) AS last_reading_value'))
            ->addSelect(new Expression('MAX(meter_readings.reading_value) - MIN(meter_readings.reading_value) AS total_consumption_value'))
            ->addSelect(new Expression('MIN(meters.unit) AS meter_unit'))
            ->addSelect(new Expression('MIN(buildings.name) AS building_name'))
            ->addSelect(new Expression('MIN(properties.name) AS property_name'))
            ->addSelect(new Expression('MIN(properties.unit_number) AS property_unit'))
            ->groupBy([
                'property_assignments.tenant_user_id',
                'tenants.name',
                'tenants.email',
                'meters.type',
            ])
            ->orderBy('tenant_name')
            ->orderBy('meter_type')
            ->get();

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = $query
            ->map(function (object $row): array {
                $meterTypeValue = (string) ($row->meter_type ?? '');
                $meterType = MeterType::tryFrom($meterTypeValue);
                $firstReading = (float) ($row->first_reading_value ?? 0);
                $lastReading = (float) ($row->last_reading_value ?? 0);
                $rawConsumption = max((float) ($row->total_consumption_value ?? 0), 0.0);

                $propertyLabel = trim(implode(' · ', array_filter([
                    (string) ($row->property_name ?? ''),
                    (string) ($row->property_unit ?? ''),
                ])));

                return [
                    'tenant_id' => (int) ($row->tenant_id ?? 0),
                    'tenant' => (string) ($row->tenant_name ?? __('dashboard.not_available')),
                    'tenant_email' => (string) ($row->tenant_email ?? ''),
                    'building' => (string) ($row->building_name ?: __('dashboard.not_available')),
                    'property' => $propertyLabel !== '' ? $propertyLabel : __('dashboard.not_available'),
                    'type' => (string) ($meterType?->label() ?? $meterTypeValue),
                    'first_reading' => $this->formatNumber($firstReading, 3),
                    'last_reading' => $this->formatNumber($lastReading, 3),
                    'consumption' => $this->formatNumber($rawConsumption, 3),
                    'unit' => (string) (($row->meter_unit ?? '') !== '' ? $row->meter_unit : __('dashboard.not_available')),
                    'reading_count' => (string) (int) ($row->readings_count ?? 0),
                    'raw_consumption' => $rawConsumption,
                ];
            })
            ->values();

        return [
            'title' => __('admin.reports.tabs.consumption'),
            'description' => __('admin.reports.descriptions.consumption_grouped'),
            'summary' => [
                [
                    'label' => __('admin.reports.summary.tenant_groups'),
                    'value' => (string) $rowData->pluck('tenant_id')->unique()->count(),
                ],
                [
                    'label' => __('admin.reports.summary.meter_type_groups'),
                    'value' => (string) $rowData->count(),
                ],
                [
                    'label' => __('admin.reports.summary.total_consumption'),
                    'value' => $this->formatNumber((float) $rowData->sum('raw_consumption'), 3),
                ],
            ],
            'columns' => [
                ['key' => 'tenant', 'label' => __('admin.reports.columns.tenant')],
                ['key' => 'building', 'label' => __('admin.reports.columns.building')],
                ['key' => 'property', 'label' => __('admin.reports.columns.property')],
                ['key' => 'type', 'label' => __('admin.reports.columns.type')],
                ['key' => 'first_reading', 'label' => __('admin.reports.columns.first_reading')],
                ['key' => 'last_reading', 'label' => __('admin.reports.columns.last_reading')],
                ['key' => 'consumption', 'label' => __('admin.reports.columns.consumption')],
                ['key' => 'unit', 'label' => __('admin.reports.columns.unit')],
                ['key' => 'reading_count', 'label' => __('admin.reports.columns.readings')],
            ],
            'rows' => $rowData->all(),
            'empty_state' => __('admin.reports.empty.consumption'),
        ];
    }
}
