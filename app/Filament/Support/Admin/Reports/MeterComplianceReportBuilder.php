<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\SystemConfiguration;
use App\Models\SystemSetting;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

final class MeterComplianceReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{building_id: int|null, property_id: int|null, tenant_id: int|null, meter_type: string|null, status_filter: string|null}  $filters
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
        $thresholdDays = $this->thresholdDays();
        $statusFilter = (string) ($filters['status_filter'] ?? 'all');
        $latestReadingDates = MeterReading::query()
            ->from('meter_readings as latest_candidates')
            ->join('meters as latest_meters', 'latest_meters.id', '=', 'latest_candidates.meter_id')
            ->where('latest_meters.organization_id', $organizationId)
            ->whereBetween('latest_candidates.reading_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->select(['latest_candidates.meter_id'])
            ->addSelect(new Expression('MAX(latest_candidates.reading_date) AS latest_reading_date'))
            ->groupBy('latest_candidates.meter_id');

        $latestReadingIds = MeterReading::query()
            ->from('meter_readings as latest_ranked')
            ->joinSub($latestReadingDates, 'latest_dates', function (JoinClause $join): void {
                $join->on('latest_dates.meter_id', '=', 'latest_ranked.meter_id');
                $join->on('latest_dates.latest_reading_date', '=', 'latest_ranked.reading_date');
            })
            ->select(['latest_ranked.meter_id'])
            ->addSelect(new Expression('MAX(latest_ranked.id) AS latest_reading_id'))
            ->groupBy('latest_ranked.meter_id');

        $latestReadings = MeterReading::query()
            ->from('meter_readings as latest_readings')
            ->joinSub($latestReadingIds, 'latest_ids', function (JoinClause $join): void {
                $join->on('latest_ids.latest_reading_id', '=', 'latest_readings.id');
            })
            ->select([
                'latest_readings.meter_id',
                'latest_readings.reading_date',
                'latest_readings.validation_status',
            ]);

        $driver = Meter::query()->getConnection()->getDriverName();
        $daysSinceExpression = $this->daysSinceExpression($driver, 'latest_readings.reading_date');

        $meters = Meter::query()
            ->from('meters')
            ->leftJoinSub($latestReadings, 'latest_readings', function (JoinClause $join): void {
                $join->on('latest_readings.meter_id', '=', 'meters.id');
            })
            ->join('properties', 'properties.id', '=', 'meters.property_id')
            ->leftJoin('buildings', 'buildings.id', '=', 'properties.building_id')
            ->leftJoin('property_assignments', function (JoinClause $join): void {
                $join->on('property_assignments.property_id', '=', 'properties.id');
                $join->on('property_assignments.organization_id', '=', 'meters.organization_id');
                $join->whereNull('property_assignments.unassigned_at');
            })
            ->leftJoin('users as tenants', function (JoinClause $join): void {
                $join->on('tenants.id', '=', 'property_assignments.tenant_user_id');
                $join->on('tenants.organization_id', '=', 'meters.organization_id');
            })
            ->where('meters.organization_id', $organizationId)
            ->when(
                filled($filters['building_id'] ?? null),
                fn ($query) => $query->where('properties.building_id', (int) $filters['building_id']),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn ($query) => $query->where('properties.id', (int) $filters['property_id']),
            )
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn ($query) => $query->where('property_assignments.tenant_user_id', (int) $filters['tenant_id']),
            )
            ->when(
                filled($filters['meter_type'] ?? null),
                fn ($query) => $query->where('meters.type', (string) $filters['meter_type']),
            )
            ->select([
                'meters.id as meter_id',
                'meters.name as meter_name',
                'meters.type as meter_type',
                'properties.name as property_name',
                'properties.unit_number as property_unit',
                'buildings.name as building_name',
                'tenants.name as tenant_name',
                'latest_readings.reading_date as latest_reading_date',
                'latest_readings.validation_status as latest_validation_status',
            ])
            ->addSelect(new Expression($daysSinceExpression.' AS days_since_last_reading'))
            ->orderByDesc('days_since_last_reading')
            ->orderBy('meters.name')
            ->get();

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = $meters
            ->map(function (object $meter) use ($thresholdDays): array {
                $daysSinceLastReading = isset($meter->days_since_last_reading)
                    ? (int) $meter->days_since_last_reading
                    : null;
                $complianceState = $this->complianceState(
                    $meter->latest_validation_status ?? null,
                    $daysSinceLastReading,
                    $thresholdDays,
                );

                $meterTypeValue = (string) ($meter->meter_type ?? '');
                $meterType = MeterType::tryFrom($meterTypeValue);
                $propertyLabel = trim(implode(' · ', array_filter([
                    (string) ($meter->property_name ?? ''),
                    (string) ($meter->property_unit ?? ''),
                    (string) ($meter->building_name ?? ''),
                ])));

                return [
                    'meter_id' => (int) ($meter->meter_id ?? 0),
                    'compliance_state' => $complianceState,
                    'meter' => (string) ($meter->meter_name ?? __('dashboard.not_available')),
                    'tenant' => (string) (($meter->tenant_name ?? '') !== '' ? $meter->tenant_name : __('dashboard.not_available')),
                    'property' => $propertyLabel !== '' ? $propertyLabel : __('dashboard.not_available'),
                    'type' => (string) ($meterType?->label() ?? $meterTypeValue),
                    'latest_reading' => $this->formatDate($meter->latest_reading_date ?? null),
                    'days_since_last_reading' => $daysSinceLastReading === null
                        ? __('dashboard.not_available')
                        : (string) $daysSinceLastReading,
                    'raw_days_since_last_reading' => $daysSinceLastReading ?? 100000,
                    'compliance' => __('admin.reports.states.'.$complianceState),
                ];
            })
            ->when(
                $statusFilter !== 'all',
                fn (Collection $rows): Collection => $rows->where('compliance_state', $statusFilter),
            )
            ->values();

        return [
            'title' => __('admin.reports.tabs.meter_compliance'),
            'description' => __('admin.reports.descriptions.meter_compliance_threshold', ['days' => $thresholdDays]),
            'summary' => [
                [
                    'label' => __('admin.reports.summary.compliant_meters'),
                    'value' => (string) $rowData->where('compliance_state', 'compliant')->count(),
                ],
                [
                    'label' => __('admin.reports.summary.needs_attention'),
                    'value' => (string) $rowData->where('compliance_state', 'needs_attention')->count(),
                ],
                [
                    'label' => __('admin.reports.summary.missing_readings'),
                    'value' => (string) $rowData->where('compliance_state', 'missing')->count(),
                ],
            ],
            'columns' => [
                ['key' => 'meter', 'label' => __('admin.reports.columns.meter')],
                ['key' => 'tenant', 'label' => __('admin.reports.columns.tenant')],
                ['key' => 'property', 'label' => __('admin.reports.columns.property')],
                ['key' => 'type', 'label' => __('admin.reports.columns.type')],
                ['key' => 'latest_reading', 'label' => __('admin.reports.columns.latest_reading')],
                ['key' => 'days_since_last_reading', 'label' => __('admin.reports.columns.days_since_last_reading')],
                ['key' => 'compliance', 'label' => __('admin.reports.columns.compliance')],
            ],
            'rows' => $rowData->all(),
            'empty_state' => __('admin.reports.empty.meter_compliance'),
        ];
    }

    private function daysSinceExpression(string $driver, string $column): string
    {
        return match ($driver) {
            'pgsql' => "CASE WHEN {$column} IS NULL THEN NULL WHEN (CURRENT_DATE - CAST({$column} as date)) > 0 THEN (CURRENT_DATE - CAST({$column} as date)) ELSE 0 END",
            'sqlite' => "CASE WHEN {$column} IS NULL THEN NULL WHEN CAST(julianday(date('now')) - julianday(date({$column})) AS INTEGER) > 0 THEN CAST(julianday(date('now')) - julianday(date({$column})) AS INTEGER) ELSE 0 END",
            default => "CASE WHEN {$column} IS NULL THEN NULL WHEN DATEDIFF(CURDATE(), DATE({$column})) > 0 THEN DATEDIFF(CURDATE(), DATE({$column})) ELSE 0 END",
        };
    }

    private function complianceState(
        MeterReadingValidationStatus|string|null $validationStatus,
        ?int $daysSinceLastReading,
        int $thresholdDays,
    ): string {
        if ($validationStatus === null || $validationStatus === '') {
            return 'missing';
        }

        $statusValue = $validationStatus instanceof MeterReadingValidationStatus
            ? $validationStatus->value
            : (string) $validationStatus;

        if (in_array($statusValue, [
            MeterReadingValidationStatus::PENDING->value,
            MeterReadingValidationStatus::REJECTED->value,
        ], true)) {
            return 'needs_attention';
        }

        if (($daysSinceLastReading ?? 0) > $thresholdDays) {
            return 'needs_attention';
        }

        return 'compliant';
    }

    private function thresholdDays(): int
    {
        $systemSetting = SystemSetting::query()
            ->select(['id', 'key', 'value'])
            ->where('key', 'reports.meter_compliance.threshold_days')
            ->first();

        if ($systemSetting !== null) {
            $value = is_array($systemSetting->value) ? ($systemSetting->value['value'] ?? null) : null;

            if (is_numeric($value)) {
                return max((int) $value, 1);
            }
        }

        $systemConfiguration = SystemConfiguration::query()
            ->select(['id', 'key', 'value'])
            ->where('key', 'reports.meter_compliance.threshold_days')
            ->first();

        if ($systemConfiguration !== null) {
            $value = is_array($systemConfiguration->value) ? ($systemConfiguration->value['value'] ?? null) : null;

            if (is_numeric($value)) {
                return max((int) $value, 1);
            }
        }

        return 30;
    }
}
