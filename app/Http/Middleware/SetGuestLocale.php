<?php

namespace App\Http\Middleware;

use App\Support\Geography\BalticReferenceCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetGuestLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $locale = $request->session()->get(
            config('app.guest_locale_session_key', 'guest_locale'),
        );

        if (
            is_string($locale) &&
            in_array($locale, BalticReferenceCatalog::supportedLocaleCodes(), true)
        ) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
