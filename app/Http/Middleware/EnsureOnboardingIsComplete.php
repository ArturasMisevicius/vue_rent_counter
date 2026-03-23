<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user?->isAdmin() &&
            blank($user->organization_id) &&
            ! $this->isSuperadminControlPlaneRoute($request->route()?->getName())
        ) {
            return redirect()->route('welcome.show');
        }

        return $next($request);
    }

    private function isSuperadminControlPlaneRoute(?string $routeName): bool
    {
        if (! is_string($routeName)) {
            return false;
        }

        foreach ([
            'filament.admin.pages.platform-dashboard',
            'filament.admin.pages.integration-health',
            'filament.admin.pages.system-configuration',
            'filament.admin.pages.translation-management',
            'filament.admin.resources.audit-logs.',
            'filament.admin.resources.languages.',
            'filament.admin.resources.organizations.',
            'filament.admin.resources.security-violations.',
            'filament.admin.resources.subscriptions.',
            'filament.admin.resources.users.',
        ] as $pattern) {
            if (str_ends_with($pattern, '.')) {
                if (str_starts_with($routeName, $pattern)) {
                    return true;
                }

                continue;
            }

            if ($routeName === $pattern) {
                return true;
            }
        }

        return false;
    }
}
