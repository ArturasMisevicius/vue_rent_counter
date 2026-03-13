<?php

declare(strict_types=1);

namespace App\Services\Billing;

use Carbon\Carbon;
use InvalidArgumentException;

final class TimeOfUseRateResolver
{
    /**
     * @param  array<string, mixed>  $rateSchedule
     * @return array{zone_rates: array<string, float>, source: string, context: array<string, mixed>}
     */
    public function resolve(array $rateSchedule, Carbon $contextDate): array
    {
        $zoneRates = $this->normalizeZoneRates($rateSchedule['zone_rates'] ?? null);

        if ($zoneRates !== []) {
            return [
                'zone_rates' => $zoneRates,
                'source' => 'zone_rates',
                'context' => [
                    'day_type' => $contextDate->isWeekend() ? 'weekend' : 'weekday',
                    'month' => (int) $contextDate->month,
                ],
            ];
        }

        if (isset($rateSchedule['time_windows']) && is_array($rateSchedule['time_windows'])) {
            return $this->resolveFromTimeWindows($rateSchedule, $contextDate);
        }

        if (isset($rateSchedule['time_slots']) && is_array($rateSchedule['time_slots'])) {
            return $this->resolveFromTimeSlots($rateSchedule, $contextDate);
        }

        if (isset($rateSchedule['default_rate']) && is_numeric($rateSchedule['default_rate'])) {
            return [
                'zone_rates' => ['default' => (float) $rateSchedule['default_rate']],
                'source' => 'default_rate',
                'context' => [
                    'day_type' => $contextDate->isWeekend() ? 'weekend' : 'weekday',
                    'month' => (int) $contextDate->month,
                ],
            ];
        }

        throw new InvalidArgumentException(
            'Time-of-use pricing requires at least one of zone_rates, time_windows, time_slots, or default_rate.'
        );
    }

