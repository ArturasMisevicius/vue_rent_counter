<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Value Object representing a summer calculation period.
 * 
 * Encapsulates the logic for determining summer period dates
 * based on configuration and year.
 */
final readonly class SummerPeriod
{
    public Carbon $startDate;
    public Carbon $endDate;
    public int $year;

    /**
     * Create a new summer period instance.
     *
     * @param int $year The year for the summer period
     * @throws InvalidArgumentException If year is invalid
     */
    public function __construct(int $year)
    {
        $this->validateYear($year);
        
        $this->year = $year;
        
        $startMonth = config('gyvatukas.summer_start_month', 5);
        $endMonth = config('gyvatukas.summer_end_month', 9);
        
        $this->startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
        $this->endDate = Carbon::create($year, $endMonth, 1)->endOfMonth()->endOfDay();
    }

    /**
     * Create a summer period for the previous year.
     */
    public static function forPreviousYear(): self
    {
        return new self(now()->year - 1);
    }

    /**
     * Create a summer period for the current year.
     */
    public static function forCurrentYear(): self
    {
        return new self(now()->year);
    }

    /**
     * Get a human-readable description of the period.
     */
    public function description(): string
    {
        return sprintf(
            '%s to %s',
            $this->startDate->toDateString(),
            $this->endDate->toDateString()
        );
    }

    /**
     * Validate the year is within acceptable range.
     *
     * @throws InvalidArgumentException
     */
    private function validateYear(int $year): void
    {
        $minYear = config('gyvatukas.validation.min_year', 2020);
        $maxYear = now()->year;

        if ($year < $minYear || $year > $maxYear) {
            throw new InvalidArgumentException(
                "Year must be between {$minYear} and {$maxYear}, got {$year}"
            );
        }
    }
}
