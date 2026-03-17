<?php

namespace App\Http\Middleware;

use App\Enums\LanguageStatus;
use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetAuthenticatedUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (! filled($locale) || ! $this->isSupportedLocale($locale)) {
            $locale = $this->defaultLocale();
        }

        if (filled($locale)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function isSupportedLocale(string $locale): bool
    {
        if ($this->hasLanguageSourceOfTruth()) {
            return Language::query()
                ->where('code', $locale)
                ->where('status', LanguageStatus::ACTIVE)
                ->exists();
        }

        return array_key_exists($locale, config('tenanto.locales', []));
    }

    private function defaultLocale(): string
    {
        if ($this->hasLanguageSourceOfTruth()) {
            $locale = Language::query()
                ->where('status', LanguageStatus::ACTIVE)
                ->where('is_default', true)
                ->value('code');

            if (is_string($locale) && filled($locale)) {
                return $locale;
            }
        }

        return (string) config('tenanto.localization.fallback_locale', config('app.fallback_locale', 'en'));
    }

    private function hasLanguageSourceOfTruth(): bool
    {
        return Schema::hasTable('languages') && Language::query()->exists();
    }
}
