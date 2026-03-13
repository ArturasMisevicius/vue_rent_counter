<?php

declare(strict_types=1);

namespace App\Data\System;

use Carbon\Carbon;

final readonly class DateRange
{
    public function __construct(
        public Carbon $startDate,
        public Carbon $endDate,
    ) {
        if ($this->startDate->isAfter($this->endDate)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }
    }

    public static function today(): self
    {
        return new self(
            startDate: now()->startOfDay(),
            endDate: now()->endOfDay(),
        );
    }

    public static function yesterday(): self
    {
        return new self(
            startDate: now()->subDay()->startOfDay(),
            endDate: now()->subDay()->endOfDay(),
        );
    }

    public static function thisWeek(): self
    {
        return new self(
            startDate: now()->startOfWeek(),
            endDate: now()->endOfWeek(),
        );
    }

    public static function thisMonth(): self
    {
        return new self(
            startDate: now()->startOfMonth(),
            endDate: now()->endOfMonth(),
        );
    }

    public static function lastMonth(): self
    {
        return new self(
            startDate: now()->subMonth()->startOfMonth(),
            endDate: now()->subMonth()->endOfMonth(),
        );
    }

    public static function last30Days(): self
    {
        return new self(
            startDate: now()->subDays(30)->startOfDay(),
            endDate: now()->endOfDay(),
        );
    }

    public static function custom(string $start, string $end): self
    {
        return new self(
            startDate: Carbon::parse($start)->startOfDay(),
            endDate: Carbon::parse($end)->endOfDay(),
        );
    }

    public function getDays(): int
    {
        return $this->startDate->diffInDays($this->endDate) + 1;
    }

    public function getHours(): int
    {
        return $this->startDate->diffInHours($this->endDate);
    }

    public function contains(Carbon $date): bool
    {
        return $date->between($this->startDate, $this->endDate);
    }
}