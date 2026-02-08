<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization;

use App\Enums\PricingModel;
use App\Enums\ServiceType;

/**
 * Provides default service definitions for tenant initialization.
 * 
 * This class defines the standard utility services (electricity, water, heating, gas)
 * that are created for new tenants, including their default configurations,
 * pricing models, and business logic settings.
 * 
 * @package App\Services\TenantInitialization
 * @author Laravel Development Team
 * @since 1.0.0
 */
final readonly class ServiceDefinitionProvider
{
    /**
     * Get default service definitions for tenant initialization.
     * 
     * Returns an array of service definitions with all necessary configuration
     * for creating the standard utility services for a new tenant.
     * 
     * @return array<string, array<string, mixed>> Service definitions keyed by service type
     */
    public function getDefaultServiceDefinitions(): array
    {
        return [
            'electricity' => $this->getElectricityServiceDefinition(),
            'water' => $this->getWaterServiceDefinition(),
            'heating' => $this->getHeatingServiceDefinition(),
            'gas' => $this->getGasServiceDefinition(),
        ];
    }

    /**
     * Get electricity service definition.
     * 
     * @return array<string, mixed>
     */
    private function getElectricityServiceDefinition(): array
    {
        return [
            'name' => 'Electricity',
            'unit_of_measurement' => 'kWh',
            'default_pricing_model' => PricingModel::TIME_OF_USE,
            'service_type_bridge' => ServiceType::ELECTRICITY,
            'description' => 'Electrical energy consumption with day/night rate zones',
            'calculation_formula' => 'consumption * rate_by_zone',
            'configuration_schema' => [
                'type' => 'object',
                'properties' => [
                    'day_rate' => ['type' => 'number', 'minimum' => 0],
                    'night_rate' => ['type' => 'number', 'minimum' => 0],
                    'day_start_hour' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 23],
                    'night_start_hour' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 23],
                    'supports_zones' => ['type' => 'boolean'],
                ],
                'required' => ['day_rate', 'night_rate', 'supports_zones'],
            ],
            'validation_rules' => [
                'max_monthly_consumption' => 10000,
                'variance_threshold' => 0.5,
                'require_monotonic_readings' => true,
                'allow_negative_consumption' => false,
            ],
            'business_logic_config' => [
                'supports_time_of_use' => true,
                'supports_tiered_pricing' => true,
                'requires_meter_reading' => true,
                'supports_estimated_readings' => true,
                'photo_verification_required' => true,
                'seasonal_adjustments' => false,
            ],
        ];
    }

    /**
     * Get water service definition.
     * 
     * @return array<string, mixed>
     */
    private function getWaterServiceDefinition(): array
    {
        return [
            'name' => 'Water',
            'unit_of_measurement' => 'm³',
            'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
            'service_type_bridge' => ServiceType::WATER,
            'description' => 'Water consumption with consumption-based pricing',
            'calculation_formula' => 'consumption * unit_rate + fixed_fee',
            'configuration_schema' => [
                'type' => 'object',
                'properties' => [
                    'unit_rate' => ['type' => 'number', 'minimum' => 0],
                    'fixed_fee' => ['type' => 'number', 'minimum' => 0],
                    'connection_fee' => ['type' => 'number', 'minimum' => 0],
                    'supports_hot_cold_split' => ['type' => 'boolean'],
                ],
                'required' => ['unit_rate'],
            ],
            'validation_rules' => [
                'max_monthly_consumption' => 1000,
                'variance_threshold' => 0.3,
                'require_monotonic_readings' => true,
                'allow_negative_consumption' => false,
            ],
            'business_logic_config' => [
                'supports_time_of_use' => false,
                'supports_tiered_pricing' => true,
                'requires_meter_reading' => true,
                'supports_estimated_readings' => true,
                'photo_verification_required' => false,
                'seasonal_adjustments' => false,
                'supports_hot_cold_meters' => true,
            ],
        ];
    }

    /**
     * Get heating service definition.
     * 
     * @return array<string, mixed>
     */
    private function getHeatingServiceDefinition(): array
    {
        return [
            'name' => 'Heating',
            'unit_of_measurement' => 'kWh',
            'default_pricing_model' => PricingModel::HYBRID,
            'service_type_bridge' => ServiceType::HEATING,
            'description' => 'Central heating with shared cost distribution',
            'calculation_formula' => 'base_cost + (consumption * unit_rate) + shared_distribution',
            'configuration_schema' => [
                'type' => 'object',
                'properties' => [
                    'base_rate' => ['type' => 'number', 'minimum' => 0],
                    'consumption_rate' => ['type' => 'number', 'minimum' => 0],
                    'distribution_method' => ['type' => 'string', 'enum' => ['equal', 'area', 'consumption', 'custom']],
                    'seasonal_adjustment' => ['type' => 'boolean'],
                    'summer_months' => ['type' => 'array', 'items' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 12]],
                ],
                'required' => ['base_rate', 'consumption_rate', 'distribution_method'],
            ],
            'validation_rules' => [
                'max_monthly_consumption' => 5000,
                'variance_threshold' => 0.4,
                'require_monotonic_readings' => false,
                'allow_negative_consumption' => false,
            ],
            'business_logic_config' => [
                'supports_time_of_use' => false,
                'supports_tiered_pricing' => false,
                'requires_meter_reading' => true,
                'supports_estimated_readings' => true,
                'photo_verification_required' => false,
                'seasonal_adjustments' => true,
                'supports_shared_distribution' => true,
                'distribution_methods' => ['equal', 'area', 'consumption', 'custom'],
                'summer_months' => [5, 6, 7, 8, 9], // May through September
            ],
        ];
    }

    /**
     * Get gas service definition.
     * 
     * @return array<string, mixed>
     */
    private function getGasServiceDefinition(): array
    {
        return [
            'name' => 'Gas',
            'unit_of_measurement' => 'm³',
            'default_pricing_model' => PricingModel::TIERED_RATES,
            'service_type_bridge' => ServiceType::GAS,
            'description' => 'Natural gas consumption with tiered rate structure',
            'calculation_formula' => 'tiered_consumption_calculation',
            'configuration_schema' => [
                'type' => 'object',
                'properties' => [
                    'tier_1_limit' => ['type' => 'number', 'minimum' => 0],
                    'tier_1_rate' => ['type' => 'number', 'minimum' => 0],
                    'tier_2_limit' => ['type' => 'number', 'minimum' => 0],
                    'tier_2_rate' => ['type' => 'number', 'minimum' => 0],
                    'tier_3_rate' => ['type' => 'number', 'minimum' => 0],
                    'connection_fee' => ['type' => 'number', 'minimum' => 0],
                ],
                'required' => ['tier_1_limit', 'tier_1_rate', 'tier_2_rate'],
            ],
            'validation_rules' => [
                'max_monthly_consumption' => 2000,
                'variance_threshold' => 0.4,
                'require_monotonic_readings' => true,
                'allow_negative_consumption' => false,
            ],
            'business_logic_config' => [
                'supports_time_of_use' => false,
                'supports_tiered_pricing' => true,
                'requires_meter_reading' => true,
                'supports_estimated_readings' => true,
                'photo_verification_required' => true,
                'seasonal_adjustments' => true,
                'tier_structure' => [
                    'tier_1' => ['limit' => 100, 'description' => 'Basic consumption'],
                    'tier_2' => ['limit' => 500, 'description' => 'Standard consumption'],
                    'tier_3' => ['limit' => null, 'description' => 'High consumption'],
                ],
            ],
        ];
    }

    /**
     * Get service definition for a specific service type.
     * 
     * @return array<string, mixed>|null
     */
    public function getServiceDefinition(string $serviceType): ?array
    {
        $definitions = $this->getDefaultServiceDefinitions();
        
        return $definitions[$serviceType] ?? null;
    }

    /**
     * Get all supported service types.
     * 
     * @return array<string>
     */
    public function getSupportedServiceTypes(): array
    {
        return array_keys($this->getDefaultServiceDefinitions());
    }

    /**
     * Check if a service type is supported.
     */
    public function isServiceTypeSupported(string $serviceType): bool
    {
        return in_array($serviceType, $this->getSupportedServiceTypes(), true);
    }
}