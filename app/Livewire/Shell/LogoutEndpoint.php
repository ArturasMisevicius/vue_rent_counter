<?php

namespace App\Livewire\Shell;

use App\Filament\Actions\Preferences\StoreGuestLocaleAction;
use App\Filament\Support\Auth\AuthenticatedSessionHistory;
use App\Filament\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LogoutEndpoint extends Component
{
    public function logout(
        Request $request,
        AuthenticatedSessionHistory $authenticatedSessionHistory,
        StoreGuestLocaleAction $storeGuestLocaleAction,
        SupportedLocaleOptions $supportedLocaleOptions,
    ): RedirectResponse {
        $locale = $request->user()?->locale;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (is_string($locale) && in_array($locale, $supportedLocaleOptions->codes(), true)) {
            $storeGuestLocaleAction->handle($request, $locale);
        }

        return redirect()->route('login')
            ->withCookie($authenticatedSessionHistory->forget());
    }
}
