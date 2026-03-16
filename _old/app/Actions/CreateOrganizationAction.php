<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionPlanType;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationOwnerInvitationNotification;
use App\Services\SubscriptionService;
use App\Support\Organizations\OrganizationSubscriptionTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateOrganizationAction
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    public function handle(
        string $organizationName,
        string $slug,
        string $ownerEmail,
        SubscriptionPlanType $plan,
        int $durationInMonths,
        User $actor,
    ): Organization {
        $expiresAt = OrganizationSubscriptionTerm::expiresAt($durationInMonths);
        $organizationPlan = SubscriptionPlan::tryFrom($plan->value) ?? SubscriptionPlan::BASIC;
        $owner = null;
        $temporaryPassword = null;

        /** @var Organization $organization */
        $organization = DB::transaction(function () use (
            &$owner,
            &$temporaryPassword,
            $organizationName,
            $slug,
            $ownerEmail,
            $plan,
            $organizationPlan,
            $expiresAt,
            $actor,
        ): Organization {
            $organization = Organization::create([
                'name' => $organizationName,
                'slug' => Str::slug($slug),
                'email' => $ownerEmail,
                'primary_contact_email' => $ownerEmail,
                'plan' => $organizationPlan,
                'subscription_plan' => $organizationPlan,
                'max_properties' => $organizationPlan->getMaxProperties(),
                'max_users' => $organizationPlan->getMaxUsers(),
                'subscription_ends_at' => $expiresAt,
                'is_active' => true,
                'created_by' => $actor->id,
                'created_by_admin_id' => $actor->id,
                'timezone' => config('app.timezone', 'Europe/Vilnius'),
                'locale' => app()->getLocale(),
                'currency' => 'EUR',
            ]);

            $owner = User::query()
                ->select(['id', 'role', 'name', 'email', 'currency', 'tenant_id', 'is_active'])
                ->where('email', $ownerEmail)
                ->first();

            if ($owner instanceof User) {
                $this->assignExistingOwner($owner, $organization);
            } else {
                $temporaryPassword = Str::password(16);
                $owner = $this->createOwnerAccount($organization, $ownerEmail, $temporaryPassword);
            }

            $this->syncMembership($owner, $organization, $actor);
            $this->subscriptionService->createOrRefreshSubscription($owner, $plan, $expiresAt);

            return $organization;
        });

        if (($owner instanceof User) && filled($temporaryPassword)) {
            $owner->notify(new OrganizationOwnerInvitationNotification($organization, $temporaryPassword));
        }

        return $organization;
    }

    private function assignExistingOwner(User $owner, Organization $organization): void
    {
        $owner->forceFill([
            'tenant_id' => $organization->id,
            'role' => $owner->role === UserRole::SUPERADMIN ? UserRole::SUPERADMIN : UserRole::ADMIN,
            'is_active' => true,
            'organization_name' => $organization->name,
            'currency' => $organization->currency ?? $owner->currency,
            'parent_user_id' => null,
        ]);

        if (blank($owner->name)) {
            $owner->name = $this->guessNameFromEmail($owner->email, $organization->name);
        }

        $owner->save();
    }

    private function createOwnerAccount(
        Organization $organization,
        string $ownerEmail,
        string $temporaryPassword,
    ): User {
        $owner = new User;

        $owner->forceFill([
            'tenant_id' => $organization->id,
            'name' => $this->guessNameFromEmail($ownerEmail, $organization->name),
            'email' => $ownerEmail,
            'password' => $temporaryPassword,
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'organization_name' => $organization->name,
            'currency' => $organization->currency ?? 'EUR',
            'parent_user_id' => null,
        ]);

        $owner->save();

        return $owner;
    }

    private function syncMembership(User $owner, Organization $organization, User $actor): void
    {
        $owner->organizationMemberships()->syncWithoutDetaching([
            $organization->id => [
                'role' => UserRole::ADMIN->value,
                'joined_at' => now(),
                'is_active' => true,
                'invited_by' => $actor->id,
            ],
        ]);
    }

    private function guessNameFromEmail(string $email, string $organizationName): string
    {
        $localPart = Str::before($email, '@');

        return filled($localPart)
            ? Str::headline(str_replace(['.', '_', '-'], ' ', $localPart))
            : "{$organizationName} Owner";
    }
}
