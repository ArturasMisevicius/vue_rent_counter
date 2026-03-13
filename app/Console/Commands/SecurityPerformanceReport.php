<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Security\SecurityPerformanceMonitor;
use Illuminate\Console\Command;

/**
 * Security Performance Report Command
 * 
 * Displays current security header performance metrics
 * and health status.
 */
final class SecurityPerformanceReport extends Command
{
    protected $signature = 'security:performance 
                           {--reset : Reset performance metrics after displaying}
                           {--json : Output in JSON format}';

    protected $description = 'Display security header performance metrics';

    public function handle(SecurityPerformanceMonitor $monitor): int
    {
        $summary = $monitor->getSummary();
        $isHealthy = $monitor->isPerformanceHealthy();

        if ($this->option('json')) {
            $this->line(json_encode([
                'healthy' => $isHealthy,
                'summary' => $summary,
            ], JSON_PRETTY_PRINT));
            
            return Command::SUCCESS;
        }

        $this->displayReport($summary, $isHealthy);

        if ($this->option('reset')) {
            $monitor->resetMetrics();
            $this->info('Performance metrics have been reset.');
        }

        return Command::SUCCESS;
    }

    private function displayReport(array $summary, bool $isHealthy): void
    {
        $this->info('Security Headers Performance Report');
        $this->line('=====================================');
        $this->newLine();

        // Health status
        if ($isHealthy) {
            $this->info('✓ Performance Status: HEALTHY');
        } else {
            $this->error('✗ Performance Status: DEGRADED');
        }

        $this->newLine();

        // Cache statistics
        if (isset($summary['cache_stats'])) {
            $stats = $summary['cache_stats'];
            $this->info('Cache Performance:');
            $this->line("  Hits: {$stats['hits']}");
            $this->line("  Misses: {$stats['misses']}");
            $this->line("  Hit Rate: {$stats['hit_rate']}%");
            $this->newLine();
        }

        // Operations performance
        if (empty($summary['operations'])) {
            $this->warn('No performance data available. Run some requests first.');
            return;
        }

        $this->info('Operation Performance:');
        $this->table(
            ['Operation', 'Count', 'Avg (ms)', 'Min (ms)', 'Max (ms)', 'Total (ms)', 'Last Recorded'],
            collect($summary['operations'])->map(function ($data, $operation) {
                return [
                    $operation,
                    $data['count'],
                    $data['avg_time_ms'],
                    $data['min_time_ms'],
                    $data['max_time_ms'],
                    $data['total_time_ms'],
                    $data['last_recorded'] ? \Carbon\Carbon::parse($data['last_recorded'])->diffForHumans() : 'Never',
                ];
            })->toArray()
        );

        $this->newLine();
        $this->line("Last Reset: " . \Carbon\Carbon::parse($summary['last_reset'])->diffForHumans());
    }
}