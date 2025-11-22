<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any subscriptions.
     * 
     * Requirements: 2.5, 15.3
     */
    public function viewAny(User $user): bool
    {
        // Superadmin can view all subscriptions
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can view their own subscription (Requirement 15.3)
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the subscription.
     * Allows superadmin full access, admin can view their own.
     * 
     * Requirements: 2.5, 15.3
     */
    public function view(User $user, Subscription $subscription): bool
    {
        // Superadmin can view any subscription
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admin can view their own subscription (Requirement 15.3)
        if ($user->role === UserRole::ADMIN) {
            return $subscription->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create subscriptions.
     * 
     * Requirements: 2.3
     */
    public function create(User $user): bool
    {
        // Only superadmin can create subscriptions (Requirement 2.3)
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can update the subscription.
     * Only superadmin can modify subscription details.
     * 
     * Requirements: 2.5
     */
    public function update(User $user, Subscription $subscription): bool
    {
        // Only superadmin can update subscriptions (Requirement 2.5)
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can renew the subscription.
     * Allows superadmin full access, admin can renew their own.
     * 
     * Requirements: 15.3
     */
    public function renew(User $user, Subscription $subscription): bool
    {
        // Superadmin can renew any subscription
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admin can renew their own subscription (Requirement 15.3)
        if ($user->role === UserRole::ADMIN) {
            return $subscription->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the subscription.
     * 
     * Requirements: 2.5
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        // Only superadmin can delete subscriptions (Requirement 2.5)
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can restore the subscription.
     * 
     * Requirements: 2.5
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        // Only superadmin can restore subscriptions (Requirement 2.5)
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can permanently delete the subscription.
     * 
     * Requirements: 2.5
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        // Only superadmin can force delete subscriptions (Requirement 2.5)
        return $user->role === UserRole::SUPERADMIN;
    }
}
