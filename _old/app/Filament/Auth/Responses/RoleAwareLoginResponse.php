<?php

declare(strict_types=1);

namespace App\Filament\Auth\Responses;

use App\Models\User;
use App\Services\RoleDashboardResolver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

final readonly class RoleAwareLoginResponse implements LoginResponseContract
{
    public function __construct(
        private RoleDashboardResolver $dashboardResolver,
    ) {}

    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        return $this->dashboardResolver->redirectToDashboard($user);
    }
}
