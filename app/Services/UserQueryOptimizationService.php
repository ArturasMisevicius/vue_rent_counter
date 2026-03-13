<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * User Query Optimization Service
 * 
 * Provides optimized queries for common User operations with caching
 * and performance considerations for multi-tenant architecture.
 */
class UserQueryOptimizationService
{
    private const CACHE_TTL = 900; // 15 minutes
    private const CACHE_PREFIX = 'user_query:';

    /**
     * Get users for tenant with optimized loading.
     */
    public function getUsersForTenant(int $tenantId, ?UserRole $role = null): Collection
    {
        $cacheKey = $this->getCacheKey('tenant', $tenantId, $role?->value);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $role) {
            $query = User::where('tenant_id', $tenantId)
                ->active()
                ->withCommonRelations()
                ->orderedByRole();

            if ($role) {
                $query->where('role', $role);
            }

            return $query->get();
        });
    }

    /**
     * Get hierarchical user structure for admin.
     */
    public function getHierarchicalUsers(User $admin): array
    {
        $cacheKey = $this->getCacheKey('hierarchy', $admin->id);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($admin) {
            // Get admin's tenant users with their properties
            $tenantUsers = User::where('tenant_id', $admin->tenant_id)
                ->where('role', UserRole::TENANT)
                ->with(['property:id,name,address', 'parentUser:id,name'])
                ->active()
                ->get();

            // Group by parent user
            $hierarchy = $tenantUsers->groupBy('parent_user_id');

            return [
                'admin' => $admin,
                'direct_tenants' => $hierarchy->get($admin->id, collect()),
                'all_tenants' => $tenantUsers,
                'tenant_count' => $tenantUsers->count(),
                'properties_count' => $tenantUsers->pluck('property_id')->unique()->count(),
            ];
        });
    }

    /**
     * Get user statistics for dashboard.
     */
    public function getUserStatistics(int $tenantId): array
    {
        $cacheKey = $this->getCacheKey('stats', $tenantId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            return DB::table('users')
                ->where('tenant_id', $tenantId)
                ->selectRaw('
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = ? THEN 1 END) as admin_count,
                    COUNT(CASE WHEN role = ? THEN 1 END) as manager_count,
                    COUNT(CASE WHEN role = ? THEN 1 END) as tenant_count,
                    COUNT(CASE WHEN is_active = true THEN 1 END) as active_count,
                    COUNT(CASE WHEN suspended_at IS NOT NULL THEN 1 END) as suspended_count,
                    COUNT(CASE WHEN email_verified_at IS NULL THEN 1 END) as unverified_count,
                    COUNT(CASE WHEN last_login_at >= ? THEN 1 END) as recently_active_count
                ', [
                    UserRole::ADMIN->value,
                    UserRole::MANAGER->value,
                    UserRole::TENANT->value,
                    now()->subDays(30)
                ])
                ->first();
        });
    }

    /**
     * Search users with optimized query.
     */
    public function searchUsers(string $query, int $tenantId, int $limit = 20): Collection
    {
        return User::where('tenant_id', $tenantId)
            ->where(function (Builder $q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('organization_name', 'LIKE', "%{$query}%");
            })
            ->active()
            ->withCommonRelations()
            ->limit($limit)
            ->get();
    }

    /**
     * Get users requiring attention (suspended, unverified, etc.).
     */
    public function getUsersRequiringAttention(int $tenantId): array
    {
        $cacheKey = $this->getCacheKey('attention', $tenantId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            $baseQuery = User::where('tenant_id', $tenantId);

            return [
                'suspended' => (clone $baseQuery)->suspended()->count(),
                'unverified' => (clone $baseQuery)->unverified()->count(),
                'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
                'stale_login' => (clone $baseQuery)
                    ->where('last_login_at', '<', now()->subDays(90))
                    ->orWhereNull('last_login_at')
                    ->count(),
                'no_property_tenants' => (clone $baseQuery)
                    ->where('role', UserRole::TENANT)
                    ->whereNull('property_id')
                    ->count(),
            ];
        });
    }

    /**
     * Get API token usage statistics.
     */
    public function getApiTokenStatistics(int $tenantId): array
    {
        $cacheKey = $this->getCacheKey('api_tokens', $tenantId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            return DB::table('users')
                ->leftJoin('personal_access_tokens', function ($join) {
                    $join->on('users.id', '=', 'personal_access_tokens.tokenable_id')
                         ->where('personal_access_tokens.tokenable_type', '=', User::class);
                })
                ->where('users.tenant_id', $tenantId)
                ->selectRaw('
                    COUNT(DISTINCT users.id) as total_users,
                    COUNT(DISTINCT CASE WHEN personal_access_tokens.id IS NOT NULL THEN users.id END) as users_with_tokens,
                    COUNT(personal_access_tokens.id) as total_tokens,
                    COUNT(CASE WHEN personal_access_tokens.last_used_at >= ? THEN 1 END) as active_tokens,
                    COUNT(CASE WHEN personal_access_tokens.expires_at < NOW() THEN 1 END) as expired_tokens
                ', [now()->subDays(7)])
                ->first();
        });
    }

    /**
     * Get user workload summary with caching.
     */
    public function getUserWorkloadSummary(User $user): array
    {
        $cacheKey = $this->getCacheKey('workload_summary', $user->id);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return [
                'projects_count' => $user->getProjectsCount(),
                'tasks_summary' => $user->getTasksSummary(),
                'organizations_count' => $user->organizations()->count(),
                'recent_activity' => $user->last_login_at?->diffForHumans(),
            ];
        });
    }

    /**
     * Get users with similar roles for recommendations.
     */
    public function getSimilarUsers(User $user, int $limit = 10): Collection
    {
        $cacheKey = $this->getCacheKey('similar_users', $user->id, $limit);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $limit) {
            return User::where('role', $user->role)
                ->where('tenant_id', $user->tenant_id)
                ->where('id', '!=', $user->id)
                ->active()
                ->forListing()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get user activity metrics for analytics.
     */
    public function getUserActivityMetrics(User $user): array
    {
        $cacheKey = $this->getCacheKey('activity_metrics', $user->id);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $baseQuery = DB::table('users')->where('id', $user->id);
            
            return [
                'login_frequency' => $this->calculateLoginFrequency($user),
                'task_completion_rate' => $this->calculateTaskCompletionRate($user),
                'collaboration_score' => $this->calculateCollaborationScore($user),
                'engagement_level' => $this->calculateEngagementLevel($user),
            ];
        });
    }

    /**
     * Bulk update user last login times (for performance).
     */
    public function bulkUpdateLastLogin(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        DB::table('users')
            ->whereIn('id', $userIds)
            ->update([
                'last_login_at' => now(),
                'updated_at' => now(),
            ]);

        // Clear relevant caches
        $this->clearCacheForUsers($userIds);
    }

    /**
     * Calculate login frequency for user.
     */
    private function calculateLoginFrequency(User $user): float
    {
        if (!$user->last_login_at || !$user->created_at) {
            return 0.0;
        }

        $daysSinceCreation = $user->created_at->diffInDays(now());
        $daysSinceLastLogin = $user->last_login_at->diffInDays(now());
        
        if ($daysSinceCreation === 0) {
            return 1.0;
        }

        return max(0, 1 - ($daysSinceLastLogin / $daysSinceCreation));
    }

    /**
     * Calculate task completion rate for user.
     */
    private function calculateTaskCompletionRate(User $user): float
    {
        $tasksSummary = $user->getTasksSummary();
        $totalTasks = $tasksSummary['total'];
        
        if ($totalTasks === 0) {
            return 0.0;
        }

        return $tasksSummary['completed'] / $totalTasks;
    }

    /**
     * Calculate collaboration score based on shared tasks.
     */
    private function calculateCollaborationScore(User $user): float
    {
        $cacheKey = $this->getCacheKey('collaboration_score', $user->id);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $sharedTasks = DB::table('task_assignments as ta1')
                ->join('task_assignments as ta2', 'ta1.task_id', '=', 'ta2.task_id')
                ->where('ta1.user_id', $user->id)
                ->where('ta2.user_id', '!=', $user->id)
                ->distinct('ta1.task_id')
                ->count();

            $totalTasks = $user->taskAssignments()->count();
            
            return $totalTasks > 0 ? $sharedTasks / $totalTasks : 0.0;
        });
    }

    /**
     * Calculate overall engagement level.
     */
    private function calculateEngagementLevel(User $user): string
    {
        $loginFreq = $this->calculateLoginFrequency($user);
        $completionRate = $this->calculateTaskCompletionRate($user);
        $collaborationScore = $this->calculateCollaborationScore($user);
        
        $overallScore = ($loginFreq + $completionRate + $collaborationScore) / 3;
        
        return match (true) {
            $overallScore >= 0.8 => 'high',
            $overallScore >= 0.5 => 'medium',
            $overallScore >= 0.2 => 'low',
            default => 'inactive',
        };
    }

    /**
     * Clear cache for specific users.
     */
    public function clearCacheForUsers(array $userIds): void
    {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->clearCacheForTenant($user->tenant_id);
            }
        }
    }

    /**
     * Clear all cache for a tenant.
     */
    public function clearCacheForTenant(int $tenantId): void
    {
        $patterns = [
            self::CACHE_PREFIX . "tenant:{$tenantId}:*",
            self::CACHE_PREFIX . "stats:{$tenantId}",
            self::CACHE_PREFIX . "attention:{$tenantId}",
            self::CACHE_PREFIX . "api_tokens:{$tenantId}",
        ];

        foreach ($patterns as $pattern) {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                // For non-Redis stores, we'd need to track keys manually
                // This is a simplified approach
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Generate cache key.
     */
    private function getCacheKey(string $type, mixed ...$params): string
    {
        return self::CACHE_PREFIX . $type . ':' . implode(':', $params);
    }
}