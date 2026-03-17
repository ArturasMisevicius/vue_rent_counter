<?php

namespace App\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpgradeSubscriptionPlanAction
{
    /**
     * @param  array{plan: string}  $attributes
     */
    public function __invoke(Subscription $subscription, array $attributes): Subscription
    {
        $data = Validator::make($attributes, [
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
        ])->validate();

        $plan = SubscriptionPlan::from($data['plan']);

        $subscription->update([
            'plan' => $plan,
            ...$plan->snapshotAttributes(),
        ]);

        return $subscription->refresh();
    }
}
