<?php

namespace App\Livewire\Tenant;

use App\Support\Tenant\Portal\PaymentInstructionsResolver;
use App\Support\Tenant\Portal\TenantInvoiceIndexQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Livewire\Component;

class InvoiceHistoryPage extends Component
{
    public string $selectedStatus = 'all';

    public function mount(Request $request): void
    {
        $this->selectedStatus = $request->string('status')->toString() ?: 'all';

        if ($this->selectedStatus === 'outstanding') {
            $this->selectedStatus = 'unpaid';
        }
    }

    public function render(
        TenantInvoiceIndexQuery $tenantInvoiceIndexQuery,
        PaymentInstructionsResolver $paymentInstructionsResolver,
    ): View {
        $tenant = auth()->user()->loadMissing(
            'organization.settings:id,organization_id,billing_contact_name,billing_contact_email,billing_contact_phone,payment_instructions,invoice_footer',
        );

        return view('tenant.invoices.index', [
            'invoices' => $tenantInvoiceIndexQuery->for(
                $tenant,
                $this->selectedStatus === 'all' ? null : $this->selectedStatus,
            ),
            'paymentGuidance' => $paymentInstructionsResolver->resolve($tenant->organization?->settings),
            'selectedStatus' => $this->selectedStatus,
        ]);
    }
}
