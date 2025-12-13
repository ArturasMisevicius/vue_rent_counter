<?php

declare(strict_types=1);

namespace App\Services\BillingCalculation;

use App\Enums\DistributionMethod;
use App\Models\Building;
use InvalidArgumentException;

/**
 * Distributes circulation costs among properties in a building.
 */
final readonly class CirculationCostDistributor
{
    public function distribute(
        Building $building,
        float $totalCost,
        DistributionMethod $method = DistributionMethod::EQUAL,
    ): array {
        $this->validateInputs($totalCost);

        $properties = $building->properties;
        
        if ($properties->isEmpty()) {
            return [];
        }

        return match ($method) {
            DistributionMethod::AREA => $this->distributeByArea($properties, $totalCost),
            DistributionMethod::EQUAL => $this->distributeEqually($properties, $totalCost),
        };
    }

    private function distributeByArea($properties, float $totalCost): array
    {
        $totalArea = $properties->sum('area_sqm');
        
        if ($totalArea <= 0) {
            // Fallback to equal distribution if no area data
            return $this->distributeEqually($properties, $totalCost);
        }

        $distribution = [];
        foreach ($properties as $property) {
            $proportion = $property->area_sqm / $totalArea;
            $distribution[$property->id] = round($totalCost * $proportion, 2);
        }

        return $distribution;
    }

    private function distributeEqually($properties, float $totalCost): array
    {
        $costPerProperty = round($totalCost / $properties->count(), 2);
        
        $distribution = [];
        foreach ($properties as $property) {
            $distribution[$property->id] = $costPerProperty;
        }

        return $distribution;
    }

    private function validateInputs(float $totalCost): void
    {
        if ($totalCost < 0) {
            throw new InvalidArgumentException('Total cost cannot be negative');
        }
    }
}