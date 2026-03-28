<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Superadmin\Users\UpdateOrganizationRosterUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationRosterUserAction
{
    public function __construct(
        private readonly SyncOrganizationMembershipAction $syncOrganizationMembershipAction,
    ) {}

    public function handle(User $user, array $attributes, User $actor): User
    {
        /** @var UpdateOrganizationRosterUserRequest $request */
        $request = (new UpdateOrganizationRosterUserRequest)->forRecord($user);
        $validated = $request->validatePayload($attributes, $actor);

        return DB::transaction(function () use ($user, $validated, $actor): User {
            $user->update([
                'name' => (string) $validated['name'],
                'email' => (string) $validated['email'],
                'role' => UserRole::from((string) $validated['role']),
                'status' => UserStatus::from((string) $validated['status']),
                'locale' => (string) $validated['locale'],
                'suspended_at' => $validated['status'] === UserStatus::SUSPENDED->value ? now() : null,
                'suspension_reason' => null,
                ...(
                    filled($validated['password'] ?? null)
                        ? ['password' => (string) $validated['password']]
                        : []
                ),
            ]);

            if ($user->organization_id !== null) {
                $this->syncOrganizationMembershipAction->handle($user->organization, $user->fresh(), $actor);
            }

            return $user->fresh();
        });
    }
}
