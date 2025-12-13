<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Building;
use Carbon\Carbon;

/**
 * Data Transfer Object for Gyvatukas calculations.
 * 
 * Encapsulates all data needed for gyvatukas calculations with validation.
 */
final readonly class GyvatukasCalculationDTO
{
    public function __construct(
        public Building $building,
        public Carbon $month,
        public float $baseRate,
        public string $calculationType,
        public ?float $summerAverage = null,
        public ?float $winterAdjustment = null,
    ) {
        $this->validate();
    }

    /**
     * Create DTO for summer calculation.
     */
    public static function forSummer(Building $building, Carbon $month, float $baseRate): self
    {
        return new self(
            building: $building,
            month: $month,
            baseRate: $baseRate,
            calculationType: 'summer'
        );
    }

    /**
     * Create DTO for winter calculation.
     */
    public static function forWinter(
        Building $building,
        Carbon $month,
        float $baseRate,
        float $summerAverage,
        float $winterAdjustment
    ): self {
        return new self(
            building: $building,
            month: $month,
            baseRate: $baseRate,
            calculationType: 'winter',
            summerAverage: $summerAverage,
            winterAdjustment: $winterAdjustment
        );
    }

    /**
     * Get the cache key for this calculation.
     */
    public function getCacheKey(): string
    {
        return sprintf(
            'gyvatukas:%s:%d:%s',
            $this->calculationType,
            $this->building->id,
            $this->month->format('Y-m')
        );
    }

    /**
     * Get calculation context for logging.
     */
    public function getLogContext(): array
    {
        return [
            'building_id' => $this->building->id,
            'month' => $this->month->format('Y-m'),
            'calculation_type' => $this->calculationType,
            'base_rate' => $this->baseRate,
            'summer_average' => $this->summerAverage,
            'winter_adjustment' => $this->winterAdjustment,
        ];
    }

    /**
     * Validate the DTO data.
     * 
     * @throws \InvalidArgumentException When data is invalid
     */
    private function validate(): void
    {
        if ($this->building->total_apartments <= 0) {
            throw new \InvalidArgumentException(
                "Building {$this->building->id} has invalid apartment count: {$this->building->total_apartments}"
            );
        }

        if ($this->baseRate < 0) {
            throw new \InvalidArgumentException(
                "Base rate cannot be negative: {$this->baseRate}"
            );
        }

        if (!in_array($this->calculationType, ['summer', 'winter'], true)) {
            throw new \InvalidArgumentException(
                "Invalid calculation type: {$this->calculationType}"
            );
        }

        if ($this->calculationType === 'winter') {
            if ($this->summerAverage === null || $this->summerAverage < 0) {
                throw new \InvalidArgumentException(
                    "Winter calculation requires valid summer average: {$this->summerAverage}"
                );
            }

            if ($this->winterAdjustment === null || $this->winterAdjustment <= 0) {
                throw new \InvalidArgumentException(
                    "Winter calculation requires valid adjustment factor: {$this->winterAdjustment}"
                );
            }
        }
    }
}