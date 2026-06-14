<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\OrganizationUsers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Auth\CreateOrganizationInvitationAction;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrganizationManagerAction
{
    public function __construct(
        private readonly CreateOrganizationInvitationAction $createOrganizationInvitationAction,
    ) {}

    /**
     * @param  array{name: string, email: string, locale: string}  $data
     */
    public function handle(Organization $organization, User $actor, array $data): OrganizationUser
    {
        abort_unless(
            $actor->isSuperadmin() || $actor->currentOrganization()?->is($organization),
            403,
        );

        Gate::forUser($actor)->authorize('create', OrganizationUser::class);

        return DB::transaction(function () use ($organization, $actor, $data): OrganizationUser {
            $manager = $organization->users()->create([
                'role' => UserRole::MANAGER,
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => UserStatus::INACTIVE,
                'locale' => $data['locale'],
                'password' => Str::random(32),
            ]);

            $membership = $organization->memberships()->create([
                'user_id' => $manager->id,
                'role' => UserRole::MANAGER->value,
                'permissions' => null,
                'joined_at' => now(),
                'left_at' => null,
                'is_active' => true,
                'invited_by' => $actor->id,
            ]);

            $this->issueInvitation($actor, $organization, $manager);

            return $membership->fresh(['organization', 'user', 'inviter']);
        });
    }

    private function issueInvitation(User $actor, Organization $organization, User $manager): OrganizationInvitation
    {
        $inviter = $actor->isSuperadmin()
            ? $this->resolveInviterForSuperadmin($organization)
            : $actor;

        return $this->createOrganizationInvitationAction->handle($inviter, [
            'email' => $manager->email,
            'role' => UserRole::MANAGER,
            'full_name' => $manager->name,
            'existing_user_id' => $manager->id,
        ]);
    }

    private function resolveInviterForSuperadmin(Organization $organization): User
    {
        $organization->loadMissing([
            'owner:id,organization_id,name,email,role,status',
        ]);

        $inviter = $organization->owner;

        if (! $inviter instanceof User || ! $inviter->isAdminLike()) {
            $inviter = $organization->users()
                ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
                ->adminLike()
                ->orderedByName()
                ->first();
        }

        if (! $inviter instanceof User) {
            throw ValidationException::withMessages([
                'organization_id' => __('superadmin.organizations.messages.no_primary_admin'),
            ]);
        }

        return $inviter;
    }
}
