<?php

namespace Database\Seeders;

use App\Enums\SystemSettingCategory;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            [
                'key' => 'platform.name',
                'category' => SystemSettingCategory::GENERAL,
                'label' => 'Platform Name',
                'value' => ['value' => config('app.name', 'Tenanto')],
            ],
            [
                'key' => 'platform.billing.currency',
                'category' => SystemSettingCategory::BILLING,
                'label' => 'Billing Currency',
                'value' => ['value' => 'EUR'],
            ],
            [
                'key' => 'platform.locales.supported',
                'category' => SystemSettingCategory::LOCALIZATION,
                'label' => 'Supported Locales',
                'value' => ['value' => array_keys(config('app.supported_locales', []))],
            ],
            [
                'key' => 'platform.notifications.email.enabled',
                'category' => SystemSettingCategory::NOTIFICATIONS,
                'label' => 'Email Notifications Enabled',
                'value' => ['value' => true],
            ],
            [
                'key' => 'platform.email.from_address',
                'category' => SystemSettingCategory::EMAIL,
                'label' => 'Default From Address',
                'value' => ['value' => config('mail.from.address')],
            ],
            [
                'key' => 'platform.subscription.renewal_grace_days',
                'category' => SystemSettingCategory::SUBSCRIPTION,
                'label' => 'Renewal Grace Period (Days)',
                'value' => ['value' => 7],
            ],
            [
                'key' => 'platform.backups.retention_days',
                'category' => SystemSettingCategory::BACKUPS,
                'label' => 'Backup Retention (Days)',
                'value' => ['value' => 30],
            ],
            [
                'key' => 'platform.maintenance.enabled',
                'category' => SystemSettingCategory::MAINTENANCE,
                'label' => 'Maintenance Mode Enabled',
                'value' => ['value' => false],
            ],
            [
                'key' => 'platform.reporting.default_timezone',
                'category' => SystemSettingCategory::REPORTING,
                'label' => 'Reporting Timezone',
                'value' => ['value' => config('app.timezone')],
            ],
            [
                'key' => 'platform.api.rate_limit_per_minute',
                'category' => SystemSettingCategory::API,
                'label' => 'API Rate Limit Per Minute',
                'value' => ['value' => 120],
            ],
            [
                'key' => 'platform.compliance.data_retention_months',
                'category' => SystemSettingCategory::COMPLIANCE,
                'label' => 'Data Retention (Months)',
                'value' => ['value' => 24],
            ],
        ])->each(function (array $setting): void {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'category' => $setting['category'],
                    'label' => $setting['label'],
                    'value' => $setting['value'],
                    'is_encrypted' => false,
                ],
            );
        });
    }
}
