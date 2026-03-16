<?php

namespace Database\Factories;

use App\Enums\ServiceType;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Provider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'service_type' => fake()->randomElement([
                ServiceType::ELECTRICITY,
                ServiceType::WATER,
                ServiceType::HEATING,
            ]),
            'contact_info' => [
                'phone' => fake()->phoneNumber(),
                'email' => fake()->companyEmail(),
                'website' => fake()->url(),
            ],
        ];
    }

    /**
     * Indicate that the provider is Ignitis (electricity).
     */
    public function ignitis(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Ignitis',
            'service_type' => ServiceType::ELECTRICITY,
        ]);
    }

    /**
     * Indicate that the provider is Vilniaus Vandenys (water).
     */
    public function vilniausVandenys(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Vilniaus Vandenys',
            'service_type' => ServiceType::WATER,
        ]);
    }

    /**
     * Indicate that the provider is Vilniaus Energija (heating).
     */
    public function vilniausEnergija(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Vilniaus Energija',
            'service_type' => ServiceType::HEATING,
        ]);
    }
}
