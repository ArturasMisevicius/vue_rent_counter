<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\Superadmin\Subscriptions\StoreOrganizationSubscriptionRequest;
use App\Models\Organization;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrganizationSubscriptionAction
{
    public function handle(Organization $organization, array $attributes): Subscription
    {
        /** @var StoreOrganizationSubscriptionRequest $request */
        $request = new StoreOrganizationSubscriptionRequest;
        $validated = $request->validatePayload($attributes);

        return DB::transaction(function () use ($organization, $validated): Subscription {
            if ($organization->subscriptions()->exists()) {
                throw ValidationException::withMessages([
                    'subscription' => __('superadmin.organizations.relations.subscriptions.validation.already_exists'),
                ]);
            }

            $plan = SubscriptionPlan::from((string) $validated['plan']);
            $status = SubscriptionStatus::from((string) $validated['status']);

            $subscription = new Subscription([
                'status' => $status,
                'starts_at' => CarbonImmutable::parse((string) $validated['starts_at']),
                'expires_at' => CarbonImmutable::parse((string) $validated['expires_at']),
                'is_trial' => $status === SubscriptionStatus::TRIALING,
            ]);

            $subscription->organization()->associate($organization);
            $subscription->applyPlanSnapshots($plan);
            $subscription->save();

            return $subscription->fresh();
        });
    }
}
