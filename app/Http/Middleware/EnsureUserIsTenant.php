<?php

namespace App\Http\Middleware;

use App\Filament\Support\Workspace\WorkspaceResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTenant
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $this->workspaceResolver->resolveForRequest($request);

        abort_unless($workspace?->isTenant(), 403);

        return $next($request);
    }
}
