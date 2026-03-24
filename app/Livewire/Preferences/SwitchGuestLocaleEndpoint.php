<?php

declare(strict_types=1);

namespace App\Livewire\Preferences;

use App\Filament\Actions\Preferences\ResolveGuestLocaleRedirectAction;
use App\Filament\Actions\Preferences\StoreGuestLocaleAction;
use App\Filament\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Livewire\Component;

final class SwitchGuestLocaleEndpoint extends Component
{
    public function change(
        string $locale,
        Request $request,
        StoreGuestLocaleAction $storeGuestLocaleAction,
        ResolveGuestLocaleRedirectAction $resolveGuestLocaleRedirectAction,
        SupportedLocaleOptions $supportedLocaleOptions,
    ): RedirectResponse {
        if (! in_array($locale, $supportedLocaleOptions->codes(), true)) {
            abort(404);
        }

        $storeGuestLocaleAction->handle($request, $locale);

        return redirect()->to($resolveGuestLocaleRedirectAction->handle($request));
    }
}
