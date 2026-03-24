<?php

namespace Database\Seeders;

use App\Filament\Support\Superadmin\SystemConfiguration\SystemSettingCatalog;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        collect(app(SystemSettingCatalog::class)->seedData())->each(function (array $setting): void {
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
