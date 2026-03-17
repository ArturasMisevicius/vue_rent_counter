<?php

namespace App\Actions\Admin\Settings;

use App\Models\Organization;
use App\Models\OrganizationSetting;

class UpdateOrganizationSettingsAction
{
    /**
     * @param  array{
     *     billing_contact_name: string|null,
     *     billing_contact_email: string|null,
     *     billing_contact_phone: string|null,
     *     payment_instructions: string|null,
     *     invoice_footer: string|null
     * }  $attributes
     */
    public function handle(Organization $organization, array $attributes): OrganizationSetting
    {
        return OrganizationSetting::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
            ],
            $attributes,
        );
    }
}
