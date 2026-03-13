<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Property;
use App\ValueObjects\BillingPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for calculating heating charges and distribution costs.
 * 
 * This service handles Lithuanian heating system calculations including:
 * - Seasonal heating calculations (winter/summer periods)
 * - Building-specific heating factors
 * - Area-based cost distribution
 * - Circulation energy calculations
 */
final readonly class HeatingCalculatorService
{
    public function __construct(
        private SharedServiceCostDistributorService $costDistributor,
    ) {}

    /**
     * Calculate heating charges for a property in a given billing period.
     */
    public function calculateHeatingCharges(
        Property $property,
        BillingPeriod $billingPeriod,
        array $options = []
    ): array {
        $cacheKey = "heating_charges_{$property->id}_{$billingPeriod->getStartDate()->format('Y-m-d')}_{$billingPeriod->getEndDate()->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($property, $billingPeriod, $options) {
            try {
                Log::info('Calculating heating charges', [
                    'property_id' => $property->id,
                    'period' => $billingPeriod->getLabel(),
                ]);

                // Get seasonal factor (winter heating is more expensive)
                $seasonalFactor = $this->getSeasonalFactor($billingPeriod);
                
                // Get building-specific heating factor
                $buildingFactor = $this->getBuildingHeatingFactor($property);
                
                // Calculate base heating charge
                $baseCharge = $this->calculateBaseHeatingCharge($property, $billingPeriod);
                
                // Calculate consumption-based charge if meter readings available
                $consumptionCharge = $this->calculateConsumptionCharge($property, $billingPeriod);
                
                // Calculate shared heating costs (circulation, maintenance)
                $sharedCosts = $this->calculateSharedHeatingCosts($property, $billingPeriod);
                
                // Apply factors
                $totalCharge = ($baseCharge + $consumptionCharge) * $seasonalFactor * $buildingFactor + $sharedCosts;
                
                return [
                    'base_charge' => $baseCharge,
                    'consumption_charge' => $consumptionCharge,
                    'shared_costs' => $sharedCosts,
                    'seasonal_factor' => $seasonalFactor,
                    'building_factor' => $buildingFactor,
                    'total_charge' => $totalCharge,
                    'calculation_date' => now(),
                ];
                
            } catch (\Exception $e) {
                Log::error('Heating calculation failed', [
                    'property_id' => $property->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                throw $e;
            }
        });
    }

    /**
     * Get seasonal factor for heating calculations.
     * Winter months have higher heating costs.
     */
    private function getSeasonalFactor(BillingPeriod $billingPeriod): float
    {
        $startMonth = (int) $billingPeriod->getStartDate()->format('n');
        $endMonth = (int) $billingPeriod->getEndDate()->format('n');
        
        // Winter months (October - April) have higher heating factor
        $winterMonths = [10, 11, 12, 1, 2, 3, 4];
        
        $isWinterPeriod = in_array($startMonth, $winterMonths) || in_array($endMonth, $winterMonths);
        
        return $isWinterPeriod ? 1.5 : 0.3; // Reduced heating in summer
    }

    /**
     * Get building-specific heating factor based on building characteristics.
     */
    private function getBuildingHeatingFactor(Property $property): float
    {
        $building = $property->building;
        
        if (!$building) {
            return 1.0; // Default factor
        }
        
        // Factors based on building age, insulation, etc.
        $baseFactor = 1.0;
        
        // Older buildings typically need more heating
        if ($building->built_year && $building->built_year < 1990) {
            $baseFactor += 0.2;
        }
        
        // Larger buildings may have better efficiency
        if ($building->total_area && $building->total_area > 1000) {
            $baseFactor -= 0.1;
        }
        
        return max(0.5, min(2.0, $baseFactor)); // Keep factor between 0.5 and 2.0
    }

    /**
     * Calculate base heating charge (fixed monthly charge).
     */
    private function calculateBaseHeatingCharge(Property $property, BillingPeriod $billingPeriod): float
    {
        // Base charge per square meter per month
        $baseRatePerSqm = 2.50; // EUR per m² per month
        
        $area = $property->total_area ?? 50; // Default 50 m² if not set
        $daysInPeriod = $billingPeriod->getDaysInPeriod();
        $daysInMonth = 30; // Average days per month
        
        return $baseRatePerSqm * $area * ($daysInPeriod / $daysInMonth);
    }

    /**
     * Calculate consumption-based heating charge from meter readings.
     */
    private function calculateConsumptionCharge(Property $property, BillingPeriod $billingPeriod): float
    {
        // Get heating meters for this property
        $heatingMeters = $property->meters()
            ->where('type', 'heating')
            ->get();
        
        if ($heatingMeters->isEmpty()) {
            return 0.0; // No consumption charge if no meters
        }
        
        $totalConsumption = 0;
        $ratePerUnit = 0.08; // EUR per kWh or unit
        
        foreach ($heatingMeters as $meter) {
            $readings = $meter->readings()
                ->whereBetween('reading_date', [
                    $billingPeriod->getStartDate(),
                    $billingPeriod->getEndDate()
                ])
                ->orderBy('reading_date')
                ->get();
            
            if ($readings->count() >= 2) {
                $firstReading = $readings->first();
                $lastReading = $readings->last();
                $consumption = $lastReading->value - $firstReading->value;
                $totalConsumption += max(0, $consumption); // Ensure non-negative
            }
        }
        
        return $totalConsumption * $ratePerUnit;
    }

    /**
     * Calculate shared heating costs (circulation, maintenance, etc.).
     */
    private function calculateSharedHeatingCosts(Property $property, BillingPeriod $billingPeriod): float
    {
        $building = $property->building;
        
        if (!$building) {
            return 0.0;
        }
        
        // Get all properties in the building for cost distribution
        $buildingProperties = $building->properties()->get();
        
        if ($buildingProperties->isEmpty()) {
            return 0.0;
        }
        
        // Calculate total shared costs for the building
        $totalSharedCosts = $this->calculateBuildingSharedCosts($building, $billingPeriod);
        
        // Create a service configuration for heating shared costs
        $serviceConfig = new \App\Models\ServiceConfiguration([
            'distribution_method' => \App\Enums\DistributionMethod::AREA,
            'rate_schedule' => [],
        ]);
        
        // Distribute costs using the cost distributor service
        $distributionResult = $this->costDistributor->distributeCost(
            $serviceConfig,
            $buildingProperties,
            $totalSharedCosts,
            $billingPeriod
        );
        
        // Find this property's share
        $distributions = $distributionResult->getDistributedAmounts();
        if (isset($distributions[$property->id])) {
            return $distributions[$property->id];
        }
        
        return 0.0;
    }

    /**
     * Calculate total shared heating costs for a building.
     */
    private function calculateBuildingSharedCosts(object $building, BillingPeriod $billingPeriod): float
    {
        // Base shared costs (circulation pumps, maintenance, etc.)
        $baseSharedCosts = 500.0; // EUR per month for the building
        
        // Adjust for building size
        if ($building->total_area) {
            $baseSharedCosts *= ($building->total_area / 1000); // Scale by building size
        }
        
        // Adjust for period length
        $daysInPeriod = $billingPeriod->getDaysInPeriod();
        $daysInMonth = 30;
        
        return $baseSharedCosts * ($daysInPeriod / $daysInMonth);
    }

    /**
     * Distribute heating circulation costs among properties.
     */
    public function distributeCirculationCost(
        float $totalCost,
        \Illuminate\Support\Collection $properties,
        \App\Enums\DistributionMethod $method = \App\Enums\DistributionMethod::AREA
    ): array {
        // Create a service configuration for circulation cost distribution
        $serviceConfig = new \App\Models\ServiceConfiguration([
            'distribution_method' => $method,
            'rate_schedule' => [],
        ]);
        
        // Create a billing period for current month
        $billingPeriod = BillingPeriod::currentMonth();
        
        $result = $this->costDistributor->distributeCost(
            $serviceConfig,
            $properties,
            $totalCost,
            $billingPeriod
        );
        
        return $result->toArray();
    }

    /**
     * Get heating efficiency factor for a property.
     */
    public function getHeatingEfficiencyFactor(Property $property): float
    {
        // Base efficiency
        $efficiency = 1.0;
        
        // Adjust based on property characteristics
        if ($property->total_area) {
            // Larger properties may be more efficient
            if ($property->total_area > 100) {
                $efficiency += 0.1;
            }
        }
        
        // Building-specific factors
        if ($property->building) {
            $building = $property->building;
            
            // Newer buildings are more efficient
            if ($building->built_year && $building->built_year > 2000) {
                $efficiency += 0.2;
            }
        }
        
        return max(0.5, min(2.0, $efficiency));
    }

    /**
     * Calculate heating cost for a property (alias for calculateHeatingCharges).
     * 
     * This method provides backward compatibility with existing code.
     */
    public function calculateHeatingCost(
        Property $property,
        object $reading,
        \Carbon\Carbon $startDate
    ): array {
        // Create a billing period from the start date to end of month
        $billingPeriod = BillingPeriod::forMonth($startDate->year, $startDate->month);
        
        // Calculate heating charges
        $result = $this->calculateHeatingCharges($property, $billingPeriod);
        
        return [
            'amount' => $result['total_charge'],
            'details' => $result,
        ];
    }

    /**
     * Get seasonal multiplier for a specific date.
     * 
     * This method provides backward compatibility with existing code.
     */
    public function getSeasonalMultiplier(\Carbon\Carbon $date): float
    {
        // Create a single-day billing period for the date
        $billingPeriod = BillingPeriod::fromRange($date, $date);
        
        return $this->getSeasonalFactor($billingPeriod);
    }

    /**
     * Clear heating calculation cache for a property.
     */
    public function clearCalculationCache(Property $property): void
    {
        $pattern = "heating_charges_{$property->id}_*";
        
        // In a real implementation, you'd use a more sophisticated cache clearing mechanism
        // For now, we'll just log the cache clear request
        Log::info('Clearing heating calculation cache', [
            'property_id' => $property->id,
            'pattern' => $pattern,
        ]);
    }
}