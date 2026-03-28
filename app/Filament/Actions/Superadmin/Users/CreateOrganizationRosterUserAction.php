<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Superadmin\Users\StoreOrganizationRosterUserRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOrganizationRosterUserAction
{
    public function __construct(
        private readonly SyncOrganizationMembershipAction $syncOrganizationMembershipAction,
    ) {}

    public function handle(Organization $organization, array $attributes, User $actor): User
    {
        /** @var StoreOrganizationRosterUserRequest $request */
        $request = new StoreOrganizationRosterUserRequest;
        $validated = $request->validatePayload($attributes, $actor);

        return DB::transaction(function () use ($organization, $validated, $actor): User {
            $user = User::query()->create([
                'organization_id' => $organization->id,
                'name' => (string) $validated['name'],
                'email' => (string) $validated['email'],
                'role' => UserRole::from((string) $validated['role']),
                'status' => UserStatus::from((string) $validated['status']),
                'locale' => (string) $validated['locale'],
                'password' => (string) $validated['password'],
                'suspended_at' => $validated['status'] === UserStatus::SUSPENDED->value ? now() : null,
                'suspension_reason' => null,
            ]);

            $this->syncOrganizationMembershipAction->handle($organization, $user, $actor);

            return $user->fresh();
        });
    }
}
