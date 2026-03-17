<?php

namespace App\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionDuration;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExtendSubscriptionAction
{
    /**
     * @param  array{duration: string}  $attributes
     */
    public function __invoke(Subscription $subscription, array $attributes): Subscription
    {
        $data = Validator::make($attributes, [
            'duration' => ['required', Rule::enum(SubscriptionDuration::class)],
        ])->validate();

        $duration = SubscriptionDuration::from($data['duration']);
        $anchor = ($subscription->expires_at?->isFuture() ?? false)
            ? $subscription->expires_at->copy()
            : now()->startOfDay();

        $subscription->update([
            'expires_at' => $this->resolveExpiry($anchor, $duration),
        ]);

        return $subscription->refresh();
    }

    private function resolveExpiry(Carbon $startsAt, SubscriptionDuration $duration): Carbon
    {
        if ($duration === SubscriptionDuration::WEEKLY) {
            return $startsAt->copy()->addWeek();
        }

        return $startsAt->copy()->addMonthsNoOverflow($duration->months());
    }
}
