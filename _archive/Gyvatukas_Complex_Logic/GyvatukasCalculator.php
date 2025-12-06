<?php

namespace App\Services;

use App\Enums\MeterType;
use App\Models\Building;
use App\Models\MeterReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * GyvatukasCalculator Service
 * 
 * Implements seasonal circulation fee (gyvatukas) calculations for Lithuanian
 * hot water circulation systems. The calculation differs between heating season
 * (October-April) and non-heating season (May-September).
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.5
 */
class GyvatukasCalculator
{
    /**
     * Specific heat capacity of water (kWh/m³·°C)
     */
    private float $waterSpecificHeat;

    /**
     * Temperature difference for hot water heating (°C)
     */
    private float $temperatureDelta;

    /**
     * Heating season start month (October = 10)
     */
    private int $heatingSeasonStartMonth;

    /**
     * Heating season end month (April = 4)
     */
    private int $heatingSeasonEndMonth;

    /**
     * Decimal precision for monetary calculations
     */
    private const DECIMAL_PRECISION = 2;

    /**
     * Cache for calculation results to avoid redundant queries
     * Format: [building_id => [month => result]]
     */
    private array $calculationCache = [];

    /**
     * Cache for meter consumption results
     * Format: [cache_key => consumption_value]
     */
    private array $consumptionCache = [];

    public function __construct()
    {
        $this->waterSpecificHeat = config('gyvatukas.water_specific_heat', 1.163);
        $this->temperatureDelta = config('gyvatukas.temperature_delta', 45.0);
        $this->heatingSeasonStartMonth = config('gyvatukas.heating_season_start_month', 10);
        $this->heatingSeasonEndMonth = config('gyvatukas.heating_season_end_month', 4);
    }

    /**
     * Calculate gyvatukas (circulation fee) for a building in a given billing month.
     * 
     * Routes to summer or winter calculation based on the season.
     *
     * @param Building $building The building to calculate for
     * @param Carbon $billingMonth The billing period month
     * @return float Circulation energy in kWh
     */
    public function calculate(Building $building, Carbon $billingMonth): float
    {
        if ($this->isHeatingSeason($billingMonth)) {
            return $this->calculateWinterGyvatukas($building);
        }

        return $this->calculateSummerGyvatukas($building, $billingMonth);
    }

    /**
     * Determine if a given date falls within the heating season.
     * 
     * Heating season is October through April (months 10, 11, 12, 1, 2, 3, 4).
     * 
     * Requirement: 4.1, 4.2
     *
     * @param Carbon $date The date to check
     * @return bool True if in heating season, false otherwise
     */
    public function isHeatingSeason(Carbon $date): bool
    {
        $month = $date->month;

        // Heating season: October (10) through April (4)
        // This means months >= 10 OR months <= 4
        return $month >= $this->heatingSeasonStartMonth || $month <= $this->heatingSeasonEndMonth;
    }

