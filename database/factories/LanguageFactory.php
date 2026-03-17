<?php

namespace Database\Factories;

use App\Enums\LanguageStatus;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    public function definition(): array
    {
        $code = fake()->unique()->lexify('??');

        return [
            'code' => strtolower($code),
            'name' => strtoupper($code),
            'native_name' => strtoupper($code),
            'status' => LanguageStatus::ACTIVE,
            'is_default' => false,
        ];
    }
}
