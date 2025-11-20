<?php

namespace App\Services;

use App\Enums\MeterType;
use App\Models\Building;
use App\Models\MeterReading;
use Carbon\Carbon;

/**
 * Service for calculating "gyvatukas" (hot water circulation fees).
 * 
 * Implements seasonal calculation logic:
 * - Summer (May-September): Calculate from actual consumption using formula
 * - Winter (October-April): Use stored summer average as norm
 */
class GyvatukasCalculator
{
    private float $waterSpecificHeat;
    private float $temperatureDelta;

    /**
     * Create a new GyvatukasCalculator instance.
     */
    public function __construct(?float $waterSpecificHeat = null, ?float $temperatureDelta = null)
    {
        $this->waterSpecificHeat = $waterSpecificHeat ?? config('gyvatukas.water_specific_heat', 1.163);
        $this->temperatureDelta = $temperatureDelta ?? config('gyvatukas.temperature_delta', 45.0);
    }

    /**
     * Calculate gyvatukas (circulation fee) for a building in a given billing period.
     *
     * @param Building $building
     * @param Carbon $billingMonth
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
     * Heating season: October through April (months 10, 11, 12, 1, 2, 3, 4)
     *
     * @param Carbon $date
     * @return bool
     */
    public function isHeatingSeason(Carbon $date): bool
    {
        $month = $date->month;
        return $month >= 10 || $month <= 4;
    }

    /**
     * Calculate summer gyvatukas using the formula:
     * Q_circ = Q_total - (V_water × c × ΔT)
     * 
     * Where:
     * - Q_total: Total building heating energy consumption
     * - V_water: Hot water volume consumed
     * - c: Specific heat capacity of water (1.163 kWh/m³·°C)
     * - ΔT: Temperature difference (45°C)
     *
     * @param Building $building
     * @param Carbon $billingMonth
     * @return float Circulation energy in kWh
     */
    public function calculateSummerGyvatukas(Building $building, Carbon $billingMonth): float
    {
        $startDate = $billingMonth->copy()->startOfMonth();
        $endDate = $billingMonth->copy()->endOfMonth();

        // Get total heating energy for the building
        $totalHeatingEnergy = $this->getBuildingHeatingConsumption($building, $startDate, $endDate);

        // Get hot water volume consumed
        $hotWaterVolume = $this->getBuildingHotWaterConsumption($building, $startDate, $endDate);

        // Calculate energy used for heating water: V_water × c × ΔT
        $waterHeatingEnergy = $hotWaterVolume * $this->waterSpecificHeat * $this->temperatureDelta;

        // Circulation energy is the difference
        $circulationEnergy = $totalHeatingEnergy - $waterHeatingEnergy;

        // Ensure non-negative result (data validation)
        return max(0.0, $circulationEnergy);
    }

    /**
     * Calculate winter gyvatukas using the stored summer average.
     * During heating season, use the pre-calculated summer average as a fixed norm.
     *
     * @param Building $building
     * @return float Circulation energy in kWh
     */
    public function calculateWinterGyvatukas(Building $building): float
    {
        // Use stored summer average, default to 0 if not calculated yet
        return (float) ($building->gyvatukas_summer_average ?? 0.0);
    }

    /**
     * Distribute circulation cost among apartments in a building.
     *
     * @param Building $building
     * @param float $totalCirculationCost Total cost to distribute
     * @param string $method Distribution method: 'equal' or 'area'
     * @return array<int, float> Array mapping property_id to allocated cost
     */
    public function distributeCirculationCost(Building $building, float $totalCirculationCost, string $method = 'equal'): array
    {
        $properties = $building->properties;

        if ($properties->isEmpty()) {
            return [];
        }

        $distribution = [];

        if ($method === 'equal') {
            // Divide equally among all apartments
            $costPerProperty = $totalCirculationCost / $properties->count();
            
            foreach ($properties as $property) {
                $distribution[$property->id] = $costPerProperty;
            }
        } elseif ($method === 'area') {
            // Distribute proportionally by area
            $totalArea = $properties->sum('area_sqm');
            
            if ($totalArea > 0) {
                foreach ($properties as $property) {
                    $proportion = $property->area_sqm / $totalArea;
                    $distribution[$property->id] = $totalCirculationCost * $proportion;
                }
            }
        }

        return $distribution;
    }

    /**
     * Get total heating energy consumption for a building in a period.
     *
     * @param Building $building
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float Total heating consumption in kWh
     */
    private function getBuildingHeatingConsumption(Building $building, Carbon $startDate, Carbon $endDate): float
    {
        $totalConsumption = 0.0;

        // Eager load properties with heating meters and their readings for the period
        // This prevents N+1 queries by loading all related data in a single query
        $properties = $building->load([
            'properties.meters' => function ($query) {
                $query->where('type', MeterType::HEATING);
            },
            'properties.meters.readings' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('reading_date', [$startDate, $endDate])
                    ->orderBy('reading_date');
            }
        ])->properties;

        foreach ($properties as $property) {
            foreach ($property->meters as $meter) {
                $readings = $meter->readings;

                // Calculate consumption from readings
                if ($readings->count() >= 2) {
                    $firstReading = $readings->first();
                    $lastReading = $readings->last();
                    $totalConsumption += $lastReading->value - $firstReading->value;
                }
            }
        }

        return $totalConsumption;
    }

    /**
     * Get total hot water volume consumption for a building in a period.
     *
     * @param Building $building
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float Total hot water volume in m³
     */
    private function getBuildingHotWaterConsumption(Building $building, Carbon $startDate, Carbon $endDate): float
    {
        $totalVolume = 0.0;

        // Eager load properties with hot water meters and their readings for the period
        // This prevents N+1 queries by loading all related data in a single query
        $properties = $building->load([
            'properties.meters' => function ($query) {
                $query->where('type', MeterType::WATER_HOT);
            },
            'properties.meters.readings' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('reading_date', [$startDate, $endDate])
                    ->orderBy('reading_date');
            }
        ])->properties;

        foreach ($properties as $property) {
            foreach ($property->meters as $meter) {
                $readings = $meter->readings;

                // Calculate consumption from readings
                if ($readings->count() >= 2) {
                    $firstReading = $readings->first();
                    $lastReading = $readings->last();
                    $totalVolume += $lastReading->value - $firstReading->value;
                }
            }
        }

        return $totalVolume;
    }
}
