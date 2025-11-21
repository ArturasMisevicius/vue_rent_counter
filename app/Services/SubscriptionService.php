<?php

namespace App\Services;

use App\Exceptions\SubscriptionExpiredException;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Create a new subscription for an admin user.
     *
     * @param User $admin The admin user to create subscription for
     * @param string $planType The subscription plan type (basic, professional, enterprise)
     * @param Carbon $expiresAt The expiration date for the subscription
     * @return Subscription The created subscription
     */
    public function createSubscription(User $admin, string $planType, Carbon $expiresAt): Subscription
    {
        $limits = $this->getPlanLimits($planType);

        return Subscription::create([
            'user_id' => $admin->id,
            'plan_type' => $planType,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'max_properties' => $limits['max_properties'],
            'max_tenants' => $limits['max_tenants'],
        ]);
    }

    /**
     * Renew an existing subscription with a new expiry date.
     *
     * @param Subscription $subscription The subscription to renew
     * @param Carbon $newExpiryDate The new expiration date
     * @return Subscription The renewed subscription
     */
    public function renewSubscription(Subscription $subscription, Carbon $newExpiryDate): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'expires_at' => $newExpiryDate,
        ]);

        return $subscription->fresh();
    }

    /**
     * Suspend a subscription with a reason.
     *
     * @param Subscription $subscription The subscription to suspend
     * @param string $reason The reason for suspension
     * @return void
     */
    public function suspendSubscription(Subscription $subscription, string $reason): void
    {
        $subscription->update([
            'status' => 'suspended',
        ]);

        // Log the suspension reason (could be stored in audit table)
        logger()->info("Subscription {$subscription->id} suspended", [
            'reason' => $reason,
            'user_id' => $subscription->user_id,
        ]);
    }

    /**
     * Cancel a subscription.
     *
     * @param Subscription $subscription The subscription to cancel
     * @return void
     */
    public function cancelSubscription(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Check the subscription status and return detailed information.
     *
     * @param User $admin The admin user to check subscription for
     * @return array Status information including active status, expiry, and limits
     */
    public function checkSubscriptionStatus(User $admin): array
    {
        $subscription = $admin->subscription;

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'is_active' => false,
                'status' => null,
                'expires_at' => null,
                'days_until_expiry' => null,
                'max_properties' => 0,
                'max_tenants' => 0,
                'current_properties' => 0,
                'current_tenants' => 0,
            ];
        }

        $currentProperties = $admin->properties()->count();
        $currentTenants = $admin->childUsers()->where('role', 'tenant')->count();

        return [
            'has_subscription' => true,
            'is_active' => $subscription->isActive(),
            'status' => $subscription->status,
            'expires_at' => $subscription->expires_at,
            'days_until_expiry' => $subscription->daysUntilExpiry(),
            'max_properties' => $subscription->max_properties,
            'max_tenants' => $subscription->max_tenants,
            'current_properties' => $currentProperties,
            'current_tenants' => $currentTenants,
            'can_add_property' => $subscription->canAddProperty(),
            'can_add_tenant' => $subscription->canAddTenant(),
        ];
    }

    /**
     * Enforce subscription limits for an admin user.
     * Throws exceptions if subscription is expired or limits are exceeded.
     *
     * @param User $admin The admin user to check limits for
     * @param string $resourceType The type of resource being created ('property' or 'tenant')
     * @return void
     * @throws SubscriptionExpiredException If subscription is expired
     * @throws SubscriptionLimitExceededException If resource limit is exceeded
     */
    public function enforceSubscriptionLimits(User $admin, string $resourceType = null): void
    {
        $subscription = $admin->subscription;

        if (!$subscription) {
            throw new SubscriptionExpiredException('No active subscription found.');
        }

        if (!$subscription->isActive()) {
            throw new SubscriptionExpiredException('Your subscription has expired. Please renew to continue managing your properties.');
        }

        if ($resourceType === 'property' && !$subscription->canAddProperty()) {
            throw new SubscriptionLimitExceededException(
                "You have reached the maximum number of properties ({$subscription->max_properties}) for your plan. Please upgrade your subscription."
            );
        }

        if ($resourceType === 'tenant' && !$subscription->canAddTenant()) {
            throw new SubscriptionLimitExceededException(
                "You have reached the maximum number of tenants ({$subscription->max_tenants}) for your plan. Please upgrade your subscription."
            );
        }
    }

    /**
     * Get the limits for a given plan type.
     *
     * @param string $planType The plan type (basic, professional, enterprise)
     * @return array The limits for the plan
     */
    protected function getPlanLimits(string $planType): array
    {
        $limits = [
            'basic' => [
                'max_properties' => config('subscription.max_properties_basic', 10),
                'max_tenants' => config('subscription.max_tenants_basic', 50),
            ],
            'professional' => [
                'max_properties' => config('subscription.max_properties_professional', 50),
                'max_tenants' => config('subscription.max_tenants_professional', 200),
            ],
            'enterprise' => [
                'max_properties' => config('subscription.max_properties_enterprise', 999999),
                'max_tenants' => config('subscription.max_tenants_enterprise', 999999),
            ],
        ];

        return $limits[$planType] ?? $limits['basic'];
    }
}
