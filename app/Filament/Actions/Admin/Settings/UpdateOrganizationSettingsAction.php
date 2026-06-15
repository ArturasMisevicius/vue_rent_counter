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
     *     invoice_footer: string|null,
     *     kyc_required: bool,
     *     required_document_types: list<string>|null,
     *     require_expiry_date: bool,
     *     block_portal_until_verified: bool,
     *     block_invoice_download_until_verified: bool,
     *     block_reading_submission_until_verified: bool
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
                'kyc_required' => $attributes['kyc_required'],
                'required_document_types' => $attributes['required_document_types'] ?? null,
                'require_expiry_date' => $attributes['require_expiry_date'],
                'block_portal_until_verified' => $attributes['block_portal_until_verified'],
                'block_invoice_download_until_verified' => $attributes['block_invoice_download_until_verified'],
                'block_reading_submission_until_verified' => $attributes['block_reading_submission_until_verified'],
            ],
        );
    }
}