    /**
     * @return array<string, float>
     */
    private function normalizeZoneRates(mixed $zoneRates): array
    {
        if (! is_array($zoneRates)) {
            return [];
        }

        $normalized = [];

        foreach ($zoneRates as $zone => $rate) {
            if (! is_string($zone) || $zone === '' || ! is_numeric($rate)) {
                continue;
            }

            $normalized[$zone] = (float) $rate;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $rateSchedule
     * @return array{zone_rates: array<string, float>, source: string, context: array<string, mixed>}
     */
    private function resolveFromTimeWindows(array $rateSchedule, Carbon $contextDate): array
    {
        $windows = $this->normalizeTimeWindows($rateSchedule['time_windows']);
        $this->assertNoWindowOverlaps($windows);

        $dayType = $contextDate->isWeekend() ? 'weekend' : 'weekday';
        $month = (int) $contextDate->month;

        $applicable = array_values(array_filter(
            $windows,
            fn (array $window): bool => in_array($dayType, $window['day_types'], true)
                && in_array($month, $window['months'], true)
        ));

        if ($applicable === []) {
            throw new InvalidArgumentException(
                "No time window matched day_type '{$dayType}' and month '{$month}'."
            );
        }

        $zoneRates = [];

        foreach ($applicable as $window) {
            $zone = $window['zone'];
            $rate = $window['rate'];

            if (array_key_exists($zone, $zoneRates) && $zoneRates[$zone] !== $rate) {
                throw new InvalidArgumentException(
                    "Ambiguous time-of-use rate for zone '{$zone}'. Zone-based consumption requires one rate per zone in context."
                );
            }

            $zoneRates[$zone] = $rate;
        }

        if (isset($rateSchedule['default_rate']) && is_numeric($rateSchedule['default_rate'])) {
            $zoneRates['default'] = (float) $rateSchedule['default_rate'];
        }

        return [
            'zone_rates' => $zoneRates,
            'source' => 'time_windows',
            'context' => [
                'day_type' => $dayType,
                'month' => $month,
                'window_count' => count($applicable),
            ],
        ];
    }

    /**
     * @param  array<int, mixed>  $rawWindows
     * @return array<int, array{
     *   zone: string,
     *   rate: float,
     *   start: int,
     *   end: int,
     *   day_types: array<int, string>,
     *   months: array<int, int>
     * }>
     */
    private function normalizeTimeWindows(array $rawWindows): array
    {
        $normalized = [];

        foreach ($rawWindows as $index => $window) {
            if (! is_array($window)) {
                throw new InvalidArgumentException("Invalid time window at index {$index}.");
            }

            $zone = $window['zone'] ?? null;
            $start = $window['start'] ?? null;
            $end = $window['end'] ?? null;
            $rate = $window['rate'] ?? null;

            if (! is_string($zone) || $zone === '') {
                throw new InvalidArgumentException("Time window at index {$index} must include a non-empty zone.");
            }

            if (! is_string($start) || ! $this->isValidTimeString($start)) {
                throw new InvalidArgumentException("Time window '{$zone}' has invalid start time.");
            }

            if (! is_string($end) || ! $this->isValidTimeString($end)) {
                throw new InvalidArgumentException("Time window '{$zone}' has invalid end time.");
            }

            if (! is_numeric($rate) || (float) $rate < 0) {
                throw new InvalidArgumentException("Time window '{$zone}' has invalid rate.");
            }

            $startMinutes = $this->timeToMinutes($start);
            $endMinutes = $this->timeToMinutes($end);

            if ($startMinutes === $endMinutes) {
                throw new InvalidArgumentException(
                    "Time window '{$zone}' has identical start and end times, resulting in an empty range."
                );
            }

            $dayTypes = $this->normalizeDayTypes($window['day_types'] ?? null, $zone);
            $months = $this->normalizeMonths($window['months'] ?? null, $zone);

            $normalized[] = [
                'zone' => $zone,
                'rate' => (float) $rate,
                'start' => $startMinutes,
                'end' => $endMinutes,
                'day_types' => $dayTypes,
                'months' => $months,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array{
     *   zone: string,
     *   rate: float,
     *   start: int,
     *   end: int,
     *   day_types: array<int, string>,
     *   months: array<int, int>
     * }> $windows
     */
    private function assertNoWindowOverlaps(array $windows): void
    {
        $dayTypes = ['weekday', 'weekend'];

        foreach ($dayTypes as $dayType) {
            for ($month = 1; $month <= 12; $month++) {
                $segments = [];

                foreach ($windows as $window) {
                    if (! in_array($dayType, $window['day_types'], true) || ! in_array($month, $window['months'], true)) {
                        continue;
                    }

                    foreach ($this->expandWindowToSegments($window['start'], $window['end']) as $segment) {
                        $segment['zone'] = $window['zone'];
                        $segments[] = $segment;
                    }
                }

                usort(
                    $segments,
                    fn (array $left, array $right): int => $left['start'] <=> $right['start']
                );

                $lastEnd = null;
                $lastZone = null;

                foreach ($segments as $segment) {
                    if ($lastEnd !== null && $segment['start'] < $lastEnd) {
                        throw new InvalidArgumentException(
                            "Overlapping time windows detected for {$dayType} in month {$month} between zones '{$lastZone}' and '{$segment['zone']}'."
                        );
                    }

                    $lastEnd = $segment['end'];
                    $lastZone = $segment['zone'];
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $rateSchedule
     * @return array{zone_rates: array<string, float>, source: string, context: array<string, mixed>}
     */
    private function resolveFromTimeSlots(array $rateSchedule, Carbon $contextDate): array
    {
        $slots = $rateSchedule['time_slots'];
        $dayType = $contextDate->isWeekend() ? 'weekend' : 'weekday';
        $zoneRates = [];

        foreach ($slots as $index => $slot) {
            if (! is_array($slot)) {
                continue;
            }

            $zone = $slot['zone'] ?? null;
            $rate = $slot['rate'] ?? null;
            $slotDayType = $slot['day_type'] ?? 'all';

            if (! is_string($zone) || $zone === '' || ! is_numeric($rate)) {
                continue;
            }

            if (! in_array($slotDayType, ['weekday', 'weekend', 'all'], true)) {
                throw new InvalidArgumentException("Invalid day_type value in time_slots at index {$index}.");
            }

            if ($slotDayType !== 'all' && $slotDayType !== $dayType) {
                continue;
            }

            if (array_key_exists($zone, $zoneRates) && $zoneRates[$zone] !== (float) $rate) {
                throw new InvalidArgumentException(
                    "Ambiguous legacy time_slot rate for zone '{$zone}' in day_type '{$dayType}'."
                );
            }

            $zoneRates[$zone] = (float) $rate;
        }

        if (isset($rateSchedule['default_rate']) && is_numeric($rateSchedule['default_rate'])) {
            $zoneRates['default'] = (float) $rateSchedule['default_rate'];
        }

        if ($zoneRates === []) {
            throw new InvalidArgumentException('Legacy time_slots did not resolve any applicable zone rates.');
        }

        return [
            'zone_rates' => $zoneRates,
            'source' => 'time_slots',
            'context' => [
                'day_type' => $dayType,
                'month' => (int) $contextDate->month,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeDayTypes(mixed $dayTypes, string $zone): array
    {
        if ($dayTypes === null) {
            return ['weekday', 'weekend'];
        }

        $values = is_array($dayTypes) ? $dayTypes : [$dayTypes];
        $normalized = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                throw new InvalidArgumentException("Invalid day_types value for zone '{$zone}'.");
            }

            if ($value === 'all') {
                $normalized[] = 'weekday';
                $normalized[] = 'weekend';

                continue;
            }

            if (! in_array($value, ['weekday', 'weekend'], true)) {
                throw new InvalidArgumentException("Unsupported day type '{$value}' for zone '{$zone}'.");
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return array<int, int>
     */
    private function normalizeMonths(mixed $months, string $zone): array
    {
        if ($months === null) {
            return range(1, 12);
        }

        if (! is_array($months) || $months === []) {
            throw new InvalidArgumentException("Months for zone '{$zone}' must be a non-empty array.");
        }

        $normalized = [];

        foreach ($months as $value) {
            if (! is_numeric($value)) {
                throw new InvalidArgumentException("Invalid month value for zone '{$zone}'.");
            }

            $month = (int) $value;

            if ($month < 1 || $month > 12) {
                throw new InvalidArgumentException("Month '{$month}' for zone '{$zone}' is outside the 1..12 range.");
            }

            $normalized[] = $month;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return array<int, array{start: int, end: int}>
     */
    private function expandWindowToSegments(int $start, int $end): array
    {
        if ($start < $end) {
            return [['start' => $start, 'end' => $end]];
        }

        return [
            ['start' => $start, 'end' => 24 * 60],
            ['start' => 0, 'end' => $end],
        ];
    }

    private function isValidTimeString(string $value): bool
    {
        return (bool) preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value);
    }

    private function timeToMinutes(string $value): int
    {
        [$hour, $minute] = explode(':', $value);

        return ((int) $hour * 60) + (int) $minute;
    }
}
