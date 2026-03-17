<?php

namespace App\Support\Superadmin\Usage;

use App\Models\Organization;

final class NullOrganizationUsageReader implements OrganizationUsageReader
{
    public function forOrganization(Organization $organization): OrganizationUsageSnapshot
    {
        return OrganizationUsageSnapshot::empty();
    }
}
