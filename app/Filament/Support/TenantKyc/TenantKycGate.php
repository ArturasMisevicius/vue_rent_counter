<?php

declare(strict_types=1);

namespace App\Filament\Support\TenantKyc;

use App\Enums\TenantKycProfileStatus;
use App\Models\TenantKycProfile;
use App\Models\User;

class TenantKycGate
{
    public function __construct(
        private readonly TenantKycSettings $settings,
    ) {}

    public function isSatisfied(User $tenant): bool
    {
        $organizationId = (int) $tenant->organization_id;

        if ($this->settings->requiredDocumentTypes($organizationId) === []) {
            return true;
        }

        $profile = TenantKycProfile::query()
            ->select(['id', 'organization_id', 'tenant_id', 'status', 'expires_at'])
            ->forOrganization($organizationId)
            ->forTenant((int) $tenant->id)
            ->first();

        if ($profile === null || $profile->status !== TenantKycProfileStatus::VERIFIED) {
            return false;
        }

        return $profile->expires_at === null || $profile->expires_at->isFuture();
    }

    public function blocksPortal(User $tenant): bool
    {
        return $this->settings->blocksPortal((int) $tenant->organization_id)
            && ! $this->isSatisfied($tenant);
    }

    public function blocksInvoiceDownload(User $tenant): bool
    {
        return $this->settings->blocksInvoiceDownload((int) $tenant->organization_id)
            && ! $this->isSatisfied($tenant);
    }

    public function blocksReadingSubmission(User $tenant): bool
    {
        return $this->settings->blocksReadingSubmission((int) $tenant->organization_id)
            && ! $this->isSatisfied($tenant);
    }
}
