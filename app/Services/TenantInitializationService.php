<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DistributionMethod;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Organization;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for initializing new tenants with default utility service templates
 * and configurations. Extends existing tenant creation to include universal
 * service setup alongside existing heating initialization.
 */
final readonly class TenantInitializationService
{
    /**
     * Initialize a new tenant with default utility service templates.
     * Creates tenant-specific copies of global templates and sets up
     * default configurations based on tenant type.
     */
    public function initializeUniversalServices(Organization $tenant): array
    {
        return DB::transaction(function () use ($tenant) {
            try {
                Log::info('Initializing universal services for tenant', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                ]);

                // Create default utility service templates for the tenant
                $utilityServices = $this->createDefaultUtilityServices($tenant);

                // Initialize default meter configurations for each service
                $meterConfigurations = $this->createDefaultMeterConfigurations($utilityServices);

                Log::info('Universal services initialized successfully', [
                    'tenant_id' => $tenant->id,
                    'services_created' => count($utilityServices),
                    'meter_configs_created' => count($meterConfigurations),
                ]);

                return [
                    'utility_services' => $utilityServices,
                    'meter_configurations' => $meterConfigurations,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to initialize universal services for tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
                throw new \RuntimeException('Failed to initialize universal services: ' . $e->getMessage(), 0, $e);
            }
        });
    }

    /**
     * Create default utility service templates for a new tenant.
     * Includes electricity, water, heating, and gas services with
     * standard pricing models and regional defaults.
     */
    private function createDefaultUtilityServices(Organization $tenant): array
    {
        $services = [];

        // Define default utility service templates
        $serviceDefinitions = $this->getDefaultServiceDefinitions();

        foreach ($serviceDefinitions as $key => $definition) {
            // Check if a global template exists for this service type
            $globalTemplate = UtilityService::where('is_global_template', true)
                ->where('service_type_bridge', $definition['service_type_bridge'])
                ->first();

            if ($globalTemplate) {
                // Create tenant copy from global template
                $services[$key] = $globalTemplate->createTenantCopy($tenant->id, [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ]);
            } else {
                // Create new service if no global template exists
                $services[$key] = UtilityService::create([
                    'tenant_id' => $tenant->id,
                    'name' => $definition['name'],
                    'slug' => $this->generateUniqueSlug($definition['name'], $tenant->id),
                    'unit_of_measurement' => $definition['unit_of_measurement'],
                    'default_pricing_model' => $definition['default_pricing_model'],
                    'calculation_formula' => $definition['calculation_formula'],
                    'is_global_template' => false,
                    'created_by_tenant_id' => $tenant->id,
                    'configuration_schema' => $definition['configuration_schema'],
                    'validation_rules' => $definition['validation_rules'],
                    'business_logic_config' => $definition['business_logic_config'],
                    'service_type_bridge' => $definition['service_type_bridge'],
                    'description' => $definition['description'],
                    'is_active' => true,
                ]);
            }
        }

        return $services;
    }

    /**
     * Get default service definitions for tenant initialization.
     */
    private function getDefaultServiceDefinitions(): array
    {
        return [
            'electricity' => [
                'name' => 'Electricity Service',
                'unit_of_measurement' => 'kWh',
                'default_pricing_model' => PricingModel::TIME_OF_USE,
                'calculation_formula' => [
                    'base_formula' => 'consumption * rate',
                    'supports_zones' => true,
                    'zone_multipliers' => ['day' => 1.0, 'night' => 0.7],
                ],
                'configuration_schema' => [
                    'required' => ['rate_schedule', 'zone_configuration'],
                    'optional' => ['seasonal_adjustments', 'peak_hour_rates'],
                    'validation' => [
                        'rate_schedule' => 'array',
                        'zone_configuration' => 'array',
                    ],
                ],
                'validation_rules' => [
                    'reading_frequency' => 'monthly',
                    'min_consumption' => 0,
                    'max_consumption' => 10000,
                    'consumption_variance_threshold' => 0.5,
                ],
                'business_logic_config' => [
                    'supports_time_of_use' => true,
                    'supports_seasonal_rates' => true,
                    'requires_meter_zones' => true,
                ],
                'service_type_bridge' => ServiceType::ELECTRICITY,
                'description' => 'Standard electricity service with day/night rate support',
            ],
            'water' => [
                'name' => 'Water Service',
                'unit_of_measurement' => 'm³',
                'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
                'calculation_formula' => [
                    'base_formula' => 'consumption * unit_rate',
                    'supports_zones' => false,
                ],
                'configuration_schema' => [
                    'required' => ['unit_rate'],
                    'optional' => ['connection_fee', 'minimum_charge'],
                    'validation' => [
                        'unit_rate' => 'numeric|min:0',
                    ],
                ],
                'validation_rules' => [
                    'reading_frequency' => 'monthly',
                    'min_consumption' => 0,
                    'max_consumption' => 1000,
                    'consumption_variance_threshold' => 0.3,
                ],
                'business_logic_config' => [
                    'supports_time_of_use' => false,
                    'supports_seasonal_rates' => false,
                    'requires_meter_zones' => false,
                ],
                'service_type_bridge' => ServiceType::WATER,
                'description' => 'Standard water service with consumption-based billing',
            ],
            'heating' => [
                'name' => 'Heating Service',
                'unit_of_measurement' => 'kWh',
                'default_pricing_model' => PricingModel::HYBRID,
                'calculation_formula' => [
                    'base_formula' => 'base_fee + (consumption * unit_rate * seasonal_factor)',
                    'supports_zones' => false,
                    'seasonal_factors' => ['winter' => 1.2, 'summer' => 0.8],
                ],
                'configuration_schema' => [
                    'required' => ['base_fee', 'unit_rate', 'distribution_method'],
                    'optional' => ['seasonal_adjustments', 'building_efficiency_factor'],
                    'validation' => [
                        'base_fee' => 'numeric|min:0',
                        'unit_rate' => 'numeric|min:0',
                    ],
                ],
                'validation_rules' => [
                    'reading_frequency' => 'monthly',
                    'min_consumption' => 0,
                    'max_consumption' => 5000,
                    'consumption_variance_threshold' => 0.4,
                ],
                'business_logic_config' => [
                    'supports_time_of_use' => false,
                    'supports_seasonal_rates' => true,
                    'requires_meter_zones' => false,
                    'supports_shared_distribution' => true,
                ],
                'service_type_bridge' => ServiceType::HEATING,
                'description' => 'Heating service with seasonal adjustments and shared cost distribution',
            ],
            'gas' => [
                'name' => 'Gas Service',
                'unit_of_measurement' => 'm³',
                'default_pricing_model' => PricingModel::TIERED_RATES,
                'calculation_formula' => [
                    'base_formula' => 'tiered_calculation(consumption, rate_tiers)',
                    'supports_zones' => false,
                ],
                'configuration_schema' => [
                    'required' => ['rate_tiers'],
                    'optional' => ['connection_fee', 'delivery_charge'],
                    'validation' => [
                        'rate_tiers' => 'array',
                    ],
                ],
                'validation_rules' => [
                    'reading_frequency' => 'monthly',
                    'min_consumption' => 0,
                    'max_consumption' => 2000,
                    'consumption_variance_threshold' => 0.4,
                ],
                'business_logic_config' => [
                    'supports_time_of_use' => false,
                    'supports_seasonal_rates' => true,
                    'requires_meter_zones' => false,
                    'supports_tiered_rates' => true,
                ],
                'service_type_bridge' => ServiceType::GAS,
                'description' => 'Gas service with tiered rate structure',
            ],
        ];
    }

    /**
     * Create default meter configurations for each utility service.
     * Sets up property-level service assignments based on tenant type.
     */
    private function createDefaultMeterConfigurations(array $utilityServices): array
    {
        $configurations = [];

        foreach ($utilityServices as $serviceKey => $utilityService) {
            $configurations[$serviceKey] = $this->getDefaultMeterConfiguration($serviceKey, $utilityService);
        }

        return $configurations;
    }

    /**
     * Get default meter configuration for a specific utility service.
     */
    private function getDefaultMeterConfiguration(string $serviceKey, UtilityService $utilityService): array
    {
        $baseConfig = [
            'utility_service_id' => $utilityService->id,
            'pricing_model' => $utilityService->default_pricing_model,
            'is_active' => true,
            'effective_from' => now(),
        ];

        return match ($serviceKey) {
            'electricity' => array_merge($baseConfig, [
                'distribution_method' => DistributionMethod::EQUAL,
                'is_shared_service' => false,
                'rate_schedule' => [
                    'zone_rates' => [
                        'day' => 0.15,
                        'night' => 0.10,
                    ],
                    'default_rate' => 0.15,
                ],
                'configuration_overrides' => [
                    'meter_type' => MeterType::ELECTRICITY,
                    'supports_zones' => true,
                    'reading_structure' => [
                        'zones' => ['day', 'night'],
                        'required_fields' => ['day_reading', 'night_reading'],
                    ],
                ],
            ]),
            'water' => array_merge($baseConfig, [
                'distribution_method' => DistributionMethod::EQUAL,
                'is_shared_service' => false,
                'rate_schedule' => [
                    'unit_rate' => 2.50,
                    'connection_fee' => 5.00,
                ],
                'configuration_overrides' => [
                    'meter_type' => MeterType::WATER,
                    'supports_zones' => false,
                    'reading_structure' => [
                        'zones' => [],
                        'required_fields' => ['total_reading'],
                    ],
                ],
            ]),
            'heating' => array_merge($baseConfig, [
                'distribution_method' => DistributionMethod::BY_AREA,
                'is_shared_service' => true,
                'rate_schedule' => [
                    'base_fee' => 15.00,
                    'unit_rate' => 0.08,
                    'seasonal_factors' => [
                        'winter' => 1.2,
                        'summer' => 0.8,
                    ],
                ],
                'configuration_overrides' => [
                    'meter_type' => MeterType::HEATING,
                    'supports_zones' => false,
                    'reading_structure' => [
                        'zones' => [],
                        'required_fields' => ['consumption_reading'],
                    ],
                ],
                'area_type' => 'heated_area',
            ]),
            'gas' => array_merge($baseConfig, [
                'distribution_method' => DistributionMethod::EQUAL,
                'is_shared_service' => false,
                'rate_schedule' => [
                    'tiers' => [
                        ['limit' => 50, 'rate' => 0.45],
                        ['limit' => 150, 'rate' => 0.40],
                        ['limit' => PHP_FLOAT_MAX, 'rate' => 0.35],
                    ],
                    'connection_fee' => 8.00,
                ],
                'configuration_overrides' => [
                    'meter_type' => MeterType::GAS,
                    'supports_zones' => false,
                    'reading_structure' => [
                        'zones' => [],
                        'required_fields' => ['total_reading'],
                    ],
                ],
            ]),
            default => $baseConfig,
        };
    }

    /**
     * Initialize property-level service assignments for existing properties.
     * Creates ServiceConfiguration records for properties that already exist.
     */
    public function initializePropertyServiceAssignments(Organization $tenant, array $utilityServices): array
    {
        $serviceConfigurations = [];
        $properties = Property::where('tenant_id', $tenant->id)->get();

        if ($properties->isEmpty()) {
            Log::info('No existing properties found for tenant, skipping property service assignments', [
                'tenant_id' => $tenant->id,
            ]);
            return $serviceConfigurations;
        }

        return DB::transaction(function () use ($properties, $utilityServices, $tenant) {
            $serviceConfigurations = [];

            foreach ($properties as $property) {
                foreach ($utilityServices as $serviceKey => $utilityService) {
                    $configData = $this->getDefaultMeterConfiguration($serviceKey, $utilityService);
                    $configData['tenant_id'] = $tenant->id;
                    $configData['property_id'] = $property->id;

                    $serviceConfiguration = ServiceConfiguration::create($configData);
                    $serviceConfigurations[$property->id][$serviceKey] = $serviceConfiguration;
                }
            }

            Log::info('Property service assignments initialized', [
                'tenant_id' => $tenant->id,
                'properties_configured' => count($properties),
                'total_configurations' => count($serviceConfigurations) * count($utilityServices),
            ]);

            return $serviceConfigurations;
        });
    }

    /**
     * Generate a unique slug for a utility service within a tenant.
     */
    private function generateUniqueSlug(string $name, int $tenantId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (UtilityService::where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Ensure backward compatibility with existing heating initialization.
     * This method can be called to verify that existing heating setup
     * is preserved and enhanced with universal service capabilities.
     */
    public function ensureHeatingCompatibility(Organization $tenant): bool
    {
        try {
            // Check if heating service exists and is properly configured
            $heatingService = UtilityService::where('tenant_id', $tenant->id)
                ->where('service_type_bridge', ServiceType::HEATING)
                ->first();

            if (!$heatingService) {
                Log::warning('No heating service found for tenant during compatibility check', [
                    'tenant_id' => $tenant->id,
                ]);
                return false;
            }

            // Verify heating service has proper bridge configuration
            $isCompatible = $heatingService->service_type_bridge === ServiceType::HEATING
                && $heatingService->default_pricing_model === PricingModel::HYBRID
                && !empty($heatingService->business_logic_config['supports_shared_distribution']);

            Log::info('Heating compatibility check completed', [
                'tenant_id' => $tenant->id,
                'heating_service_id' => $heatingService->id,
                'is_compatible' => $isCompatible,
            ]);

            return $isCompatible;
        } catch (\Exception $e) {
            Log::error('Failed to check heating compatibility', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}