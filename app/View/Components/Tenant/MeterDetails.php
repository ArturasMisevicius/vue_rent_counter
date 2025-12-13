<?php

declare(strict_types=1);

namespace App\View\Components\Tenant;

use App\Models\Meter;
use App\Models\MeterReading;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

final class MeterDetails extends Component
{
    public readonly string $unit;
    public readonly ?MeterReading $latestReading;
    public readonly ?MeterReading $previousReading;
    public readonly ?float $delta;
    public readonly Collection $chartReadings;
    public readonly float $minValue;
    public readonly float $maxValue;
    public readonly float $averageValue;
    public readonly Collection $monthlyChart;
    public readonly array $trendLabels;
    public readonly array $trendValues;
    public readonly array $usageLabels;
    public readonly array $usageValues;
    public readonly float $maxMonthly;
    public readonly float $totalUsage;

    public function __construct(public readonly Meter $meter)
    {
        $this->unit = $meter->serviceConfiguration?->utilityService?->unit_of_measurement
            ?? ($meter->type->value === 'electricity' ? 'kWh' : 'm³');
        $this->latestReading = $meter->readings->first();
        $this->previousReading = $meter->readings->skip(1)->first();
        
        $this->delta = $this->latestReading && $this->previousReading
            ? max($this->latestReading->getEffectiveValue() - $this->previousReading->getEffectiveValue(), 0)
            : null;

        // Chart data (latest 12 readings, ordered oldest -> newest)
        $this->chartReadings = $meter->readings
            ->sortByDesc('reading_date')
            ->take(12)
            ->sortBy('reading_date')
            ->values();

        $values = $this->chartReadings
            ->map(static fn (MeterReading $reading): float => $reading->getEffectiveValue())
            ->values();

        $this->trendLabels = $this->chartReadings
            ->map(static fn ($reading): string => $reading->reading_date->format('M d'))
            ->all();

        $this->trendValues = $values->all();
        $this->minValue = $values->isEmpty() ? 0.0 : (float) $values->min();
        $this->maxValue = $values->isEmpty() ? 0.0 : (float) $values->max();
        $this->averageValue = $values->isEmpty() ? 0.0 : (float) $values->avg();

        // Monthly deltas
        $monthlyDeltas = $this->calculateMonthlyDeltas($meter->readings);
        $this->monthlyChart = collect($monthlyDeltas)
            ->sortKeys()
            ->slice(-12, 12, true);

        $this->usageLabels = $this->monthlyChart
            ->keys()
            ->map(static fn (string $month): string => Carbon::createFromFormat('Y-m', $month)->format('M Y'))
            ->values()
            ->all();

        $this->usageValues = $this->monthlyChart
            ->values()
            ->map(static fn ($value): float => (float) $value)
            ->all();

        $this->maxMonthly = empty($this->usageValues) ? 0.0 : (float) max($this->usageValues);
        $this->totalUsage = array_sum($this->usageValues);
    }

    private function calculateMonthlyDeltas(Collection $readings): array
    {
        $monthlyDeltas = [];
        $sorted = $readings->sortBy('reading_date')->values();
        $prevReading = null;

        foreach ($sorted as $reading) {
            if ($prevReading) {
                $delta = max($reading->getEffectiveValue() - $prevReading->getEffectiveValue(), 0);
                $monthKey = $reading->reading_date->format('Y-m');
                $monthlyDeltas[$monthKey] = ($monthlyDeltas[$monthKey] ?? 0) + $delta;
            }
            $prevReading = $reading;
        }

        return $monthlyDeltas;
    }

    public function render(): View
    {
        return view('components.tenant.meter-details');
    }
}
