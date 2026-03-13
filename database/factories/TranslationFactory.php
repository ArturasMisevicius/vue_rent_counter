<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => $this->faker->randomElement(['app', 'common', 'auth', 'validation', 'messages']),
            'key' => $this->faker->word() . '_' . $this->faker->unique()->numberBetween(1, 999999),
            'values' => [
                'en' => $this->faker->sentence(),
                'lt' => $this->faker->sentence(),
                'ru' => $this->faker->sentence(),
            ],
        ];
    }

    /**
     * Indicate that the translation has only English value.
     */
    public function englishOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'values' => [
                'en' => $this->faker->sentence(),
            ],
        ]);
    }

    /**
     * Indicate that the translation has specific group.
     */
    public function group(string $group): static
    {
        return $this->state(fn (array $attributes) => [
            'group' => $group,
        ]);
    }

    /**
     * Indicate that the translation has specific key.
     */
    public function key(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
        ]);
    }
}
