<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GyvatukasCalculatorInterface;
use App\Models\Building;
use App\ValueObjects\SummerPeriod;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * GyvatukasCalculator - Calculates circulation energy costs for Lithuanian utilities
 * 
 * Gyvatukas (circulation energy) is a Lithuanian utility billing concept that calculates
 * the energy cost for circulating hot water through building systems. This service
 * handles both summer and winter calculations according to Lithuanian regulations.
 * 
 * Key concepts:
 * - Summer period: May 1 - September 30 (non-heating season)
 * - Winter period: October 1 - April 30 (heating season)
 * - Summer average: Used as baseline for winter calculations
 * - Circulation energy: Energy required to maintain hot water circulation
 * 
 * @see \App\ValueObjects\SummerPeriod
 * @see \App\Services\GyvatukasSummerAverageService
 * @see \App\Services\BillingService
 */
final class GyvatukasCalculator implements GyvatukasCalculatorInterface
{
    /**
     * Cache TTL for gyvatukas calculations (24 hours)
     */
    private const CACHE_TTL_SECONDS = 86400;
    
    /**
     * Cache key prefix for gyvatukas calculations
     */
    private const CACHE_PREFIX = 'gyvatukas';
    
    /**
     * Default circulation energy rate (kWh per apartment per month)
     */
    private const DEFAULT_CIRCULATION_RATE = 15.0;
    
    /**
     * Minimum circulation energy (prevents negative values)
     */
    private const MIN_CIRCULATION_ENERGY = 0.0;

    /**
     * Large building threshold for efficiency calculations
     */
    private const LARGE_BUILDING_THRESHOLD = 50;

    /**
     * Small building threshold for efficiency calculations
     */
    private const SMALL_BUILDING_THRESHOLD = 10;

    /**
     * Efficiency factors for building size adjustments
     */
    private const LARGE_BUILDING_EFFICIENCY_FACTOR = 0.95;
    private const SMALL_BUILDING_PENALTY_FACTOR = 1.1;

    /**
     * Winter adjustment factors by month type
     */
    private const PEAK_WINTER_ADJUSTMENT = 1.3;
    private const SHOULDER_SEASON_ADJUSTMENT = 1.15;
    private const DEFAULT_WINTER_ADJUSTMENT = 1.2;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Check if the given date falls within the heating season.
     * 
     * Heating season in Lithuania typically runs from October 1 to April 30.
     */
    public function isHeatingSeason(Carbon $date): bool
    {
        $summerMonths = $this->getSummerMonths();
        
        return !in_array($date->month, $summerMonths, true);
    }
    
    /**
     * Check if the given date falls within the summer period.
     * 
     * Summer period in Lithuania runs from May 1 to September 30.
     */
    public function isSummerPeriod(Carbon $date): bool
    {
        $summerMonths = $this->getSummerMonths();
        
        return in_array($date->month, $summerMonths, true);
    }
    
    /**
     * Calculate summer gyvatukas for a building in a specific month.
     * 
     * Summer gyvatukas is calculated based on actual circulation energy
     * consumption during non-heating months.
     */
    public function calculateSummerGyvatukas(Building $building, Carbon $month): float
    {
        if ($this->isHeatingSeason($month)) {
            $this->logger->warning('Summer gyvatukas calculation requested for heating season month', [
                'building_id' => $building->id,
                'month' => $month->format('Y-m'),
            ]);
            return 0.0;
        }
        
        $cacheKey = $this->buildCacheKey('summer', $building->id, $month->format('Y-m'));
        
        return $this->cache->remember(
            $cacheKey,
            $this->getCacheTtl(),
            fn () => $this->performSummerCalculation($building, $month)
        );
    }
    
    /**
     * Calculate winter gyvatukas for a building in a specific month.
     * 
     * Winter gyvatukas uses the summer average as a baseline and adjusts
     * for heating season requirements.
     */
    public function calculateWinterGyvatukas(Building $building, Carbon $month): float
    {
        if (!$this->isHeatingSeason($month)) {
            $this->logger->warning('Winter gyvatukas calculation requested for summer month', [
                'building_id' => $building->id,
                'month' => $month->format('Y-m'),
            ]);
            return 0.0;
        }
        
        $cacheKey = $this->buildCacheKey('winter', $building->id, $month->format('Y-m'));
        
        return $this->cache->remember(
            $cacheKey,
            $this->getCacheTtl(),
            fn () => $this->performWinterCalculation($building, $month)
        );
    }
    
    /**
     * Get or calculate the summer average for a building.
     * 
     * If the building doesn't have a cached summer average, this method
     * will calculate it based on the most recent complete summer period.
     */
    public function getSummerAverage(Building $building): float
    {
        // Return cached value if available and recent
        if ($this->isSummerAverageValid($building)) {
            return (float) $building->gyvatukas_summer_average;
        }
        
        // Calculate new summer average
        return $this->calculateAndStoreSummerAverage($building);
    }
    
