<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTenant
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $this->workspaceResolver->resolveForRequest($request);
        $user = $request->user();

        abort_unless($workspace?->isTenant(), 403);
        abort_unless($user instanceof User, 403);

        Gate::forUser($user)->authorize('accessTenantPortal', $user);

        return $next($request);
    }
}
