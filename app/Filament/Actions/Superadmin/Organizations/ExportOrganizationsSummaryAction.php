<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Models\Organization;
use Illuminate\Support\Collection;

class ExportOrganizationsSummaryAction
{
    /**
     * @param  Collection<int, Organization>  $organizations
     */
    public function handle(Collection $organizations): string
    {
        $organizations->loadMissing([
            'owner:id,name,email',
            'currentSubscription:id,organization_id,plan,status,expires_at',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'organizations-export-');

        if ($path === false) {
            abort(500, 'Unable to prepare the organizations export.');
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            abort(500, 'Unable to write the organizations export.');
        }

        fputcsv($handle, [
            'Organization Name',
            'Owner Email',
            'Plan',
            'Subscription Status',
            'Properties',
            'Tenants',
            'Created',
        ]);

        foreach ($organizations as $organization) {
            fputcsv($handle, [
                $organization->name,
                $organization->owner?->email,
                $organization->currentSubscription?->plan?->label(),
                $organization->currentSubscription?->status?->label(),
                $organization->buildings_count,
                $organization->tenants_count,
                $organization->created_at?->format('d M Y'),
            ]);
        }

        fclose($handle);

        return $path;
    }
}
