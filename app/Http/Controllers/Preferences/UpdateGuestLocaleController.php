<?php

namespace App\Http\Controllers\Preferences;

use App\Actions\Preferences\StoreGuestLocaleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Preferences\UpdateGuestLocaleRequest;
use Illuminate\Http\RedirectResponse;

class UpdateGuestLocaleController extends Controller
{
    public function __invoke(
        UpdateGuestLocaleRequest $request,
        StoreGuestLocaleAction $storeGuestLocaleAction,
    ): RedirectResponse {
        $storeGuestLocaleAction->handle($request, $request->locale());

        return back();
    }
}
