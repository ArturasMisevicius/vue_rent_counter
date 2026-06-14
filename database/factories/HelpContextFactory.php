<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HelpAudienceRole;
use App\Models\HelpArticle;
use App\Models\HelpContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpContext>
 */
class HelpContextFactory extends Factory
{
    protected $model = HelpContext::class;

    public function definition(): array
    {
        return [
            'page_key' => fake()->randomElement([
                'service_configurations.index',
                'invoices.review',
                'tenant.invitation',
                'rental_contracts.create',
                'tenant.readings',
            ]),
            'article_slug' => fn (): string => HelpArticle::factory()->create()->slug,
            'role' => HelpAudienceRole::ALL,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function forPage(string $pageKey): static
    {
        return $this->state([
            'page_key' => $pageKey,
        ]);
    }

    public function forArticle(HelpArticle|string $article): static
    {
        return $this->state([
            'article_slug' => $article instanceof HelpArticle ? $article->slug : $article,
        ]);
    }

    public function forRole(HelpAudienceRole $role): static
    {
        return $this->state([
            'role' => $role,
        ]);
    }
}
