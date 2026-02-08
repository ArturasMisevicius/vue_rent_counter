<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing universal consumption data for billing calculations.
 * 
 * Supports various consumption patterns including single values, zone-based
 * consumption (time-of-use), and composite readings from multiple meters.
 */
final readonly class UniversalConsumptionData
{
    /**
     * @param float $totalConsumption Total consumption amount
     * @param array<string, float> $zoneConsumption Zone-based consumption (e.g., day/night rates)
     * @param array<string, mixed> $metadata Additional consumption metadata
     */
    public function __construct(
        public float $totalConsumption,
        public array $zoneConsumption = [],
        public array $metadata = [],
    ) {
        $this->validate();
    }

    /**
     * Create from simple consumption value.
     */
    public static function fromTotal(float $totalConsumption): self
    {
        return new self(
            totalConsumption: $totalConsumption,
        );
    }

    /**
     * Create from zone-based consumption data.
     * 
     * @param array<string, float> $zoneConsumption
     */
    public static function fromZones(array $zoneConsumption): self
    {
        $total = array_sum($zoneConsumption);
        
        return new self(
            totalConsumption: $total,
            zoneConsumption: $zoneConsumption,
        );
    }

    /**
     * Create from meter reading data.
     */
    public static function fromMeterReadings(
        float $currentReading,
        float $previousReading,
        ?array $zoneReadings = null
    ): self {
        $totalConsumption = max(0, $currentReading - $previousReading);
        
        $zoneConsumption = [];
        if ($zoneReadings) {
            foreach ($zoneReadings as $zone => $readings) {
                $zoneConsumption[$zone] = max(0, $readings['current'] - $readings['previous']);
            }
        }
        
        return new self(
            totalConsumption: $totalConsumption,
            zoneConsumption: $zoneConsumption,
            metadata: [
                'current_reading' => $currentReading,
                'previous_reading' => $previousReading,
                'zone_readings' => $zoneReadings,
            ],
        );
    }

    /**
     * Get total consumption amount.
     */
    public function getTotalConsumption(): float
    {
        return $this->totalConsumption;
    }

    /**
     * Get zone-based consumption data.
     * 
     * @return array<string, float>
     */
    public function getZoneConsumption(): array
    {
        return $this->zoneConsumption;
    }

    /**
     * Get consumption for a specific zone.
     */
    public function getConsumptionForZone(string $zone): float
    {
        return $this->zoneConsumption[$zone] ?? 0.0;
    }

    /**
     * Check if this consumption data has zone information.
     */
    public function hasZoneData(): bool
    {
        return !empty($this->zoneConsumption);
    }

    /**
     * Get available zones.
     * 
     * @return array<string>
     */
    public function getZones(): array
    {
        return array_keys($this->zoneConsumption);
    }

    /**
     * Get metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if metadata key exists.
     */
    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'total_consumption' => $this->totalConsumption,
            'zone_consumption' => $this->zoneConsumption,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create a new instance with additional zone data.
     * 
     * @param array<string, float> $additionalZones
     */
    public function withZones(array $additionalZones): self
    {
        $newZoneConsumption = array_merge($this->zoneConsumption, $additionalZones);
        $newTotal = array_sum($newZoneConsumption);
        
        return new self(
            totalConsumption: $newTotal,
            zoneConsumption: $newZoneConsumption,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new instance with additional metadata.
     */
    public function withMetadata(array $additionalMetadata): self
    {
        return new self(
            totalConsumption: $this->totalConsumption,
            zoneConsumption: $this->zoneConsumption,
            metadata: array_merge($this->metadata, $additionalMetadata),
        );
    }

    /**
     * Validate consumption data.
     */
    private function validate(): void
    {
        if ($this->totalConsumption < 0) {
            throw new InvalidArgumentException('Total consumption cannot be negative');
        }

        foreach ($this->zoneConsumption as $zone => $consumption) {
            if (!is_string($zone) || empty($zone)) {
                throw new InvalidArgumentException('Zone names must be non-empty strings');
            }
            
            if ($consumption < 0) {
                throw new InvalidArgumentException("Zone consumption for '{$zone}' cannot be negative");
            }
        }

        // Validate that zone consumption doesn't exceed total (with small tolerance for rounding)
        if (!empty($this->zoneConsumption)) {
            $zoneTotal = array_sum($this->zoneConsumption);
            if (abs($zoneTotal - $this->totalConsumption) > 0.001) {
                throw new InvalidArgumentException(
                    "Zone consumption total ({$zoneTotal}) does not match total consumption ({$this->totalConsumption})"
                );
            }
        }
    }
}