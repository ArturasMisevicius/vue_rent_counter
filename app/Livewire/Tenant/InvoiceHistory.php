<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenant\Portal\PaymentInstructionsResolver;
use App\Filament\Support\Tenant\Portal\TenantInvoiceIndexQuery;
use App\Http\Requests\Tenant\InvoiceHistoryFilterRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoicePresentationService;
use App\Services\Billing\InvoicePdfService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceHistory extends Component
{
    use AppliesShellLocale;
    use ResolvesTenantWorkspace;
    use WithPagination;

    #[Url(as: 'status')]
    public string $selectedStatus = 'all';

    public function mount(): void
    {
        $this->tenantWorkspace();

        /** @var InvoiceHistoryFilterRequest $filtersRequest */
        $filtersRequest = new InvoiceHistoryFilterRequest;
        $validated = $filtersRequest->validatePayload([
            'selectedStatus' => $this->selectedStatus,
        ]);

        $this->selectedStatus = (string) $validated['selectedStatus'];
    }

    public function render(): View
    {
        $invoices = $this->invoices;

        if (method_exists($invoices, 'through')) {
            $invoicePresentationService = app(InvoicePresentationService::class);

            $invoices = $invoices->through(fn (Invoice $invoice): array => [
                'record' => $invoice,
                'presentation' => $invoicePresentationService->present($invoice),
            ]);
        }

        return view('livewire.tenant.invoice-history', [
            'invoices' => $invoices,
            'paymentGuidance' => $this->paymentGuidance,
            'selectedStatus' => $this->selectedStatus,
        ]);
    }

    public function downloadPdf(int $invoiceId, InvoicePdfService $invoicePdfService): StreamedResponse
    {
        $invoice = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'billing_period_start',
                'billing_period_end',
                'due_date',
                'items',
                'snapshot_data',
                'document_path',
            ])
            ->findOrFail($invoiceId);

        Gate::forUser($this->tenant())->authorize('download', $invoice);

        return $invoicePdfService->streamDownload($invoice);
    }

    public function updatedSelectedStatus(string $status): void
    {
        /** @var InvoiceHistoryFilterRequest $filtersRequest */
        $filtersRequest = new InvoiceHistoryFilterRequest;
        $validated = $filtersRequest->validatePayload([
            'selectedStatus' => $status,
        ]);

        $this->selectedStatus = (string) $validated['selectedStatus'];
        $this->resetPage();

        unset($this->invoices, $this->statusFilter);
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();

        unset(
            $this->tenant,
            $this->invoices,
            $this->statusFilter,
            $this->paymentGuidance,
        );
    }

    #[Computed]
    public function tenant(): User
    {
        $workspace = $this->tenantWorkspace();

        /** @var User $tenant */
        $tenant = $this->currentTenant();

        return $tenant->loadMissing([
            'organization.settings:id,organization_id,billing_contact_name,billing_contact_email,billing_contact_phone,payment_instructions,invoice_footer',
            'currentPropertyAssignment' => fn ($query) => $query
                ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
                ->forOrganization($workspace->organizationId)
                ->when(
                    $workspace->propertyId !== null,
                    fn ($query) => $query->forProperty($workspace->propertyId),
                )
                ->current(),
        ]);
    }

    #[Computed]
    public function invoices(): Paginator
    {
        return app(TenantInvoiceIndexQuery::class)->for(
            $this->tenant,
            $this->statusFilter,
        );
    }

    #[Computed]
    public function statusFilter(): ?string
    {
        return $this->selectedStatus === 'all' ? null : $this->selectedStatus;
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
}
