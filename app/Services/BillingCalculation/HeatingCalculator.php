<?php

namespace App\Services\BillingCalculation;

use App\Models\Meter;
use App\Models\Tariff;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;

/**
 * Calculator for heating billing with gyvatukas (circulation fee).
 */
class HeatingCalculator implements BillingCalculator
{
    public function __construct(
        private GyvatukasCalculator $gyvatukasCalculator
    ) {}

    public function calculate(
        Meter $meter,
        float $consumption,
        Tariff $tariff,
        Carbon $periodStart,
        $property
    ): array {
        $config = $tariff->configuration;
        $heatingRate = $config['rate'] ?? 0;

        // Calculate base heating cost
        $heatingCost = $consumption * $heatingRate;

        // Add gyvatukas if property is in a building
        $gyvatukasCost = 0;
        if ($property->building_id) {
            $building = $property->building;
            $circulationEnergy = $this->gyvatukasCalculator->calculate($building, $periodStart);
            
            // Distribute circulation cost among properties
            $distribution = $this->gyvatukasCalculator->distributeCirculationCost(
                $building,
                $circulationEnergy * $heatingRate,
                'equal' // or 'area' based on configuration
            );
            
            $gyvatukasCost = $distribution[$property->id] ?? 0;
        }

        $total = $heatingCost + $gyvatukasCost;
        $unitPrice = $heatingRate;

        return [
            'unit_price' => $unitPrice,
            'total' => $total,
        ];
    }
}
