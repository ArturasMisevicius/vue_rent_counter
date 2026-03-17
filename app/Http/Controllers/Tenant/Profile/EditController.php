<?php

namespace App\Http\Controllers\Tenant\Profile;

use App\Http\Controllers\Controller;
use App\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(): View
    {
        return view('tenant.profile.edit', [
            'breadcrumbs' => [
                new BreadcrumbItemData(__('tenant.navigation.home'), route('tenant.home')),
                new BreadcrumbItemData(__('tenant.pages.profile.heading')),
            ],
        ]);
    }
}
