<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Settings;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\Organization;
use App\Models\OrganizationSetting;

class UpdateOrganizationBillingSettingsAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    /**
     * @param  array{
     *     auto_generation_enabled: bool,
     *     billing_frequency: string,
     *     invoice_generation_day: int,
     *     reading_deadline_day: int,
     *     payment_due_days: int,
     *     send_created_notification: bool,
     *     send_reminders: bool,
     *     reminder_days_before_deadline?: list<int>|null,
     *     timezone: string,
     *     default_currency: string
     * }  $attributes
     */
    public function handle(Organization $organization, array $attributes): OrganizationSetting
    {
        $this->subscriptionLimitGuard->ensureCanWrite($organization);

        return OrganizationSetting::query()->updateOrCreate(
            ['organization_id' => $organization->id],
            [
                'auto_generation_enabled' => $attributes['auto_generation_enabled'],
                'billing_frequency' => $attributes['billing_frequency'],
                'invoice_generation_day' => $attributes['invoice_generation_day'],
                'reading_deadline_day' => $attributes['reading_deadline_day'],
                'payment_due_days' => $attributes['payment_due_days'],
                'send_created_notification' => $attributes['send_created_notification'],
                'send_reminders' => $attributes['send_reminders'],
                'reminder_days_before_deadline' => array_values(array_unique($attributes['reminder_days_before_deadline'] ?? [])),
                'timezone' => $attributes['timezone'],
                'default_currency' => strtoupper($attributes['default_currency']),
            ],
        );
    }
}
