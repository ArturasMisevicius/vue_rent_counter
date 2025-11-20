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
     * @param MeterReading $reading
     * @param string|null $zone
     * @param string $direction 'previous' or 'next'
     * @return MeterReading|null
     */
    public function getAdjacentReading(MeterReading $reading, ?string $zone, string $direction): ?MeterReading
    {
        $query = $reading->meter
            ->readings()
            ->where('id', '!=', $reading->id)
            ->when($zone, fn($q) => $q->where('zone', $zone), fn($q) => $q->whereNull('zone'));

        if ($direction === 'previous') {
            return $query
                ->where('reading_date', '<=', $reading->reading_date)
                ->orderBy('reading_date', 'desc')
                ->first();
        }

        return $query
            ->where('reading_date', '>=', $reading->reading_date)
            ->orderBy('reading_date', 'asc')
            ->first();
    }
}
