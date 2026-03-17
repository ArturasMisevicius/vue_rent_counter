<?php

namespace App\Livewire\Preferences;

use App\Filament\Actions\Preferences\ResolveGuestLocaleRedirectAction;
use App\Filament\Actions\Preferences\StoreGuestLocaleAction;
use App\Http\Requests\Preferences\SetLocaleRequest;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

class UpdateGuestLocaleEndpoint extends Component
{
    public function update(
        SetLocaleRequest $request,
        ResolveGuestLocaleRedirectAction $resolveGuestLocaleRedirectAction,
        StoreGuestLocaleAction $storeGuestLocaleAction,
    ): RedirectResponse {
        $storeGuestLocaleAction->handle($request, $request->locale());

        return redirect()->to($resolveGuestLocaleRedirectAction->handle($request));
    }
}
