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
class MeterReadingService extends BaseService
{
    /**
     * Create a new meter reading.
     * 
     * @param Meter $meter
     * @param string $readingDate
     * @param float $value
     * @param string|null $zone
     * @param int $enteredByUserId
     * @param int|null $tenantId Organization tenant_id (optional)
     * @return MeterReading
     */
    public function createReading(
        Meter $meter,
        string $readingDate,
        float $value,
        ?string $zone,
        int $enteredByUserId,
        ?int $tenantId = null
    ): MeterReading {
        $tenantId ??= $meter->tenant_id ?? auth()->user()?->tenant_id;

        if (!$tenantId) {
            throw new \InvalidArgumentException('Missing tenant_id for meter reading');
        }

        return $meter->readings()->create([
            'tenant_id' => $tenantId,
            'reading_date' => $readingDate,
            'value' => $value,
            'zone' => $zone,
            'entered_by' => $enteredByUserId,
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
        $direction = $direction === 'previous' ? 'previous' : 'next';

        $query = $reading->meter
            ->readings()
            ->select(['id', 'meter_id', 'value', 'reading_date', 'zone'])
            ->whereKeyNot($reading->id)
            ->when($zone, fn ($q) => $q->where('zone', $zone), fn ($q) => $q->whereNull('zone'));

        if ($direction === 'previous') {
            return $query
                ->where(function ($q) use ($reading) {
                    $q->where('reading_date', '<', $reading->reading_date)
                        ->orWhere(function ($sameDate) use ($reading) {
                            $sameDate
                                ->where('reading_date', '=', $reading->reading_date)
                                ->where('id', '<', $reading->id);
                        });
                })
                ->orderBy('reading_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
        }

        return $query
            ->where(function ($q) use ($reading) {
                $q->where('reading_date', '>', $reading->reading_date)
                    ->orWhere(function ($sameDate) use ($reading) {
                        $sameDate
                            ->where('reading_date', '=', $reading->reading_date)
                            ->where('id', '>', $reading->id);
                    });
            })
            ->orderBy('reading_date', 'asc')
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Validate input data for meter reading creation.
     * 
     * @param array $data
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateInput(array $data): bool
    {
        $required = ['meter_id', 'reading_date', 'value', 'tenant_id'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!is_numeric($data['value']) || $data['value'] < 0) {
            throw new \InvalidArgumentException("Reading value must be a positive number");
        }

        return true;
    }

    /**
     * Check if the meter reading service is available.
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        // Check if meter reading functionality is enabled
        return config('app.features.meter_readings', true);
    }
}
