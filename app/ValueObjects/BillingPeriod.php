<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * Value object representing a billing period.
 */
class BillingPeriod
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end
    ) {
        if ($this->end->lte($this->start)) {
            throw new \InvalidArgumentException('End date must be after start date');
        }
    }

    /**
     * Create a billing period from date strings.
     */
    public static function fromStrings(string $start, string $end): self
    {
        return new self(
            Carbon::parse($start),
            Carbon::parse($end)
        );
    }

    /**
     * Create a billing period for a specific month.
     */
    public static function forMonth(int $year, int $month): self
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        
        return new self($start, $end);
    }

    /**
     * Get the number of days in this period.
     */
    public function days(): int
    {
        return $this->start->diffInDays($this->end) + 1;
    }

    /**
     * Get the number of days in this period (alias for days()).
     */
    public function getDays(): int
    {
        return $this->days();
    }

    /**
     * Get the start date of this period.
     */
    public function getStartDate(): Carbon
    {
        return $this->start;
    }

    /**
     * Get the end date of this period.
     */
    public function getEndDate(): Carbon
    {
        return $this->end;
    }

    /**
     * Check if a date falls within this period.
     */
    public function contains(Carbon $date): bool
    {
        return $date->between($this->start, $this->end);
    }

    /**
     * Get a human-readable representation.
     */
    public function toString(): string
    {
        return sprintf(
            '%s to %s',
            $this->start->format('Y-m-d'),
            $this->end->format('Y-m-d')
        );
    }
}
