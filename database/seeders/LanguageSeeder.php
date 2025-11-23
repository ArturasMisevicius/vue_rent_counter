<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'is_default' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'code' => 'lt',
                'name' => 'Lithuanian',
                'native_name' => 'Lietuvių',
                'is_default' => false,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'code' => 'ru',
                'name' => 'Russian',
                'native_name' => 'Русский',
                'is_default' => false,
                'is_active' => true,
                'display_order' => 3,
            ],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}
