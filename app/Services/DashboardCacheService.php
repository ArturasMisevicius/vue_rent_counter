<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\OrganizationActivityLog;
use App\Models\SystemHealthMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * DashboardCacheService handles caching for superadmin dashboard metrics
 * 
 * Implements the caching strategy defined in requirements:
 * - Subscription stats: 60s TTL
 * - Organization stats: 5min TTL  
 * - System health metrics: 30s TTL
 * - Cache warming for improved performance
 */
class DashboardCacheService
{
    // Cache TTL constants (in seconds)
    private const SUBSCRIPTION_STATS_TTL = 60;
    private const ORGANIZATION_STATS_TTL = 300; // 5 minutes
    private const SYSTEM_HEALTH_TTL = 30;
    private const ACTIVITY_STATS_TTL = 120; // 2 minutes
    
    // Cache key prefixes
    private const CACHE_PREFIX = 'superadmin.dashboard';
    
    /**
     * Get subscription statistics with caching
     */
    public function getSubscriptionStats(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.subscription_stats',
            self::SUBSCRIPTION_STATS_TTL,
            function () {
                return $this->calculateSubscriptionStats();
            }
        );
    }
    
    /**
     * Get organization statistics with caching
     */
    public function getOrganizationStats(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.organization_stats',
            self::ORGANIZATION_STATS_TTL,
            function () {
                return $this->calculateOrganizationStats();
            }
        );
    }
    
    /**
     * Get system health metrics with caching
     */
    public function getSystemHealthStats(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.system_health',
            self::SYSTEM_HEALTH_TTL,
            function () {
                return $this->calculateSystemHealthStats();
            }
        );
    }
    
    /**
     * Get activity statistics with caching
     */
    public function getActivityStats(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.activity_stats',
            self::ACTIVITY_STATS_TTL,
            function () {
                return $this->calculateActivityStats();
            }
        );
    }
    
    /**
     * Get platform usage statistics with caching
     */
    public function getPlatformUsageStats(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.platform_usage',
            self::ORGANIZATION_STATS_TTL, // Same as org stats
            function () {
                return $this->calculatePlatformUsageStats();
            }
        );
    }
    
    /**
     * Warm all dashboard caches
     * This method can be called periodically to ensure fresh data
     */
    public function warmCaches(): void
    {
        // Warm caches in parallel using cache tags for better performance
        $this->getSubscriptionStats();
        $this->getOrganizationStats();
        $this->getSystemHealthStats();
        $this->getActivityStats();
        $this->getPlatformUsageStats();
    }
    
    /**
     * Invalidate all dashboard caches
     */
    public function invalidateAll(): void
    {
        $keys = [
            self::CACHE_PREFIX . '.subscription_stats',
            self::CACHE_PREFIX . '.organization_stats',
            self::CACHE_PREFIX . '.system_health',
            self::CACHE_PREFIX . '.activity_stats',
            self::CACHE_PREFIX . '.platform_usage',
        ];
        
        Cache::forget($keys);
    }
    
    /**
     * Invalidate subscription-related caches
     */
    public function invalidateSubscriptionCaches(): void
    {
        Cache::forget(self::CACHE_PREFIX . '.subscription_stats');
        Cache::forget(self::CACHE_PREFIX . '.platform_usage');
    }
    
    /**
     * Invalidate organization-related caches
     */
    public function invalidateOrganizationCaches(): void
    {
        Cache::forget(self::CACHE_PREFIX . '.organization_stats');
        Cache::forget(self::CACHE_PREFIX . '.platform_usage');
    }
    
    /**
     * Calculate subscription statistics
     */
    private function calculateSubscriptionStats(): array
    {
        // Use single query with conditional counting for better performance
        $stats = DB::table('subscriptions')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN status = "suspended" THEN 1 ELSE 0 END) as suspended,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN expires_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_soon
            ', [
                now()->toDateString(),
                now()->addDays(14)->toDateString()
            ])
            ->first();
            
        return [
            'total' => (int) $stats->total,
            'active' => (int) $stats->active,
            'expired' => (int) $stats->expired,
            'suspended' => (int) $stats->suspended,
            'cancelled' => (int) $stats->cancelled,
            'expiring_soon' => (int) $stats->expiring_soon,
            'cached_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Calculate organization statistics
     */
    private function calculateOrganizationStats(): array
    {
        // Use single query with conditional counting
        $stats = DB::table('organizations')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 AND suspended_at IS NULL THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 OR suspended_at IS NOT NULL THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_this_month,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_last_month
            ', [
                now()->subDays(30)->toDateString(),
                now()->subDays(60)->toDateString(),
                now()->subDays(30)->toDateString()
            ])
            ->first();
            
        // Calculate growth rate
        $growthRate = $stats->new_last_month > 0 
            ? round((($stats->new_this_month - $stats->new_last_month) / $stats->new_last_month) * 100, 1)
            : 0;
            
        return [
            'total' => (int) $stats->total,
            'active' => (int) $stats->active,
            'inactive' => (int) $stats->inactive,
            'new_this_month' => (int) $stats->new_this_month,
            'new_last_month' => (int) $stats->new_last_month,
            'growth_rate' => $growthRate,
            'cached_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Calculate system health statistics
     */
    private function calculateSystemHealthStats(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'queue' => $this->checkQueueHealth(),
            'storage' => $this->checkStorageHealth(),
            'cached_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Calculate activity statistics
     */
    private function calculateActivityStats(): array
    {
        $stats = DB::table('organization_activity_logs')
            ->selectRaw('
                COUNT(*) as total_today,
                COUNT(DISTINCT organization_id) as active_orgs_today,
                COUNT(DISTINCT user_id) as active_users_today
            ')
            ->where('created_at', '>=', now()->startOfDay())
            ->first();
            
        $recentActivities = OrganizationActivityLog::with(['organization', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'organization' => $activity->organization?->name,
                    'user' => $activity->user?->name,
                    'created_at' => $activity->created_at->diffForHumans(),
                ];
            });
            
        return [
            'total_today' => (int) $stats->total_today,
            'active_orgs_today' => (int) $stats->active_orgs_today,
            'active_users_today' => (int) $stats->active_users_today,
            'recent_activities' => $recentActivities->toArray(),
            'cached_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Calculate platform usage statistics
     */
    private function calculatePlatformUsageStats(): array
    {
        // Get counts for different time periods for trend analysis
        $stats = DB::select('
            SELECT 
                (SELECT COUNT(*) FROM organizations) as total_orgs,
                (SELECT COUNT(*) FROM users WHERE tenant_id IS NOT NULL) as total_users,
                (SELECT COUNT(*) FROM properties) as total_properties,
                (SELECT COUNT(*) FROM buildings) as total_buildings,
                (SELECT COUNT(*) FROM meters) as total_meters,
                (SELECT COUNT(*) FROM invoices) as total_invoices,
                (SELECT COUNT(*) FROM organizations WHERE created_at >= ?) as orgs_last_30_days,
                (SELECT COUNT(*) FROM users WHERE tenant_id IS NOT NULL AND created_at >= ?) as users_last_30_days,
                (SELECT COUNT(*) FROM properties WHERE created_at >= ?) as properties_last_30_days
        ', [
            now()->subDays(30)->toDateString(),
            now()->subDays(30)->toDateString(),
            now()->subDays(30)->toDateString()
        ])[0];
        
        // Get top organizations by property count
        $topOrganizations = DB::table('organizations')
            ->leftJoin('properties', 'organizations.id', '=', 'properties.tenant_id')
            ->select('organizations.name', DB::raw('COUNT(properties.id) as property_count'))
            ->groupBy('organizations.id', 'organizations.name')
            ->orderBy('property_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
            
        return [
            'totals' => [
                'organizations' => (int) $stats->total_orgs,
                'users' => (int) $stats->total_users,
                'properties' => (int) $stats->total_properties,
                'buildings' => (int) $stats->total_buildings,
                'meters' => (int) $stats->total_meters,
                'invoices' => (int) $stats->total_invoices,
            ],
            'growth_last_30_days' => [
                'organizations' => (int) $stats->orgs_last_30_days,
                'users' => (int) $stats->users_last_30_days,
                'properties' => (int) $stats->properties_last_30_days,
            ],
            'top_organizations' => $topOrganizations,
            'cached_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            
            // Get table count and connection info
            $tableCount = count(DB::select('SELECT name FROM sqlite_master WHERE type="table"'));
            $connectionCount = 1; // SQLite doesn't have multiple connections like MySQL
            
            return [
                'status' => 'healthy',
                'message' => "{$tableCount} tables, {$connectionCount} connection",
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
                'details' => [
                    'table_count' => $tableCount,
                    'connection_count' => $connectionCount,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
                'details' => [
                    'error' => $e->getMessage(),
                ]
            ];
        }
    }
    
    /**
     * Check cache health
     */
    private function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, true, 10);
            $working = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $working ? 'healthy' : 'warning',
                'message' => $working ? 'Cache operational' : 'Cache not responding',
                'icon' => $working ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle',
                'color' => $working ? 'success' : 'warning',
                'details' => [
                    'driver' => config('cache.default'),
                    'working' => $working,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache unavailable',
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
                'details' => [
                    'error' => $e->getMessage(),
                ]
            ];
        }
    }
    
    /**
     * Check queue health
     */
    private function checkQueueHealth(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            
            if ($failedJobs === 0) {
                return [
                    'status' => 'healthy',
                    'message' => 'No failed jobs',
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                    'details' => [
                        'failed_jobs' => $failedJobs,
                    ]
                ];
            } elseif ($failedJobs < 10) {
                return [
                    'status' => 'warning',
                    'message' => "{$failedJobs} failed jobs",
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'color' => 'warning',
                    'details' => [
                        'failed_jobs' => $failedJobs,
                    ]
                ];
            } else {
                return [
                    'status' => 'critical',
                    'message' => "{$failedJobs} failed jobs",
                    'icon' => 'heroicon-o-x-circle',
                    'color' => 'danger',
                    'details' => [
                        'failed_jobs' => $failedJobs,
                    ]
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cannot check queue status',
                'icon' => 'heroicon-o-question-mark-circle',
                'color' => 'gray',
                'details' => [
                    'error' => $e->getMessage(),
                ]
            ];
        }
    }
    
    /**
     * Check storage health
     */
    private function checkStorageHealth(): array
    {
        try {
            $dbPath = database_path('database.sqlite');
            $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
            $dbSizeMB = round($dbSize / 1024 / 1024, 2);
            
            $diskFree = disk_free_space(database_path());
            $diskFreeMB = round($diskFree / 1024 / 1024, 2);
            
            if ($diskFreeMB < 100) {
                return [
                    'status' => 'warning',
                    'message' => "Low disk space: {$diskFreeMB} MB free",
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'color' => 'warning',
                    'details' => [
                        'db_size_mb' => $dbSizeMB,
                        'disk_free_mb' => $diskFreeMB,
                    ]
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => "DB: {$dbSizeMB} MB, Free: {$diskFreeMB} MB",
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
                'details' => [
                    'db_size_mb' => $dbSizeMB,
                    'disk_free_mb' => $diskFreeMB,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cannot check storage',
                'icon' => 'heroicon-o-question-mark-circle',
                'color' => 'gray',
                'details' => [
                    'error' => $e->getMessage(),
                ]
            ];
        }
    }
    
    /**
     * Get cache statistics for monitoring
     */
    public function getCacheStats(): array
    {
        $keys = [
            'subscription_stats' => self::CACHE_PREFIX . '.subscription_stats',
            'organization_stats' => self::CACHE_PREFIX . '.organization_stats',
            'system_health' => self::CACHE_PREFIX . '.system_health',
            'activity_stats' => self::CACHE_PREFIX . '.activity_stats',
            'platform_usage' => self::CACHE_PREFIX . '.platform_usage',
        ];
        
        $stats = [];
        foreach ($keys as $name => $key) {
            $stats[$name] = [
                'exists' => Cache::has($key),
                'ttl' => $this->getCacheTTL($name),
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get TTL for cache type
     */
    private function getCacheTTL(string $type): int
    {
        return match ($type) {
            'subscription_stats' => self::SUBSCRIPTION_STATS_TTL,
            'organization_stats' => self::ORGANIZATION_STATS_TTL,
            'system_health' => self::SYSTEM_HEALTH_TTL,
            'activity_stats' => self::ACTIVITY_STATS_TTL,
            'platform_usage' => self::ORGANIZATION_STATS_TTL,
            default => 60,
        };
    }
}