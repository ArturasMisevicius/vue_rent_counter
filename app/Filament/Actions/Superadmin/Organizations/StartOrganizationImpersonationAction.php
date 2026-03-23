<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Http\Requests\Superadmin\Organizations\ImpersonateUserRequest;
use App\Models\User;
use App\Services\ImpersonationService;

class StartOrganizationImpersonationAction
{
    public function __construct(
        private readonly ImpersonationService $impersonationService,
    ) {}

    public function handle(User $impersonator, User $target): void
    {
        /** @var ImpersonateUserRequest $request */
        $request = new ImpersonateUserRequest;
        $request->validatePayload([
            'user_id' => $target->id,
        ], $impersonator);

        $this->impersonationService->start($impersonator, $target);
    }
}
