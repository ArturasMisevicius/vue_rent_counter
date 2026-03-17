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
