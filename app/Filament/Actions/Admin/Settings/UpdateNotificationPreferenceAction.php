<?php

namespace App\Filament\Actions\Admin\Settings;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Services\NotificationPreferenceService;

class UpdateNotificationPreferenceAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    /**
     * @return array{
     *     new_invoice_generated: bool,
     *     invoice_overdue: bool,
     *     tenant_submits_reading: bool,
     *     subscription_expiring: bool
     * }
     */
    public static function defaults(): array
    {
        return NotificationPreferenceService::defaults();
    }

    /**
     * @param  array{
     *     new_invoice_generated: bool,
     *     invoice_overdue: bool,
     *     tenant_submits_reading: bool,
     *     subscription_expiring: bool
     * }  $preferences
     */
    public function handle(Organization $organization, array $preferences): OrganizationSetting
    {
        $this->subscriptionLimitGuard->ensureCanWrite($organization);

        /** @var OrganizationSetting $settings */
        $settings = OrganizationSetting::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
            ],
        );

        $settings->forceFill([
            'notification_preferences' => array_replace(self::defaults(), $preferences),
        ])->save();

        return $settings->refresh();
    }
}
