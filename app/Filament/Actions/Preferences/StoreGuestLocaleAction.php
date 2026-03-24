<?php

namespace App\Filament\Actions\Preferences;

use Illuminate\Http\Request;

class StoreGuestLocaleAction
{
    public function handle(Request $request, string $locale): void
    {
        if (! $request->hasSession()) {
            $session = app('session')->driver();
            $session->start();
            $request->setLaravelSession($session);
        }

        $request->session()->put(
            config('app.guest_locale_session_key', 'guest_locale'),
            $locale,
        );

        app()->setLocale($locale);
    }
}
