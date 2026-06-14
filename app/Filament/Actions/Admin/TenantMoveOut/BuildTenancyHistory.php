<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut;

use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class BuildTenancyHistory
{
    /**
     * @return Collection<int, PropertyAssignment>
     */
    public function forTenant(User $tenant): Collection
    {
        if ($tenant->organization_id === null) {
            return new Collection;
        }

        return PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'status',
                'is_primary',
                'occupants_count',
                'assigned_at',
                'unassigned_at',
                'move_out_date',
                'billing_start_date',
                'billing_end_date',
                'move_out_reason',
                'move_out_completed_at',
            ])
            ->forOrganization((int) $tenant->organization_id)
            ->forTenant((int) $tenant->id)
            ->with([
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm,occupancy_status',
                'property.building:id,organization_id,name',
                'moveOutProcesses:id,organization_id,property_assignment_id,status,move_out_date,final_invoice_id,completed_at',
                'rentalContracts:id,organization_id,tenant_id,property_id,property_assignment_id,contract_number,status,start_date,end_date',
            ])
            ->latestAssignedFirst()
            ->get();
    }
}
