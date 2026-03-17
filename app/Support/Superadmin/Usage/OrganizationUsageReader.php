<?php

namespace App\Support\Superadmin\Usage;

use App\Models\Organization;

interface OrganizationUsageReader
{
    public function forOrganization(Organization $organization): OrganizationUsageSnapshot;
}
