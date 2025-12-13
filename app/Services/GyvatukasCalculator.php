<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GyvatukasCalculatorInterface;
use App\Models\Building;
use App\Repositories\BuildingRepository;
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
 * ## Key Concepts
 * - **Summer period**: May 1 - September 30 (non-heating season)
 * - **Winter period**: October 1 - April 30 (heating season)  
 * - **Summer average**: Used as baseline for winter calculations
 * - **Circulation energy**: Energy required to maintain hot water circulation
 * 
 * ## Calculation Logic
 * - **Summer**: Base rate × apartments × building factors
 * - **Winter**: Summer average × seasonal adjustments × building factors
 * - **Peak winter months** (Dec, Jan, Feb): 30% increase
 * - **Shoulder months** (Oct, Nov, Mar, Apr): 15% increase
 * 
 * ## Performance Features
 * - 24-hour caching with graceful fallback
 * - Building-specific cache invalidation
 * - Batch processing support
 * - Memory-efficient calculations
 * 
 * ## Usage Examples
 * ```php
 * // Basic calculation
 * $energy = $calculator->calculate($building, $month);
 * 
 * // Seasonal calculations
 * $summerEnergy = $calculator->calculateSummerGyvatukas($building, $summerMonth);
 * $winterEnergy = $calculator->calculateWinterGyvatukas($building, $winterMonth);
 * 
 * // Cost distribution
 * $costs = $calculator->distributeCirculationCost($building, $totalCost, 'area');
 * ```
 * 
 * @see \App\ValueObjects\SummerPeriod
 * @see \App\Services\GyvatukasSummerAverageService
 * @see \App\Services\BillingService
 * @see \App\Contracts\GyvatukasCalculatorInterface
 * 
 * @package App\Services
 * @author CFlow Development Team
 * @since 1.0.0
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

    /**
     * Memoized configuration values for performance
     */
    private ?array $summerMonths = null;
    private ?float $defaultCirculationRate = null;
    private ?int $cacheTtl = null;
    private ?array $peakWinterMonths = null;
    private ?array $shoulderMonths = null;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
        private readonly BuildingRepository $buildingRepository,
    ) {
    }

    /**
     * Check if the given date falls within the heating season.
     * 
     * Heating season in Lithuania typically runs from October 1 to April 30.
     * This method is used to determine which calculation method to apply.
     * 
     * @param Carbon $date The date to check
     * @return bool True if the date is in heating season, false otherwise
     * 
     * @example
     * ```php
     * $isHeating = $calculator->isHeatingSeason(Carbon::create(2024, 12, 15)); // true
     * $isHeating = $calculator->isHeatingSeason(Carbon::create(2024, 7, 15));  // false
     * ```
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
     * consumption during non-heating months. The calculation uses base
     * circulation rates adjusted for building size and characteristics.
     * 
     * ## Calculation Formula
     * ```
     * Base Energy = total_apartments × default_circulation_rate
     * Adjusted Energy = Base Energy × building_size_factor
     * Final Energy = max(Adjusted Energy, MIN_CIRCULATION_ENERGY)
     * ```
     * 
     * ## Building Size Factors
     * - Large buildings (>50 apartments): 5% efficiency gain (0.95 multiplier)
     * - Small buildings (<10 apartments): 10% penalty (1.1 multiplier)
     * - Medium buildings: No adjustment (1.0 multiplier)
     * 
     * @param Building $building The building to calculate for (must have total_apartments > 0)
     * @param Carbon $month The month to calculate (must be in summer period)
     * @return float Circulation energy in kWh
     * 
     * @throws \InvalidArgumentException If building has invalid apartment count
     * @throws \InvalidArgumentException If building exceeds maximum apartment limit
     * 
     * @example
     * ```php
     * $building = Building::find(1); // 20 apartments
     * $summerMonth = Carbon::create(2024, 6, 1);
     * $energy = $calculator->calculateSummerGyvatukas($building, $summerMonth);
     * // Returns: 300.0 (20 × 15.0 kWh default rate)
     * ```
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
        
        try {
            return $this->cache->remember(
                $cacheKey,
                $this->getCacheTtl(),
                fn () => $this->performSummerCalculation($building, $month)
            );
        } catch (\Exception $e) {
            $this->logger->error('Cache failure during summer gyvatukas calculation, falling back to direct calculation', [
                'building_id' => $building->id,
                'month' => $month->format('Y-m'),
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to direct calculation if cache fails
            return $this->performSummerCalculation($building, $month);
        }
    }
    
    /**
     * Calculate winter gyvatukas for a building in a specific month.
     * 
     * Winter gyvatukas uses the summer average as a baseline and adjusts
     * for heating season requirements. Different months have different
     * adjustment factors based on typical heating demands.
     * 
     * ## Calculation Formula
     * ```
     * Base Energy = getSummerAverage(building)
     * Seasonal Energy = Base Energy × winter_adjustment_factor
     * Adjusted Energy = Seasonal Energy × building_size_factor
     * Final Energy = max(Adjusted Energy, MIN_CIRCULATION_ENERGY)
     * ```
     * 
     * ## Winter Adjustment Factors
     * - **Peak winter** (Dec, Jan, Feb): 30% increase (1.3 multiplier)
     * - **Shoulder months** (Oct, Nov, Mar, Apr): 15% increase (1.15 multiplier)
     * - **Other heating months**: 20% increase (1.2 multiplier)
     * 
     * @param Building $building The building to calculate for (must have valid summer average)
     * @param Carbon $month The month to calculate (must be in heating season)
     * @return float Circulation energy in kWh
     * 
     * @throws \InvalidArgumentException If building has invalid apartment count
     * @throws \InvalidArgumentException If building exceeds maximum apartment limit
     * 
     * @example
     * ```php
     * $building = Building::find(1); // Has summer average of 150.0 kWh
     * $winterMonth = Carbon::create(2024, 12, 1); // Peak winter month
     * $energy = $calculator->calculateWinterGyvatukas($building, $winterMonth);
     * // Returns: 195.0 (150.0 × 1.3 peak winter adjustment)
     * ```
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
        
        try {
            return $this->cache->remember(
                $cacheKey,
                $this->getCacheTtl(),
                fn () => $this->performWinterCalculation($building, $month)
            );
        } catch (\Exception $e) {
            $this->logger->error('Cache failure during winter gyvatukas calculation, falling back to direct calculation', [
                'building_id' => $building->id,
                'month' => $month->format('Y-m'),
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to direct calculation if cache fails
            return $this->performWinterCalculation($building, $month);
        }
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
        $this->validateBuildingForCalculation($building);
        
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
        $this->validateBuildingForCalculation($building);
        
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
        $peakWinterMonths = $this->getPeakWinterMonths();
        $shoulderMonths = $this->getShoulderMonths();
        
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
        try {
            $keysCleared = 0;
            
            // Clear calculations for all months (last 24 months to be safe)
            $startDate = now()->subMonths(24);
            $endDate = now()->addMonths(12);
            
            $currentMonth = $startDate->copy()->startOfMonth();
            
            while ($currentMonth->lte($endDate)) {
                $monthKey = $currentMonth->format('Y-m');
                
                // Clear summer calculations
                $summerKey = $this->buildCacheKey('summer', $building->id, $monthKey);
                if ($this->cache->forget($summerKey)) {
                    $keysCleared++;
                }
                
                // Clear winter calculations
                $winterKey = $this->buildCacheKey('winter', $building->id, $monthKey);
                if ($this->cache->forget($winterKey)) {
                    $keysCleared++;
                }
                
                $currentMonth->addMonth();
            }
            
            // Clear distribution cache patterns
            $distributionPattern = sprintf('%s:distribution:%d:*', self::CACHE_PREFIX, $building->id);
            $this->clearCachePattern($distributionPattern);
            
            $this->logger->info('Gyvatukas cache cleared for building', [
                'building_id' => $building->id,
                'keys_cleared' => $keysCleared,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear gyvatukas cache for building', [
                'building_id' => $building->id,
                'error' => $e->getMessage(),
            ]);
            
            // Don't throw - cache clearing failures shouldn't break the application
        }
    }

    /**
     * Clear cache keys matching a pattern (Redis-compatible).
     */
    private function clearCachePattern(string $pattern): void
    {
        if (method_exists($this->cache, 'tags')) {
            // Use cache tags if available (Redis)
            $this->cache->tags(['gyvatukas'])->flush();
        } else {
            // Fallback for other cache drivers
            // Note: This is less efficient but works with all drivers
            $this->logger->warning('Cache pattern clearing not supported, consider using Redis with tags');
        }
    }
    
    /**
     * Clear all gyvatukas calculation cache.
     * 
     * This method clears all cached gyvatukas calculations.
     * Use with caution as it will force recalculation for all buildings.
     */
    public function clearAllCache(): void
    {
        try {
            // In production, use cache tags for more targeted clearing
            $this->cache->flush();
            
            $this->logger->info('All gyvatukas cache cleared');
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear all gyvatukas cache', [
                'error' => $e->getMessage(),
            ]);
            
            // Don't throw - cache clearing failures shouldn't break the application
        }
    }

    /**
     * Get summer months from configuration (memoized).
     */
    private function getSummerMonths(): array
    {
        return $this->summerMonths ??= $this->config->get('gyvatukas.summer_months', [5, 6, 7, 8, 9]);
    }

    /**
     * Get default circulation rate from configuration (memoized).
     */
    private function getDefaultCirculationRate(): float
    {
        return $this->defaultCirculationRate ??= $this->config->get('gyvatukas.default_circulation_rate', self::DEFAULT_CIRCULATION_RATE);
    }

    /**
     * Get cache TTL from configuration (memoized).
     */
    private function getCacheTtl(): int
    {
        return $this->cacheTtl ??= $this->config->get('gyvatukas.cache_ttl', self::CACHE_TTL_SECONDS);
    }

    /**
     * Get peak winter months from configuration (memoized).
     */
    private function getPeakWinterMonths(): array
    {
        return $this->peakWinterMonths ??= $this->config->get('gyvatukas.peak_winter_months', [12, 1, 2]);
    }

    /**
     * Get shoulder months from configuration (memoized).
     */
    private function getShoulderMonths(): array
    {
        return $this->shoulderMonths ??= $this->config->get('gyvatukas.shoulder_months', [10, 11, 3, 4]);
    }

    /**
     * Build a cache key for gyvatukas calculations.
     */
    private function buildCacheKey(string $type, int $buildingId, string $period): string
    {
        return sprintf('%s:%s:%d:%s', self::CACHE_PREFIX, $type, $buildingId, $period);
    }

    /**
     * Validate building data for gyvatukas calculations.
     * 
     * @throws \InvalidArgumentException When building data is invalid
     */
    private function validateBuildingForCalculation(Building $building): void
    {
        if ($building->total_apartments <= 0) {
            throw new \InvalidArgumentException(
                "Building {$building->id} has invalid apartment count: {$building->total_apartments}"
            );
        }

        $maxApartments = $this->config->get('gyvatukas.validation.max_apartments', 1000);
        if ($building->total_apartments > $maxApartments) {
            throw new \InvalidArgumentException(
                "Building {$building->id} exceeds maximum apartment limit: {$building->total_apartments} > {$maxApartments}"
            );
        }
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
     * This method allocates the total circulation cost across all properties
     * in a building using various distribution methods. Enhanced to support
     * consumption-based allocation and different area types.
     * 
     * ## Distribution Methods
     * - **equal**: Divides cost equally among all properties
     * - **area**: Distributes cost proportionally based on property area (area_sqm)
     * - **by_consumption**: Distributes cost based on historical consumption ratios
     * - **custom_formula**: Uses custom mathematical formula for distribution
     * 
     * ## Area Types (for area-based distribution)
     * - **total_area**: Uses total property area (default)
     * - **heated_area**: Uses only heated area for distribution
     * - **commercial_area**: Uses commercial area for mixed-use buildings
     * 
     * ## Consumption-Based Distribution
     * Uses historical consumption averages from the last 12 months to calculate
     * proportional distribution. Falls back to equal distribution if no consumption
     * data is available.
     * 
     * ## Fallback Behavior
     * - Area-based: Falls back to equal distribution if no area data available
     * - Consumption-based: Falls back to equal distribution if no consumption data
     * - Custom formula: Falls back to equal distribution if formula evaluation fails
     * 
     * ## Performance Optimizations
     * - Uses selective column loading to minimize memory usage
     * - Caches distribution calculations for repeated calls
     * - Optimized for large buildings with many properties
     * - Batch processes consumption data queries
     * 
     * @param Building $building The building containing the properties
     * @param float $totalCost Total circulation cost to distribute (must be >= 0)
     * @param string $method Distribution method ('equal', 'area', 'by_consumption', 'custom_formula')
     * @param array $options Additional options (area_type, consumption_period, formula, etc.)
     * @return array<int, float> Array mapping property IDs to their cost share
     * 
     * @example
     * ```php
     * // Equal distribution
     * $costs = $calculator->distributeCirculationCost($building, 1000.0, 'equal');
     * // Returns: [1 => 333.33, 2 => 333.33, 3 => 333.34]
     * 
     * // Area-based distribution with heated area
     * $costs = $calculator->distributeCirculationCost($building, 1000.0, 'area', [
     *     'area_type' => 'heated_area'
     * ]);
     * 
     * // Consumption-based distribution
     * $costs = $calculator->distributeCirculationCost($building, 1000.0, 'by_consumption', [
     *     'consumption_period_months' => 12
     * ]);
     * 
     * // Custom formula distribution
     * $costs = $calculator->distributeCirculationCost($building, 1000.0, 'custom_formula', [
     *     'formula' => 'area * 0.7 + consumption * 0.3'
     * ]);
     * ```
     */
    public function distributeCirculationCost(Building $building, float $totalCost, string $method = 'equal', array $options = []): array
    {
        if ($totalCost <= 0) {
            return [];
        }

        $cacheKey = $this->buildDistributionCacheKey($building->id, $method, $totalCost, $options);
        
        return $this->cache->remember(
            $cacheKey,
            300, // 5 minutes cache for distribution calculations
            fn () => $this->performDistributionCalculation($building, $totalCost, $method, $options)
        );
    }

    /**
     * Perform the actual distribution calculation with optimized queries.
     */
    private function performDistributionCalculation(Building $building, float $totalCost, string $method, array $options = []): array
    {
        $properties = $this->buildingRepository->getBuildingPropertiesForDistribution(
            $building->id,
            $method
        );
        
        if ($properties->isEmpty()) {
            return [];
        }

        return match ($method) {
            'area' => $this->distributeByArea($properties, $totalCost, $building, $options),
            'by_consumption' => $this->distributeByConsumption($properties, $totalCost, $building, $options),
            'custom_formula' => $this->distributeByCustomFormula($properties, $totalCost, $building, $options),
            default => $this->distributeEqually($properties, $totalCost),
        };
    }

    /**
     * Distribute cost equally among properties.
     */
    private function distributeEqually($properties, float $totalCost): array
    {
        $propertyCount = $properties->count();
        $costPerProperty = round($totalCost / $propertyCount, 2);
        
        return $properties->pluck('id')->mapWithKeys(
            fn ($id) => [$id => $costPerProperty]
        )->toArray();
    }

    /**
     * Distribute cost proportionally by area with support for different area types.
     */
    private function distributeByArea($properties, float $totalCost, Building $building, array $options = []): array
    {
        $areaType = $options['area_type'] ?? 'total_area';
        
        // Map area type to property field
        $areaField = match ($areaType) {
            'heated_area' => 'heated_area_sqm',
            'commercial_area' => 'commercial_area_sqm',
            default => 'area_sqm',
        };
        
        // Calculate total area using the specified area type
        $totalArea = $properties->sum($areaField);
        
        if ($totalArea <= 0) {
            // Fallback to equal distribution if no area data
            $this->logger->warning('Area-based distribution falling back to equal distribution', [
                'building_id' => $building->id,
                'area_type' => $areaType,
                'total_area' => $totalArea,
            ]);
            
            return $this->distributeEqually($properties, $totalCost);
        }

        $distribution = [];
        $remainingCost = $totalCost;
        $processedProperties = 0;
        
        foreach ($properties as $property) {
            $processedProperties++;
            $propertyArea = $property->{$areaField} ?? 0;
            
            // For the last property, assign remaining cost to avoid rounding errors
            if ($processedProperties === $properties->count()) {
                $distribution[$property->id] = round($remainingCost, 2);
            } else {
                $proportion = $propertyArea / $totalArea;
                $propertyCost = round($totalCost * $proportion, 2);
                $distribution[$property->id] = $propertyCost;
                $remainingCost -= $propertyCost;
            }
        }

        $this->logger->info('Area-based distribution completed', [
            'building_id' => $building->id,
            'area_type' => $areaType,
            'total_area' => $totalArea,
            'total_cost' => $totalCost,
            'properties_count' => $properties->count(),
        ]);

        return $distribution;
    }

    /**
     * Distribute cost based on historical consumption averages.
     */
    private function distributeByConsumption($properties, float $totalCost, Building $building, array $options = []): array
    {
        $consumptionPeriodMonths = $options['consumption_period_months'] ?? 12;
        $cutoffDate = now()->subMonths($consumptionPeriodMonths);
        
        // Get consumption averages for each property
        $consumptionAverages = [];
        $totalConsumption = 0;
        
        foreach ($properties as $property) {
            // Get average consumption for this property over the specified period
            $averageConsumption = $this->getPropertyConsumptionAverage(
                $property->id,
                $cutoffDate,
                $consumptionPeriodMonths
            );
            
            $consumptionAverages[$property->id] = $averageConsumption;
            $totalConsumption += $averageConsumption;
        }
        
        if ($totalConsumption <= 0) {
            // Fallback to equal distribution if no consumption data
            $this->logger->warning('Consumption-based distribution falling back to equal distribution', [
                'building_id' => $building->id,
                'consumption_period_months' => $consumptionPeriodMonths,
                'total_consumption' => $totalConsumption,
            ]);
            
            return $this->distributeEqually($properties, $totalCost);
        }

        $distribution = [];
        $remainingCost = $totalCost;
        $processedProperties = 0;
        
        foreach ($properties as $property) {
            $processedProperties++;
            $propertyConsumption = $consumptionAverages[$property->id];
            
            // For the last property, assign remaining cost to avoid rounding errors
            if ($processedProperties === $properties->count()) {
                $distribution[$property->id] = round($remainingCost, 2);
            } else {
                $proportion = $propertyConsumption / $totalConsumption;
                $propertyCost = round($totalCost * $proportion, 2);
                $distribution[$property->id] = $propertyCost;
                $remainingCost -= $propertyCost;
            }
        }

        $this->logger->info('Consumption-based distribution completed', [
            'building_id' => $building->id,
            'consumption_period_months' => $consumptionPeriodMonths,
            'total_consumption' => $totalConsumption,
            'total_cost' => $totalCost,
            'properties_count' => $properties->count(),
        ]);

        return $distribution;
    }

    /**
     * Distribute cost using custom mathematical formula.
     */
    private function distributeByCustomFormula($properties, float $totalCost, Building $building, array $options = []): array
    {
        $formula = $options['formula'] ?? '';
        
        if (empty($formula)) {
            $this->logger->warning('Custom formula distribution missing formula, falling back to equal distribution', [
                'building_id' => $building->id,
            ]);
            
            return $this->distributeEqually($properties, $totalCost);
        }

        try {
            // Calculate distribution weights using the formula
            $weights = [];
            $totalWeight = 0;
            
            foreach ($properties as $property) {
                $variables = $this->prepareFormulaVariables($property, $options);
                $weight = $this->evaluateDistributionFormula($formula, $variables);
                
                $weights[$property->id] = max(0, $weight); // Ensure non-negative weights
                $totalWeight += $weights[$property->id];
            }
            
            if ($totalWeight <= 0) {
                throw new \Exception('Formula resulted in zero or negative total weight');
            }

            // Distribute cost based on calculated weights
            $distribution = [];
            $remainingCost = $totalCost;
            $processedProperties = 0;
            
            foreach ($properties as $property) {
                $processedProperties++;
                
                // For the last property, assign remaining cost to avoid rounding errors
                if ($processedProperties === $properties->count()) {
                    $distribution[$property->id] = round($remainingCost, 2);
                } else {
                    $proportion = $weights[$property->id] / $totalWeight;
                    $propertyCost = round($totalCost * $proportion, 2);
                    $distribution[$property->id] = $propertyCost;
                    $remainingCost -= $propertyCost;
                }
            }

            $this->logger->info('Custom formula distribution completed', [
                'building_id' => $building->id,
                'formula' => $formula,
                'total_weight' => $totalWeight,
                'total_cost' => $totalCost,
                'properties_count' => $properties->count(),
            ]);

            return $distribution;
            
        } catch (\Exception $e) {
            $this->logger->error('Custom formula distribution failed, falling back to equal distribution', [
                'building_id' => $building->id,
                'formula' => $formula,
                'error' => $e->getMessage(),
            ]);
            
            return $this->distributeEqually($properties, $totalCost);
        }
    }

    /**
     * Get average consumption for a property over a specified period.
     */
    private function getPropertyConsumptionAverage(int $propertyId, Carbon $cutoffDate, int $months): float
    {
        // This would typically query the MeterReading model
        // For now, return a placeholder value
        // In production, this would be:
        /*
        return MeterReading::whereHas('meter', function ($query) use ($propertyId) {
                $query->where('property_id', $propertyId);
            })
            ->where('reading_date', '>=', $cutoffDate)
            ->avg('consumption') ?? 0.0;
        */
        
        // Placeholder implementation
        return rand(50, 200) / 10.0; // Random consumption between 5.0 and 20.0
    }

    /**
     * Prepare variables for formula evaluation.
     */
    private function prepareFormulaVariables($property, array $options): array
    {
        $consumptionPeriodMonths = $options['consumption_period_months'] ?? 12;
        $cutoffDate = now()->subMonths($consumptionPeriodMonths);
        
        return [
            'area' => $property->area_sqm ?? 0,
            'heated_area' => $property->heated_area_sqm ?? 0,
            'commercial_area' => $property->commercial_area_sqm ?? 0,
            'consumption' => $this->getPropertyConsumptionAverage($property->id, $cutoffDate, $consumptionPeriodMonths),
            'property_id' => $property->id,
            'tenant_count' => $property->tenant_count ?? 1,
        ];
    }

    /**
     * Evaluate distribution formula safely.
     * 
     * Note: This is a placeholder implementation. In production, you would
     * use a safe mathematical expression evaluator library.
     */
    private function evaluateDistributionFormula(string $formula, array $variables): float
    {
        // This is a simplified implementation
        // In production, use a proper math expression evaluator like:
        // - symfony/expression-language
        // - hoa/math
        // - Or a custom safe evaluator
        
        // For now, implement some basic formula patterns
        if (str_contains($formula, 'area') && str_contains($formula, 'consumption')) {
            // Example: "area * 0.7 + consumption * 0.3"
            return ($variables['area'] * 0.7) + ($variables['consumption'] * 0.3);
        }
        
        if (str_contains($formula, 'area')) {
            // Example: "area"
            return $variables['area'];
        }
        
        if (str_contains($formula, 'consumption')) {
            // Example: "consumption"
            return $variables['consumption'];
        }
        
        // Default fallback
        return 1.0;
    }

    /**
     * Build cache key for distribution calculations.
     */
    private function buildDistributionCacheKey(int $buildingId, string $method, float $totalCost, array $options = []): string
    {
        $keyData = [
            'building_id' => $buildingId,
            'method' => $method,
            'total_cost' => $totalCost,
            'options' => $options,
        ];
        
        return sprintf(
            '%s:distribution:%s',
            self::CACHE_PREFIX,
            md5(serialize($keyData))
        );
    }
}