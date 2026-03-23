<?php

namespace Database\Factories;

use App\Models\FrameworkShowcase;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FrameworkShowcase>
 */
class FrameworkShowcaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'organization_id' => Organization::factory(),
            'created_by_user_id' => User::factory()->superadmin(),
            'title' => $title,
            'slug' => Str::slug($title),
            'status' => fake()->randomElement(['draft', 'review', 'published']),
            'summary' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'meta_title' => fake()->sentence(4),
            'meta_description' => fake()->sentence(),
            'featured_description' => fake()->sentence(),
            'thumbnail_path' => null,
            'tags' => fake()->randomElements(['livewire', 'filament', 'tailwind', 'laravel'], fake()->numberBetween(1, 3)),
            'is_featured' => fake()->boolean(),
            'published_at' => now()->subDays(fake()->numberBetween(0, 14)),
        ];
    }
}
