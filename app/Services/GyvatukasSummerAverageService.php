<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Building;
use App\ValueObjects\CalculationResult;
use App\ValueObjects\SummerPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for calculating summer average gyvatukas for buildings.
 * 
 * This service handles the business logic for calculating and storing
 * summer average circulation fees, which are used as norms during
 * the heating season.
 * 
 * Requirements: 4.4
 */
final class GyvatukasSummerAverageService
{
    /**
     * Calculate summer average for a single building.
     *
     * @param Building $building The building to calculate for
     * @param SummerPeriod $period The summer period
     * @param bool $force Force recalculation even if already calculated
     * @return CalculationResult The result of the calculation
     */
    public function calculateForBuilding(
        Building $building,
        SummerPeriod $period,
        bool $force = false
    ): CalculationResult {
        try {
            // Check if already calculated for this year
            if (!$force && $this->isAlreadyCalculated($building, $period->year)) {
                return CalculationResult::skipped(
                    $building,
                    "Already calculated for {$period->year}"
                );
            }

            // Perform calculation within a transaction
            $average = DB::transaction(function () use ($building, $period) {
                return $building->calculateSummerAverage(
                    $period->startDate,
                    $period->endDate
                );
            });

            // Log successful calculation
            $this->logCalculation($building, $period->year, $average);

            return CalculationResult::success($building, $average);
        } catch (\Exception $e) {
            // Log error
            $this->logError($building, $period->year, $e);

            return CalculationResult::failed($building, $e->getMessage());
        }
    }

    /**
     * Calculate summer average for multiple buildings.
     *
     * @param Collection<Building> $buildings The buildings to calculate for
     * @param SummerPeriod $period The summer period
     * @param bool $force Force recalculation
     * @return Collection<CalculationResult> Collection of calculation results
     */
    public function calculateForBuildings(
        Collection $buildings,
        SummerPeriod $period,
        bool $force = false
    ): Collection {
        return $buildings->map(function (Building $building) use ($period, $force) {
            return $this->calculateForBuilding($building, $period, $force);
        });
    }

    /**
     * Calculate summer average for all buildings using chunked processing.
     *
     * @param SummerPeriod $period The summer period
     * @param bool $force Force recalculation
     * @param int $chunkSize Number of buildings to process at once
     * @param callable|null $callback Optional callback for progress tracking
     * @return array{success: int, skipped: int, failed: int, results: Collection<CalculationResult>}
     */
    public function calculateForAllBuildings(
        SummerPeriod $period,
        bool $force = false,
        int $chunkSize = 100,
        ?callable $callback = null
    ): array {
        $results = collect();
        $stats = ['success' => 0, 'skipped' => 0, 'failed' => 0];

        // Eager load relationships to prevent N+1 queries
        // Only select necessary columns to reduce memory usage
        Building::query()
            ->select(['id', 'tenant_id', 'name', 'address', 'gyvatukas_summer_average', 'gyvatukas_last_calculated'])
            ->with([
                'properties:id,building_id,area_sqm',
                'properties.meters' => function ($query) {
                    $query->select('id', 'property_id', 'type')
                          ->whereIn('type', [\App\Enums\MeterType::HEATING, \App\Enums\MeterType::WATER_HOT]);
                }
            ])
            ->chunk($chunkSize, function (Collection $buildings) use (
                $period,
                $force,
                $callback,
                &$results,
                &$stats
            ) {
                foreach ($buildings as $building) {
                    $result = $this->calculateForBuilding($building, $period, $force);
                    $results->push($result);

                    // Update statistics
                    match ($result->status) {
                        'success' => $stats['success']++,
                        'skipped' => $stats['skipped']++,
                        'failed' => $stats['failed']++,
                        default => null,
                    };

                    // Call progress callback if provided
                    if ($callback !== null) {
                        $callback($result);
                    }
                }
                
                // Clear calculator cache after each chunk to prevent memory buildup
                $calculator = app(\App\Services\GyvatukasCalculator::class);
                $calculator->clearCache();
            });

        return [
            'success' => $stats['success'],
            'skipped' => $stats['skipped'],
            'failed' => $stats['failed'],
            'results' => $results,
        ];
    }

    /**
     * Calculate for a specific building by ID.
     *
     * @param int $buildingId The building ID
     * @param SummerPeriod $period The summer period
     * @param bool $force Force recalculation
     * @return CalculationResult|null The result or null if building not found
     */
    public function calculateForBuildingId(
        int $buildingId,
        SummerPeriod $period,
        bool $force = false
    ): ?CalculationResult {
        // Eager load relationships to prevent N+1 queries
        $building = Building::query()
            ->select(['id', 'tenant_id', 'name', 'address', 'gyvatukas_summer_average', 'gyvatukas_last_calculated'])
            ->with([
                'properties:id,building_id,area_sqm',
                'properties.meters' => function ($query) {
                    $query->select('id', 'property_id', 'type')
                          ->whereIn('type', [\App\Enums\MeterType::HEATING, \App\Enums\MeterType::WATER_HOT]);
                }
            ])
            ->find($buildingId);

        if ($building === null) {
            return null;
        }

        return $this->calculateForBuilding($building, $period, $force);
    }

    /**
     * Check if a building already has a calculation for the given year.
     */
    private function isAlreadyCalculated(Building $building, int $year): bool
    {
        if ($building->gyvatukas_last_calculated === null) {
            return false;
        }

        $lastCalculatedYear = Carbon::parse($building->gyvatukas_last_calculated)->year;

        return $lastCalculatedYear === $year;
    }

    /**
     * Log successful calculation.
     */
    private function logCalculation(Building $building, int $year, float $average): void
    {
        if (!config('gyvatukas.audit.enabled', true)) {
            return;
        }

        Log::info('Summer average calculated for building', [
            'building_id' => $building->id,
            'building_name' => $building->display_name,
            'year' => $year,
            'average' => $average,
            'calculated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log calculation error.
     */
    private function logError(Building $building, int $year, \Exception $exception): void
    {
        Log::error('Failed to calculate summer average for building', [
            'building_id' => $building->id,
            'building_name' => $building->display_name,
            'year' => $year,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
