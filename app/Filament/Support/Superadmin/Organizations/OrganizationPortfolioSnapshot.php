<?php

namespace App\Filament\Support\Superadmin\Organizations;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

final readonly class OrganizationPortfolioSnapshot
{
    public function __construct(
        public int $buildingsCount,
        public int $propertiesCount,
        public int $occupiedUnitsCount,
        public int $vacantUnitsCount,
        public int $occupancyRatePercentage,
        public int $activeTenantsCount,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        if ($organization->getAttribute('buildings_count') === null) {
            $organization->loadCount('buildings');
        }

        if ($organization->getAttribute('properties_count') === null) {
            $organization->loadCount('properties');
        }

        $propertiesCount = (int) ($organization->properties_count ?? 0);
        $occupiedUnitsCount = (int) $organization->propertyAssignments()
            ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
            ->current()
            ->count();
        $vacantUnitsCount = max(0, $propertiesCount - $occupiedUnitsCount);
        $activeTenantsCount = (int) $organization->users()
            ->select(['id', 'organization_id', 'role', 'status'])
            ->tenants()
            ->active()
            ->whereHas('propertyAssignments', fn (Builder $query): Builder => $query->current())
            ->count();

        return new self(
            buildingsCount: (int) ($organization->buildings_count ?? 0),
            propertiesCount: $propertiesCount,
            occupiedUnitsCount: $occupiedUnitsCount,
            vacantUnitsCount: $vacantUnitsCount,
            occupancyRatePercentage: $propertiesCount > 0
                ? (int) round(($occupiedUnitsCount / $propertiesCount) * 100)
                : 0,
            activeTenantsCount: $activeTenantsCount,
        );
    }
}
