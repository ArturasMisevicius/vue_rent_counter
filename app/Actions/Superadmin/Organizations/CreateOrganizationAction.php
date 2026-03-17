<?php

namespace App\Actions\Superadmin\Organizations;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\PlatformOrganizationInvitation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrganizationAction
{
    public function handle(User $actor, array $attributes): Organization
    {
        abort_unless($actor->isSuperadmin(), 403);

        /** @var array{name: string, owner_email: string, owner_name: string, plan: SubscriptionPlan, duration: SubscriptionDuration} $validated */
        $validated = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email:rfc', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'plan' => ['required'],
            'duration' => ['required'],
        ])->validate();

        return DB::transaction(function () use ($actor, $validated): Organization {
            $organization = Organization::query()->create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
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
                    'name' => $validated['owner_name'],
                ])->save();

                $organization->forceFill([
                    'owner_user_id' => $owner->id,
                ])->save();
            } else {
                PlatformOrganizationInvitation::query()->create([
                    'organization_name' => $organization->name,
                    'admin_email' => $validated['owner_email'],
                    'plan_type' => $validated['plan']->value,
                    'max_properties' => $validated['plan']->limits()['properties'],
                    'max_users' => $validated['plan']->limits()['tenants'],
                    'invited_by' => $actor->id,
                ]);
            }

            $subscription = new Subscription([
                'organization_id' => $organization->id,
                'status' => SubscriptionStatus::ACTIVE,
                'starts_at' => now()->startOfDay(),
                'expires_at' => now()->startOfDay()->addMonths($validated['duration']->months()),
                'is_trial' => false,
            ]);

            $subscription->applyPlanSnapshots($validated['plan']);
            $subscription->save();

            return $organization->fresh([
                'owner:id,name,email',
                'subscriptions',
            ]);
        });
    }
}
