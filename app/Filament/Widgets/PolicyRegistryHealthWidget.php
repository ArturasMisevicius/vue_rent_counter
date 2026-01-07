<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\PolicyRegistryMonitoringService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

/**
 * Policy Registry Health Widget
 * 
 * Displays comprehensive health metrics for the policy registry system in the superadmin dashboard.
 * Monitors policy registration performance, cache efficiency, error rates, and overall system health.
 * 
 * Key Features:
 * - Real-time health status monitoring with color-coded indicators
 * - Policy and gate registration counts
 * - Cache hit rate performance metrics
 * - Average registration time tracking
 * - 24-hour error rate monitoring
 * - Automatic fallback to fresh health checks when cache is unavailable
 * 
 * The widget polls every 30 seconds and is restricted to super_admin users only.
 * Health data is sourced from PolicyRegistryMonitoringService with graceful error handling.
 * 
 * @package App\Filament\Widgets
 * @see \App\Services\PolicyRegistryMonitoringService
 * @see \App\Contracts\ServiceRegistration\PolicyRegistryInterface
 */
final class PolicyRegistryHealthWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';
    protected static bool $isLazy = false;
    protected static ?int $sort = 100;
    
    public function __construct(
        private readonly PolicyRegistryMonitoringService $monitoringService
    ) {
        parent::__construct();
    }

    /**
     * Determine if the widget can be viewed by the current user
     * 
     * Restricts widget visibility to authenticated super_admin users only.
     * This ensures sensitive policy registry metrics are only accessible
     * to users with appropriate system-level permissions.
     * 
     * @return bool True if user is authenticated and has super_admin role
     */
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    /**
     * Get the stats for the widget
     * 
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        try {
            $healthData = $this->getHealthData();
            
            if (!$this->isValidHealthData($healthData)) {
                return $this->getErrorStats();
            }

            return $this->buildHealthStats($healthData);
            
        } catch (\Throwable $e) {
            Log::error('PolicyRegistryHealthWidget: Failed to load health data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->getErrorStats();
        }
    }

    /**
     * Get health data from monitoring service
     * 
     * Attempts to retrieve cached health data first for performance,
     * falling back to a fresh health check if cache is unavailable.
     * This two-tier approach balances performance with data freshness.
     * 
     * @return array|null Health data array or null if unavailable
     */
    private function getHealthData(): ?array
    {
        // Try cached data first
        $cachedData = $this->monitoringService->getLastHealthCheck();
        
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Fallback to fresh health check
        return $this->monitoringService->healthCheck();
    }

    /**
     * Validate health data structure
     * 
     * Ensures the health data contains all required keys with correct types
     * before attempting to build stats. Prevents runtime errors from
     * malformed or incomplete monitoring service responses.
     * 
     * @param array|null $data Health data to validate
     * @return bool True if data structure is valid
     */
    private function isValidHealthData(?array $data): bool
    {
        return $data !== null 
            && isset($data['healthy'], $data['metrics'], $data['issues'])
            && is_bool($data['healthy'])
            && is_array($data['metrics'])
            && is_array($data['issues']);
    }

    /**
     * Build stats array from health data
     * 
     * @return array<Stat>
     */
    private function buildHealthStats(array $healthData): array
    {
        $metrics = $healthData['metrics'];
        $healthy = $healthData['healthy'];
        $issues = $healthData['issues'];

        return [
            $this->buildHealthStatusStat($healthy, $issues),
            $this->buildPoliciesStat($metrics),
            $this->buildGatesStat($metrics),
            $this->buildCacheHitRateStat($metrics),
            $this->buildPerformanceStat($metrics),
            $this->buildErrorRateStat($metrics),
        ];
    }

    /**
     * Build health status stat
     */
    private function buildHealthStatusStat(bool $healthy, array $issues): Stat
    {
        $criticalCount = count($issues['critical'] ?? []);
        $warningCount = count($issues['warnings'] ?? []);

        if (!$healthy || $criticalCount > 0) {
            $description = $criticalCount > 0 
                ? __('app.widgets.policy_registry.critical_issues', ['count' => $criticalCount])
                : __('app.widgets.policy_registry.configuration_issues');
            
            return Stat::make(__('app.widgets.policy_registry.health_status'), __('app.status.unhealthy'))
                ->description($description)
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger');
        }

        if ($warningCount > 0) {
            return Stat::make(__('app.widgets.policy_registry.health_status'), __('app.status.healthy'))
                ->description(__('app.widgets.policy_registry.warnings', ['count' => $warningCount]))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning');
        }

        return Stat::make(__('app.widgets.policy_registry.health_status'), __('app.widgets.policy_registry.all_systems_operational'))
            ->description(__('app.widgets.policy_registry.last_24h'))
            ->descriptionIcon('heroicon-o-check-circle')
            ->color('success');
    }

    /**
     * Build policies count stat
     */
    private function buildPoliciesStat(array $metrics): Stat
    {
        $count = $metrics['total_policies'] ?? 0;
        
        return Stat::make(__('app.widgets.policy_registry.total_policies'), (string) $count)
            ->description(__('app.widgets.policy_registry.registered_policies'))
            ->descriptionIcon('heroicon-o-shield-check')
            ->color('primary');
    }

    /**
     * Build gates count stat
     */
    private function buildGatesStat(array $metrics): Stat
    {
        $count = $metrics['total_gates'] ?? 0;
        
        return Stat::make(__('app.widgets.policy_registry.total_gates'), (string) $count)
            ->description(__('app.widgets.policy_registry.registered_gates'))
            ->descriptionIcon('heroicon-o-key')
            ->color('primary');
    }

    /**
     * Build cache hit rate stat
     */
    private function buildCacheHitRateStat(array $metrics): Stat
    {
        $hitRate = $metrics['cache_hit_rate'] ?? 0.0;
        $percentage = $this->formatPercentage($hitRate);
        
        $color = match (true) {
            $hitRate >= 0.9 => 'success',
            $hitRate >= 0.8 => 'warning',
            default => 'danger',
        };

        return Stat::make(__('app.widgets.policy_registry.cache_hit_rate'), $percentage)
            ->description(__('app.widgets.policy_registry.cache_performance'))
            ->descriptionIcon('heroicon-o-bolt')
            ->color($color);
    }

    /**
     * Build performance stat
     */
    private function buildPerformanceStat(array $metrics): Stat
    {
        $avgTime = $metrics['average_registration_time'] ?? 0.0;
        $formatted = $this->formatDuration($avgTime);
        
        $color = match (true) {
            $avgTime <= 50 => 'success',
            $avgTime <= 100 => 'warning',
            default => 'danger',
        };

        return Stat::make(__('app.widgets.policy_registry.avg_registration_time'), $formatted)
            ->description(__('app.widgets.policy_registry.performance_metric'))
            ->descriptionIcon('heroicon-o-clock')
            ->color($color);
    }

    /**
     * Build error rate stat
     */
    private function buildErrorRateStat(array $metrics): Stat
    {
        $errorRate = $metrics['error_rate_24h'] ?? 0.0;
        $percentage = $this->formatPercentage($errorRate);
        
        $color = match (true) {
            $errorRate <= 0.01 => 'success', // <= 1%
            $errorRate <= 0.05 => 'warning', // <= 5%
            default => 'danger',
        };

        return Stat::make(__('app.widgets.policy_registry.error_rate'), $percentage)
            ->description(__('app.widgets.policy_registry.last_24h'))
            ->descriptionIcon('heroicon-o-exclamation-circle')
            ->color($color);
    }

    /**
     * Get error stats when health data is unavailable
     * 
     * @return array<Stat>
     */
    private function getErrorStats(): array
    {
        return [
            Stat::make(__('app.widgets.policy_registry.health_status'), __('app.widgets.policy_registry.data_unavailable'))
                ->description(__('app.widgets.policy_registry.data_unavailable'))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    /**
     * Format duration in milliseconds to human-readable format
     * 
     * Converts millisecond values to appropriate time units:
     * - < 1ms: "< 1ms"
     * - < 1000ms: "XXXms" 
     * - >= 1000ms: "X.XXs"
     * 
     * @param float $milliseconds Duration in milliseconds
     * @return string Formatted duration string
     */
    private function formatDuration(float $milliseconds): string
    {
        if ($milliseconds < 1) {
            return '< 1ms';
        }
        
        if ($milliseconds < 1000) {
            return number_format($milliseconds, 0) . 'ms';
        }
        
        return number_format($milliseconds / 1000, 2) . 's';
    }

    /**
     * Format decimal to percentage string
     * 
     * Converts decimal values (0.0-1.0) to percentage format
     * with one decimal place precision (e.g., 0.95 → "95.0%").
     * 
     * @param float $decimal Decimal value between 0.0 and 1.0
     * @return string Formatted percentage string
     */
    private function formatPercentage(float $decimal): string
    {
        return number_format($decimal * 100, 1) . '%';
    }
}