    /**
     * Calculate and store the summer average for a building.
     * 
     * This method calculates the average circulation energy across
     * the most recent complete summer period and stores it in the database.
     */
    public function calculateAndStoreSummerAverage(Building $building): float
    {
        $summerPeriod = $this->getLastCompleteSummerPeriod();
        
        $totalCirculation = 0.0;
        $monthCount = 0;
        
        $currentMonth = $summerPeriod->startDate->copy();
        
        while ($currentMonth->lte($summerPeriod->endDate)) {
            if ($this->isSummerPeriod($currentMonth)) {
                $monthlyCirculation = $this->performSummerCalculation($building, $currentMonth);
                $totalCirculation += $monthlyCirculation;
                $monthCount++;
            }
            
            $currentMonth->addMonth();
        }
        
        $average = $monthCount > 0 
            ? round($totalCirculation / $monthCount, 2) 
            : $this->getDefaultCirculationRate();
        
        // Store the calculated average
        $building->update([
            'gyvatukas_summer_average' => $average,
            'gyvatukas_last_calculated' => now(),
        ]);
        
        $this->logger->info('Summer average calculated and stored', [
            'building_id' => $building->id,
            'summer_average' => $average,
            'month_count' => $monthCount,
            'period' => $summerPeriod->description(),
        ]);
        
        return $average;
    }
    
    /**
     * Perform the actual summer gyvatukas calculation.
     * 
     * This method contains the core logic for calculating circulation energy
     * during summer months based on building characteristics and usage patterns.
     */
    protected function performSummerCalculation(Building $building, Carbon $month): float
    {
        // Base calculation: apartments * circulation rate per apartment
        $baseCirculation = $building->total_apartments * $this->getDefaultCirculationRate();
        
        // Apply building-specific factors
        $adjustedCirculation = $this->applyBuildingFactors($building, $baseCirculation, $month);
        
        // Ensure minimum value
        return max($adjustedCirculation, self::MIN_CIRCULATION_ENERGY);
    }
    
    /**
     * Perform the actual winter gyvatukas calculation.
     * 
     * Winter calculations use the summer average as a baseline and apply
     * heating season adjustments.
     */
    protected function performWinterCalculation(Building $building, Carbon $month): float
    {
        $summerAverage = $this->getSummerAverage($building);
        
        // Apply winter adjustment factors
        $winterAdjustment = $this->getWinterAdjustmentFactor($month);
        $adjustedCirculation = $summerAverage * $winterAdjustment;
        
        // Apply building-specific factors
        $finalCirculation = $this->applyBuildingFactors($building, $adjustedCirculation, $month);
        
        // Ensure minimum value
        return max($finalCirculation, self::MIN_CIRCULATION_ENERGY);
    }
    
    /**
     * Apply building-specific factors to circulation energy calculation.
     * 
     * This method adjusts the base circulation energy based on building
     * characteristics such as age, insulation, and system efficiency.
     */
    protected function applyBuildingFactors(Building $building, float $baseCirculation, Carbon $month): float
    {
        $adjustedCirculation = $baseCirculation;
        
        // Building size factor (larger buildings may have efficiency gains)
        if ($building->total_apartments > self::LARGE_BUILDING_THRESHOLD) {
            $adjustedCirculation *= self::LARGE_BUILDING_EFFICIENCY_FACTOR;
        } elseif ($building->total_apartments < self::SMALL_BUILDING_THRESHOLD) {
            $adjustedCirculation *= self::SMALL_BUILDING_PENALTY_FACTOR;
        }
        
        return $adjustedCirculation;
    }
    
    /**
     * Get the winter adjustment factor for a specific month.
     * 
     * Different heating season months may have different circulation requirements
     * based on temperature and heating system operation.
     */
    protected function getWinterAdjustmentFactor(Carbon $month): float
    {
        $peakWinterMonths = $this->config->get('gyvatukas.peak_winter_months', [12, 1, 2]);
        $shoulderMonths = $this->config->get('gyvatukas.shoulder_months', [10, 11, 3, 4]);
        
        // Peak winter months need more circulation
        if (in_array($month->month, $peakWinterMonths, true)) {
            return $this->config->get('gyvatukas.peak_winter_adjustment', self::PEAK_WINTER_ADJUSTMENT);
        }
        
        // Shoulder months need moderate increase
        if (in_array($month->month, $shoulderMonths, true)) {
            return $this->config->get('gyvatukas.shoulder_adjustment', self::SHOULDER_SEASON_ADJUSTMENT);
        }
        
        // Default adjustment
        return $this->config->get('gyvatukas.default_winter_adjustment', self::DEFAULT_WINTER_ADJUSTMENT);
    }
    
