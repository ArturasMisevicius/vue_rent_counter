<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Models\MeterReading;
use App\Models\PropertyAssignment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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
        $comparables = MeterReadingValidationStatus::comparableValues();
        $tenantFilter = filled($filters['tenant_id'] ?? null)
            ? (int) $filters['tenant_id']
            : null;

        $readings = MeterReading::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'meter_id',
                'reading_value',
                'reading_date',
                'validation_status',
            ])
            ->forOrganization($organizationId)
            ->betweenDates($startDate, $endDate)
            ->whereIn('validation_status', $comparables)
            ->with([
                'meter:id,organization_id,property_id,type,unit',
                'meter.property:id,organization_id,building_id,name,unit_number',
                'meter.property.building:id,organization_id,name',
            ])
            ->when(
                filled($filters['meter_type'] ?? null),
                fn (Builder $query): Builder => $query->whereHas(
                    'meter',
                    fn (Builder $meterQuery): Builder => $meterQuery->where('type', (string) $filters['meter_type']),
                ),
            )
            ->when(
                filled($filters['building_id'] ?? null),
                fn (Builder $query): Builder => $query->whereHas(
                    'meter.property',
                    fn (Builder $propertyQuery): Builder => $propertyQuery->where('building_id', (int) $filters['building_id']),
                ),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn (Builder $query): Builder => $query->where('property_id', (int) $filters['property_id']),
            )
            ->orderBy('reading_date')
            ->orderBy('id')
            ->get();

        $assignmentMap = $this->assignmentMap($organizationId, $readings, $startDate, $endDate, $tenantFilter);
        $groupedRows = [];

        foreach ($readings as $reading) {
            $meter = $reading->meter;

            if ($meter === null || $reading->property_id === null) {
                continue;
            }

            $assignments = $assignmentMap->get($reading->property_id, collect());
            $assignment = $this->resolveTenantAssignment($assignments, $reading->reading_date);
            $tenant = $assignment?->tenant;

            if ($assignment === null || $tenant === null) {
                continue;
            }

            if ($tenantFilter !== null && (int) $assignment->tenant_user_id !== $tenantFilter) {
                continue;
            }

            $meterType = $meter->type;
            $meterTypeValue = $meterType instanceof MeterType
                ? $meterType->value
                : (string) $meterType;
            $groupKey = $assignment->tenant_user_id.'|'.$meterTypeValue;

            if (! array_key_exists($groupKey, $groupedRows)) {
                $groupedRows[$groupKey] = [
                    'tenant_id' => (int) $assignment->tenant_user_id,
                    'tenant' => (string) $tenant->name,
                    'tenant_email' => (string) $tenant->email,
                    'meter_type' => (string) $meterTypeValue,
                    'unit' => (string) ($meter->unit ?? ''),
                    'building_names' => [],
                    'property_names' => [],
                    'raw_reading_count' => 0,
                    'first_reading' => null,
                    'last_reading' => null,
                    'first_sort_key' => null,
                    'last_sort_key' => null,
                ];
            }

            $group = $groupedRows[$groupKey];
            $sortKey = $this->readingSortKey($reading->reading_date, (int) $reading->id);
            $readingValue = (float) $reading->reading_value;
            $propertyName = (string) ($meter->property?->name ?? '');
            $buildingName = (string) ($meter->property?->building?->name ?? '');

            $group['raw_reading_count']++;

            if ($propertyName !== '') {
                $group['property_names'][$propertyName] = $propertyName;
            }

            if ($buildingName !== '') {
                $group['building_names'][$buildingName] = $buildingName;
            }

            if ($group['first_sort_key'] === null || $sortKey < $group['first_sort_key']) {
                $group['first_sort_key'] = $sortKey;
                $group['first_reading'] = $readingValue;
            }

            if ($group['last_sort_key'] === null || $sortKey > $group['last_sort_key']) {
                $group['last_sort_key'] = $sortKey;
                $group['last_reading'] = $readingValue;
            }

            $groupedRows[$groupKey] = $group;
        }

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = collect($groupedRows)
            ->map(function (array $group): array {
                $firstReading = (float) ($group['first_reading'] ?? 0.0);
                $lastReading = (float) ($group['last_reading'] ?? 0.0);
                $rawConsumption = max($lastReading - $firstReading, 0.0);
                $type = MeterType::tryFrom((string) $group['meter_type']);
                $buildings = implode(', ', array_values($group['building_names']));
                $properties = implode(', ', array_values($group['property_names']));

                return [
                    'tenant_id' => (int) $group['tenant_id'],
                    'tenant' => (string) $group['tenant'],
                    'tenant_email' => (string) $group['tenant_email'],
                    'building' => $buildings !== '' ? $buildings : __('dashboard.not_available'),
                    'property' => $properties !== '' ? $properties : __('dashboard.not_available'),
                    'type' => (string) ($type?->label() ?? $group['meter_type']),
                    'first_reading' => $this->formatNumber($firstReading, 3),
                    'last_reading' => $this->formatNumber($lastReading, 3),
                    'consumption' => $this->formatNumber($rawConsumption, 3),
                    'unit' => $group['unit'] !== '' ? (string) $group['unit'] : __('dashboard.not_available'),
                    'reading_count' => (string) (int) $group['raw_reading_count'],
                    'raw_consumption' => $rawConsumption,
                ];
            })
            ->sortBy([
                ['tenant', 'asc'],
                ['type', 'asc'],
            ])
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

    /**
     * @param  Collection<int, MeterReading>  $readings
     * @return Collection<int, Collection<int, PropertyAssignment>>
     */
    private function assignmentMap(
        int $organizationId,
        Collection $readings,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        ?int $tenantFilter = null,
    ): Collection {
        $propertyIds = $readings
            ->pluck('property_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

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
            ->where('assigned_at', '<=', $endDate->copy()->endOfDay())
            ->when(
                $tenantFilter !== null,
                fn (Builder $query): Builder => $query->where('tenant_user_id', $tenantFilter),
            )
            ->where(function (Builder $query) use ($startDate): void {
                $query
                    ->whereNull('unassigned_at')
                    ->orWhere('unassigned_at', '>=', $startDate->copy()->startOfDay());
            })
            ->with([
                'tenant:id,organization_id,name,email',
            ])
            ->orderBy('property_id')
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();

        return $assignments->groupBy('property_id');
    }

    /**
     * @param  Collection<int, PropertyAssignment>  $assignments
     */
    private function resolveTenantAssignment(Collection $assignments, CarbonInterface|string|null $readingDate): ?PropertyAssignment
    {
        if (! filled($readingDate)) {
            return null;
        }

        $readingDay = $readingDate instanceof CarbonInterface
            ? $readingDate->copy()->startOfDay()
            : Carbon::parse((string) $readingDate)->startOfDay();

        return $assignments->first(function (PropertyAssignment $assignment) use ($readingDay): bool {
            $assignedAt = $assignment->assigned_at?->copy()->startOfDay();
            $unassignedAt = $assignment->unassigned_at?->copy()->startOfDay();

            if ($assignedAt !== null && $assignedAt->greaterThan($readingDay)) {
                return false;
            }

            return $unassignedAt === null || $unassignedAt->greaterThanOrEqualTo($readingDay);
        });
    }

    private function readingSortKey(CarbonInterface|string|null $readingDate, int $readingId): string
    {
        $dateKey = $readingDate instanceof CarbonInterface
            ? $readingDate->toDateString()
            : (filled($readingDate) ? Carbon::parse((string) $readingDate)->toDateString() : '0000-00-00');

        return $dateKey.'|'.str_pad((string) $readingId, 10, '0', STR_PAD_LEFT);
    }
}
