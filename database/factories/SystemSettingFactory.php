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
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'category' => fake()->randomElement(SystemSettingCategory::cases()),
            'key' => $key,
            'label' => str($key)->replace('-', ' ')->title()->toString(),
            'value' => ['value' => fake()->sentence()],
            'is_encrypted' => false,
        ];
    }
}
