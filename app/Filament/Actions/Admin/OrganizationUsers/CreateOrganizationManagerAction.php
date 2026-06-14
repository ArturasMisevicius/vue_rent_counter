<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\OrganizationUsers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateOrganizationManagerAction
{
    /**
     * @param  array{name: string, email: string, status: string, locale: string, password: string}  $data
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
                'status' => UserStatus::from($data['status']),
                'locale' => $data['locale'],
                'password' => $data['password'],
            ]);

            return $organization->memberships()->create([
                'user_id' => $manager->id,
                'role' => UserRole::MANAGER->value,
                'permissions' => null,
                'joined_at' => now(),
                'left_at' => null,
                'is_active' => true,
                'invited_by' => $actor->id,
            ]);
        });
    }
}
