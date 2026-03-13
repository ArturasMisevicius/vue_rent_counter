<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Tenant;
use App\Models\UtilityService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UtilityService>
 */
class UtilityServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UtilityService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true) . ' Service';
        $pricingModel = $this->faker->randomElement(PricingModel::cases());

        return [
            // tenant_id is the Organization (multi-tenancy scope)
            'tenant_id' => 1,
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'unit_of_measurement' => $this->faker->randomElement(['kWh', 'L', 'kW', 'm3', 'units']),
            'default_pricing_model' => $pricingModel,
            'calculation_formula' => $this->getCalculationFormula($pricingModel),
            'is_global_template' => false,
            'created_by_tenant_id' => null,
            'configuration_schema' => $this->getConfigurationSchema(),
            'validation_rules' => $this->getValidationRules(),
            'business_logic_config' => $this->getBusinessLogicConfig(),
            'service_type_bridge' => $this->faker->randomElement(ServiceType::cases()),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Create a global template service.
     */
    public function globalTemplate(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'is_global_template' => true,
            'created_by_tenant_id' => Tenant::factory(),
        ]);
    }

    /**
     * Create an inactive service.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a service with a specific pricing model.
     */
    public function withPricingModel(PricingModel $pricingModel): static
    {
        return $this->state(fn (array $attributes) => [
            'default_pricing_model' => $pricingModel,
            'calculation_formula' => $this->getCalculationFormula($pricingModel),
        ]);
    }

    /**
     * Create a service with custom formula support.
     */
    public function withCustomFormula(): static
    {
        return $this->withPricingModel(PricingModel::CUSTOM_FORMULA);
    }

    /**
     * Get calculation formula based on pricing model.
     */
    private function getCalculationFormula(PricingModel $pricingModel): array
    {
        return match ($pricingModel) {
            PricingModel::FIXED_MONTHLY => [
                'type' => 'fixed',
                'monthly_rate' => $this->faker->randomFloat(2, 10, 500),
            ],
            PricingModel::CONSUMPTION_BASED => [
                'type' => 'consumption',
                'rate_per_unit' => $this->faker->randomFloat(4, 0.01, 5.0),
            ],
            PricingModel::TIERED_RATES => [
                'type' => 'tiered',
                'tiers' => [
                    ['limit' => 100, 'rate' => $this->faker->randomFloat(4, 0.01, 1.0)],
                    ['limit' => 500, 'rate' => $this->faker->randomFloat(4, 1.0, 2.0)],
                    ['limit' => PHP_FLOAT_MAX, 'rate' => $this->faker->randomFloat(4, 2.0, 5.0)],
                ],
            ],
            PricingModel::HYBRID => [
                'type' => 'hybrid',
                'base_fee' => $this->faker->randomFloat(2, 5, 50),
                'rate_per_unit' => $this->faker->randomFloat(4, 0.01, 2.0),
            ],
            PricingModel::CUSTOM_FORMULA => [
                'type' => 'custom',
                'formula' => 'consumption * rate + base_fee',
                'variables' => [
                    'rate' => $this->faker->randomFloat(4, 0.01, 3.0),
                    'base_fee' => $this->faker->randomFloat(2, 0, 100),
                ],
            ],
            default => [
                'type' => 'flat',
                'rate' => $this->faker->randomFloat(4, 0.01, 2.0),
            ],
        };
    }

    /**
     * Get configuration schema.
     */
    private function getConfigurationSchema(): array
    {
        return [
            'required' => ['rate_schedule'],
            'optional' => ['custom_formula', 'area_type'],
            'validation' => [
                'rate_schedule' => 'array',
                'custom_formula' => 'string|nullable',
            ],
        ];
    }

    /**
     * Get validation rules.
     */
    private function getValidationRules(): array
    {
        return [
            'consumption_limits' => [
                'min' => $this->faker->numberBetween(0, 10),
                'max' => $this->faker->numberBetween(1000, 10000),
            ],
            'reading_frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
        ];
    }

    /**
     * Get business logic configuration.
     */
    private function getBusinessLogicConfig(): array
    {
        return [
            'auto_validation' => $this->faker->boolean(),
            'seasonal_adjustments' => $this->faker->boolean(),
            'distribution_rules' => [
                'default_method' => $this->faker->randomElement(['equal', 'area', 'by_consumption']),
                'allow_overrides' => $this->faker->boolean(),
            ],
        ];
    }
}
