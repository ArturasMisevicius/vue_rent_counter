<?php

declare(strict_types=1);

namespace App\Services\SubscriptionStatusHandlers;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\ValueObjects\SubscriptionCheckResult;
use Illuminate\Http\Request;

/**
 * Handler for suspended or cancelled subscriptions.
 *
 * Implements read-only mode with status-specific messaging.
 */
final readonly class InactiveSubscriptionHandler implements SubscriptionStatusHandler
{
    private const DASHBOARD_ROUTE = 'admin.dashboard';

    private const SUSPENDED_MESSAGE = 'Your subscription has been suspended. Please contact support.';

    private const CANCELLED_MESSAGE = 'Your subscription has been cancelled. Please contact support to reactivate.';

    private const GENERIC_MESSAGE = 'Your subscription is not active. Please contact support.';

    public function handle(Request $request, ?Subscription $subscription): SubscriptionCheckResult
    {
        $message = $this->getMessage($subscription);

        // Allow read-only access for GET requests
        if ($request->isMethod('GET')) {
            return SubscriptionCheckResult::allowWithWarning($message);
        }

        // Block write operations
        return SubscriptionCheckResult::block($message, self::DASHBOARD_ROUTE);
    }

    private function getMessage(?Subscription $subscription): string
    {
        if (! $subscription) {
            return self::GENERIC_MESSAGE;
        }

        $status = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status
            : SubscriptionStatus::from($subscription->status);

        return match ($status) {
            SubscriptionStatus::SUSPENDED => self::SUSPENDED_MESSAGE,
            SubscriptionStatus::CANCELLED => self::CANCELLED_MESSAGE,
            default => self::GENERIC_MESSAGE,
        };
    }
}
