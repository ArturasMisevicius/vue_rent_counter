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
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-heart';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'System Health';

    protected string $view = 'filament.pages.system-health';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperadmin(), 403);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runHealthCheck')
                ->label('Run Health Check')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    Cache::forget('system_health_database');
                    Cache::forget('system_health_backup');
                    Cache::forget('system_health_queue');
                    Cache::forget('system_health_storage');
                    Cache::forget('system_health_cache');

                    Notification::make()
                        ->title('Health check completed')
                        ->success()
                        ->send();
                }),

            Action::make('triggerBackup')
                ->label('Trigger Manual Backup')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        Artisan::call('backup:run');
                        
                        Notification::make()
                            ->title('Backup started')
                            ->body('The backup process has been initiated.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Backup failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('clearCache')
                ->label('Clear Cache')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    Cache::flush();
                    
                    Notification::make()
                        ->title('Cache cleared')
                        ->success()
                        ->send();
                }),

            Action::make('downloadDiagnostic')
                ->label('Download Diagnostic Report')
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
                $status = 'Connected';
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
                    'connection' => 'Active',
                    'tableCount' => $tableCount,
                    'dbSize' => $dbSizeMB,
                    'tableSizes' => $tableSizes,
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'Error',
                    'color' => 'danger',
                    'connection' => 'Failed',
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
                        'status' => 'Not Configured',
                        'color' => 'warning',
                        'lastBackup' => null,
                        'backupSize' => 0,
                        'location' => $backupPath,
                    ];
                }
                
                $files = glob($backupPath . '/*.zip');
                
                if (empty($files)) {
                    return [
                        'status' => 'No Backups',
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
                    $status = 'Outdated';
                    $color = 'warning';
                } else {
                    $status = 'Healthy';
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
                    'status' => 'Error',
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
                    $status = 'Critical';
                    $color = 'danger';
                } elseif ($failedJobs > 0) {
                    $status = 'Warning';
                    $color = 'warning';
                } else {
                    $status = 'Healthy';
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
                    'status' => 'Error',
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
                    $status = 'Critical';
                    $color = 'danger';
                } elseif ($diskUsagePercent > 80) {
                    $status = 'Warning';
                    $color = 'warning';
                } else {
                    $status = 'Healthy';
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
                    'status' => 'Error',
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
                        'status' => 'Not Working',
                        'color' => 'danger',
                        'connection' => 'Failed',
                    ];
                }
                
                return [
                    'status' => 'Operational',
                    'color' => 'success',
                    'connection' => 'Active',
                    'driver' => config('cache.default'),
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'Error',
                    'color' => 'danger',
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    protected function generateDiagnosticReport(): string
    {
        $report = "System Health Diagnostic Report\n";
        $report .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $report .= str_repeat('=', 80) . "\n\n";
        
        // Database Health
        $report .= "DATABASE HEALTH\n";
        $report .= str_repeat('-', 80) . "\n";
        $db = $this->getDatabaseHealth();
        $report .= "Status: " . $db['status'] . "\n";
        $report .= "Connection: " . ($db['connection'] ?? 'Unknown') . "\n";
        if (isset($db['tableCount'])) {
            $report .= "Tables: " . $db['tableCount'] . "\n";
            $report .= "Database Size: " . $db['dbSize'] . " MB\n";
        }
        $report .= "\n";
        
        // Backup Status
        $report .= "BACKUP STATUS\n";
        $report .= str_repeat('-', 80) . "\n";
        $backup = $this->getBackupStatus();
        $report .= "Status: " . $backup['status'] . "\n";
        if (isset($backup['lastBackup'])) {
            $report .= "Last Backup: " . $backup['lastBackup'] . "\n";
            $report .= "Backup Size: " . $backup['backupSize'] . " MB\n";
        }
        $report .= "\n";
        
        // Queue Status
        $report .= "QUEUE STATUS\n";
        $report .= str_repeat('-', 80) . "\n";
        $queue = $this->getQueueStatus();
        $report .= "Status: " . $queue['status'] . "\n";
        if (isset($queue['pendingJobs'])) {
            $report .= "Pending Jobs: " . $queue['pendingJobs'] . "\n";
            $report .= "Failed Jobs: " . $queue['failedJobs'] . "\n";
        }
        $report .= "\n";
        
        // Storage Metrics
        $report .= "STORAGE METRICS\n";
        $report .= str_repeat('-', 80) . "\n";
        $storage = $this->getStorageMetrics();
        $report .= "Status: " . $storage['status'] . "\n";
        if (isset($storage['diskTotal'])) {
            $report .= "Disk Total: " . $storage['diskTotal'] . " GB\n";
            $report .= "Disk Used: " . $storage['diskUsed'] . " GB\n";
            $report .= "Disk Free: " . $storage['diskFree'] . " GB\n";
            $report .= "Usage: " . $storage['diskUsagePercent'] . "%\n";
            $report .= "Database Size: " . $storage['dbSize'] . " MB\n";
            $report .= "Log Size: " . $storage['logSize'] . " MB\n";
        }
        $report .= "\n";
        
        // Cache Status
        $report .= "CACHE STATUS\n";
        $report .= str_repeat('-', 80) . "\n";
        $cache = $this->getCacheStatus();
        $report .= "Status: " . $cache['status'] . "\n";
        if (isset($cache['driver'])) {
            $report .= "Driver: " . $cache['driver'] . "\n";
        }
        $report .= "\n";
        
        return $report;
    }
}
