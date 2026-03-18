<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Subscriptions;

use App\Http\Requests\Superadmin\Subscriptions\ExtendSubscriptionExpiryRequest;
use App\Models\Subscription;

class UpdateSubscriptionExpiryAction
{
    public function handle(Subscription $subscription, array $attributes): Subscription
    {
        /** @var ExtendSubscriptionExpiryRequest $request */
        $request = new ExtendSubscriptionExpiryRequest;
        $validated = $request->validatePayload($attributes);

        $subscription->update([
            'expires_at' => $validated['expires_at'],
        ]);

        return $subscription->fresh();
    }
}
