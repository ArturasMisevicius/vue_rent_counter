<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class SystemHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = null;

    protected string $view = 'filament.pages.system-health';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperadmin(), 403);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('system_health.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('system_health.navigation.label');
    }

    public function getTitle(): string
    {
        return __('system_health.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runHealthCheck')
                ->label(__('system_health.actions.run_health_check'))
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    Cache::forget('system_health_database');
                    Cache::forget('system_health_backup');
                    Cache::forget('system_health_queue');
                    Cache::forget('system_health_storage');
                    Cache::forget('system_health_cache');

                    Notification::make()
                        ->title(__('system_health.notifications.health_check_completed'))
                        ->success()
                        ->send();
                }),

            Action::make('triggerBackup')
                ->label(__('system_health.actions.trigger_manual_backup'))
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        Artisan::call('backup:run');
                        
                        Notification::make()
                            ->title(__('system_health.notifications.backup_started'))
                            ->body(__('system_health.messages.backup_started_body'))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('system_health.notifications.backup_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('clearCache')
                ->label(__('system_health.actions.clear_cache'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    Cache::flush();
                    
                    Notification::make()
                        ->title(__('system_health.notifications.cache_cleared'))
                        ->success()
                        ->send();
                }),

            Action::make('downloadDiagnostic')
                ->label(__('system_health.actions.download_diagnostic_report'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $report = $this->generateDiagnosticReport();
                    
                    return response()->streamDownload(function () use ($report) {
                        echo $report;
                    }, 'diagnostic-report-' . now()->format('Y-m-d-His') . '.txt');
                }),
        ];
    }

    public function getDatabaseHealth(): array
    {
        return Cache::remember('system_health_database', 60, function () {
            try {
                $connection = DB::connection();
                $pdo = $connection->getPdo();
                
                // Get connection status
                $status = 'connected';
                $color = 'success';
                
                // Get table count
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tableCount = count($tables);
                
                // Get database size
                $dbPath = database_path('database.sqlite');
                $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
                $dbSizeMB = round($dbSize / 1024 / 1024, 2);
                
                // Get table sizes
                $tableSizes = [];
                foreach ($tables as $table) {
                    $tableName = $table->name;
                    $count = DB::table($tableName)->count();
                    $tableSizes[] = [
                        'name' => $tableName,
                        'rows' => $count,
                    ];
                }
                
                // Sort by row count
                usort($tableSizes, fn($a, $b) => $b['rows'] <=> $a['rows']);
                $tableSizes = array_slice($tableSizes, 0, 10); // Top 10 tables
                
                return [
                    'status' => $status,
                    'color' => $color,
                    'connection' => 'active',
                    'tableCount' => $tableCount,
                    'dbSize' => $dbSizeMB,
                    'tableSizes' => $tableSizes,
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'color' => 'danger',
                    'connection' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    public function getBackupStatus(): array
    {
        return Cache::remember('system_health_backup', 300, function () {
            try {
                $backupPath = storage_path('app/backups');
                
                if (!is_dir($backupPath)) {
                    return [
                        'status' => 'not_configured',
                        'color' => 'warning',
                        'lastBackup' => null,
                        'backupSize' => 0,
                        'location' => $backupPath,
                    ];
                }
                
                $files = glob($backupPath . '/*.zip');
                
                if (empty($files)) {
                    return [
                        'status' => 'no_backups',
                        'color' => 'warning',
                        'lastBackup' => null,
                        'backupSize' => 0,
                        'location' => $backupPath,
                    ];
                }
                
                // Get most recent backup
                usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
                $latestBackup = $files[0];
                $lastBackupTime = filemtime($latestBackup);
                $backupSize = filesize($latestBackup);
                $backupSizeMB = round($backupSize / 1024 / 1024, 2);
                
                // Check if backup is recent (within 24 hours)
                $hoursSinceBackup = (time() - $lastBackupTime) / 3600;
                
                if ($hoursSinceBackup > 24) {
                    $status = 'outdated';
                    $color = 'warning';
                } else {
                    $status = 'healthy';
                    $color = 'success';
                }
                
                return [
                    'status' => $status,
                    'color' => $color,
                    'lastBackup' => date('Y-m-d H:i:s', $lastBackupTime),
                    'backupSize' => $backupSizeMB,
                    'location' => $backupPath,
                    'backupCount' => count($files),
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'color' => 'danger',
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    public function getQueueStatus(): array
    {
        return Cache::remember('system_health_queue', 60, function () {
            try {
                // Get pending jobs
                $pendingJobs = DB::table('jobs')->count();
                
                // Get failed jobs
                $failedJobs = DB::table('failed_jobs')->count();
                
                // Determine status
                if ($failedJobs > 10) {
                    $status = 'critical';
                    $color = 'danger';
                } elseif ($failedJobs > 0) {
                    $status = 'warning';
                    $color = 'warning';
                } else {
                    $status = 'healthy';
                    $color = 'success';
                }
                
                return [
                    'status' => $status,
                    'color' => $color,
                    'pendingJobs' => $pendingJobs,
                    'failedJobs' => $failedJobs,
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'color' => 'danger',
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    public function getStorageMetrics(): array
    {
        return Cache::remember('system_health_storage', 300, function () {
            try {
                $basePath = base_path();
                
                // Get disk space
                $diskTotal = disk_total_space($basePath);
                $diskFree = disk_free_space($basePath);
                $diskUsed = $diskTotal - $diskFree;
                
                $diskTotalGB = round($diskTotal / 1024 / 1024 / 1024, 2);
                $diskUsedGB = round($diskUsed / 1024 / 1024 / 1024, 2);
                $diskFreeGB = round($diskFree / 1024 / 1024 / 1024, 2);
                $diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 2);
                
                // Get database size
                $dbPath = database_path('database.sqlite');
                $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
                $dbSizeMB = round($dbSize / 1024 / 1024, 2);
                
                // Get log file sizes
                $logPath = storage_path('logs');
                $logSize = 0;
                if (is_dir($logPath)) {
                    $files = glob($logPath . '/*.log');
                    foreach ($files as $file) {
                        $logSize += filesize($file);
                    }
                }
                $logSizeMB = round($logSize / 1024 / 1024, 2);
                
                // Determine status
                if ($diskUsagePercent > 90) {
                    $status = 'critical';
                    $color = 'danger';
                } elseif ($diskUsagePercent > 80) {
                    $status = 'warning';
                    $color = 'warning';
                } else {
                    $status = 'healthy';
                    $color = 'success';
                }
                
                return [
                    'status' => $status,
                    'color' => $color,
                    'diskTotal' => $diskTotalGB,
                    'diskUsed' => $diskUsedGB,
                    'diskFree' => $diskFreeGB,
                    'diskUsagePercent' => $diskUsagePercent,
                    'dbSize' => $dbSizeMB,
                    'logSize' => $logSizeMB,
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'color' => 'danger',
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    public function getCacheStatus(): array
    {
        return Cache::remember('system_health_cache', 60, function () {
            try {
                // Test cache
                $testKey = 'health_check_' . time();
                Cache::put($testKey, true, 10);
                $working = Cache::get($testKey);
                Cache::forget($testKey);
                
                if (!$working) {
                    return [
                        'status' => 'not_working',
                        'color' => 'danger',
                        'connection' => 'failed',
                    ];
                }
                
                return [
                    'status' => 'operational',
                    'color' => 'success',
                    'connection' => 'active',
                    'driver' => config('cache.default'),
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'color' => 'danger',
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    protected function generateDiagnosticReport(): string
    {
        $report = __('system_health.report.title') . "\n";
        $report .= __('system_health.report.generated', [
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]) . "\n";
        $report .= str_repeat('=', 80) . "\n\n";
        
        // Database Health
        $report .= __('system_health.report.sections.database') . "\n";
        $report .= str_repeat('-', 80) . "\n";
        $db = $this->getDatabaseHealth();
        $report .= __('system_health.report.labels.status') . ": " . __('system_health.status.' . $db['status']) . "\n";
        $report .= __('system_health.report.labels.connection') . ": " . __('system_health.status.' . ($db['connection'] ?? 'unknown')) . "\n";
        if (isset($db['tableCount'])) {
            $report .= __('system_health.report.labels.tables') . ": " . $db['tableCount'] . "\n";
            $report .= __('system_health.report.labels.database_size') . ": " . $db['dbSize'] . " MB\n";
        }
        $report .= "\n";
        
        // Backup Status
        $report .= __('system_health.report.sections.backup') . "\n";
        $report .= str_repeat('-', 80) . "\n";
        $backup = $this->getBackupStatus();
        $report .= __('system_health.report.labels.status') . ": " . __('system_health.status.' . $backup['status']) . "\n";
        if (isset($backup['lastBackup'])) {
            $report .= __('system_health.report.labels.last_backup') . ": " . $backup['lastBackup'] . "\n";
            $report .= __('system_health.report.labels.backup_size') . ": " . $backup['backupSize'] . " MB\n";
        }
        $report .= "\n";
        
        // Queue Status
        $report .= __('system_health.report.sections.queue') . "\n";
        $report .= str_repeat('-', 80) . "\n";
        $queue = $this->getQueueStatus();
        $report .= __('system_health.report.labels.status') . ": " . __('system_health.status.' . $queue['status']) . "\n";
        if (isset($queue['pendingJobs'])) {
            $report .= __('system_health.report.labels.pending_jobs') . ": " . $queue['pendingJobs'] . "\n";
            $report .= __('system_health.report.labels.failed_jobs') . ": " . $queue['failedJobs'] . "\n";
        }
        $report .= "\n";
        
        // Storage Metrics
        $report .= __('system_health.report.sections.storage') . "\n";
        $report .= str_repeat('-', 80) . "\n";
        $storage = $this->getStorageMetrics();
        $report .= __('system_health.report.labels.status') . ": " . __('system_health.status.' . $storage['status']) . "\n";
        if (isset($storage['diskTotal'])) {
            $report .= __('system_health.report.labels.disk_total') . ": " . $storage['diskTotal'] . " GB\n";
            $report .= __('system_health.report.labels.disk_used') . ": " . $storage['diskUsed'] . " GB\n";
            $report .= __('system_health.report.labels.disk_free') . ": " . $storage['diskFree'] . " GB\n";
            $report .= __('system_health.report.labels.usage') . ": " . $storage['diskUsagePercent'] . "%\n";
            $report .= __('system_health.report.labels.database_size') . ": " . $storage['dbSize'] . " MB\n";
            $report .= __('system_health.report.labels.log_size') . ": " . $storage['logSize'] . " MB\n";
        }
        $report .= "\n";
        
        // Cache Status
        $report .= __('system_health.report.sections.cache') . "\n";
        $report .= str_repeat('-', 80) . "\n";
        $cache = $this->getCacheStatus();
        $report .= __('system_health.report.labels.status') . ": " . __('system_health.status.' . $cache['status']) . "\n";
        if (isset($cache['driver'])) {
            $report .= __('system_health.report.labels.driver') . ": " . $cache['driver'] . "\n";
        }
        $report .= "\n";
        
        return $report;
    }
}
