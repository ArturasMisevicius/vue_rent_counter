<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Filament\Actions\Preferences\ResolveGuestLocaleAction;
use App\Filament\Support\Preferences\SupportedLocaleOptions;

trait AppliesShellLocale
{
    protected function applyShellLocale(): void
    {
        $supportedLocaleOptions = app(SupportedLocaleOptions::class);
        $locale = auth()->guard()->user()?->locale;

        if (! is_string($locale) || ! in_array($locale, $supportedLocaleOptions->codes(), true)) {
            $locale = app(ResolveGuestLocaleAction::class)->handle(request());
        }

        app()->setLocale($locale);
    }
}
