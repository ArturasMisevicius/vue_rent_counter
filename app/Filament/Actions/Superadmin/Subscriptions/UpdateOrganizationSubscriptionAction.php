<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\Superadmin\Subscriptions\UpdateOrganizationSubscriptionRequest;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationSubscriptionAction
{
    public function handle(Subscription $subscription, array $attributes): Subscription
    {
        /** @var UpdateOrganizationSubscriptionRequest $request */
        $request = new UpdateOrganizationSubscriptionRequest;
        $validated = $request->validatePayload($attributes);

        return DB::transaction(function () use ($subscription, $validated): Subscription {
            $plan = SubscriptionPlan::from((string) $validated['plan']);
            $status = SubscriptionStatus::from((string) $validated['status']);

            $subscription->forceFill([
                'status' => $status,
                'starts_at' => CarbonImmutable::parse((string) $validated['starts_at']),
                'expires_at' => CarbonImmutable::parse((string) $validated['expires_at']),
                'is_trial' => $status === SubscriptionStatus::TRIALING,
            ]);

            $subscription->applyPlanSnapshots($plan);
            $subscription->save();

            return $subscription->fresh();
        });
    }
}
