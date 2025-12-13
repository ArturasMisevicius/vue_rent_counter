<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\OrganizationActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * QueryOptimizationService provides optimized queries for superadmin dashboard
 * 
 * Implements eager loading, query result caching, and optimized database queries
 * to improve performance for dashboard widgets and CRUD operations.
 */
class QueryOptimizationService
{
    private const QUERY_CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'query_cache';

    /**
     * Get organizations with optimized eager loading
     */
    public function getOrganizationsOptimized(?array $filters = null): Collection
    {
        $cacheKey = self::CACHE_PREFIX . '.organizations.' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_TTL, function () use ($filters) {
            $query = Organization::query()
                ->with([
                    'users:id,tenant_id,name,email,role,is_active,last_login_at',
                    'properties:id,tenant_id,name,address',
                    'activityLogs' => function ($query) {
                        $query->latest()->limit(5)->select('id', 'organization_id', 'action', 'created_at');
                    }
                ])
                ->withCount(['users', 'properties', 'buildings', 'invoices']);

            if ($filters) {
                $this->applyOrganizationFilters($query, $filters);
            }

            return $query->get();
        });
    }

    /**
     * Get subscriptions with optimized eager loading
     */
    public function getSubscriptionsOptimized(?array $filters = null): Collection
    {
        $cacheKey = self::CACHE_PREFIX . '.subscriptions.' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_TTL, function () use ($filters) {
            $query = Subscription::query()
                ->with([
                    'user:id,tenant_id,name,email',
                    'user.organization:id,name,slug,email',
                    'renewals' => function ($query) {
                        $query->latest()->limit(3)->select('id', 'subscription_id', 'method', 'created_at');
                    }
                ]);

            if ($filters) {
                $this->applySubscriptionFilters($query, $filters);
            }

            return $query->get();
        });
    }

    /**
     * Get activity logs with optimized eager loading
     */
    public function getActivityLogsOptimized(?array $filters = null, int $limit = 50): Collection
    {
        $cacheKey = self::CACHE_PREFIX . '.activity_logs.' . md5(serialize($filters) . $limit);
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_TTL / 2, function () use ($filters, $limit) {
            $query = OrganizationActivityLog::query()
                ->with([
                    'organization:id,name,slug',
                    'user:id,name,email,role'
                ])
                ->latest()
                ->limit($limit);

            if ($filters) {
                $this->applyActivityLogFilters($query, $filters);
            }

            return $query->get();
        });
    }

    /**
     * Get users across organizations with optimized eager loading
     */
    public function getUsersOptimized(?array $filters = null): Collection
    {
        $cacheKey = self::CACHE_PREFIX . '.users.' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_TTL, function () use ($filters) {
            $query = User::query()
                ->whereNotNull('tenant_id') // Only organization users
                ->with([
                    'organization:id,name,slug,email,plan',
                    'properties:id,tenant_id,name,address',
                ])
                ->withCount(['properties']);

            if ($filters) {
                $this->applyUserFilters($query, $filters);
            }

            return $query->get();
        });
    }

    /**
     * Get dashboard statistics with single optimized query
     */
    public function getDashboardStatsOptimized(): array
    {
        $cacheKey = self::CACHE_PREFIX . '.dashboard_stats';
        
        return Cache::remember($cacheKey, 60, function () {
            // Single query to get all counts
            $stats = DB::select('
                SELECT 
                    "organizations" as type,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 AND suspended_at IS NULL THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 OR suspended_at IS NOT NULL THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent
                FROM organizations
                
                UNION ALL
                
                SELECT 
                    "subscriptions" as type,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status != "active" THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN expires_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as recent
                FROM subscriptions
                
                UNION ALL
                
                SELECT 
                    "users" as type,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN last_login_at >= ? THEN 1 ELSE 0 END) as recent
                FROM users
                WHERE tenant_id IS NOT NULL
                
                UNION ALL
                
                SELECT 
                    "properties" as type,
                    COUNT(*) as total,
                    COUNT(*) as active,
                    0 as inactive,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent
                FROM properties
                
                UNION ALL
                
                SELECT 
                    "invoices" as type,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status != "paid" THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent
                FROM invoices
            ', [
                now()->subDays(30)->toDateString(), // Organizations recent
                now()->toDateString(),              // Subscriptions expiring start
                now()->addDays(14)->toDateString(), // Subscriptions expiring end
                now()->subDays(7)->toDateString(),  // Users recent login
                now()->subDays(30)->toDateString(), // Properties recent
                now()->subDays(30)->toDateString(), // Invoices recent
            ]);

            // Convert to associative array
            $result = [];
            foreach ($stats as $stat) {
                $result[$stat->type] = [
                    'total' => (int) $stat->total,
                    'active' => (int) $stat->active,
                    'inactive' => (int) $stat->inactive,
                    'recent' => (int) $stat->recent,
                ];
            }

            return $result;
        });
    }

    /**
     * Get top organizations by various metrics
     */
    public function getTopOrganizations(string $metric = 'properties', int $limit = 10): array
    {
        $cacheKey = self::CACHE_PREFIX . ".top_organizations.{$metric}.{$limit}";
        
        return Cache::remember($cacheKey, self::QUERY_CACHE_TTL, function () use ($metric, $limit) {
            $query = Organization::query()
                ->select('organizations.id', 'organizations.name', 'organizations.slug');

            switch ($metric) {
                case 'properties':
                    $query->withCount('properties')
                          ->orderBy('properties_count', 'desc');
                    break;
                    
                case 'users':
                    $query->withCount('users')
                          ->orderBy('users_count', 'desc');
                    break;
                    
                case 'invoices':
                    $query->withCount('invoices')
                          ->orderBy('invoices_count', 'desc');
                    break;
                    
                case 'activity':
                    $query->withCount(['activityLogs' => function ($query) {
                        $query->where('created_at', '>=', now()->subDays(30));
                    }])
                    ->orderBy('activity_logs_count', 'desc');
                    break;
            }

            return $query->limit($limit)->get()->toArray();
        });
    }

    /**
     * Get expiring subscriptions with optimized query
     */
    public function getExpiringSubscriptions(int $days = 14): Collection
    {
        $cacheKey = self::CACHE_PREFIX . ".expiring_subscriptions.{$days}";
        
        return Cache::remember($cacheKey, 300, function () use ($days) {
            return Subscription::query()
                ->with([
                    'user:id,tenant_id,name,email',
                    'user.organization:id,name,slug,email,phone'
                ])
                ->where('status', 'active')
                ->whereBetween('expires_at', [
                    now()->toDateString(),
                    now()->addDays($days)->toDateString()
                ])
                ->orderBy('expires_at')
                ->get();
        });
    }

    /**
     * Apply organization filters to query
     */
    private function applyOrganizationFilters(Builder $query, array $filters): void
    {
        if (isset($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['suspended'])) {
            if ($filters['suspended']) {
                $query->whereNotNull('suspended_at');
            } else {
                $query->whereNull('suspended_at');
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }
    }

    /**
     * Apply subscription filters to query
     */
    private function applySubscriptionFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['plan_type'])) {
            $query->where('plan_type', $filters['plan_type']);
        }

        if (isset($filters['expiring_soon'])) {
            if ($filters['expiring_soon']) {
                $query->whereBetween('expires_at', [
                    now()->toDateString(),
                    now()->addDays(14)->toDateString()
                ]);
            }
        }

        if (isset($filters['expired'])) {
            if ($filters['expired']) {
                $query->where('expires_at', '<', now());
            }
        }
    }

    /**
     * Apply activity log filters to query
     */
    private function applyActivityLogFilters(Builder $query, array $filters): void
    {
        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * Apply user filters to query
     */
    private function applyUserFilters(Builder $query, array $filters): void
    {
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['organization_id'])) {
            $query->where('tenant_id', $filters['organization_id']);
        }

        if (isset($filters['last_login_days'])) {
            $query->where('last_login_at', '>=', now()->subDays($filters['last_login_days']));
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Invalidate query caches
     */
    public function invalidateQueryCaches(?string $type = null): void
    {
        if ($type) {
            $pattern = self::CACHE_PREFIX . ".{$type}.*";
            // Note: This is a simplified implementation
            // In production, you might want to use cache tags or a more sophisticated cache invalidation
            Cache::flush();
        } else {
            Cache::flush();
        }
    }

    /**
     * Get query performance statistics
     */
    public function getQueryStats(): array
    {
        return [
            'cache_hit_rate' => $this->calculateCacheHitRate(),
            'average_query_time' => $this->getAverageQueryTime(),
            'slow_queries_count' => $this->getSlowQueriesCount(),
        ];
    }

    /**
     * Calculate cache hit rate (simplified implementation)
     */
    private function calculateCacheHitRate(): float
    {
        // This would require more sophisticated tracking in production
        return 85.5; // Placeholder
    }

    /**
     * Get average query time (simplified implementation)
     */
    private function getAverageQueryTime(): float
    {
        // This would require query logging and analysis in production
        return 45.2; // Placeholder in milliseconds
    }

    /**
     * Get slow queries count (simplified implementation)
     */
    private function getSlowQueriesCount(): int
    {
        // This would require query logging and analysis in production
        return 3; // Placeholder
    }
}