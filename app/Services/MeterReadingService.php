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

    /**
     * Calculate average consumption for a meter based on historical readings.
     * 
     * Used for anomaly detection to identify unusually high or low consumption.
     * Calculates the average consumption from the last N readings.
     *
     * @param Meter $meter The meter to calculate average consumption for
     * @param string|null $zone Optional zone filter (day/night for electricity)
     * @param int $readingsCount Number of readings to use for calculation (default: 6)
     * @return float|null Average consumption or null if insufficient data
     */
    public function getAverageConsumption(Meter $meter, ?string $zone = null, int $readingsCount = 6): ?float
    {
        $query = $meter->readings()
            ->select(['value', 'reading_date'])
            ->orderBy('reading_date', 'desc')
            ->limit($readingsCount + 1);

        if ($zone !== null) {
            $query->where('zone', $zone);
        } else {
            $query->where(function ($q) {
                $q->whereNull('zone')->orWhere('zone', '');
            });
        }

        $readings = $query->get();

        if ($readings->count() < 2) {
            return null;
        }

        $consumptions = [];
        $previousValue = null;

        foreach ($readings->sortBy('reading_date') as $reading) {
            if ($previousValue !== null) {
                $consumption = $reading->value - $previousValue;
                // Only include positive consumption values
                if ($consumption >= 0) {
                    $consumptions[] = $consumption;
                }
            }
            $previousValue = $reading->value;
        }

        if (empty($consumptions)) {
            return null;
        }

        return array_sum($consumptions) / count($consumptions);
    }

    /**
     * Check if a consumption value is anomalous compared to historical average.
     * 
     * @param float $consumption Current consumption value
     * @param float $averageConsumption Historical average consumption
     * @param float $highThreshold Multiplier for high consumption warning (default: 2.5 = 250%)
     * @param float $lowThreshold Multiplier for low consumption warning (default: 0.1 = 10%)
     * @return array{is_anomaly: bool, type: string|null, message: string|null}
     */
    public function checkConsumptionAnomaly(
        float $consumption,
        float $averageConsumption,
        float $highThreshold = 2.5,
        float $lowThreshold = 0.1
    ): array {
        if ($averageConsumption <= 0) {
            return ['is_anomaly' => false, 'type' => null, 'message' => null];
        }

        if ($consumption > ($averageConsumption * $highThreshold)) {
            return [
                'is_anomaly' => true,
                'type' => 'high',
                'message' => sprintf(
                    'Consumption %.2f is %.1f%% higher than average %.2f',
                    $consumption,
                    (($consumption / $averageConsumption) - 1) * 100,
                    $averageConsumption
                ),
            ];
        }

        if ($consumption > 0 && $consumption < ($averageConsumption * $lowThreshold)) {
            return [
                'is_anomaly' => true,
                'type' => 'low',
                'message' => sprintf(
                    'Consumption %.2f is %.1f%% lower than average %.2f',
                    $consumption,
                    (1 - ($consumption / $averageConsumption)) * 100,
                    $averageConsumption
                ),
            ];
        }

        return ['is_anomaly' => false, 'type' => null, 'message' => null];
    }
}
