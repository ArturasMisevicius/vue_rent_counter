<?php

namespace App\Http\Middleware;

use App\Filament\Actions\Preferences\ResolveGuestLocaleAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetGuestLocale
{
    public function __construct(
        private readonly ResolveGuestLocaleAction $resolveGuestLocale,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        if ($locale = $this->resolveGuestLocale->sessionLocale($request)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
