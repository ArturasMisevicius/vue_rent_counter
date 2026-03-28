<?php

namespace App\Filament\Support\Superadmin\Organizations;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

final class OrganizationListQuery
{
    /**
     * @param  Builder<Organization>|null  $query
     * @return Builder<Organization>
     */
    public function build(?Builder $query = null): Builder
    {
        return ($query ?? Organization::query())->forSuperadminControlPlane();
    }
}