    /**
     * Calculate summer gyvatukas using the formula:
     * Q_circ = Q_total - (V_water × c × ΔT)
     * 
     * Where:
     * - Q_circ = Circulation energy (kWh)
     * - Q_total = Total building heating energy consumption (kWh)
     * - V_water = Hot water volume consumption (m³)
     * - c = Specific heat capacity of water (kWh/m³·°C)
     * - ΔT = Temperature difference (°C)
     * 
     * Requirement: 4.1, 4.3
     *
     * @param Building $building The building to calculate for
     * @param Carbon $month The billing month
     * @return float Circulation energy in kWh
     */
    public function calculateSummerGyvatukas(Building $building, Carbon $month): float
    {
        // Check cache first
        $cacheKey = $building->id . '_' . $month->format('Y-m');
        if (isset($this->calculationCache[$cacheKey])) {
            return $this->calculationCache[$cacheKey];
        }

        // Get the start and end of the billing month
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        // Fetch total heating energy for the building (Q_total)
        $totalHeatingEnergy = $this->getBuildingHeatingEnergy($building, $periodStart, $periodEnd);

        // Fetch hot water consumption for the building (V_water)
        $hotWaterVolume = $this->getBuildingHotWaterVolume($building, $periodStart, $periodEnd);

        // Calculate energy used for heating water: V_water × c × ΔT
        $waterHeatingEnergy = $hotWaterVolume * $this->waterSpecificHeat * $this->temperatureDelta;

        // Calculate circulation energy: Q_circ = Q_total - (V_water × c × ΔT)
        $circulationEnergy = $totalHeatingEnergy - $waterHeatingEnergy;

        // Ensure we don't return negative values (data quality issue)
        if ($circulationEnergy < 0) {
            Log::warning('Negative circulation energy calculated for building', [
                'building_id' => $building->id,
                'month' => $month->format('Y-m'),
                'total_heating' => $totalHeatingEnergy,
                'water_heating' => $waterHeatingEnergy,
                'circulation' => $circulationEnergy,
            ]);

            $result = 0.0;
        } else {
            $result = round($circulationEnergy, self::DECIMAL_PRECISION);
        }

        // Cache the result
        $this->calculationCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Calculate winter gyvatukas using the stored summer average.
     * 
     * During heating season, we use the pre-calculated summer average
     * instead of recalculating from consumption data.
     * 
     * Requirement: 4.2
     *
     * @param Building $building The building to calculate for
     * @return float Circulation energy in kWh (from stored average)
     */
    public function calculateWinterGyvatukas(Building $building): float
    {
        // Use the stored summer average
        $summerAverage = $building->gyvatukas_summer_average;

        // If no summer average is stored, log a warning and return 0
        if ($summerAverage === null || $summerAverage <= 0) {
            Log::warning('Missing or invalid summer average for building during heating season', [
                'building_id' => $building->id,
                'summer_average' => $summerAverage,
            ]);

            return 0.0;
        }

        return (float) $summerAverage;
    }

    /**
     * Distribute circulation cost among apartments in a building.
     * 
     * Supports two distribution methods:
     * - EQUAL: Divide cost equally among all apartments (C/N)
     * - AREA: Divide cost proportionally by apartment area (C × A_i / Σ A_j)
     * 
     * Requirement: 4.5
     *
     * @param Building $building The building containing the apartments
     * @param float $totalCirculationCost Total circulation cost to distribute
     * @param string $method Distribution method: 'equal' or 'area'
     * @return array<int, float> Array mapping property_id to allocated cost
     */
    public function distributeCirculationCost(
        Building $building,
        float $totalCirculationCost,
        string $method = 'equal'
    ): array {
        $properties = $building->properties;

        if ($properties->isEmpty()) {
            Log::warning('No properties found for building during circulation cost distribution', [
                'building_id' => $building->id,
            ]);

            return [];
        }

        $distribution = [];

        if ($method === 'equal') {
            // Equal distribution: C/N
            $costPerProperty = $totalCirculationCost / $properties->count();

            foreach ($properties as $property) {
                $distribution[$property->id] = round($costPerProperty, 2);
            }
        } elseif ($method === 'area') {
            // Area-based distribution: C × (A_i / Σ A_j)
            $totalArea = $properties->sum('area_sqm');

            if ($totalArea <= 0) {
                Log::warning('Total area is zero or negative for building', [
                    'building_id' => $building->id,
                    'total_area' => $totalArea,
                ]);

                // Fall back to equal distribution
                return $this->distributeCirculationCost($building, $totalCirculationCost, 'equal');
            }

            foreach ($properties as $property) {
                $propertyArea = (float) $property->area_sqm;
                $proportion = $propertyArea / $totalArea;
                $distribution[$property->id] = round($totalCirculationCost * $proportion, 2);
            }
        } else {
            Log::error('Invalid distribution method specified', [
                'method' => $method,
                'building_id' => $building->id,
            ]);

            // Default to equal distribution
            return $this->distributeCirculationCost($building, $totalCirculationCost, 'equal');
        }

        return $distribution;
    }

    /**
     * Get total heating energy consumption for a building in a period.
     * 
     * Optimized with eager loading to prevent N+1 queries.
     * Reduces queries from 1 + N properties + M meters to just 2 queries.
     *
     * @param Building $building The building
     * @param Carbon $periodStart Start of period
     * @param Carbon $periodEnd End of period
     * @return float Total heating energy in kWh
     */
    private function getBuildingHeatingEnergy(Building $building, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Check consumption cache
        $cacheKey = sprintf('heating_%d_%s_%s', 
            $building->id, 
            $periodStart->format('Y-m-d'), 
            $periodEnd->format('Y-m-d')
        );
        
        if (isset($this->consumptionCache[$cacheKey])) {
            return $this->consumptionCache[$cacheKey];
        }

        $totalEnergy = 0.0;

        // Eager load properties with heating meters and their readings in 2 queries
        // This prevents N+1 by loading all related data upfront
        $building->load([
            'properties.meters' => function ($query) {
                $query->where('type', MeterType::HEATING)
                      ->select('id', 'property_id', 'type'); // Only select needed columns
            },
            'properties.meters.readings' => function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('reading_date', [$periodStart, $periodEnd])
                      ->orderBy('reading_date')
                      ->select('id', 'meter_id', 'reading_date', 'value'); // Only select needed columns
            }
        ]);

        foreach ($building->properties as $property) {
            foreach ($property->meters as $meter) {
                $readings = $meter->readings;

                // Calculate consumption from readings
                if ($readings->count() >= 2) {
                    $firstReading = $readings->first();
                    $lastReading = $readings->last();
                    $consumption = $lastReading->value - $firstReading->value;
                    $totalEnergy += max(0, $consumption); // Ensure non-negative
                }
            }
        }

        // Cache the result
        $this->consumptionCache[$cacheKey] = $totalEnergy;

        return $totalEnergy;
    }

