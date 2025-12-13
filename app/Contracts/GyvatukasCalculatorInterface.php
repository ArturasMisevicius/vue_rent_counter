<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Building;
use Carbon\Carbon;

/**
 * Interface for Gyvatukas (circulation energy) calculation services.
 * 
 * Defines the contract for calculating circulation energy costs in Lithuanian
 * utilities billing systems. Implementations must handle both summer and winter
 * calculations with appropriate caching and performance optimizations.
 * 
 * @see \App\Services\GyvatukasCalculator Primary implementation
 * @package App\Contracts
 */
interface GyvatukasCalculatorInterface
{
    /**
     * Check if the given date falls within the heating season.
     * 
     * @param Carbon $date The date to check
     * @return bool True if in heating season (Oct-Apr), false otherwise
     */
    public function isHeatingSeason(Carbon $date): bool;

    /**
     * Check if the given date falls within the summer period.
     * 
     * @param Carbon $date The date to check  
     * @return bool True if in summer period (May-Sep), false otherwise
     */
    public function isSummerPeriod(Carbon $date): bool;

    /**
     * Calculate summer gyvatukas for a building in a specific month.
     * 
     * @param Building $building Building with valid apartment count
     * @param Carbon $month Month in summer period (May-Sep)
     * @return float Circulation energy in kWh
     * @throws \InvalidArgumentException If building data is invalid
     */
    public function calculateSummerGyvatukas(Building $building, Carbon $month): float;

    /**
     * Calculate winter gyvatukas for a building in a specific month.
     * 
     * @param Building $building Building with valid summer average
     * @param Carbon $month Month in heating season (Oct-Apr)
     * @return float Circulation energy in kWh with winter adjustments
     * @throws \InvalidArgumentException If building data is invalid
     */
    public function calculateWinterGyvatukas(Building $building, Carbon $month): float;

    /**
     * Get or calculate the summer average for a building.
     */
    public function getSummerAverage(Building $building): float;

    /**
     * Calculate and store the summer average for a building.
     */
    public function calculateAndStoreSummerAverage(Building $building): float;

    /**
     * Clear gyvatukas calculation cache for a building.
     */
    public function clearBuildingCache(Building $building): void;

    /**
     * Clear all gyvatukas calculation cache.
     */
    public function clearAllCache(): void;

    /**
     * Calculate gyvatukas for a building in a specific month (backward compatibility).
     */
    public function calculate(Building $building, Carbon $month): float;

    /**
     * Distribute circulation costs among properties in a building.
     */
    public function distributeCirculationCost(Building $building, float $totalCost, string $method = 'equal'): array;
}