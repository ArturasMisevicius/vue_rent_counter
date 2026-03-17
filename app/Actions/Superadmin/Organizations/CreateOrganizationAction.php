<?php

namespace App\Actions\Superadmin\Organizations;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Superadmin\Organizations\StoreOrganizationRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrganizationAction
{
    public function __invoke(array $attributes): Organization
    {
        $data = Validator::make($attributes, StoreOrganizationRequest::ruleset())->validate();

        return DB::transaction(function () use ($data): Organization {
            $organization = Organization::query()->create([
                'name' => $data['name'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['name']),
                'status' => OrganizationStatus::ACTIVE,
                'owner_user_id' => null,
            ]);

            $owner = $this->assignExistingOwner($organization, $data);

            if ($owner instanceof User) {
                $organization->forceFill([
                    'owner_user_id' => $owner->id,
                ])->save();
            } else {
                $this->inviteOwner($organization, $data);
            }

            $this->createSubscription($organization, $data);

            return $organization->refresh();
        });
    }

    /**
     * @param  array{name: string, owner_email: string}  $data
     */
    private function assignExistingOwner(Organization $organization, array $data): ?User
    {
        $existingOwner = User::query()
            ->assignableOrganizationOwner()
            ->where('email', $data['owner_email'])
            ->first();

        if (! $existingOwner instanceof User) {
            return null;
        }

        if (filled($existingOwner->organization_id) && ($existingOwner->organization_id !== $organization->id)) {
            throw ValidationException::withMessages([
                'data.owner_email' => 'The selected owner already belongs to another organization.',
            ]);
        }

        $existingOwner->forceFill([
            'organization_id' => $organization->id,
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ])->save();

        return $existingOwner;
    }

    /**
     * @param  array{owner_name: string, owner_email: string}  $data
     */
    private function inviteOwner(Organization $organization, array $data): void
    {
        $invitation = OrganizationInvitation::query()->create([
            'organization_id' => $organization->id,
            'inviter_user_id' => auth()->id(),
            'email' => $data['owner_email'],
            'role' => UserRole::ADMIN,
            'full_name' => $data['owner_name'],
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationInvitationNotification($invitation));
    }

    /**
     * @param  array{plan: string, duration: string}  $data
     */
    private function createSubscription(Organization $organization, array $data): Subscription
    {
        $plan = SubscriptionPlan::from($data['plan']);
        $duration = SubscriptionDuration::from($data['duration']);
        $startsAt = now()->startOfDay();

        return Subscription::query()->create([
            'organization_id' => $organization->id,
            'plan' => $plan,
            'plan_name_snapshot' => $plan->label(),
            'limits_snapshot' => $plan->limitsSnapshot(),
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => $startsAt,
            'expires_at' => $this->resolveExpiry($startsAt, $duration),
            'is_trial' => false,
        ]);
    }

    private function resolveExpiry(Carbon $startsAt, SubscriptionDuration $duration): Carbon
    {
        if ($duration === SubscriptionDuration::WEEKLY) {
            return $startsAt->copy()->addWeek();
        }

        return $startsAt->copy()->addMonthsNoOverflow($duration->months());
    }

    private function resolveSlug(?string $slug, string $name): string
    {
        $baseSlug = Str::slug(filled($slug) ? $slug : $name);
        $resolvedSlug = $baseSlug;
        $suffix = 2;

        while (Organization::query()->where('slug', $resolvedSlug)->exists()) {
            $resolvedSlug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $resolvedSlug;
    }
}
