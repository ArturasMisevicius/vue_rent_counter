<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Represents a billing period with start and end dates.
 * 
 * Immutable value object that ensures billing periods are valid
 * and provides utility methods for period calculations. Used throughout
 * the utility billing system for consistent date range handling.
 * 
 * @package App\ValueObjects
 * @see \App\Services\SharedServiceCostDistributorService
 * @see \Tests\Property\SharedServiceCostDistributionPropertyTest
 * 
 * @example
 * ```php
 * // Create monthly billing period
 * $period = BillingPeriod::forMonth(2024, 3);
 * 
 * // Create custom range
 * $period = BillingPeriod::fromRange(
 *     Carbon::parse('2024-03-01'),
 *     Carbon::parse('2024-03-31')
 * );
 * 
 * echo $period->getDays(); // 31
 * echo $period->getLabel(); // "March 2024"
 * ```
 */
final readonly class BillingPeriod
{
    public function __construct(
        public Carbon $startDate,
        public Carbon $endDate,
    ) {
        if ($this->startDate->isAfter($this->endDate)) {
            throw new InvalidArgumentException('Start date must be before or equal to end date');
        }
    }

    /**
     * Create a billing period for a specific month.
     */
    public static function forMonth(int $year, int $month): self
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        return new self($startDate, $endDate);
    }

    /**
     * Create a billing period for the current month.
     */
    public static function currentMonth(): self
    {
        return self::forMonth(now()->year, now()->month);
    }

    /**
     * Create a billing period for the previous month.
     */
    public static function previousMonth(): self
    {
        $previous = now()->subMonth();
        return self::forMonth($previous->year, $previous->month);
    }

    /**
     * Create a billing period from a date range.
     */
    public static function fromRange(Carbon $startDate, Carbon $endDate): self
    {
        return new self($startDate->copy(), $endDate->copy());
    }

    /**
     * Get the start date of this billing period.
     */
    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    /**
     * Get the end date of this billing period.
     */
    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    /**
     * Get the number of days in this billing period.
     */
    public function getDays(): int
    {
        return (int) ($this->startDate->diffInDays($this->endDate) + 1);
    }

    /**
     * Get the number of days in this billing period (alias for getDays).
     */
    public function getDaysInPeriod(): int
    {
        return $this->getDays();
    }

    /**
     * Check if a date falls within this billing period.
     */
    public function contains(Carbon $date): bool
    {
        return $date->between($this->startDate, $this->endDate);
    }

    /**
     * Check if this period overlaps with another period.
     */
    public function overlaps(BillingPeriod $other): bool
    {
        return $this->startDate->lte($other->endDate) && $this->endDate->gte($other->startDate);
    }

    /**
     * Get a human-readable representation of the period.
     */
    public function getLabel(): string
    {
        if ($this->startDate->isSameMonth($this->endDate)) {
            return $this->startDate->format('F Y');
        }
        
        return $this->startDate->format('M j, Y') . ' - ' . $this->endDate->format('M j, Y');
    }

    /**
     * Get the period as an array.
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->toISOString(),
            'end_date' => $this->endDate->toISOString(),
            'days' => $this->getDays(),
            'label' => $this->getLabel(),
        ];
    }

    /**
     * Check if this period equals another period.
     */
    public function equals(BillingPeriod $other): bool
    {
        return $this->startDate->equalTo($other->startDate) 
            && $this->endDate->equalTo($other->endDate);
    }

    /**
     * Get the next billing period (same duration).
     */
    public function next(): self
    {
        $duration = $this->getDays();
        $nextStart = $this->endDate->copy()->addDay();
        $nextEnd = $nextStart->copy()->addDays($duration - 1);
        
        return new self($nextStart, $nextEnd);
    }

    /**
     * Get the previous billing period (same duration).
     */
    public function previous(): self
    {
        $duration = $this->getDays();
        $prevEnd = $this->startDate->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($duration - 1);
        
        return new self($prevStart, $prevEnd);
    }

    /**
     * Get the duration in days (alias for getDays).
     */
    public function getDurationInDays(): int
    {
        return $this->getDays();
    }

    /**
     * Get the duration in months (approximate).
     */
    public function getDurationInMonths(): float
    {
        return $this->getDays() / 30.0;
    }
}