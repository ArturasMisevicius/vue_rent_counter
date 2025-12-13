<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Optimized repository for Building queries with gyvatukas calculations.
 */
final readonly class BuildingRepository
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Find building with minimal data for gyvatukas calculations.
     */
    public function findForGyvatukasCalculation(int $id): ?Building
    {
        return Cache::remember(
            "building:gyvatukas:{$id}",
            self::CACHE_TTL,
            fn () => Building::forGyvatukasCalculation()->find($id)
        );
    }

    /**
     * Get buildings needing summer average recalculation.
     */
    public function getBuildingsNeedingSummerAverageRecalculation(int $limit = 100): Collection
    {
        return Building::needingSummerAverageRecalculation()
            ->forGyvatukasCalculation()
            ->limit($limit)
            ->get();
    }

    /**
     * Get buildings with valid summer averages for batch processing.
     */
    public function getBuildingsWithValidSummerAverage(int $limit = 100): Collection
    {
        return Building::withValidSummerAverage()
            ->forGyvatukasCalculation()
            ->limit($limit)
            ->get();
    }

    /**
     * Get building properties for cost distribution (optimized query).
     */
    public function getBuildingPropertiesForDistribution(int $buildingId, string $method = 'equal'): Collection
    {
        $cacheKey = "building:properties:{$buildingId}:{$method}";
        
        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($buildingId, $method) {
                $columns = $method === 'area' ? ['id', 'area_sqm'] : ['id'];
                
                return Building::find($buildingId)
                    ?->properties()
                    ->select($columns)
                    ->get() ?? collect();
            }
        );
    }

    /**
     * Clear building cache when data changes.
     */
    public function clearBuildingCache(int $buildingId): void
    {
        Cache::forget("building:gyvatukas:{$buildingId}");
        Cache::forget("building:properties:{$buildingId}:equal");
        Cache::forget("building:properties:{$buildingId}:area");
    }
}