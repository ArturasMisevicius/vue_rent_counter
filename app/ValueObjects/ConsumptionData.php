<?php

namespace App\ValueObjects;

use App\Models\MeterReading;

/**
 * Value object representing meter consumption data.
 */
class ConsumptionData
{
    public function __construct(
        public readonly MeterReading $startReading,
        public readonly MeterReading $endReading,
        public readonly ?string $zone = null
    ) {
        if ($this->endReading->value < $this->startReading->value) {
            throw new \InvalidArgumentException('End reading cannot be less than start reading');
        }
    }

    /**
     * Calculate the consumption amount.
     */
    public function amount(): float
    {
        return $this->endReading->value - $this->startReading->value;
    }

    /**
     * Check if there is any consumption.
     */
    public function hasConsumption(): bool
    {
        return $this->amount() > 0;
    }

    /**
     * Get consumption data as an array for snapshotting.
     */
    public function toSnapshot(): array
    {
        return [
            'start_reading_id' => $this->startReading->id,
            'start_value' => (float) $this->startReading->value,
            'start_date' => $this->startReading->reading_date->toDateString(),
            'end_reading_id' => $this->endReading->id,
            'end_value' => (float) $this->endReading->value,
            'end_date' => $this->endReading->reading_date->toDateString(),
            'zone' => $this->zone,
            'consumption' => $this->amount(),
        ];
    }
}
