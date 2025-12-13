<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GyvatukasCalculatorInterface;
use App\Models\Building;
use App\Repositories\BuildingRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Batch processor for gyvatukas calculations with memory optimization.
 */
final readonly class GyvatukasBatchProcessor
{
    public function __construct(
        private GyvatukasCalculatorInterface $calculator,
        private BuildingRepository $buildingRepository,
    ) {}

    /**
     * Process gyvatukas calculations for multiple buildings efficiently.
     * 
     * @param Collection<Building> $buildings
     * @param Carbon $month
     * @return array<int, float> Building ID => Energy mapping
     */
    public function processBatch(Collection $buildings, Carbon $month): array
    {
        $results = [];
        $batchSize = 50; // Process in chunks to manage memory
        
        $buildings->chunk($batchSize)->each(function (Collection $chunk) use ($month, &$results) {
            $chunkResults = $this->processChunk($chunk, $month);
            $results = array_merge($results, $chunkResults);
            
            // Force garbage collection after each chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });

        return $results;
    }

    /**
     * Process a chunk of buildings with transaction safety.
     */
    private function processChunk(Collection $buildings, Carbon $month): array
    {
        $results = [];
        
        DB::transaction(function () use ($buildings, $month, &$results) {
            foreach ($buildings as $building) {
                try {
                    $energy = $this->calculator->calculate($building, $month);
                    $results[$building->id] = $energy;
                } catch (\Exception $e) {
                    Log::error('Batch gyvatukas calculation failed', [
                        'building_id' => $building->id,
                        'month' => $month->format('Y-m'),
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Continue processing other buildings
                    $results[$building->id] = 0.0;
                }
            }
        });

        return $results;
    }

    /**
     * Recalculate summer averages for buildings that need it.
     */
    public function recalculateSummerAverages(int $limit = 100): int
    {
        $buildings = $this->buildingRepository->getBuildingsNeedingSummerAverageRecalculation($limit);
        $processed = 0;

        foreach ($buildings as $building) {
            try {
                $this->calculator->calculateAndStoreSummerAverage($building);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Summer average recalculation failed', [
                    'building_id' => $building->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Summer averages recalculated', [
            'processed' => $processed,
            'total_candidates' => $buildings->count(),
        ]);

        return $processed;
    }

    /**
     * Distribute circulation costs for multiple buildings efficiently.
     */
    public function distributeCostsForBuildings(array $buildingCosts, string $method = 'equal'): array
    {
        $distributions = [];
        
        foreach ($buildingCosts as $buildingId => $totalCost) {
            try {
                $building = $this->buildingRepository->findForGyvatukasCalculation($buildingId);
                
                if ($building) {
                    $distributions[$buildingId] = $this->calculator->distributeCirculationCost(
                        $building,
                        $totalCost,
                        $method
                    );
                }
            } catch (\Exception $e) {
                Log::error('Cost distribution failed', [
                    'building_id' => $buildingId,
                    'total_cost' => $totalCost,
                    'method' => $method,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $distributions;
    }
}