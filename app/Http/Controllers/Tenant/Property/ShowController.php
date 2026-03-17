<?php

namespace App\Http\Controllers\Tenant\Property;

use App\Http\Controllers\Controller;
use App\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use App\Support\Tenant\Portal\TenantPropertyPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowController extends Controller
{
    public function __invoke(Request $request, TenantPropertyPresenter $presenter): View
    {
        abort_unless($request->user()?->isTenant(), 403);

        return view('tenant.property.show', [
            'breadcrumbs' => [
                new BreadcrumbItemData(__('tenant.navigation.home'), route('tenant.home')),
                new BreadcrumbItemData(__('tenant.pages.property.heading')),
            ],
            'summary' => $presenter->for($request->user()),
        ]);
    }
}
