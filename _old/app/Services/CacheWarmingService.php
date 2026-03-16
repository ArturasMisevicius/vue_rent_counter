<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheWarmingService
{
    /**
     * Warm all dashboard caches
     */
    public function warmAll(): void
    {
        $this->warmSubscriptionStats();
        $this->warmOrganizationStats();
        $this->warmSystemHealth();
        $this->warmTopOrganizations();
        $this->warmPlatformUsage();
    }

    /**
     * Warm subscription statistics cache (60s TTL)
     */
    public function warmSubscriptionStats(): void
    {
        Cache::remember('superadmin.subscription_stats', 60, function () {
            $total = Subscription::count();
            $active = Subscription::where('status', SubscriptionStatus::ACTIVE->value)->count();
            $expired = Subscription::where('status', SubscriptionStatus::EXPIRED->value)->count();
            $suspended = Subscription::where('status', SubscriptionStatus::SUSPENDED->value)->count();
            $cancelled = Subscription::where('status', SubscriptionStatus::CANCELLED->value)->count();

            return compact('total', 'active', 'expired', 'suspended', 'cancelled');
        });
    }

    /**
     * Warm organization statistics cache (5min TTL)
     */
    public function warmOrganizationStats(): void
    {
        Cache::remember('superadmin.organization_stats', 300, function () {
            $total = Organization::count();
            $active = Organization::where('is_active', true)->count();
            $inactive = Organization::where('is_active', false)->count();
            
            // Calculate growth (new orgs in last 30 days)
            $lastMonth = Organization::where('created_at', '>=', now()->subDays(30))->count();
            $previousMonth = Organization::whereBetween('created_at', [
                now()->subDays(60),
                now()->subDays(30)
            ])->count();
            
            $growthRate = $previousMonth > 0 
                ? round((($lastMonth - $previousMonth) / $previousMonth) * 100, 1)
                : 0;

            return compact('total', 'active', 'inactive', 'lastMonth', 'growthRate');
        });
    }

    /**
     * Warm system health cache (30s TTL)
     */
    public function warmSystemHealth(): void
    {
        Cache::remember('superadmin.system_health', 30, function () {
            return [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'queue' => $this->checkQueueHealth(),
                'storage' => $this->checkStorageHealth(),
            ];
        });
    }

    /**
     * Warm top organizations cache (5min TTL)
     */
    public function warmTopOrganizations(): void
    {
        Cache::remember('superadmin.top_organizations', 300, function () {
            $topOrgs = Organization::query()
                ->withCount('properties')
                ->orderBy('properties_count', 'desc')
                ->limit(10)
                ->get();

            return [
                'labels' => $topOrgs->pluck('name')->toArray(),
                'data' => $topOrgs->pluck('properties_count')->toArray(),
            ];
        });
    }

    /**
     * Warm platform usage cache (5min TTL)
     */
    public function warmPlatformUsage(): void
    {
        Cache::remember('superadmin.platform_usage', 300, function () {
            $months = collect();
            $labels = [];
            
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $labels[] = $date->format('M Y');
                
                $months->push([
                    'properties' => Property::where('created_at', '<=', $date->endOfMonth())->count(),
                    'users' => User::where('created_at', '<=', $date->endOfMonth())->count(),
                    'invoices' => Invoice::where('created_at', '<=', $date->endOfMonth())->count(),
                ]);
            }

            return [
                'labels' => $labels,
                'properties' => $months->pluck('properties')->toArray(),
                'users' => $months->pluck('users')->toArray(),
                'invoices' => $months->pluck('invoices')->toArray(),
            ];
        });
    }

    /**
     * Check database health
     */
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

    /**
     * Check cache health
     */
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

    /**
     * Check queue health
     */
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

    /**
     * Check storage health
     */
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
}
