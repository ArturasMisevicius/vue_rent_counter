<?php

namespace App\Http\Controllers\Tenant\Invoices;

use App\Http\Controllers\Controller;
use App\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use App\Support\Tenant\Portal\PaymentInstructionsResolver;
use App\Support\Tenant\Portal\TenantInvoiceIndexQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(
        Request $request,
        TenantInvoiceIndexQuery $tenantInvoiceIndexQuery,
        PaymentInstructionsResolver $paymentInstructionsResolver,
    ): View {
        abort_unless($request->user()?->isTenant(), 403);

        $tenant = $request->user()->loadMissing('organization.settings:id,organization_id,payment_instructions,invoice_footer');

        return view('tenant.invoices.index', [
            'breadcrumbs' => [
                new BreadcrumbItemData(__('tenant.navigation.home'), route('tenant.home')),
                new BreadcrumbItemData(__('tenant.pages.invoices.heading')),
            ],
            'invoices' => $tenantInvoiceIndexQuery->for($tenant, $request->string('status')->toString() ?: null),
            'paymentInstructions' => $paymentInstructionsResolver->resolve($tenant->organization?->settings),
            'selectedStatus' => $request->string('status')->toString() ?: 'all',
        ]);
    }
}
