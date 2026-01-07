<?php

declare(strict_types=1);

namespace App\Filament\Superadmin\Widgets;

use App\Models\User;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * System Overview Widget
 * 
 * Provides key system metrics for superadmin dashboard including
 * user counts, subscription status, and system health indicators.
 * 
 * @package App\Filament\Superadmin\Widgets
 */
final class SystemOverviewWidget extends BaseWidget
{
    /**
     * Widget column span configuration.
     * 
     * @var string
     */
    protected int | string | array $columnSpan = 'full';

    /**
     * Widget sort order.
     * 
     * @var int
     */
    protected static ?int $sort = 1;

    /**
     * Get the stats for the widget.
     * 
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make(__('app.labels.total_users'), $this->getTotalUsers())
                ->description(__('app.labels.system_wide_users'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make(__('app.labels.active_subscriptions'), $this->getActiveSubscriptions())
                ->description(__('app.labels.currently_active'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('app.labels.total_organizations'), $this->getTotalOrganizations())
                ->description(__('app.labels.registered_organizations'))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make(__('app.labels.system_health'), $this->getSystemHealthScore())
                ->description(__('app.labels.overall_system_status'))
                ->descriptionIcon('heroicon-m-heart')
                ->color($this->getSystemHealthColor()),
        ];
    }

    /**
     * Get total number of users across all organizations.
     * 
     * @return int
     */
    private function getTotalUsers(): int
    {
        return Cache::remember(
            'superadmin.stats.total_users',
            300, // 5 minutes
            fn () => User::count()
        );
    }

    /**
     * Get number of active subscriptions.
     * 
     * @return int
     */
    private function getActiveSubscriptions(): int
    {
        return Cache::remember(
            'superadmin.stats.active_subscriptions',
            300, // 5 minutes
            fn () => Subscription::where('status', 'active')->count()
        );
    }

    /**
     * Get total number of organizations.
     * 
     * @return int
     */
    private function getTotalOrganizations(): int
    {
        return Cache::remember(
            'superadmin.stats.total_organizations',
            300, // 5 minutes
            function () {
                // For now, return a placeholder count
                // This will be replaced with actual organization model when implemented
                return 0;
            }
        );
    }

    /**
     * Get system health score as a percentage.
     * 
     * @return string
     */
    private function getSystemHealthScore(): string
    {
        $healthScore = Cache::remember(
            'superadmin.stats.system_health',
            60, // 1 minute for health checks
            function () {
                $checks = [
                    'database' => $this->checkDatabaseHealth(),
                    'cache' => $this->checkCacheHealth(),
                    'storage' => $this->checkStorageHealth(),
                ];

                $passedChecks = array_sum($checks);
                $totalChecks = count($checks);

                return round(($passedChecks / $totalChecks) * 100);
            }
        );

        return $healthScore . '%';
    }

    /**
     * Get system health color based on score.
     * 
     * @return string
     */
    private function getSystemHealthColor(): string
    {
        $score = (int) str_replace('%', '', $this->getSystemHealthScore());

        return match (true) {
            $score >= 90 => 'success',
            $score >= 70 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Check database connectivity and health.
     * 
     * @return int 1 if healthy, 0 if not
     */
    private function checkDatabaseHealth(): int
    {
        try {
            DB::connection()->getPdo();
            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check cache system health.
     * 
     * @return int 1 if healthy, 0 if not
     */
    private function checkCacheHealth(): int
    {
        try {
            Cache::put('health_check', 'ok', 10);
            $result = Cache::get('health_check');
            Cache::forget('health_check');
            
            return $result === 'ok' ? 1 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check storage system health.
     * 
     * @return int 1 if healthy, 0 if not
     */
    private function checkStorageHealth(): int
    {
        try {
            $testFile = storage_path('app/health_check.txt');
            file_put_contents($testFile, 'health check');
            $content = file_get_contents($testFile);
            unlink($testFile);
            
            return $content === 'health check' ? 1 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}