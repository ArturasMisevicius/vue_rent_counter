<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionPolicy handles authorization for superadmin subscription management.
 * 
 * This policy enforces superadmin-only access to subscription management operations
 * as part of the superadmin dashboard enhancement. While admins can view their own
 * subscriptions, only superadmins can perform management operations like create,
 * update, renew, suspend, and activate.
 * 
 * Requirements: 3.1, 3.2, 3.4, 3.5
 */
class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any subscriptions.
     * 
     * Requirements: 3.1
     */
    public function viewAny(User $user): bool
    {
        // Superadmin can view all subscriptions for management
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can view their own subscription
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the subscription.
     * 
     * Requirements: 3.1
     */
    public function view(User $user, Subscription $subscription): bool
    {
        // Superadmin can view any subscription
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admin can view their own subscription
        if ($user->role === UserRole::ADMIN) {
            return $subscription->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create subscriptions.
     * 
     * Requirements: 3.1
     */
    public function create(User $user): bool
    {
        // Only superadmin can create subscriptions
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can update the subscription.
     * 
     * Requirements: 3.2
     */
    public function update(User $user, Subscription $subscription): bool
    {
        // Only superadmin can update subscriptions
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can delete the subscription.
     * 
     * Requirements: 3.1
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        // Only superadmin can delete subscriptions
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can restore the subscription.
     * 
     * Requirements: 3.1
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        // Only superadmin can restore subscriptions
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can permanently delete the subscription.
     * 
     * Requirements: 3.1
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        // Only superadmin can force delete subscriptions
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can renew the subscription.
     * 
     * Requirements: 3.4
     */
    public function renew(User $user, Subscription $subscription): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            $this->logSensitiveOperation('renew', $user, $subscription);
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can suspend the subscription.
     * 
     * Requirements: 3.5
     */
    public function suspend(User $user, Subscription $subscription): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            $this->logSensitiveOperation('suspend', $user, $subscription);
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can activate the subscription.
     * 
     * Requirements: 3.5
     */
    public function activate(User $user, Subscription $subscription): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            $this->logSensitiveOperation('activate', $user, $subscription);
            return true;
        }

        return false;
    }

    /**
     * Log sensitive subscription management operations for audit compliance.
     * 
     * @param string $operation The operation being performed
     * @param User $user The authenticated superadmin user
     * @param Subscription $subscription The target subscription
     * @return void
     */
    private function logSensitiveOperation(string $operation, User $user, Subscription $subscription): void
    {
        $request = request();
        
        Log::channel('audit')->info("Subscription {$operation} operation", [
            'operation' => $operation,
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_role' => $user->role->value,
            'target_subscription_id' => $subscription->id,
            'target_subscription_plan' => $subscription->plan_type,
            'target_subscription_status' => $subscription->status,
            'target_user_id' => $subscription->user_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
