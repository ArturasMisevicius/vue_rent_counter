<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\PropertyAssignment;
use App\Models\SystemConfiguration;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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
        $tenantFilter = filled($filters['tenant_id'] ?? null)
            ? (int) $filters['tenant_id']
            : null;

        $meters = Meter::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'name',
                'type',
            ])
            ->forOrganization($organizationId)
            ->with([
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name',
            ])
            ->when(
                filled($filters['building_id'] ?? null),
                fn (Builder $query): Builder => $query->whereHas(
                    'property',
                    fn (Builder $propertyQuery): Builder => $propertyQuery->where('building_id', (int) $filters['building_id']),
                ),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn (Builder $query): Builder => $query->where('property_id', (int) $filters['property_id']),
            )
            ->when(
                filled($filters['meter_type'] ?? null),
                fn (Builder $query): Builder => $query->where('type', (string) $filters['meter_type']),
            )
            ->ordered()
            ->get();

        $latestReadingsByMeter = $this->latestReadingsByMeter($organizationId, $meters);
        $currentAssignmentsByProperty = $this->currentAssignmentsByProperty($organizationId, $meters);

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = $meters
            ->map(function (Meter $meter) use (
                $latestReadingsByMeter,
                $currentAssignmentsByProperty,
                $tenantFilter,
                $statusFilter,
                $thresholdDays
            ): ?array {
                $latestReading = $latestReadingsByMeter->get($meter->id);
                $assignment = $meter->property_id !== null
                    ? $currentAssignmentsByProperty->get($meter->property_id)
                    : null;

                if ($tenantFilter !== null && (int) ($assignment?->tenant_user_id ?? 0) !== $tenantFilter) {
                    return null;
                }

                $daysSinceLastReading = $this->daysSinceLastReading($latestReading?->reading_date);
                $complianceState = $this->complianceState(
                    $latestReading?->validation_status,
                    $daysSinceLastReading,
                    $thresholdDays,
                );

                if ($statusFilter !== 'all' && $statusFilter !== $complianceState) {
                    return null;
                }

                $meterType = $meter->type;
                $typeValue = $meterType instanceof MeterType
                    ? $meterType->value
                    : (string) $meterType;
                $typeLabel = MeterType::tryFrom($typeValue)?->label() ?? $typeValue;

                return [
                    'meter_id' => (int) $meter->id,
                    'compliance_state' => $complianceState,
                    'meter' => (string) $meter->name,
                    'tenant' => (string) ($assignment?->tenant?->name ?: __('dashboard.not_available')),
                    'property' => $this->propertyLabel($meter->property),
                    'type' => (string) $typeLabel,
                    'latest_reading' => $this->formatDate($latestReading?->reading_date),
                    'days_since_last_reading' => $daysSinceLastReading === null
                        ? __('dashboard.not_available')
                        : (string) $daysSinceLastReading,
                    'raw_days_since_last_reading' => $daysSinceLastReading ?? 100000,
                    'compliance' => __('admin.reports.states.'.$complianceState),
                ];
            })
            ->filter()
            ->sortByDesc('raw_days_since_last_reading')
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

    /**
     * @param  Collection<int, Meter>  $meters
     * @return Collection<int, MeterReading>
     */
    private function latestReadingsByMeter(int $organizationId, Collection $meters): Collection
    {
        $meterIds = $meters->pluck('id')->filter()->values()->all();

        if ($meterIds === []) {
            return collect();
        }

        /** @var Collection<int, MeterReading> $readings */
        $readings = MeterReading::query()
            ->select([
                'id',
                'organization_id',
                'meter_id',
                'reading_value',
                'reading_date',
                'validation_status',
            ])
            ->forOrganization($organizationId)
            ->whereIn('meter_id', $meterIds)
            ->orderBy('meter_id')
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->get();

        return $readings
            ->groupBy('meter_id')
            ->map(fn (Collection $meterReadings): MeterReading => $meterReadings->first())
            ->values()
            ->keyBy('meter_id');
    }

    /**
     * @param  Collection<int, Meter>  $meters
     * @return Collection<int, PropertyAssignment>
     */
    private function currentAssignmentsByProperty(int $organizationId, Collection $meters): Collection
    {
        $propertyIds = $meters->pluck('property_id')->filter()->unique()->values()->all();

        if ($propertyIds === []) {
            return collect();
        }

        /** @var Collection<int, PropertyAssignment> $assignments */
        $assignments = PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'assigned_at',
                'unassigned_at',
            ])
            ->forOrganization($organizationId)
            ->whereIn('property_id', $propertyIds)
            ->current()
            ->with([
                'tenant:id,organization_id,name,email',
            ])
            ->orderBy('property_id')
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();

        return $assignments
            ->groupBy('property_id')
            ->map(fn (Collection $propertyAssignments): PropertyAssignment => $propertyAssignments->first())
            ->values()
            ->keyBy('property_id');
    }

    private function daysSinceLastReading(CarbonInterface|string|null $readingDate): ?int
    {
        if (! filled($readingDate)) {
            return null;
        }

        $readingDay = $readingDate instanceof CarbonInterface
            ? $readingDate->copy()->startOfDay()
            : Carbon::parse((string) $readingDate)->startOfDay();
        $today = now()->startOfDay();

        if ($readingDay->greaterThan($today)) {
            return 0;
        }

        return $readingDay->diffInDays($today);
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
