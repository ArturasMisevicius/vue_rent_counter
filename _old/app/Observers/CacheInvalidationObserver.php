<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\DashboardCacheService;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\OrganizationActivityLog;

/**
 * Observer to handle cache invalidation when models are updated
 * 
 * This ensures dashboard caches are automatically invalidated
 * when underlying data changes, maintaining data consistency
 */
class CacheInvalidationObserver
{
    public function __construct(
        private DashboardCacheService $cacheService
    ) {}

    /**
     * Handle organization model events
     */
    public function organizationSaved(Organization $organization): void
    {
        $this->cacheService->invalidateOrganizationCaches();
    }

    public function organizationDeleted(Organization $organization): void
    {
        $this->cacheService->invalidateOrganizationCaches();
    }

    /**
     * Handle subscription model events
     */
    public function subscriptionSaved(Subscription $subscription): void
    {
        $this->cacheService->invalidateSubscriptionCaches();
    }

    public function subscriptionDeleted(Subscription $subscription): void
    {
        $this->cacheService->invalidateSubscriptionCaches();
    }

    /**
     * Handle activity log events
     */
    public function activityLogCreated(OrganizationActivityLog $log): void
    {
        // Only invalidate activity cache, not all caches
        \Illuminate\Support\Facades\Cache::forget('superadmin.dashboard.activity_stats');
    }
}