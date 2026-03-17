<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Invoices\DownloadInvoiceAction;
use App\Filament\Support\Tenant\Portal\PaymentInstructionsResolver;
use App\Filament\Support\Tenant\Portal\TenantInvoiceIndexQuery;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceHistoryPage extends Component
{
    #[Validate('required|string|in:all,unpaid,paid,outstanding')]
    public string $selectedStatus = 'all';

    public function mount(Request $request): void
    {
        $this->selectedStatus = $this->normalizeSelectedStatus(
            $request->string('status')->toString() ?: $this->selectedStatus,
        );
    }

    public function updatedSelectedStatus(string $status): void
    {
        $this->selectedStatus = $this->normalizeSelectedStatus($status);
        $this->validateOnly('selectedStatus');
    }

    public function download(
        Invoice $invoice,
        DownloadInvoiceAction $downloadInvoiceAction,
    ): StreamedResponse|Response {
        return $downloadInvoiceAction->handle($invoice);
    }

    public function render(
        TenantInvoiceIndexQuery $tenantInvoiceIndexQuery,
        PaymentInstructionsResolver $paymentInstructionsResolver,
    ): View {
        $tenant = $this->tenant;

        return view('tenant.invoices.index', [
            'invoices' => $tenantInvoiceIndexQuery->for(
                $tenant,
                $this->selectedStatusFilter(),
            ),
            'paymentGuidance' => $paymentInstructionsResolver->resolve($tenant->organization?->settings),
            'selectedStatus' => $this->selectedStatus,
        ]);
    }

    #[Computed]
    public function tenant(): User
    {
        /** @var User $tenant */
        $tenant = auth()->user();

        return $tenant->loadMissing(
            'organization.settings:id,organization_id,billing_contact_name,billing_contact_email,billing_contact_phone,payment_instructions,invoice_footer',
        );
    }

    private function selectedStatusFilter(): ?string
    {
        return $this->selectedStatus === 'all' ? null : $this->selectedStatus;
    }

    private function normalizeSelectedStatus(string $status): string
    {
        return match ($status) {
            'outstanding', 'unpaid' => 'unpaid',
            'paid' => 'paid',
            default => 'all',
        };
    }
}
