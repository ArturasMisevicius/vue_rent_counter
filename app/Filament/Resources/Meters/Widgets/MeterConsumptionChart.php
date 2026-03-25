<?php

namespace App\Filament\Resources\Meters\Widgets;

use App\Models\Meter;
use App\Models\MeterReading;
use Filament\Widgets\ChartWidget;

class MeterConsumptionChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public int $meterId;

    public function getHeading(): ?string
    {
        return __('admin.meters.sections.chart');
    }

    protected function getData(): array
    {
        $meter = Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
            ->find($this->meterId);

        if (! $meter instanceof Meter) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => __('admin.meters.chart.empty'),
                        'data' => [],
                        'borderColor' => '#0f172a',
                        'backgroundColor' => 'rgba(15, 23, 42, 0.12)',
                        'tension' => 0.25,
                    ],
                ],
            ];
        }

        $readings = MeterReading::query()
            ->select(['id', 'organization_id', 'meter_id', 'reading_value', 'reading_date'])
            ->forOrganization($meter->organization_id)
            ->forMeter($meter->id)
            ->orderBy('reading_date')
            ->orderBy('id')
            ->get();

        $labels = $readings
            ->map(fn (MeterReading $reading): string => (string) $reading->reading_date?->locale(app()->getLocale())->isoFormat('ll'))
            ->all();
        $values = $readings
            ->map(fn (MeterReading $reading): float => (float) $reading->reading_value)
            ->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('admin.meters.sections.consumption'),
                    'data' => $values,
                    'borderColor' => '#0f172a',
                    'backgroundColor' => 'rgba(15, 23, 42, 0.12)',
                    'tension' => 0.25,
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => __('admin.meters.fields.reading_value'),
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => __('admin.meter_readings.columns.reading_date'),
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
