<?php

namespace App\Http\Controllers\Tenant\Invoices;

use App\Http\Controllers\Controller;
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
        $tenant = $request->user()->loadMissing('organization.settings:id,organization_id,payment_instructions,invoice_footer');
        $selectedStatus = $request->string('status')->toString() ?: 'all';

        if ($selectedStatus === 'outstanding') {
            $selectedStatus = 'unpaid';
        }

        return view('tenant.invoices.index', [
            'invoices' => $tenantInvoiceIndexQuery->for($tenant, $selectedStatus === 'all' ? null : $selectedStatus),
            'paymentInstructions' => $paymentInstructionsResolver->resolve($tenant->organization?->settings),
            'selectedStatus' => $selectedStatus,
        ]);
    }
}
