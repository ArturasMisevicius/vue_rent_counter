<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Actions\Preferences\ResolveGuestLocaleRedirectAction;
use App\Filament\Actions\Preferences\StoreGuestLocaleAction;
use App\Filament\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SwitchGuestLocaleController extends Controller
{
    public function __invoke(
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
