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
     *     billing_contact_name: string|null,
     *     billing_contact_email: string|null,
     *     billing_contact_phone: string|null,
     *     payment_instructions: string|null,
     *     invoice_footer: string|null
     * }  $attributes
     */
    public function handle(Organization $organization, array $attributes): OrganizationSetting
    {
        $this->subscriptionLimitGuard->ensureCanWrite($organization);

        return OrganizationSetting::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
            ],
            $attributes,
        );
    }
}
