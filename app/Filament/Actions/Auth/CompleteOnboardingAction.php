<?php

namespace App\Filament\Actions\Auth;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteOnboardingAction
{
    public function handle(User $user, array $attributes): Organization
    {
        return DB::transaction(function () use ($user, $attributes): Organization {
            $organization = Organization::query()->create([
                'name' => $attributes['name'],
                'status' => OrganizationStatus::ACTIVE,
                'owner_user_id' => $user->id,
            ]);

            Subscription::query()->create([
                'organization_id' => $organization->id,
                'plan' => SubscriptionPlan::BASIC,
                'status' => SubscriptionStatus::TRIALING,
                'starts_at' => now()->startOfDay(),
                'expires_at' => now()->startOfDay()->addDays(14),
                'is_trial' => true,
            ]);

            $user->forceFill([
                'organization_id' => $organization->id,
            ])->save();

            return $organization;
        });
    }
}
