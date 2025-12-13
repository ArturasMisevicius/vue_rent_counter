<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing building consumption data for calculations.
 */
final readonly class ConsumptionData
{
    public function __construct(
        public int $totalApartments,
        public float $baseCirculationRate,
        public float $buildingEfficiencyFactor = 1.0,
        public ?float $summerAverage = null,
    ) {
        $this->validate();
    }

    public static function fromBuilding(object $building, float $baseRate): self
    {
        return new self(
            totalApartments: $building->total_apartments,
            baseCirculationRate: $baseRate,
            buildingEfficiencyFactor: self::calculateEfficiencyFactor($building->total_apartments),
            summerAverage: $building->gyvatukas_summer_average,
        );
    }

    public function calculateBaseEnergy(): float
    {
        return $this->totalApartments * $this->baseCirculationRate;
    }

    public function calculateAdjustedEnergy(): float
    {
        return $this->calculateBaseEnergy() * $this->buildingEfficiencyFactor;
    }

    private function validate(): void
    {
        if ($this->totalApartments <= 0) {
            throw new InvalidArgumentException('Total apartments must be greater than 0');
        }

        if ($this->baseCirculationRate < 0) {
            throw new InvalidArgumentException('Base circulation rate cannot be negative');
        }

        if ($this->buildingEfficiencyFactor <= 0) {
            throw new InvalidArgumentException('Building efficiency factor must be greater than 0');
        }
    }

    private static function calculateEfficiencyFactor(int $apartments): float
    {
        return match (true) {
            $apartments >= 50 => 0.95, // Large building efficiency
            $apartments < 10 => 1.1,   // Small building penalty
            default => 1.0,           // Medium building baseline
        };
    }
}