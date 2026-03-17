<?php

namespace App\Actions\Superadmin\Organizations;

use App\Models\Organization;
use App\Models\User;
use App\Support\Auth\ImpersonationManager;
use Illuminate\Validation\ValidationException;

class StartOrganizationImpersonationAction
{
    public function __construct(
        private readonly ImpersonationManager $impersonationManager,
    ) {}

    public function __invoke(User $impersonator, Organization $organization): User
    {
        $targetUser = $organization->owner()
            ->select(['id', 'name', 'email', 'role', 'organization_id', 'status', 'locale', 'last_login_at', 'password', 'remember_token'])
            ->first()
            ?? $organization->users()
                ->assignableOrganizationOwner()
                ->orderBy('id')
                ->first();

        if (! $targetUser instanceof User) {
            throw ValidationException::withMessages([
                'organization' => 'The organization does not have an admin user available for impersonation.',
            ]);
        }

        return $this->impersonationManager->start($impersonator, $targetUser);
    }
}
