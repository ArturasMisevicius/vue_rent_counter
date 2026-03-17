<?php

namespace App\Http\Controllers\Tenant\Profile;

use App\Http\Controllers\Controller;
use App\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isTenant(), 403);

        return view('tenant.profile.edit', [
            'breadcrumbs' => [
                new BreadcrumbItemData(__('tenant.navigation.home'), route('tenant.home')),
                new BreadcrumbItemData(__('tenant.pages.profile.heading')),
            ],
            'tenant' => $request->user(),
            'supportedLocales' => collect(config('tenanto.locales', []))
                ->mapWithKeys(fn (array $locale, string $code): array => [$code => data_get($locale, 'native_name', $code)])
                ->all(),
        ]);
    }
}