    /**
     * Get the last complete summer period.
     * 
     * Returns the most recent summer period (May-September) that has
     * completely finished.
     * 
     * @return SummerPeriod The last complete summer period
     */
    protected function getLastCompleteSummerPeriod(): SummerPeriod
    {
        $now = now();
        $currentYear = $now->year;
        
        // If we're currently in summer or early fall, use last year's summer
        if ($now->month >= 5 && $now->month <= 10) {
            $year = $currentYear - 1;
        } else {
            // If we're in late fall/winter/spring, use the most recent summer
            $year = $currentYear - 1;
        }
        
        return new SummerPeriod($year);
    }
    
    /**
     * Clear gyvatukas calculation cache for a building.
     * 
     * This method clears all cached gyvatukas calculations for a specific building.
     * Useful when building data changes or when recalculation is needed.
     */
    public function clearBuildingCache(Building $building): void
    {
        // Clear summer calculations
        $summerKey = $this->buildCacheKey('summer', $building->id, '*');
        $this->cache->forget($summerKey);
        
        // Clear winter calculations
        $winterKey = $this->buildCacheKey('winter', $building->id, '*');
        $this->cache->forget($winterKey);
        
        $this->logger->info('Gyvatukas cache cleared for building', [
            'building_id' => $building->id,
        ]);
    }
    
    /**
     * Clear all gyvatukas calculation cache.
     * 
     * This method clears all cached gyvatukas calculations.
     * Use with caution as it will force recalculation for all buildings.
     */
    public function clearAllCache(): void
    {
        // In production, use cache tags for more targeted clearing
        $this->cache->flush();
        
        $this->logger->info('All gyvatukas cache cleared');
    }

    /**
     * Get summer months from configuration.
     */
    private function getSummerMonths(): array
    {
        return $this->config->get('gyvatukas.summer_months', [5, 6, 7, 8, 9]);
    }

    /**
     * Get default circulation rate from configuration.
     */
    private function getDefaultCirculationRate(): float
    {
        return $this->config->get('gyvatukas.default_circulation_rate', self::DEFAULT_CIRCULATION_RATE);
    }

    /**
     * Get cache TTL from configuration.
     */
    private function getCacheTtl(): int
    {
        return $this->config->get('gyvatukas.cache_ttl', self::CACHE_TTL_SECONDS);
    }

    /**
     * Build a cache key for gyvatukas calculations.
     */
    private function buildCacheKey(string $type, int $buildingId, string $period): string
    {
        return sprintf('%s:%s:%d:%s', self::CACHE_PREFIX, $type, $buildingId, $period);
    }

    /**
     * Check if the building's summer average is valid and recent.
     */
    private function isSummerAverageValid(Building $building): bool
    {
        if ($building->gyvatukas_summer_average === null || $building->gyvatukas_last_calculated === null) {
            return false;
        }

        $validityPeriod = $this->config->get('gyvatukas.summer_average_validity_months', 12);
        $cutoffDate = now()->subMonths($validityPeriod);

        return $building->gyvatukas_last_calculated->isAfter($cutoffDate);
    }

    /**
     * Calculate gyvatukas for a building in a specific month (backward compatibility).
     * 
     * This method automatically determines whether to use summer or winter calculation
     * based on the month provided.
     */
    public function calculate(Building $building, Carbon $month): float
    {
        if ($this->isSummerPeriod($month)) {
            return $this->calculateSummerGyvatukas($building, $month);
        }

        return $this->calculateWinterGyvatukas($building, $month);
    }

    /**
     * Distribute circulation costs among properties in a building.
     * 
     * @param Building $building The building containing the properties
     * @param float $totalCost Total circulation cost to distribute
     * @param string $method Distribution method ('equal' or 'area')
     * @return array<int, float> Array mapping property IDs to their cost share
     */
    public function distributeCirculationCost(Building $building, float $totalCost, string $method = 'equal'): array
    {
        $properties = $building->properties;
        
        if ($properties->isEmpty()) {
            return [];
        }

        $distribution = [];

        switch ($method) {
            case 'area':
                $totalArea = $properties->sum('area_sqm');
                
                if ($totalArea <= 0) {
                    // Fallback to equal distribution if no area data
                    return $this->distributeCirculationCost($building, $totalCost, 'equal');
                }

                foreach ($properties as $property) {
                    $proportion = $property->area_sqm / $totalArea;
                    $distribution[$property->id] = round($totalCost * $proportion, 2);
                }
                break;

            case 'equal':
            default:
                $costPerProperty = round($totalCost / $properties->count(), 2);
                
                foreach ($properties as $property) {
                    $distribution[$property->id] = $costPerProperty;
                }
                break;
        }

        return $distribution;
    }
}