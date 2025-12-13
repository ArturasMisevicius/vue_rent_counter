<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Building;
use Carbon\Carbon;

/**
 * Interface for Gyvatukas (circulation energy) calculation services.
 */
interface GyvatukasCalculatorInterface
{
    /**
     * Check if the given date falls within the heating season.
     */
    public function isHeatingSeason(Carbon $date): bool;

    /**
     * Check if the given date falls within the summer period.
     */
    public function isSummerPeriod(Carbon $date): bool;

    /**
     * Calculate summer gyvatukas for a building in a specific month.
     */
    public function calculateSummerGyvatukas(Building $building, Carbon $month): float;

    /**
     * Calculate winter gyvatukas for a building in a specific month.
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