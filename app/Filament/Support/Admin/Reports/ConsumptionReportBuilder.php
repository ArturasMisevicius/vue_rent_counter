<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\PropertyAssignment;
use Carbon\CarbonInterface;
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
                'tenant:id,organization_id,name,email',
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name',
                'property.meters' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'name',
                        'type',
                        'unit',
                    ])
                    ->when(
                        filled($filters['meter_type'] ?? null),
                        fn ($query) => $query->where('type', $filters['meter_type']),
                    )
                    ->with([
                        'readings' => fn ($query) => $query
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
                            ->comparable()
                            ->orderBy('reading_date')
                            ->orderBy('id'),
                    ]),
            ])
            ->forOrganization($organizationId)
            ->activeDuring($startDate, $endDate)
            ->when(
                filled($filters['building_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'property',
                    fn ($query) => $query->where('building_id', $filters['building_id']),
                ),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn ($query) => $query->forProperty($filters['property_id']),
            )
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn ($query) => $query->forTenant($filters['tenant_id']),
            )
            ->get()
            ->values();

        /** @var array<string, array<string, mixed>> $aggregated */
        $aggregated = [];

        $assignments->each(function (PropertyAssignment $assignment) use (&$aggregated): void {
            $property = $assignment->property;
            $tenant = $assignment->tenant;

            if ($property === null || $tenant === null) {
                return;
            }

            $property->meters
                ->groupBy(fn (Meter $meter): string => $meter->type?->value ?? MeterType::WATER->value)
                ->each(function (Collection $meters, string $typeValue) use (&$aggregated, $property, $tenant): void {
                    $firstReading = null;
                    $lastReading = null;
                    $consumption = 0.0;
                    $readingCount = 0;
                    $unit = null;

                    $meters->each(function (Meter $meter) use (&$consumption, &$firstReading, &$lastReading, &$readingCount, &$unit): void {
                        $readings = $meter->readings
                            ->sortBy('reading_date')
                            ->values();

                        if ($readings->isEmpty()) {
                            return;
                        }

                        $first = $readings->first();
                        $last = $readings->last();

                        if ($first === null || $last === null) {
                            return;
                        }

                        $firstValue = (float) $first->reading_value;
                        $lastValue = (float) $last->reading_value;

                        $consumption += max($lastValue - $firstValue, 0.0);
                        $readingCount += $readings->count();
                        $unit ??= $meter->unit;
                        $firstReading = $firstReading === null ? $firstValue : min($firstReading, $firstValue);
                        $lastReading = $lastReading === null ? $lastValue : max($lastReading, $lastValue);
                    });

                    if ($readingCount === 0) {
                        return;
                    }

                    $key = $tenant->id.'|'.$typeValue;
                    $propertyLabel = $this->propertyLabel($property);
                    $buildingName = (string) ($property->building?->name ?? __('dashboard.not_available'));

                    if (! array_key_exists($key, $aggregated)) {
                        $aggregated[$key] = [
                            'tenant_id' => $tenant->id,
                            'tenant' => $tenant->name,
                            'tenant_email' => $tenant->email,
                            'building_labels' => [$buildingName],
                            'property_labels' => [$propertyLabel],
                            'type' => MeterType::tryFrom($typeValue)?->label() ?? $typeValue,
                            'raw_first_reading' => $firstReading,
                            'raw_last_reading' => $lastReading,
                            'raw_consumption' => $consumption,
                            'unit' => $unit ?? __('dashboard.not_available'),
                            'reading_count' => $readingCount,
                        ];

                        return;
                    }

                    $aggregated[$key]['building_labels'] = array_values(array_unique([
                        ...$aggregated[$key]['building_labels'],
                        $buildingName,
                    ]));
                    $aggregated[$key]['property_labels'] = array_values(array_unique([
                        ...$aggregated[$key]['property_labels'],
                        $propertyLabel,
                    ]));
                    $aggregated[$key]['raw_first_reading'] = min(
                        (float) $aggregated[$key]['raw_first_reading'],
                        (float) $firstReading,
                    );
                    $aggregated[$key]['raw_last_reading'] = max(
                        (float) $aggregated[$key]['raw_last_reading'],
                        (float) $lastReading,
                    );
                    $aggregated[$key]['raw_consumption'] = (float) $aggregated[$key]['raw_consumption'] + $consumption;
                    $aggregated[$key]['reading_count'] = (int) $aggregated[$key]['reading_count'] + $readingCount;
                });
        });

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = collect($aggregated)
            ->values()
            ->sortBy([
                fn (array $row): string => (string) $row['tenant'],
                fn (array $row): string => (string) $row['type'],
            ])
            ->map(fn (array $row): array => [
                'tenant_id' => $row['tenant_id'],
                'tenant' => $row['tenant'],
                'tenant_email' => $row['tenant_email'],
                'building' => implode(', ', $row['building_labels']),
                'property' => implode(', ', $row['property_labels']),
                'type' => $row['type'],
                'first_reading' => $this->formatNumber((float) $row['raw_first_reading'], 3),
                'last_reading' => $this->formatNumber((float) $row['raw_last_reading'], 3),
                'consumption' => $this->formatNumber((float) $row['raw_consumption'], 3),
                'unit' => $row['unit'],
                'reading_count' => (string) $row['reading_count'],
                'raw_consumption' => $row['raw_consumption'],
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
}
