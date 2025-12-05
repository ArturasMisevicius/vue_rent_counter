<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Subscription;
use App\Models\User;

/**
 * Interface for subscription checking services.
 * 
 * Provides methods for checking subscription status with caching support.
 * 
 * @package App\Contracts
 */
interface SubscriptionCheckerInterface
{
    /**
     * Get user's subscription with caching.
     * 
     * @param User $user The user to get subscription for
     * @return Subscription|null The user's subscription or null
     */
    public function getSubscription(User $user): ?Subscription;

    /**
     * Check if user has an active subscription.
     *
     * @param User $user The user to check
     * @return bool True if user has active subscription
     */
    public function isActive(User $user): bool;

    /**
     * Check if user's subscription is expired.
     *
     * @param User $user The user to check
     * @return bool True if subscription is expired or doesn't exist
     */
    public function isExpired(User $user): bool;

    /**
     * Get days until subscription expiry.
     *
     * @param User $user The user to check
     * @return int|null Days until expiry, negative if expired, null if no subscription
     */
    public function daysUntilExpiry(User $user): ?int;

    /**
     * Invalidate cached subscription for a user.
     *
     * @param User $user The user whose subscription cache should be invalidated
     * @return void
     */
    public function invalidateCache(User $user): void;

    /**
     * Invalidate cache for multiple users.
     *
     * @param array<User> $users The users whose subscription cache should be invalidated
     * @return void
     */
    public function invalidateMany(array $users): void;

    /**
     * Pre-warm the cache for a user.
     *
     * @param User $user The user to warm cache for
     * @return void
     */
    public function warmCache(User $user): void;

    /**
     * Get subscriptions for multiple users efficiently.
     * 
     * Performance: Optimized for batch operations (e.g., admin dashboards).
     * Uses eager loading to avoid N+1 queries when cache is cold.
     * 
     * @param array<User> $users The users to get subscriptions for
     * @return array<int, Subscription|null> Array keyed by user ID
     */
    public function getSubscriptionsForUsers(array $users): array;
}
