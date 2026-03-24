<?php

namespace App\Filament\Actions\Preferences;

use App\Filament\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Http\Request;

class ResolveGuestLocaleAction
{
    public function __construct(
        private readonly SupportedLocaleOptions $supportedLocaleOptions,
    ) {}

    public function handle(Request $request): string
    {
        return $this->sessionLocale($request)
            ?? $this->supportedLocale(app()->getLocale())
            ?? $this->fallbackLocale();
    }

    public function sessionLocale(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        return $this->supportedLocale(
            $request->session()->get(config('app.guest_locale_session_key', 'guest_locale')),
        );
    }

    private function supportedLocale(mixed $locale): ?string
    {
        if (is_string($locale) && in_array($locale, $this->supportedLocaleOptions->codes(), true)) {
            return $locale;
        }

        return null;
    }

    private function fallbackLocale(): string
    {
        return $this->supportedLocaleOptions->fallbackLocale();
    }
}
