<?php

namespace Database\Factories;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
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
        $serviceType = fake()->randomElement([
            ServiceType::ELECTRICITY,
            ServiceType::WATER,
            ServiceType::HEATING,
        ]);
        $pricingModel = $serviceType === ServiceType::HEATING
            ? PricingModel::CUSTOM_FORMULA
            : ($serviceType === ServiceType::ELECTRICITY ? PricingModel::TIME_OF_USE : PricingModel::HYBRID);
        $distributionMethod = $serviceType === ServiceType::HEATING
            ? DistributionMethod::CUSTOM_FORMULA
            : ($serviceType === ServiceType::ELECTRICITY ? DistributionMethod::BY_CONSUMPTION : DistributionMethod::EQUAL);

        return [
            'organization_id' => $organization,
            'property_id' => fn (array $attributes): int => Property::factory()
                ->create([
                    'organization_id' => $attributes['organization_id'],
                    'building_id' => Building::factory()->create([
                        'organization_id' => $attributes['organization_id'],
                    ])->getKey(),
                ])
                ->getKey(),
            'utility_service_id' => fn (array $attributes): int => UtilityService::factory()->create([
                'organization_id' => $attributes['organization_id'],
                'service_type_bridge' => $serviceType,
                'default_pricing_model' => $pricingModel,
            ])->getKey(),
            'pricing_model' => $pricingModel,
            'rate_schedule' => [
                'unit_rate' => fake()->randomFloat(4, 0.05, 2.00),
                'base_fee' => fake()->randomFloat(2, 0, 10),
            ],
            'distribution_method' => $distributionMethod,
            'is_shared_service' => $serviceType === ServiceType::HEATING,
            'effective_from' => now()->startOfMonth(),
            'effective_until' => now()->addMonths(fake()->numberBetween(3, 12)),
            'configuration_overrides' => [
                'seeded' => true,
                'loss_factor' => fake()->randomFloat(2, 1, 1.2),
            ],
            'provider_id' => fn (array $attributes): int => Provider::factory()->create([
                'organization_id' => $attributes['organization_id'],
                'service_type' => $serviceType,
            ])->getKey(),
            'tariff_id' => fn (array $attributes): int => Tariff::factory()->create([
                'provider_id' => $attributes['provider_id'],
            ])->getKey(),
            'area_type' => $serviceType === ServiceType::HEATING ? 'heated' : 'gross',
            'custom_formula' => $serviceType === ServiceType::HEATING
                ? '({consumption} * {unit_rate}) + ({area_sqm} * 0.35) + {base_fee}'
                : '({quantity} * {unit_rate}) + {base_fee}',
            'is_active' => true,
        ];
    }
}
