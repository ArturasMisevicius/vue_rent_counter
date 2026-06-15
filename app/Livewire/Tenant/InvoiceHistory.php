<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Actions\Billing\SubmitTenantPaymentProof;
use App\Enums\PaymentMethod;
use App\Filament\Support\Tenant\Portal\PaymentInstructionsResolver;
use App\Filament\Support\Tenant\Portal\TenantInvoiceIndexQuery;
use App\Http\Requests\Tenant\InvoiceHistoryFilterRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoicePdfService;
use App\Services\Billing\InvoicePresentationService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceHistory extends Component
{
    use AppliesShellLocale;
    use ResolvesTenantWorkspace;
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'status')]
    public string $selectedStatus = 'all';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $paymentForms = [];

    /**
     * @var array<int, mixed>
     */
    public array $paymentProofFiles = [];

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
        $this->ensurePaymentForms($invoices->items());
        $invoicePresentationService = app(InvoicePresentationService::class);
        $invoicePresentations = collect($invoices->items())
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $invoice->id => $invoicePresentationService->present($invoice),
            ])
            ->all();

        return view('livewire.tenant.invoice-history', [
            'invoices' => $invoices,
            'invoicePresentations' => $invoicePresentations,
            'paymentGuidance' => $this->paymentGuidance,
            'paymentForms' => $this->paymentForms,
            'selectedStatus' => $this->selectedStatus,
            'statusFilters' => $this->statusFilters(),
            'tenant' => $this->tenant,
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
                'finalized_at',
                'items',
                'snapshot_data',
                'document_path',
                'created_at',
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

    public function submitPaymentProof(int $invoiceId, SubmitTenantPaymentProof $submitTenantPaymentProof): void
    {
        $invoice = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'billing_period_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
                'payment_status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'balance_amount',
                'due_date',
                'paid_at',
                'payment_reference',
                'overdue_at',
            ])
            ->findOrFail($invoiceId);
        $tenant = $this->tenant;

        Gate::forUser($tenant)->authorize('view', $invoice);

        $form = $this->paymentForms[$invoiceId] ?? [];

        $submitTenantPaymentProof->handle($invoice, $tenant, [
            'amount' => $form['amount'] ?? null,
            'payment_date' => $form['payment_date'] ?? null,
            'payment_method' => $form['payment_method'] ?? null,
            'reference' => $form['reference'] ?? null,
            'transaction_id' => $form['transaction_id'] ?? null,
            'tenant_comment' => $form['tenant_comment'] ?? null,
            'proof_file' => $this->paymentProofFiles[$invoiceId] ?? null,
        ]);

        unset($this->paymentProofFiles[$invoiceId], $this->paymentForms[$invoiceId], $this->invoices);

        session()->flash('payment-proof-submitted-'.$invoiceId, __('tenant.pages.invoices.payment_proof_submitted'));
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

    /**
     * @param  array<int, Invoice>  $invoices
     */
    private function ensurePaymentForms(array $invoices): void
    {
        foreach ($invoices as $invoice) {
            if (array_key_exists($invoice->id, $this->paymentForms)) {
                continue;
            }

            $this->paymentForms[$invoice->id] = [
                'amount' => number_format($invoice->outstanding_balance, 2, '.', ''),
                'payment_date' => now()->toDateString(),
                'payment_method' => PaymentMethod::BANK_TRANSFER->value,
                'reference' => $invoice->payment_reference ?: $invoice->invoice_number,
                'transaction_id' => '',
                'tenant_comment' => '',
            ];
        }
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    private function statusFilters(): array
    {
        return [
            'all' => [
                'label' => __('tenant.status.all'),
                'icon' => 'heroicon-m-rectangle-stack',
            ],
            'unpaid' => [
                'label' => __('tenant.status.unpaid'),
                'icon' => 'heroicon-m-exclamation-circle',
            ],
            'paid' => [
                'label' => __('tenant.status.paid'),
                'icon' => 'heroicon-m-check-badge',
            ],
        ];
    }
}
