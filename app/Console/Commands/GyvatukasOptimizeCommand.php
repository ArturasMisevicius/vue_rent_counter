<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GyvatukasBatchProcessor;
use App\Services\GyvatukasCalculator;
use App\Repositories\BuildingRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GyvatukasOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gyvatukas:optimize 
                            {--recalculate-averages : Recalculate summer averages for buildings that need it}
                            {--warm-cache : Warm cache for current and next month calculations}
                            {--clear-cache : Clear all gyvatukas cache}
                            {--limit=100 : Limit number of buildings to process}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize gyvatukas calculations through cache warming and batch processing';

    public function __construct(
        private readonly GyvatukasBatchProcessor $batchProcessor,
        private readonly GyvatukasCalculator $calculator,
        private readonly BuildingRepository $buildingRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting gyvatukas optimization...');

        if ($this->option('clear-cache')) {
            $this->clearCache();
        }

        if ($this->option('recalculate-averages')) {
            $this->recalculateSummerAverages();
        }

        if ($this->option('warm-cache')) {
            $this->warmCache();
        }

        $this->info('Gyvatukas optimization completed successfully!');
        return self::SUCCESS;
    }

    /**
     * Clear all gyvatukas cache.
     */
    private function clearCache(): void
    {
        $this->info('Clearing gyvatukas cache...');
        
        $this->calculator->clearAllCache();
        
        $this->info('✓ Cache cleared');
    }

    /**
     * Recalculate summer averages for buildings that need it.
     */
    private function recalculateSummerAverages(): void
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Recalculating summer averages (limit: {$limit})...");
        
        $processed = $this->batchProcessor->recalculateSummerAverages($limit);
        
        $this->info("✓ Recalculated summer averages for {$processed} buildings");
    }

    /**
     * Warm cache for current and next month calculations.
     */
    private function warmCache(): void
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Warming cache for calculations (limit: {$limit})...");
        
        $buildings = $this->buildingRepository->getBuildingsWithValidSummerAverage($limit);
        
        if ($buildings->isEmpty()) {
            $this->warn('No buildings with valid summer averages found');
            return;
        }

        $months = [
            Carbon::now()->startOfMonth(),
            Carbon::now()->addMonth()->startOfMonth(),
        ];

        $totalCalculations = 0;

        foreach ($months as $month) {
            $this->info("Warming cache for {$month->format('Y-m')}...");
            
            $results = $this->batchProcessor->processBatch($buildings, $month);
            $totalCalculations += count($results);
            
            $this->info("✓ Processed {$buildings->count()} buildings for {$month->format('Y-m')}");
        }

        $this->info("✓ Cache warmed with {$totalCalculations} calculations");
    }
}
