<?php

namespace Database\Factories;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceConfiguration>
 */
class ServiceConfigurationFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'property_id' => Property::factory()
                ->for($organization)
                ->for(Building::factory()->for($organization)),
            'utility_service_id' => UtilityService::factory(),
            'pricing_model' => fake()->randomElement(PricingModel::cases()),
            'rate_schedule' => [
                'unit_rate' => fake()->randomFloat(4, 0.05, 2.00),
            ],
            'distribution_method' => fake()->randomElement(DistributionMethod::cases()),
            'is_shared_service' => false,
            'effective_from' => now()->startOfMonth(),
            'effective_until' => null,
            'configuration_overrides' => null,
            'tariff_id' => null,
            'provider_id' => null,
            'area_type' => null,
            'custom_formula' => null,
            'is_active' => true,
        ];
    }
}
