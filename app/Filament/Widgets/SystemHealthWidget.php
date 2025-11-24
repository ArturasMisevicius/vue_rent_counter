<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        // Cache for 30 seconds as per requirements
        $stats = Cache::remember('superadmin.system_health', 30, function () {
            return [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'queue' => $this->checkQueueHealth(),
                'storage' => $this->checkStorageHealth(),
            ];
        });

        return [
            Stat::make('Database', $stats['database']['status'])
                ->description($stats['database']['message'])
                ->descriptionIcon($stats['database']['icon'])
                ->color($stats['database']['color']),

            Stat::make('Cache', $stats['cache']['status'])
                ->description($stats['cache']['message'])
                ->descriptionIcon($stats['cache']['icon'])
                ->color($stats['cache']['color']),

            Stat::make('Queue', $stats['queue']['status'])
                ->description($stats['queue']['message'])
                ->descriptionIcon($stats['queue']['icon'])
                ->color($stats['queue']['color']),

            Stat::make('Storage', $stats['storage']['status'])
                ->description($stats['storage']['message'])
                ->descriptionIcon($stats['storage']['icon'])
                ->color($stats['storage']['color']),
        ];
    }

    protected function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            $tableCount = count(DB::select('SELECT name FROM sqlite_master WHERE type="table"'));
            
            return [
                'status' => 'Connected',
                'message' => "{$tableCount} tables",
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'Disconnected',
                'message' => 'Connection failed',
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
            ];
        }
    }

    protected function checkCacheHealth(): array
    {
        try {
            Cache::put('health_check', true, 10);
            $working = Cache::get('health_check');
            
            return [
                'status' => $working ? 'Operational' : 'Issues',
                'message' => $working ? 'Cache working' : 'Cache not responding',
                'icon' => $working ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle',
                'color' => $working ? 'success' : 'warning',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'Error',
                'message' => 'Cache unavailable',
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
            ];
        }
    }

    protected function checkQueueHealth(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            
            if ($failedJobs === 0) {
                return [
                    'status' => 'Healthy',
                    'message' => 'No failed jobs',
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                ];
            } elseif ($failedJobs < 10) {
                return [
                    'status' => 'Warning',
                    'message' => "{$failedJobs} failed jobs",
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'color' => 'warning',
                ];
            } else {
                return [
                    'status' => 'Critical',
                    'message' => "{$failedJobs} failed jobs",
                    'icon' => 'heroicon-o-x-circle',
                    'color' => 'danger',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'Unknown',
                'message' => 'Cannot check queue',
                'icon' => 'heroicon-o-question-mark-circle',
                'color' => 'gray',
            ];
        }
    }

    protected function checkStorageHealth(): array
    {
        try {
            $dbPath = database_path('database.sqlite');
            $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
            $dbSizeMB = round($dbSize / 1024 / 1024, 2);
            
            $diskFree = disk_free_space(database_path());
            $diskFreeMB = round($diskFree / 1024 / 1024, 2);
            
            if ($diskFreeMB < 100) {
                return [
                    'status' => 'Low Space',
                    'message' => "{$diskFreeMB} MB free",
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'color' => 'warning',
                ];
            }
            
            return [
                'status' => 'Healthy',
                'message' => "DB: {$dbSizeMB} MB",
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'Unknown',
                'message' => 'Cannot check storage',
                'icon' => 'heroicon-o-question-mark-circle',
                'color' => 'gray',
            ];
        }
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
