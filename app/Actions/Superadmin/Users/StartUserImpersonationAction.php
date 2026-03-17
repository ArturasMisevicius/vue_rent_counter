<?php

namespace App\Actions\Superadmin\Users;

use App\Models\User;
use App\Support\Auth\ImpersonationManager;
use Illuminate\Validation\ValidationException;

class StartUserImpersonationAction
{
    public function __construct(
        private readonly ImpersonationManager $impersonationManager,
    ) {}

    public function __invoke(User $impersonator, User $targetUser): User
    {
        if ($targetUser->isSuperadmin()) {
            throw ValidationException::withMessages([
                'user' => 'Superadmin users cannot be impersonated.',
            ]);
        }

        return $this->impersonationManager->start($impersonator, $targetUser);
    }
}
