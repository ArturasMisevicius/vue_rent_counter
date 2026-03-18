<?php

namespace App\Filament\Resources\Meters\Widgets;

use App\Models\Meter;
use App\Models\MeterReading;
use Filament\Widgets\Widget;

class MeterConsumptionChart extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.resources.meters.widgets.meter-consumption-chart';

    protected int|string|array $columnSpan = 'full';

    public int $meterId;

    protected function getViewData(): array
    {
        $meter = Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
            ->find($this->meterId);

        if (! $meter instanceof Meter) {
            return [
                'points' => '',
                'readings' => [],
                'minValue' => null,
                'maxValue' => null,
            ];
        }

        $readings = MeterReading::query()
            ->select(['id', 'organization_id', 'meter_id', 'reading_value', 'reading_date'])
            ->forOrganization($meter->organization_id)
            ->forMeter($meter->id)
            ->orderBy('reading_date')
            ->orderBy('id')
            ->get();

        if ($readings->isEmpty()) {
            return [
                'points' => '',
                'readings' => [],
                'minValue' => null,
                'maxValue' => null,
            ];
        }

        $width = 640;
        $height = 240;
        $paddingX = 28;
        $paddingY = 24;
        $chartWidth = $width - ($paddingX * 2);
        $chartHeight = $height - ($paddingY * 2);
        $values = $readings->map(fn (MeterReading $reading): float => (float) $reading->reading_value);
        $minValue = $values->min();
        $maxValue = $values->max();
        $range = max(($maxValue ?? 0.0) - ($minValue ?? 0.0), 1.0);
        $pointCount = max($readings->count() - 1, 1);

        $points = $readings
            ->values()
            ->map(function (MeterReading $reading, int $index) use (
                $chartHeight,
                $chartWidth,
                $maxValue,
                $paddingX,
                $paddingY,
                $pointCount,
                $range,
            ): string {
                $x = $paddingX + (($chartWidth / $pointCount) * $index);
                $y = $paddingY + ($chartHeight - ((((float) $reading->reading_value) - (float) $maxValue + $range) / $range * $chartHeight));

                return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
            })
            ->implode(' ');

        return [
            'points' => $points,
            'readings' => $readings,
            'minValue' => $minValue,
            'maxValue' => $maxValue,
            'width' => $width,
            'height' => $height,
            'paddingX' => $paddingX,
            'paddingY' => $paddingY,
            'unit' => $meter->unit,
        ];
    }
}
