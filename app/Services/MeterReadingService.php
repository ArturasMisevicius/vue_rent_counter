<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Meter;
use App\Models\MeterReading;

/**
 * Meter Reading Service
 * 
 * Handles business logic for meter reading operations:
 * - Creating new meter readings
 * - Retrieving previous/next readings
 * - Validating reading sequences
 * 
 * Performance: Optimized queries with proper indexing and eager loading
 */
class MeterReadingService
{
    /**
     * Create a new meter reading.
     * 
     * @param Meter $meter
     * @param int $tenantId
     * @param string $readingDate
     * @param float $value
     * @param string|null $zone
     * @param int $enteredByUserId
     * @return MeterReading
     */
    public function createReading(
        Meter $meter,
        int $tenantId,
        string $readingDate,
        float $value,
        ?string $zone,
        int $enteredByUserId
    ): MeterReading {
        return $meter->readings()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $meter->id,
            'reading_date' => $readingDate,
            'value' => $value,
            'zone' => $zone,
            'entered_by_user_id' => $enteredByUserId,
        ]);
    }

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
