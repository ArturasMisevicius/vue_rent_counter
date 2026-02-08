<?php

declare(strict_types=1);

namespace App\Services\SubscriptionStatusHandlers;

use App\Models\Subscription;
use App\ValueObjects\SubscriptionCheckResult;
use Illuminate\Http\Request;

/**
 * Interface for subscription status handlers.
 *
 * Implements Strategy pattern for handling different subscription statuses.
 * Each concrete handler encapsulates the logic for a specific subscription state.
 */
interface SubscriptionStatusHandler
{
    /**
     * Handle the subscription check for a specific status.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Subscription|null  $subscription  The user's subscription (null for missing)
     * @return SubscriptionCheckResult The result of the check
     */
    public function handle(Request $request, ?Subscription $subscription): SubscriptionCheckResult;
}
