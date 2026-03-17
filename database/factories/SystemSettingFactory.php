<?php

namespace Database\Factories;

use App\Enums\SystemSettingCategory;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'setting_'.fake()->unique()->slug(2),
            'category' => SystemSettingCategory::GENERAL,
            'label' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'type' => 'string',
            'value' => fake()->sentence(),
        ];
    }
}
