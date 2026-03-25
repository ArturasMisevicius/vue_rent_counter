<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Http\Requests\Superadmin\Organizations\ImpersonateUserRequest;
use App\Models\User;
use App\Services\ImpersonationService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

        if ($target->organization !== null && ! $target->organization->status->permitsAccess()) {
            throw new AccessDeniedHttpException(__('superadmin.organizations.messages.cannot_impersonate_suspended'));
        }

        $this->impersonationService->start($impersonator, $target);
    }
}
