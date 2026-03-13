<?php

namespace App\Services\TariffCalculation;

use App\Models\Tariff;
use Carbon\Carbon;

class TimeOfUseStrategy implements TariffCalculationStrategy
{
    /**
     * Calculate the cost for a time-of-use tariff.
     *
     * @param Tariff $tariff
     * @param float $consumption
     * @param Carbon $timestamp
     * @return float
     */
    public function calculate(Tariff $tariff, float $consumption, Carbon $timestamp): float
    {
        $config = $tariff->configuration;
        $zone = $this->determineZone($config['zones'], $timestamp, $config['weekend_logic'] ?? null);
        return $consumption * $zone['rate'];
    }

    /**
     * Check if this strategy supports time-of-use tariffs.
     *
     * @param string $tariffType
     * @return bool
     */
    public function supports(string $tariffType): bool
    {
        return $tariffType === 'time_of_use';
    }

    /**
     * Determine which tariff zone applies for a given timestamp.
     *
     * @param array $zones
     * @param Carbon $timestamp
     * @param string|null $weekendLogic
     * @return array
     */
    private function determineZone(array $zones, Carbon $timestamp, ?string $weekendLogic): array
    {
        if ($weekendLogic && $timestamp->isWeekend()) {
            $weekendZone = $this->getWeekendZone($zones, $weekendLogic);
            if ($weekendZone !== null) {
                return $weekendZone;
            }
        }

        return $this->getZoneByTime($zones, $timestamp);
    }

    /**
     * Get the zone to apply for weekend based on weekend logic.
     *
     * @param array $zones
     * @param string $weekendLogic
     * @return array|null
     */
    private function getWeekendZone(array $zones, string $weekendLogic): ?array
    {
        $zoneId = match ($weekendLogic) {
            'apply_night_rate' => 'night',
            'apply_day_rate' => 'day',
            'apply_weekend_rate' => 'weekend',
            default => null,
        };

        if ($zoneId === null) {
            return null;
        }

        foreach ($zones as $zone) {
            if ($zone['id'] === $zoneId) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Get the zone that matches the current time.
     *
     * @param array $zones
     * @param Carbon $timestamp
     * @return array
     */
    private function getZoneByTime(array $zones, Carbon $timestamp): array
    {
        $currentTime = $timestamp->format('H:i');

        foreach ($zones as $zone) {
            if ($this->isTimeInRange($currentTime, $zone['start'], $zone['end'])) {
                return $zone;
            }
        }

        return $zones[0];
    }

    /**
     * Check if a time falls within a given range.
     * Handles ranges that cross midnight (e.g., 23:00 to 07:00).
     *
     * @param string $time
     * @param string $start
     * @param string $end
     * @return bool
     */
    private function isTimeInRange(string $time, string $start, string $end): bool
    {
        if ($start < $end) {
            return $time >= $start && $time < $end;
        }

        return $time >= $start || $time < $end;
    }
}
