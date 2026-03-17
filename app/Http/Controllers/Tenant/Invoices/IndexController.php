<?php

namespace App\Http\Controllers\Tenant\Invoices;

use App\Http\Controllers\Controller;
use App\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use App\Support\Tenant\Portal\TenantInvoiceIndexQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Request $request, TenantInvoiceIndexQuery $tenantInvoiceIndexQuery): View
    {
        return view('tenant.invoices.index', [
            'breadcrumbs' => [
                new BreadcrumbItemData(__('tenant.navigation.home'), route('tenant.home')),
                new BreadcrumbItemData(__('tenant.pages.invoices.heading')),
            ],
            'invoices' => $tenantInvoiceIndexQuery->for($request->user()),
        ]);
    }
}
