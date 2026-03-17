<?php

namespace App\Actions\Admin\Settings;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Support\Admin\SubscriptionLimitGuard;

class UpdateNotificationPreferenceAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    /**
     * @return array{invoice_reminders: bool, reading_deadline_alerts: bool}
     */
    public static function defaults(): array
    {
        return [
            'invoice_reminders' => false,
            'reading_deadline_alerts' => false,
        ];
    }

    /**
     * @param  array{invoice_reminders: bool, reading_deadline_alerts: bool}  $preferences
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
