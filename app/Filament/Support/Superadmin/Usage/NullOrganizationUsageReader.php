<?php

namespace App\Filament\Support\Superadmin\Usage;

use App\Models\Organization;
use App\Models\Subscription;

class NullOrganizationUsageReader extends OrganizationUsageReader
{
    public function forOrganization(Organization $organization): OrganizationUsageSnapshot
    {
        return OrganizationUsageSnapshot::zero();
    }

    public function forSubscription(Subscription $subscription): OrganizationUsageSnapshot
    {
        return OrganizationUsageSnapshot::zero();
    }
}
