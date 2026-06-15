<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use App\Enums\TenantKycDocumentStatus;
use App\Enums\TenantKycDocumentType;
use App\Models\Organization;
use App\Models\TenantDocument;
use App\Models\TenantKycDocument;
use App\Models\TenantKycProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantKycDocument>
 */
class TenantKycDocumentFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $tenant = User::factory()->tenant()->for($organization);
        $profile = TenantKycProfile::factory()->for($organization)->for($tenant, 'tenant');

        return [
            'organization_id' => $organization,
            'tenant_id' => $tenant,
            'kyc_profile_id' => $profile,
            'document_type' => TenantKycDocumentType::IDENTITY_CARD,
            'document_number_encrypted' => fake()->bothify('ID-####-????'),
            'issued_country' => 'LT',
            'issued_at' => now()->subYears(2)->toDateString(),
            'expires_at' => now()->addYears(3),
            'status' => TenantKycDocumentStatus::PENDING_REVIEW,
            'file_document_id' => TenantDocument::factory()
                ->for($organization)
                ->for($tenant, 'tenant')
                ->state([
                    'document_type' => TenantDocumentType::KYC_IDENTITY,
                    'status' => TenantDocumentStatus::PENDING_REVIEW,
                    'tenant_visible' => true,
                ]),
            'submitted_by_user_id' => $tenant,
            'submitted_at' => now(),
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'internal_note' => null,
            'replaced_by_document_id' => null,
            'archived_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => TenantKycDocumentStatus::APPROVED,
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'The document image is unclear.'): static
    {
        return $this->state([
            'status' => TenantKycDocumentStatus::REJECTED,
            'reviewed_at' => now(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => TenantKycDocumentStatus::EXPIRED,
            'expires_at' => now()->subDay(),
        ]);
    }
}
