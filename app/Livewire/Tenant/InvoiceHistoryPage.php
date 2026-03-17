<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Invoices\DownloadInvoiceAction;
use App\Filament\Support\Tenant\Portal\PaymentInstructionsResolver;
use App\Filament\Support\Tenant\Portal\TenantInvoiceIndexQuery;
use App\Http\Requests\Tenant\InvoiceHistoryFilterRequest;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceHistoryPage extends Component
{
    public string $selectedStatus = 'all';

    public function mount(Request $request): void
    {
        /** @var InvoiceHistoryFilterRequest $filtersRequest */
        $filtersRequest = new InvoiceHistoryFilterRequest;
        $validated = $filtersRequest->validatePayload([
            'selectedStatus' => $request->string('status')->toString() ?: $this->selectedStatus,
        ]);

        $this->selectedStatus = (string) $validated['selectedStatus'];
    }

    public function updatedSelectedStatus(string $status): void
    {
        /** @var InvoiceHistoryFilterRequest $filtersRequest */
        $filtersRequest = new InvoiceHistoryFilterRequest;
        $validated = $filtersRequest->validatePayload([
            'selectedStatus' => $status,
        ]);

        $this->selectedStatus = (string) $validated['selectedStatus'];
    }

    public function download(
        Invoice $invoice,
        DownloadInvoiceAction $downloadInvoiceAction,
    ): StreamedResponse|Response {
        return $downloadInvoiceAction->handle($invoice);
    }

    public function render(): View
    {
        return view('tenant.invoices.index', [
            'invoices' => $this->invoices,
            'paymentGuidance' => $this->paymentGuidance,
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

    #[Computed]
    public function invoices(): Paginator
    {
        return app(TenantInvoiceIndexQuery::class)->for(
            $this->tenant,
            $this->selectedStatusFilter(),
        );
    }

    /**
     * @return array{
     *     content: string|null,
     *     contact_name: string|null,
     *     contact_email: string|null,
     *     contact_phone: string|null,
     *     has_contact_details: bool
     * }
     */
    #[Computed]
    public function paymentGuidance(): array
    {
        return app(PaymentInstructionsResolver::class)->resolve(
            $this->tenant->organization?->settings,
        );
    }

    private function selectedStatusFilter(): ?string
    {
        return $this->selectedStatus === 'all' ? null : $this->selectedStatus;
    }
}
