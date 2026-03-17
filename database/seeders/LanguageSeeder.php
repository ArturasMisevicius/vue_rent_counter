<?php

namespace Database\Seeders;

use App\Enums\LanguageStatus;
use App\Models\Language;
use App\Support\Geography\BalticReferenceCatalog;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true],
            ['code' => 'lt', 'name' => 'Lithuanian', 'native_name' => 'Lietuvių', 'is_default' => false],
            ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский', 'is_default' => false],
        ])->each(function (array $language): void {
            Language::query()->updateOrCreate(
                ['code' => $language['code']],
                [
                    'name' => $language['name'],
                    'native_name' => $language['native_name'],
                    'status' => LanguageStatus::ACTIVE,
                    'is_default' => $language['is_default'],
                ],
            );
        });

        Language::query()
            ->whereNotIn('code', BalticReferenceCatalog::supportedLocaleCodes())
            ->update([
                'status' => LanguageStatus::INACTIVE,
                'is_default' => false,
            ]);
    }
}
