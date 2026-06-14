<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use App\Models\Organization;
use App\Models\Property;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantDocument>
 */
class TenantDocumentFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $tenant = User::factory()->tenant()->for($organization);

        return [
            'organization_id' => $organization,
            'tenant_id' => $tenant,
            'property_id' => Property::factory()->for($organization),
            'related_type' => null,
            'related_id' => null,
            'document_type' => TenantDocumentType::OTHER,
            'title' => fake()->sentence(3),
            'description_for_tenant' => fake()->sentence(),
            'internal_note' => null,
            'file_path' => 'tenant-documents/'.fake()->uuid().'.pdf',
            'original_filename' => fake()->slug().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1000, 500000),
            'status' => TenantDocumentStatus::ACTIVE,
            'tenant_visible' => true,
            'uploaded_by_user_id' => User::factory()->admin()->for($organization),
            'verified_by_user_id' => null,
            'verified_at' => null,
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'expires_at' => null,
            'archived_at' => null,
        ];
    }

    public function internal(): static
    {
        return $this->state([
            'tenant_visible' => false,
            'description_for_tenant' => null,
        ]);
    }

    public function kycIdentity(): static
    {
        return $this->state([
            'document_type' => TenantDocumentType::KYC_IDENTITY,
            'status' => TenantDocumentStatus::PENDING_REVIEW,
        ]);
    }

    public function rejected(string $reason = 'Please upload a clearer scan.'): static
    {
        return $this->state([
            'status' => TenantDocumentStatus::REJECTED,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => TenantDocumentStatus::EXPIRED,
            'expires_at' => now()->subDay(),
        ]);
    }
}
