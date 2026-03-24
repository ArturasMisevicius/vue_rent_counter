<?php

namespace Database\Factories;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Organization;
use App\Models\UtilityService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UtilityService>
 */
class UtilityServiceFactory extends Factory
{
    public function definition(): array
    {
        $serviceType = fake()->randomElement([
            ServiceType::ELECTRICITY,
            ServiceType::WATER,
            ServiceType::HEATING,
        ]);
        $name = fake()->words(2, true).' Service';

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'unit_of_measurement' => $serviceType->defaultUnit()->value,
            'default_pricing_model' => fake()->randomElement(PricingModel::cases()),
            'calculation_formula' => null,
            'is_global_template' => false,
            'created_by_organization_id' => null,
            'configuration_schema' => [
                'required' => ['rate_schedule'],
            ],
            'validation_rules' => [
                'rate_schedule' => 'array',
            ],
            'business_logic_config' => [
                'auto_validation' => true,
            ],
            'service_type_bridge' => $serviceType,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function globalTemplate(): static
    {
        return $this->state(fn () => [
            'organization_id' => null,
            'is_global_template' => true,
        ]);
    }
}
