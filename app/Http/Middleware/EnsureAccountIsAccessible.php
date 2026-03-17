<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Support\Auth\AuthenticatedSessionMarker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsAccessible
{
    public function __construct(
        private readonly AuthenticatedSessionMarker $authenticatedSessionMarker,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $organization = $user->organization;

        if (
            $user->status === UserStatus::SUSPENDED ||
            $organization?->status === OrganizationStatus::SUSPENDED
        ) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withCookie($this->authenticatedSessionMarker->forget());
        }

        return $next($request);
    }
}
