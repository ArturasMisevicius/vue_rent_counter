<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Localization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);
        
        if (Localization::isAvailable($locale)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Determine the locale for the request.
     */
    private function determineLocale(Request $request): string
    {
        // 1. Check session for stored locale
        if ($sessionLocale = $request->session()->get('locale')) {
            return $sessionLocale;
        }

        // 2. Check user preference if authenticated
        if ($request->user() && method_exists($request->user(), 'locale')) {
            return $request->user()->locale ?? Localization::fallbackLocale();
        }

        // 3. Check Accept-Language header
        $preferredLocale = $request->getPreferredLanguage(
            array_keys(config('locales.available', []))
        );

        return $preferredLocale ?? Localization::fallbackLocale();
    }
}