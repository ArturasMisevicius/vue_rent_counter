<?php

namespace App\Services;

use App\ValueObjects\TimeConstants;

class TimeRangeValidator
{
    /**
     * Validate time-of-use zones for overlaps and 24-hour coverage.
     *
     * @param array $zones
     * @return array Array of error messages (empty if valid)
     */
    public function validate(array $zones): array
    {
        if (empty($zones)) {
            return [__('tariffs.validation.configuration.zones.errors.required')];
        }

        $timeRanges = $this->convertZonesToTimeRanges($zones);
        $errors = [];

        if ($this->hasOverlappingRanges($timeRanges)) {
            $errors[] = __('tariffs.validation.configuration.zones.errors.overlap');
        }

        $coverageError = $this->validateFullCoverage($timeRanges);
        if ($coverageError) {
            $errors[] = $coverageError;
        }

        return $errors;
    }

    /**
     * Convert zone definitions to normalized time ranges.
     *
     * @param array $zones
     * @return array
     */
    protected function convertZonesToTimeRanges(array $zones): array
    {
        $timeRanges = [];

        foreach ($zones as $index => $zone) {
            if (!isset($zone['start']) || !isset($zone['end'])) {
                continue;
            }

            $start = $this->timeToMinutes($zone['start']);
            $end = $this->timeToMinutes($zone['end']);

            // Handle midnight crossing (e.g., 23:00 to 07:00)
            if ($end <= $start) {
                $timeRanges[] = [
                    'start' => $start,
                    'end' => TimeConstants::MINUTES_PER_DAY,
                    'index' => $index
                ];
                $timeRanges[] = [
                    'start' => 0,
                    'end' => $end,
                    'index' => $index
                ];
            } else {
                $timeRanges[] = [
                    'start' => $start,
                    'end' => $end,
                    'index' => $index
                ];
            }
        }

        return $timeRanges;
    }

    /**
     * Check if any time ranges overlap.
     * 
     * Optimized algorithm: Sort ranges by start time and check adjacent ranges only.
     * Time complexity: O(n log n) instead of O(n²)
     *
     * @param array $timeRanges
     * @return bool
     */
    protected function hasOverlappingRanges(array $timeRanges): bool
    {
        if (count($timeRanges) < 2) {
            return false;
        }

        // Sort ranges by start time
        usort($timeRanges, fn($a, $b) => $a['start'] <=> $b['start']);

        $maxEnd = $timeRanges[0]['end'];

        // Sweep line: if the next range starts before the current max end, we have overlap.
        for ($i = 1; $i < count($timeRanges); $i++) {
            if ($timeRanges[$i]['start'] < $maxEnd) {
                return true;
            }

            if ($timeRanges[$i]['end'] > $maxEnd) {
                $maxEnd = $timeRanges[$i]['end'];
            }
        }

        return false;
    }

    /**
     * Check if two time ranges overlap.
     *
     * @param array $range1
     * @param array $range2
     * @return bool
     */
    protected function rangesOverlap(array $range1, array $range2): bool
    {
        return !($range1['end'] <= $range2['start'] || $range2['end'] <= $range1['start']);
    }

    /**
     * Validate that time zones cover all 24 hours.
     *
     * @param array $timeRanges
     * @return string|null Error message or null if valid
     */
    protected function validateFullCoverage(array $timeRanges): ?string
    {
        $minutesPerDay = TimeConstants::MINUTES_PER_DAY;
        $coverage = array_fill(0, $minutesPerDay, false);

        foreach ($timeRanges as $range) {
            for ($minute = $range['start']; $minute < $range['end']; $minute++) {
                $coverage[$minute] = true;
            }
        }

        $uncoveredMinutes = array_keys(array_filter($coverage, fn($covered) => !$covered));

        if (!empty($uncoveredMinutes)) {
            $firstUncovered = $uncoveredMinutes[0];
            $uncoveredTime = sprintf(
                '%02d:%02d',
                floor($firstUncovered / TimeConstants::MINUTES_PER_HOUR),
                $firstUncovered % TimeConstants::MINUTES_PER_HOUR
            );

            return __('tariffs.validation.configuration.zones.errors.coverage', [
                'time' => $uncoveredTime,
            ]);
        }

        return null;
    }

    /**
     * Convert time string (HH:MM) to minutes since midnight.
     *
     * @param string $time
     * @return int
     */
    protected function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int)$hours * TimeConstants::MINUTES_PER_HOUR + (int)$minutes;
    }
}
