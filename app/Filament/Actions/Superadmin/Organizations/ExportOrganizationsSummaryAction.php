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
            abort(500, __('superadmin.organizations.messages.export_prepare_failed'));
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            abort(500, __('superadmin.organizations.messages.export_write_failed'));
        }

        fputcsv($handle, [
            __('superadmin.organizations.overview.fields.organization_name'),
            __('superadmin.organizations.overview.fields.owner_email'),
            __('superadmin.organizations.form.fields.plan'),
            __('superadmin.organizations.overview.fields.subscription_status'),
            __('superadmin.organizations.overview.usage_labels.properties'),
            __('superadmin.organizations.overview.usage_labels.tenants'),
            __('superadmin.organizations.overview.fields.date_created'),
        ]);

        foreach ($organizations as $organization) {
            fputcsv($handle, [
                $organization->name,
                $organization->owner?->email,
                $organization->currentSubscription?->plan?->label(),
                $organization->currentSubscription?->status?->label(),
                $organization->buildings_count,
                $organization->tenants_count,
                $organization->created_at?->locale(app()->getLocale())->isoFormat('ll'),
            ]);
        }

        fclose($handle);

        return $path;
    }
}
