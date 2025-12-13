<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Widgets;

use App\Models\User;
use App\Contracts\SystemMonitoringInterface;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SystemMetricsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    public function __construct(
        private readonly SystemMonitoringInterface $systemMonitoring,
    ) {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $metrics = Cache::remember('superadmin.system_metrics', 300, function () {
            $totalUsers = User::count();
            $activeSessions = $this->getActiveSessionsCount();
            $apiCallsToday = $this->getApiCallsToday();
            $storageUsed = $this->getStorageUsed();

            return [
                'total_users' => $totalUsers,
                'active_sessions' => $activeSessions,
                'api_calls_today' => $apiCallsToday,
                'storage_used' => $storageUsed,
            ];
        });

        return [
            Stat::make(__('superadmin.dashboard.widgets.system_metrics.total_users'), number_format($metrics['total_users']))
                ->description(__('superadmin.dashboard.widgets.system_metrics.total_users'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart($this->getUserGrowthData()),

            Stat::make(__('superadmin.dashboard.widgets.system_metrics.active_sessions'), number_format($metrics['active_sessions']))
                ->description(__('superadmin.dashboard.widgets.system_metrics.active_sessions'))
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('success'),

            Stat::make(__('superadmin.dashboard.widgets.system_metrics.api_calls_today'), number_format($metrics['api_calls_today']))
                ->description(__('superadmin.dashboard.widgets.system_metrics.api_calls_today'))
                ->descriptionIcon('heroicon-m-bolt')
                ->color('info'),

            Stat::make(__('superadmin.dashboard.widgets.system_metrics.storage_used'), $this->formatBytes($metrics['storage_used']))
                ->description(__('superadmin.dashboard.widgets.system_metrics.storage_used'))
                ->descriptionIcon('heroicon-m-server-stack')
                ->color('warning'),
        ];
    }

    private function getActiveSessionsCount(): int
    {
        try {
            return DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
                ->count();
        } catch (\Exception) {
            return 0;
        }
    }

    private function getApiCallsToday(): int
    {
        // This would typically come from your API logging system
        // For now, we'll use a placeholder implementation
        return Cache::remember('api_calls_today', 3600, function () {
            // You could integrate with Laravel Telescope, custom logging, or API gateway metrics
            return rand(1000, 5000); // Placeholder
        });
    }

    private function getStorageUsed(): int
    {
        try {
            // Get storage usage from storage disk
            $storagePath = storage_path('app');
            if (is_dir($storagePath)) {
                return $this->getDirectorySize($storagePath);
            }
            return 0;
        } catch (\Exception) {
            return 0;
        }
    }

    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function getUserGrowthData(): array
    {
        return Cache::remember('superadmin.user_growth_data', 3600, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->startOfDay();
                $count = User::where('created_at', '<=', $date->endOfDay())->count();
                $data[] = $count;
            }
            return $data;
        });
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public static function canView(): bool
    {
        return auth()->user()?->is_super_admin ?? false;
    }
}