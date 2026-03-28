<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Filament\Support\Auth\AuthenticatedSessionHistory;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsAccessible
{
    public function __construct(
        private readonly AuthenticatedSessionHistory $authenticatedSessionHistory,
        private readonly WorkspaceResolver $workspaceResolver,
        private readonly ImpersonationService $impersonationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->impersonationService->expireIfNecessary($request);

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $organization = $user->organization;

        if (
            $user->status === UserStatus::SUSPENDED ||
            ! $this->workspaceResolver->hasValidOrganization($user) ||
            ($organization?->status instanceof OrganizationStatus && ! $organization->status->permitsAccess())
        ) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withCookie($this->authenticatedSessionHistory->forget());
        }

        $this->workspaceResolver->resolveForRequest($request);

        return $next($request);
    }
}
