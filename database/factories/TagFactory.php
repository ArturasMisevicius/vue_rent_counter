<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'color' => fake()->hexColor(),
            'description' => null,
            'type' => fake()->randomElement(['maintenance', 'project', 'priority']),
            'is_system' => false,
        ];
    }
}
