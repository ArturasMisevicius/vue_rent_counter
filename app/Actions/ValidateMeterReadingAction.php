<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InvalidMeterReadingException;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\MeterReadingService;
use Carbon\Carbon;

/**
 * Validate Meter Reading Action
 * 
 * Single responsibility: Validate a meter reading against business rules.
 * Ensures monotonicity and temporal consistency.
 * 
 * @package App\Actions
 */
final class ValidateMeterReadingAction
{
    public function __construct(
        private readonly MeterReadingService $meterReadingService
    ) {}

    /**
     * Execute validation for a meter reading.
     *
     * @param Meter $meter The meter
     * @param float $value The reading value
     * @param Carbon $readingDate The reading date
     * @param string|null $zone The zone (for multi-zone meters)
     * @throws InvalidMeterReadingException If validation fails
     */
    public function execute(Meter $meter, float $value, Carbon $readingDate, ?string $zone = null): void
    {
        // Validate value is positive
        if ($value < 0) {
            throw new InvalidMeterReadingException(
                "Meter reading value must be positive. Got: {$value}"
            );
        }

        // Validate monotonicity (reading must be >= previous reading)
        $previousReading = $this->meterReadingService->getPreviousReading(
            $meter,
            $zone,
            $readingDate->toDateString()
        );

        if ($previousReading && $value < $previousReading->value) {
            throw new InvalidMeterReadingException(
                "Meter reading must be greater than or equal to previous reading. " .
                "Previous: {$previousReading->value}, Current: {$value}"
            );
        }

        // Validate temporal consistency (reading must be <= next reading)
        $nextReading = $this->meterReadingService->getNextReading(
            $meter,
            $zone,
            $readingDate->toDateString()
        );

        if ($nextReading && $value > $nextReading->value) {
            throw new InvalidMeterReadingException(
                "Meter reading must be less than or equal to next reading. " .
                "Current: {$value}, Next: {$nextReading->value}"
            );
        }

        // Validate reasonable consumption rate (optional business rule)
        if ($previousReading) {
            $daysDiff = $readingDate->diffInDays($previousReading->reading_date);
            if ($daysDiff > 0) {
                $consumption = $value - $previousReading->value;
                $dailyRate = $consumption / $daysDiff;

                // Example: Flag if daily consumption exceeds 1000 units
                $maxDailyRate = config('billing.max_daily_consumption_rate', 1000);
                if ($dailyRate > $maxDailyRate) {
                    throw new InvalidMeterReadingException(
                        "Daily consumption rate ({$dailyRate}) exceeds maximum allowed ({$maxDailyRate}). " .
                        "Please verify the reading."
                    );
                }
            }
        }
    }
}
