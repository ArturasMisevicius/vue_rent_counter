<?php

namespace App\Filament\Actions\Admin\Settings;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\Organization;
use App\Models\OrganizationSetting;

class UpdateOrganizationSettingsAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    /**
     * @param  array{
     *     organization_name: string,
     *     billing_contact_email: string|null,
     *     invoice_footer: string|null
     * }  $attributes
     */
    public function handle(Organization $organization, array $attributes): OrganizationSetting
    {
        $this->subscriptionLimitGuard->ensureCanWrite($organization);

        $organization->forceFill([
            'name' => $attributes['organization_name'],
        ])->save();

        return OrganizationSetting::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
            ],
            [
                'billing_contact_email' => $attributes['billing_contact_email'],
                'invoice_footer' => $attributes['invoice_footer'],
            ],
        );
    }
}