    /**
     * Get total hot water volume consumption for a building in a period.
     * 
     * Optimized with eager loading to prevent N+1 queries.
     * Reduces queries from 1 + N properties + M meters to just 2 queries.
     *
     * @param Building $building The building
     * @param Carbon $periodStart Start of period
     * @param Carbon $periodEnd End of period
     * @return float Total hot water volume in m³
     */
    private function getBuildingHotWaterVolume(Building $building, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Check consumption cache
        $cacheKey = sprintf('water_%d_%s_%s', 
            $building->id, 
            $periodStart->format('Y-m-d'), 
            $periodEnd->format('Y-m-d')
        );
        
        if (isset($this->consumptionCache[$cacheKey])) {
            return $this->consumptionCache[$cacheKey];
        }

        $totalVolume = 0.0;

        // Eager load properties with hot water meters and their readings in 2 queries
        // This prevents N+1 by loading all related data upfront
        $building->load([
            'properties.meters' => function ($query) {
                $query->where('type', MeterType::WATER_HOT)
                      ->select('id', 'property_id', 'type'); // Only select needed columns
            },
            'properties.meters.readings' => function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('reading_date', [$periodStart, $periodEnd])
                      ->orderBy('reading_date')
                      ->select('id', 'meter_id', 'reading_date', 'value'); // Only select needed columns
            }
        ]);

        foreach ($building->properties as $property) {
            foreach ($property->meters as $meter) {
                $readings = $meter->readings;

                // Calculate consumption from readings
                if ($readings->count() >= 2) {
                    $firstReading = $readings->first();
                    $lastReading = $readings->last();
                    $consumption = $lastReading->value - $firstReading->value;
                    $totalVolume += max(0, $consumption); // Ensure non-negative
                }
            }
        }

        // Cache the result
        $this->consumptionCache[$cacheKey] = $totalVolume;

        return $totalVolume;
    }

    /**
     * Clear all internal caches.
     * 
     * Call this when meter readings are updated or when processing
     * multiple buildings to prevent memory buildup.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->calculationCache = [];
        $this->consumptionCache = [];
    }

    /**
     * Clear cache for a specific building.
     * 
     * Useful when meter readings are updated for a specific building.
     *
     * @param int $buildingId The building ID to clear cache for
     * @return void
     */
    public function clearBuildingCache(int $buildingId): void
    {
        // Clear calculation cache for this building
        $this->calculationCache = array_filter(
            $this->calculationCache,
            fn($key) => !str_starts_with($key, $buildingId . '_'),
            ARRAY_FILTER_USE_KEY
        );

        // Clear consumption cache for this building
        $this->consumptionCache = array_filter(
            $this->consumptionCache,
            fn($key) => !str_contains($key, '_' . $buildingId . '_'),
            ARRAY_FILTER_USE_KEY
        );
    }
}
