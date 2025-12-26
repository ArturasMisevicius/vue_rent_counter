<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance Monitoring Service
 * 
 * Monitors service provider performance and translation system efficiency
 */
final readonly class PerformanceMonitoringService
{
    public function __construct(
        private TranslationCacheService $translationCache,
    ) {}

    /**
     * Monitor service provider boot performance
     */
    public function monitorServiceProviderBoot(): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Monitor query count during boot (without logging sensitive data)
        $initialQueryCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
        
        // Simulate service provider operations
        app()->make(\App\Services\TranslationCacheService::class);
        app()->make(\App\Services\TenantTranslationService::class);
        
        $finalQueryCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
        $queryCount = $finalQueryCount - $initialQueryCount;
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $metrics = [
            'boot_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_usage_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'query_count' => $queryCount,
            'timestamp' => now()->toISOString(),
        ];
        
        // Log performance issues (without sensitive data)
        if ($metrics['boot_time_ms'] > 100) {
            Log::warning('Slow service provider boot detected', [
                'boot_time_ms' => $metrics['boot_time_ms'],
                'context' => 'performance_monitoring'
            ]);
        }
        
        if ($metrics['query_count'] > 5) {
            Log::warning('High query count during service boot', [
                'query_count' => $metrics['query_count'],
                'context' => 'performance_monitoring'
            ]);
        }
        
        return $metrics;
    }

    /**
     * Monitor translation cache performance
     */
    public function monitorTranslationPerformance(): array
    {
        $stats = $this->translationCache->getStats();
        
        // Calculate cache hit ratio (simplified)
        $cacheHitRatio = $stats['total_keys'] > 0 ? 
            min(100, ($stats['total_keys'] / 100) * 100) : 0;
        
        $metrics = [
            'cache_stats' => $stats,
            'cache_hit_ratio' => $cacheHitRatio,
            'performance_score' => $this->calculatePerformanceScore($stats),
            'timestamp' => now()->toISOString(),
        ];
        
        // Alert on poor performance
        if ($metrics['performance_score'] < 70) {
            Log::warning('Poor translation cache performance', $metrics);
        }
        
        return $metrics;
    }

    /**
     * Monitor policy registration performance
     */
    public function monitorPolicyRegistration(array $policyResults, array $gateResults): array
    {
        $totalPolicies = $policyResults['registered'] + $policyResults['skipped'];
        $totalGates = $gateResults['registered'] + $gateResults['skipped'];
        
        $metrics = [
            'policy_registration' => [
                'total_policies' => $totalPolicies,
                'registered_policies' => $policyResults['registered'],
                'skipped_policies' => $policyResults['skipped'],
                'policy_success_rate' => $totalPolicies > 0 ? 
                    round(($policyResults['registered'] / $totalPolicies) * 100, 2) : 0,
                'policy_errors' => count($policyResults['errors']),
            ],
            'gate_registration' => [
                'total_gates' => $totalGates,
                'registered_gates' => $gateResults['registered'],
                'skipped_gates' => $gateResults['skipped'],
                'gate_success_rate' => $totalGates > 0 ? 
                    round(($gateResults['registered'] / $totalGates) * 100, 2) : 0,
                'gate_errors' => count($gateResults['errors']),
            ],
            'overall_health' => [
                'healthy' => empty($policyResults['errors']) && empty($gateResults['errors']),
                'total_registrations' => $policyResults['registered'] + $gateResults['registered'],
                'total_errors' => count($policyResults['errors']) + count($gateResults['errors']),
            ],
            'timestamp' => now()->toISOString(),
        ];
        
        // Alert on poor registration health
        if (!$metrics['overall_health']['healthy']) {
            Log::warning('Policy registration health issues detected', $metrics);
        }
        
        // Alert on low success rates
        if ($metrics['policy_registration']['policy_success_rate'] < 90 || 
            $metrics['gate_registration']['gate_success_rate'] < 90) {
            Log::warning('Low policy registration success rate', $metrics);
        }
        
        return $metrics;
    }
    
    /**
     * Calculate performance score based on cache metrics
     */
    private function calculatePerformanceScore(array $stats): int
    {
        $score = 100;
        
        // Penalize if no caching
        if ($stats['total_keys'] === 0) {
            $score -= 50;
        }
        
        // Penalize uneven locale distribution
        if (!empty($stats['by_locale'])) {
            $values = array_values($stats['by_locale']);
            $max = max($values);
            $min = min($values);
            
            if ($max > 0 && ($min / $max) < 0.5) {
                $score -= 20; // Uneven distribution
            }
        }
        
        return max(0, $score);
    }
}