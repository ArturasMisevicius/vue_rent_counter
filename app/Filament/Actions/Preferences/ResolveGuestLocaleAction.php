<?php

namespace App\Filament\Actions\Preferences;

use Illuminate\Http\Request;

class ResolveGuestLocaleAction
{
    public function handle(Request $request): string
    {
        return $this->sessionLocale($request)
            ?? $this->supportedLocale(app()->getLocale())
            ?? $this->fallbackLocale();
    }

    public function sessionLocale(Request $request): ?string
    {
        return $this->supportedLocale(
            $request->session()->get(config('app.guest_locale_session_key', 'guest_locale')),
        );
    }

    private function supportedLocale(mixed $locale): ?string
    {
        if (
            is_string($locale) &&
            in_array($locale, array_keys(config('app.supported_locales', [])), true)
        ) {
            return $locale;
        }

        return null;
    }

    private function fallbackLocale(): string
    {
        $fallbackLocale = config('app.fallback_locale', 'en');

        return $this->supportedLocale($fallbackLocale) ?? 'en';
    }
}
