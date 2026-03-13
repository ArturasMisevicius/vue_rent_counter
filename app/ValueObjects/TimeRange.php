<?php

namespace App\ValueObjects;

class TimeRange
{
    public function __construct(
        public readonly int $start,
        public readonly int $end,
        public readonly int $index
    ) {
    }

    /**
     * Create from time string (HH:MM).
     */
    public static function fromTimeString(string $start, string $end, int $index): self
    {
        return new self(
            self::timeToMinutes($start),
            self::timeToMinutes($end),
            $index
        );
    }

    /**
     * Convert time string (HH:MM) to minutes since midnight.
     */
    public static function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int)$hours * 60 + (int)$minutes;
    }

    /**
     * Check if this range overlaps with another.
     */
    public function overlaps(self $other): bool
    {
        return !($this->end <= $other->start || $other->end <= $this->start);
    }

    /**
     * Check if this range crosses midnight.
     */
    public function crossesMidnight(): bool
    {
        return $this->end <= $this->start;
    }

    /**
     * Split overnight range into two ranges.
     */
    public function split(): array
    {
        if (!$this->crossesMidnight()) {
            return [$this];
        }

        return [
            new self($this->start, 1440, $this->index),
            new self(0, $this->end, $this->index),
        ];
    }
}
