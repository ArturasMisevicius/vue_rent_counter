<?php

namespace App\Console\Commands;

use App\Services\DashboardCacheService;
use Illuminate\Console\Command;

/**
 * Command to warm dashboard caches
 * 
 * This command can be scheduled to run periodically to ensure
 * dashboard metrics are always cached and ready for fast loading
 */
class WarmDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dashboard:warm-cache 
                            {--force : Force cache refresh even if cache exists}';

    /**
     * The console command description.
     */
    protected $description = 'Warm dashboard caches for improved performance';

    /**
     * Execute the console command.
     */
    public function handle(DashboardCacheService $cacheService): int
    {
        $this->info('Warming dashboard caches...');
        
        if ($this->option('force')) {
            $this->info('Force refresh enabled - invalidating existing caches');
            $cacheService->invalidateAll();
        }
        
        $startTime = microtime(true);
        
        try {
            // Warm all caches
            $cacheService->warmCaches();
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            $this->info("Dashboard caches warmed successfully in {$duration}ms");
            
            // Show cache statistics
            $stats = $cacheService->getCacheStats();
            $this->table(
                ['Cache Type', 'Status', 'TTL (seconds)'],
                collect($stats)->map(function ($stat, $name) {
                    return [
                        $name,
                        $stat['exists'] ? '✓ Cached' : '✗ Missing',
                        $stat['ttl']
                    ];
                })->toArray()
            );
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to warm dashboard caches: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}