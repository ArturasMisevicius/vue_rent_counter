<?php

namespace App\Http\Middleware;

use App\Filament\Support\Preferences\SupportedLocaleOptions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAuthenticatedUserLocale
{
    public function __construct(
        private readonly SupportedLocaleOptions $supportedLocaleOptions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (filled($locale) && in_array($locale, $this->supportedLocaleOptions->codes(), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
