<?php

namespace App\Http\Controllers\Tenant\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isTenant(), 403);

        return view('tenant.profile.edit', [
            'tenant' => $request->user(),
            'supportedLocales' => collect(config('tenanto.locales', []))
                ->mapWithKeys(fn (array $locale, string $code): array => [$code => data_get($locale, 'native_name', $code)])
                ->all(),
        ]);
    }
}
