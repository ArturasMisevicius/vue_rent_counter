<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Enums\TenantKycDocumentType;
use App\Filament\Actions\TenantKyc\AuditKycView;
use App\Filament\Actions\TenantKyc\ReplaceTenantKycDocument;
use App\Filament\Actions\TenantKyc\SubmitTenantKycDocument;
use App\Filament\Support\Tenant\Portal\TenantKycPresenter;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\TenantKycDocument;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Verification extends Component
{
    use AppliesShellLocale;
    use ResolvesTenantWorkspace;
    use WithFileUploads;

    public string $documentType = '';

    public ?string $documentNumber = null;

    public ?string $issuedCountry = null;

    public ?string $issuedAt = null;

    public ?string $expiresAt = null;

    public mixed $documentFile = null;

    public ?int $replacingDocumentId = null;

    public function mount(TenantKycPresenter $presenter, AuditKycView $auditKycView): void
    {
        $this->tenantWorkspace();

        $overview = $presenter->overview($this->tenant);
        $auditKycView->forTenantOverview($this->tenant);

        $options = $overview['document_type_options'];
        $this->documentType = (string) array_key_first($options);
    }

    public function render(TenantKycPresenter $presenter): View
    {
        return view('livewire.tenant.verification', [
            'tenant' => $this->tenant,
            'overview' => $presenter->overview($this->tenant),
        ]);
    }

    public function submitDocument(
        SubmitTenantKycDocument $submitTenantKycDocument,
        ReplaceTenantKycDocument $replaceTenantKycDocument,
    ): void {
        $tenant = $this->tenant;
        $data = [
            'organization_id' => (int) $tenant->organization_id,
            'tenant_id' => (int) $tenant->id,
            'document_type' => $this->documentType,
            'document_number_encrypted' => $this->documentNumber,
            'issued_country' => $this->issuedCountry,
            'issued_at' => $this->issuedAt,
            'expires_at' => $this->expiresAt,
        ];

        if ($this->replacingDocumentId !== null) {
            $document = TenantKycDocument::query()
                ->forOrganization((int) $tenant->organization_id)
                ->forTenant((int) $tenant->id)
                ->findOrFail($this->replacingDocumentId);

            $replaceTenantKycDocument->handle($document, $tenant, $data, $this->documentFile);
        } else {
            $submitTenantKycDocument->handle($tenant, $data, $this->documentFile);
        }

        $this->resetForm();

        Notification::make()
            ->success()
            ->title(__('tenant.pages.verification.uploaded'))
            ->send();
    }

    public function prepareReplacement(int $documentId): void
    {
        $document = TenantKycDocument::query()
            ->forOrganization((int) $this->tenant->organization_id)
            ->forTenant((int) $this->tenant->id)
            ->findOrFail($documentId);

        $this->replacingDocumentId = $document->id;
        $this->documentType = $document->document_type?->value ?? TenantKycDocumentType::IDENTITY_CARD->value;
    }

    public function cancelReplacement(): void
    {
        $this->replacingDocumentId = null;
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();

        unset($this->tenant);
    }

    #[Computed]
    public function tenant(): User
    {
        return $this->currentTenant();
    }

    private function resetForm(): void
    {
        $this->documentNumber = null;
        $this->issuedCountry = null;
        $this->issuedAt = null;
        $this->expiresAt = null;
        $this->documentFile = null;
        $this->replacingDocumentId = null;
    }
}
