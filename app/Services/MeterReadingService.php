<?php

namespace App\Services;

use App\Models\Meter;
use App\Models\MeterReading;

class MeterReadingService
{
    /**
     * Get the previous reading for a meter and zone.
     *
     * @param Meter $meter
     * @param string|null $zone
     * @param string|null $beforeDate
     * @return MeterReading|null
     */
    public function getPreviousReading(Meter $meter, ?string $zone, ?string $beforeDate = null): ?MeterReading
    {
        $query = $meter->readings()
            ->when($zone, fn($q) => $q->where('zone', $zone), fn($q) => $q->whereNull('zone'))
            ->orderBy('reading_date', 'desc');

        if ($beforeDate) {
            $query->whereDate('reading_date', '<', $beforeDate);
        }

        return $query->first();
    }

    /**
     * Get the next reading for a meter and zone.
     *
     * @param Meter $meter
     * @param string|null $zone
     * @param string $afterDate
     * @return MeterReading|null
     */
    public function getNextReading(Meter $meter, ?string $zone, string $afterDate): ?MeterReading
    {
        return $meter->readings()
            ->when($zone, fn($q) => $q->where('zone', $zone), fn($q) => $q->whereNull('zone'))
            ->whereDate('reading_date', '>', $afterDate)
            ->orderBy('reading_date', 'asc')
            ->first();
    }

    /**
     * Get adjacent reading (previous or next) for a meter reading.
     * 
     * Performance: Uses indexed columns (meter_id, reading_date, zone)
     * Query optimization: Single query with proper ordering and limit
     *
     * @param MeterReading $reading
     * @param string|null $zone
     * @param string $direction 'previous' or 'next'
     * @return MeterReading|null
     */
    public function getAdjacentReading(MeterReading $reading, ?string $zone, string $direction): ?MeterReading
    {
        // Use select() to minimize data transfer
        $query = $reading->meter
            ->readings()
            ->select(['id', 'meter_id', 'value', 'reading_date', 'zone'])
            ->where('id', '!=', $reading->id)
            ->when($zone, fn($q) => $q->where('zone', $zone), fn($q) => $q->whereNull('zone'));

        if ($direction === 'previous') {
            return $query
                ->where('reading_date', '<=', $reading->reading_date)
                ->orderBy('reading_date', 'desc')
                ->orderBy('id', 'desc') // Secondary sort for same-day readings
                ->first();
        }

        return $query
            ->where('reading_date', '>=', $reading->reading_date)
            ->orderBy('reading_date', 'asc')
            ->orderBy('id', 'asc') // Secondary sort for same-day readings
            ->first();
    }
}
