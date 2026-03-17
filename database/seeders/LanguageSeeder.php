<?php

namespace Database\Seeders;

use App\Enums\LanguageStatus;
use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true],
            ['code' => 'lt', 'name' => 'Lithuanian', 'native_name' => 'Lietuviu', 'is_default' => false],
            ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Russkii', 'is_default' => false],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Espanol', 'is_default' => false],
        ] as $language) {
            Language::query()->updateOrCreate(
                ['code' => $language['code']],
                [
                    'name' => $language['name'],
                    'native_name' => $language['native_name'],
                    'status' => LanguageStatus::ACTIVE,
                    'is_default' => $language['is_default'],
                ],
            );
        }
    }
}
