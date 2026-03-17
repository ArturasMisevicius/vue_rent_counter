<?php

namespace App\Http\Controllers\Tenant\Readings;

use App\Http\Controllers\Controller;
use App\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use Illuminate\View\View;

class CreateController extends Controller
{
    public function __invoke(): View
    {
        return view('tenant.readings.create', [
            'breadcrumbs' => [
                new BreadcrumbItemData(__('tenant.navigation.home'), route('tenant.home')),
                new BreadcrumbItemData(__('tenant.pages.readings.heading')),
            ],
        ]);
    }
}
