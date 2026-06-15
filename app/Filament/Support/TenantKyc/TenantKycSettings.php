<?php

declare(strict_types=1);

namespace App\Filament\Support\TenantKyc;

use App\Enums\TenantKycDocumentType;
use App\Models\OrganizationSetting;

class TenantKycSettings
{
    /**
     * @return list<TenantKycDocumentType>
     */
    public function requiredDocumentTypes(int $organizationId): array
    {
        $settings = $this->settingsFor($organizationId);

        if (! $settings?->kyc_required) {
            return [];
        }

        $configured = collect($settings->required_document_types ?? [])
            ->map(fn (mixed $value): ?TenantKycDocumentType => is_string($value)
                ? TenantKycDocumentType::tryFrom($value)
                : null)
            ->filter()
            ->values()
            ->all();

        if ($configured !== []) {
            return $configured;
        }

        return [
            TenantKycDocumentType::IDENTITY_CARD,
            TenantKycDocumentType::ADDRESS_PROOF,
        ];
    }

    public function requiresExpiryDate(int $organizationId): bool
    {
        return (bool) $this->settingsFor($organizationId)?->require_expiry_date;
    }

    public function blocksInvoiceDownload(int $organizationId): bool
    {
        return (bool) $this->settingsFor($organizationId)?->block_invoice_download_until_verified;
    }

    public function blocksReadingSubmission(int $organizationId): bool
    {
        return (bool) $this->settingsFor($organizationId)?->block_reading_submission_until_verified;
    }

    public function blocksPortal(int $organizationId): bool
    {
        return (bool) $this->settingsFor($organizationId)?->block_portal_until_verified;
    }

    private function settingsFor(int $organizationId): ?OrganizationSetting
    {
        return OrganizationSetting::query()
            ->select([
                'id',
                'organization_id',
                'kyc_required',
                'required_document_types',
                'require_expiry_date',
                'block_portal_until_verified',
                'block_invoice_download_until_verified',
                'block_reading_submission_until_verified',
            ])
            ->where('organization_id', $organizationId)
            ->first();
    }
}
