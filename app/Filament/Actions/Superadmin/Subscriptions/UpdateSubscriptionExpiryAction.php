<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Subscriptions;

use App\Http\Requests\Superadmin\Subscriptions\ExtendSubscriptionExpiryRequest;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class UpdateSubscriptionExpiryAction
{
    public function handle(Subscription $subscription, array $attributes): Subscription
    {
        /** @var ExtendSubscriptionExpiryRequest $request */
        $request = new ExtendSubscriptionExpiryRequest;
        $validated = $request->validatePayload($attributes);

        $nextExpiryDate = CarbonImmutable::parse($validated['expires_at'])->startOfDay();
        $currentExpiryDate = $subscription->expires_at?->toImmutable()->startOfDay();

        if ($currentExpiryDate !== null && $nextExpiryDate->lessThanOrEqualTo($currentExpiryDate)) {
            throw ValidationException::withMessages([
                'expires_at' => 'The expires at date must be later than the current expiry date.',
            ]);
        }

        $subscription->update([
            'expires_at' => $nextExpiryDate,
        ]);

        return $subscription->fresh();
    }
}
