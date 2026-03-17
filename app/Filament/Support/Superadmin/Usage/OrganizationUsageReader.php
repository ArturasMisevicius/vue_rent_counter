<?php

namespace App\Filament\Support\Superadmin\Usage;

use App\Models\Organization;
use App\Models\Subscription;

class OrganizationUsageReader
{
    public function forOrganization(Organization $organization): OrganizationUsageSnapshot
    {
        return new OrganizationUsageSnapshot(
            propertiesUsed: $organization->properties()->count(),
            tenantsUsed: $organization->users()->where('role', 'tenant')->count(),
            metersUsed: $organization->meters()->count(),
            invoicesUsed: $organization->invoices()->count(),
        );
    }

    public function forSubscription(Subscription $subscription): OrganizationUsageSnapshot
    {
        return $this->forOrganization($subscription->organization()->firstOrFail());
    }
}
