<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceConfiguration>
 */
class ServiceConfigurationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ServiceConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pricingModel = $this->faker->randomElement(PricingModel::cases());
        $distributionMethod = $this->faker->randomElement(DistributionMethod::cases());
        
        return [
            'tenant_id' => 1,
            'property_id' => Property::factory(),
            'utility_service_id' => UtilityService::factory(),
            'pricing_model' => $pricingModel,
            'rate_schedule' => $this->getRateSchedule($pricingModel),
            'distribution_method' => $distributionMethod,
            'is_shared_service' => $this->faker->boolean(),
            'effective_from' => now()->subDays($this->faker->numberBetween(1, 30)),
            'effective_until' => $this->faker->boolean() ? now()->addDays($this->faker->numberBetween(30, 365)) : null,
            'configuration_overrides' => $this->getConfigurationOverrides(),
            'tariff_id' => null,
            'provider_id' => null,
            'area_type' => $distributionMethod->requiresAreaData() ? $this->faker->randomElement(['total_area', 'heated_area', 'commercial_area']) : null,
            'custom_formula' => $pricingModel === PricingModel::CUSTOM_FORMULA ? 'consumption * rate + base_fee' : null,
            'is_active' => true,
        ];
    }

    /**
     * Create a shared service configuration.
     */
    public function sharedService(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shared_service' => true,
        ]);
    }

    /**
     * Create an individual service configuration.
     */
    public function individualService(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shared_service' => false,
        ]);
    }

    /**
     * Create an inactive configuration.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a configuration with a specific pricing model.
     */
    public function withPricingModel(PricingModel $pricingModel): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_model' => $pricingModel,
            'rate_schedule' => $this->getRateSchedule($pricingModel),
        ]);
    }

    /**
     * Create a configuration with a specific distribution method.
     */
    public function withDistributionMethod(DistributionMethod $distributionMethod): static
    {
        return $this->state(fn (array $attributes) => [
            'distribution_method' => $distributionMethod,
            'area_type' => $distributionMethod->requiresAreaData() ? $this->faker->randomElement(['total_area', 'heated_area', 'commercial_area']) : null,
        ]);
    }

    /**
     * Create a configuration that is currently effective.
     */
    public function currentlyEffective(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->subDays($this->faker->numberBetween(1, 30)),
            'effective_until' => now()->addDays($this->faker->numberBetween(30, 365)),
        ]);
    }

    /**
     * Create a configuration that is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_from' => now()->subDays($this->faker->numberBetween(60, 120)),
            'effective_until' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * Get rate schedule based on pricing model.
     */
    private function getRateSchedule(PricingModel $pricingModel): array
    {
        return match ($pricingModel) {
            PricingModel::FIXED_MONTHLY => [
                'monthly_rate' => $this->faker->randomFloat(2, 10, 500),
            ],
            PricingModel::CONSUMPTION_BASED => [
                'rate_per_unit' => $this->faker->randomFloat(4, 0.01, 5.0),
            ],
            PricingModel::TIERED_RATES => [
                'base_rate' => $this->faker->randomFloat(4, 0.01, 1.0),
                'tiers' => [
                    ['limit' => 100, 'rate' => $this->faker->randomFloat(4, 0.01, 1.0)],
                    ['limit' => 500, 'rate' => $this->faker->randomFloat(4, 1.0, 2.0)],
                    ['limit' => PHP_FLOAT_MAX, 'rate' => $this->faker->randomFloat(4, 2.0, 5.0)],
                ],
            ],
            PricingModel::TIME_OF_USE => [
                'time_slots' => [
                    [
                        'day_type' => 'weekday',
                        'start_hour' => 6,
                        'end_hour' => 22,
                        'zone' => 'day',
                        'rate' => $this->faker->randomFloat(4, 0.01, 2.0),
                    ],
                    [
                        'day_type' => 'weekday',
                        'start_hour' => 22,
                        'end_hour' => 6,
                        'zone' => 'night',
                        'rate' => $this->faker->randomFloat(4, 0.005, 1.0),
                    ],
                ],
                'default_rate' => $this->faker->randomFloat(4, 0.01, 1.5),
            ],
            PricingModel::HYBRID => [
                'base_rate' => $this->faker->randomFloat(2, 10, 100),
                'rate_per_unit' => $this->faker->randomFloat(4, 0.01, 2.0),
            ],
            default => [
                'rate' => $this->faker->randomFloat(4, 0.01, 2.0),
            ],
        };
    }

    /**
     * Get configuration overrides.
     */
    private function getConfigurationOverrides(): array
    {
        return [
            'custom_setting' => $this->faker->word(),
            'override_value' => $this->faker->numberBetween(1, 100),
            'special_rate' => $this->faker->randomFloat(4, 0.01, 1.0),
        ];
    }
}
