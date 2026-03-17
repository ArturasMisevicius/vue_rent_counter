<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAuthenticatedUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (
            filled($locale) &&
            in_array($locale, array_keys(config('app.supported_locales', [])), true)
        ) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
