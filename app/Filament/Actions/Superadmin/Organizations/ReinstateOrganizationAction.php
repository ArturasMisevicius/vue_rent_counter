<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\OrganizationStatus;
use App\Models\Organization;

class ReinstateOrganizationAction
{
    public function handle(Organization $organization): Organization
    {
        $organization->update([
            'status' => OrganizationStatus::ACTIVE,
        ]);

        return $organization->fresh();
    }
}
