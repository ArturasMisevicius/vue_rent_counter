<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenant\Portal;

use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TenantDocumentIndexQuery
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
    ) {}

    /**
     * @return Collection<int, TenantDocument>
     */
    public function for(User $tenant, ?string $documentType = null): Collection
    {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null) {
            return new Collection;
        }

        return TenantDocument::query()
            ->visibleToTenantPortal()
            ->forOrganization($workspace->organizationId)
            ->forTenant($workspace->userId)
            ->when(
                $workspace->propertyId !== null,
                fn ($query) => $query->where(function ($query) use ($workspace): void {
                    $query
                        ->whereNull('property_id')
                        ->orWhere('property_id', $workspace->propertyId);
                }),
            )
            ->when(
                $documentType !== null,
                fn ($query) => $query->forDocumentType($documentType),
            )
            ->latestActivityFirst()
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function countsFor(User $tenant): array
    {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null) {
            return ['all' => 0];
        }

        $documents = TenantDocument::query()
            ->select(['id', 'organization_id', 'tenant_id', 'property_id', 'document_type', 'tenant_visible', 'status', 'archived_at'])
            ->tenantVisible()
            ->whereIn('status', TenantDocumentStatus::tenantPortalValues())
            ->whereNull('archived_at')
            ->forOrganization($workspace->organizationId)
            ->forTenant($workspace->userId)
            ->when(
                $workspace->propertyId !== null,
                fn ($query) => $query->where(function ($query) use ($workspace): void {
                    $query
                        ->whereNull('property_id')
                        ->orWhere('property_id', $workspace->propertyId);
                }),
            )
            ->get();

        $counts = ['all' => $documents->count()];

        foreach (TenantDocumentType::cases() as $type) {
            $counts[$type->value] = $documents
                ->filter(fn (TenantDocument $document): bool => $document->document_type === $type)
                ->count();
        }

        return $counts;
    }
}
