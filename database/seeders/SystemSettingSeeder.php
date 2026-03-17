<?php

namespace Database\Seeders;

use App\Enums\SystemSettingCategory;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'key' => 'platform.app_name',
                'category' => SystemSettingCategory::GENERAL,
                'label' => 'Platform Name',
                'description' => 'The application name shown in platform-owned interfaces.',
                'type' => 'string',
                'value' => 'Tenanto',
            ],
            [
                'key' => 'billing.default_trial_days',
                'category' => SystemSettingCategory::BILLING,
                'label' => 'Default Trial Days',
                'description' => 'The default trial duration for newly onboarded organizations.',
                'type' => 'integer',
                'value' => '14',
            ],
            [
                'key' => 'localization.default_locale',
                'category' => SystemSettingCategory::LOCALIZATION,
                'label' => 'Default Locale',
                'description' => 'The default locale used for new sessions.',
                'type' => 'string',
                'value' => 'en',
            ],
            [
                'key' => 'security.blocked_ip_banner_enabled',
                'category' => SystemSettingCategory::SECURITY,
                'label' => 'Blocked IP Banner Enabled',
                'description' => 'Whether blocked-IP responses should include support guidance copy.',
                'type' => 'boolean',
                'value' => 'false',
            ],
            [
                'key' => 'integrations.health_check_interval_minutes',
                'category' => SystemSettingCategory::INTEGRATIONS,
                'label' => 'Health Check Interval',
                'description' => 'The default interval, in minutes, for platform integration checks.',
                'type' => 'integer',
                'value' => '5',
            ],
            [
                'key' => 'notifications.support_email',
                'category' => SystemSettingCategory::NOTIFICATIONS,
                'label' => 'Support Email',
                'description' => 'The default reply-to address for platform notifications.',
                'type' => 'email',
                'value' => 'support@example.test',
            ],
        ] as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }
    }
}
