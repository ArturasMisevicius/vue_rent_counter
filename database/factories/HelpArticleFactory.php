<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HelpArticleCategory;
use App\Enums\HelpAudienceRole;
use App\Models\HelpArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpArticle>
 */
class HelpArticleFactory extends Factory
{
    protected $model = HelpArticle::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'category' => fake()->randomElement(HelpArticleCategory::cases()),
            'title' => $title,
            'body' => fake()->paragraphs(3, true),
            'locale' => 'en',
            'role' => HelpAudienceRole::ALL,
            'tags' => fake()->words(3),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function forRole(HelpAudienceRole $role): static
    {
        return $this->state([
            'role' => $role,
        ]);
    }

    public function forLocale(string $locale): static
    {
        return $this->state([
            'locale' => $locale,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
