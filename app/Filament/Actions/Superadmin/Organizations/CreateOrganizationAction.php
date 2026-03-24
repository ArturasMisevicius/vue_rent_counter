<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Http\Requests\Superadmin\Organizations\StoreOrganizationRequest;
use App\Models\Organization;
use App\Models\PlatformOrganizationInvitation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrganizationAction
{
    public function handle(User $actor, array $attributes): Organization
    {
        /** @var StoreOrganizationRequest $request */
        $request = new StoreOrganizationRequest;
        $validated = $request->validatePayload($attributes, $actor);

        return DB::transaction(function () use ($actor, $validated): Organization {
            $plan = SubscriptionPlan::from((string) $validated['plan']);
            $duration = SubscriptionDuration::from((string) $validated['duration']);
            $organization = Organization::query()->create([
                'name' => $validated['name'],
            ]);

            $owner = User::query()
                ->select(['id', 'organization_id', 'email', 'name', 'role', 'status', 'locale'])
                ->where('email', $validated['owner_email'])
                ->first();

            if ($owner !== null && $owner->organization_id !== null) {
                throw ValidationException::withMessages([
                    'owner_email' => 'The selected owner already belongs to another organization.',
                ]);
            }

            if ($owner !== null) {
                $owner->forceFill([
                    'organization_id' => $organization->id,
                    'role' => UserRole::ADMIN,
                ])->save();

                $organization->forceFill([
                    'owner_user_id' => $owner->id,
                ])->save();
            } else {
                PlatformOrganizationInvitation::query()->create([
                    'organization_name' => $organization->name,
                    'admin_email' => $validated['owner_email'],
                    'plan_type' => $plan->value,
                    'max_properties' => $plan->limits()['properties'],
                    'max_users' => $plan->limits()['tenants'],
                    'invited_by' => $actor->id,
                ]);
            }

            $subscription = new Subscription([
                'organization_id' => $organization->id,
                'status' => SubscriptionStatus::ACTIVE,
                'starts_at' => now()->startOfDay(),
                'expires_at' => now()->startOfDay()->addMonths($duration->months()),
                'is_trial' => false,
            ]);

            $subscription->applyPlanSnapshots($plan);
            $subscription->save();

            return $organization->fresh([
                'owner:id,name,email',
                'subscriptions',
            ]);
        });
    }
}
