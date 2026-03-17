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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??'),
            'name' => fake()->languageCode(),
            'native_name' => fake()->languageCode(),
            'status' => LanguageStatus::ACTIVE,
            'is_default' => false,
        ];
    }
}
