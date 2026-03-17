<?php

namespace App\Filament\Support\Admin\Reports;

use App\Enums\MeterType;
use App\Models\MeterReading;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ConsumptionReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{meter_type: string|null}  $filters
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: array<int, array{label: string, value: string}>,
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, string>>,
     *     empty_state: string
     * }
     */
    public function build(int $organizationId, CarbonInterface $startDate, CarbonInterface $endDate, array $filters): array
    {
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
            ->with([
                'meter:id,organization_id,property_id,name,identifier,type,unit,status',
                'property:id,building_id,name,unit_number',
                'property.building:id,name',
            ])
            ->forOrganization($organizationId)
            ->betweenDates($startDate, $endDate)
            ->when(
                filled($filters['meter_type'] ?? null),
                fn ($query) => $query->whereHas(
                    'meter',
                    fn ($query) => $query->where('type', $filters['meter_type']),
                ),
            )
            ->latestFirst()
            ->get()
            ->sortBy('reading_date')
            ->values();

        /** @var Collection<int, array<string, float|int|string>> $rowData */
        $rowData = $readings
            ->groupBy('meter_id')
            ->map(function (Collection $meterReadings): array {
                /** @var MeterReading $first */
                $first = $meterReadings->first();
                /** @var MeterReading $last */
                $last = $meterReadings->last();

                $consumption = max((float) $last->reading_value - (float) $first->reading_value, 0);

                return [
                    'raw_consumption' => $consumption,
                    'meter' => (string) ($first->meter?->name ?? __('dashboard.not_available')),
                    'property' => $this->propertyLabel($first->property),
                    'type' => $first->meter?->type?->label() ?? MeterType::WATER->label(),
                    'first_reading' => number_format((float) $first->reading_value, 3, '.', ''),
                    'last_reading' => number_format((float) $last->reading_value, 3, '.', ''),
                    'consumption' => number_format($consumption, 3, '.', ''),
                    'unit' => (string) ($first->meter?->unit ?? __('dashboard.not_available')),
                    'reading_count' => (string) $meterReadings->count(),
                ];
            })
            ->values();

        return [
            'title' => __('admin.reports.tabs.consumption'),
            'description' => __('admin.reports.descriptions.consumption'),
            'summary' => [
                [
                    'label' => __('admin.reports.summary.total_meters'),
                    'value' => (string) $rowData->count(),
                ],
                [
                    'label' => __('admin.reports.summary.total_readings'),
                    'value' => (string) $readings->count(),
                ],
                [
                    'label' => __('admin.reports.summary.total_consumption'),
                    'value' => number_format((float) $rowData->sum('raw_consumption'), 3, '.', ''),
                ],
            ],
            'columns' => [
                ['key' => 'meter', 'label' => __('admin.reports.columns.meter')],
                ['key' => 'property', 'label' => __('admin.reports.columns.property')],
                ['key' => 'type', 'label' => __('admin.reports.columns.type')],
                ['key' => 'first_reading', 'label' => __('admin.reports.columns.first_reading')],
                ['key' => 'last_reading', 'label' => __('admin.reports.columns.last_reading')],
                ['key' => 'consumption', 'label' => __('admin.reports.columns.consumption')],
                ['key' => 'unit', 'label' => __('admin.reports.columns.unit')],
                ['key' => 'reading_count', 'label' => __('admin.reports.columns.readings')],
            ],
            'rows' => $rowData
                ->map(fn (array $row): array => Arr::except($row, ['raw_consumption']))
                ->all(),
            'empty_state' => __('admin.reports.empty.consumption'),
        ];
    }
}
