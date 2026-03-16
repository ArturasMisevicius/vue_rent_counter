<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization;

use App\Models\Organization;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles assignment of utility services to properties.
 * 
 * This class manages the creation of ServiceConfiguration records that link properties
 * to utility services with appropriate pricing models and configuration overrides.
 * It coordinates with PropertyConfigurationCustomizer to apply property-specific
 * adjustments based on property type, size, location, and tenant preferences.
 * 
 * All assignments are performed within database transactions to ensure data consistency
 * and provide rollback capability in case of failures.
 * 
 * @package App\Services\TenantInitialization
 * @author Laravel Development Team
 * @since 1.0.0
 * 
 * @see \App\Models\ServiceConfiguration
 * @see \App\Models\Property
 * @see \App\Services\TenantInitialization\PropertyConfigurationCustomizer
 */
final readonly class PropertyServiceAssigner
{
    public function __construct(
        private PropertyConfigurationCustomizer $configurationCustomizer,
    ) {}

    /**
     * Assign utility services to properties for a tenant.
     * 
     * @param Organization $tenant The tenant organization
     * @param Collection<Property> $properties The properties to configure
     * @param array<string, UtilityService> $utilityServices The utility services to assign
     * 
     * @return array<int, array<string, ServiceConfiguration>> Service configurations grouped by property ID
     */
    public function assignServicesToProperties(
        Organization $tenant,
        Collection $properties,
        array $utilityServices
    ): array {
        return DB::transaction(function () use ($properties, $utilityServices, $tenant) {
            $serviceConfigurations = [];

            foreach ($properties as $property) {
                $serviceConfigurations[$property->id] = $this->assignServicesToProperty(
                    $tenant,
                    $property,
                    $utilityServices
                );
            }

            Log::info('Property service assignments initialized', [
                'tenant_id' => $tenant->id,
                'properties_configured' => count($properties),
                'total_configurations' => $this->countTotalConfigurations($serviceConfigurations),
            ]);

            return $serviceConfigurations;
        });
    }

    /**
     * Assign utility services to a single property.
     * 
     * @param Organization $tenant The tenant organization
     * @param Property $property The property to configure
     * @param array<string, UtilityService> $utilityServices The utility services to assign
     * 
     * @return array<string, ServiceConfiguration> Service configurations keyed by service type
     */
    private function assignServicesToProperty(
        Organization $tenant,
        Property $property,
        array $utilityServices
    ): array {
        $propertyConfigurations = [];

        foreach ($utilityServices as $serviceKey => $utilityService) {
            $configData = $this->configurationCustomizer->getPropertySpecificConfiguration(
                $serviceKey,
                $utilityService,
                $property,
                $tenant
            );

            $configData['tenant_id'] = $tenant->id;
            $configData['property_id'] = $property->id;

            $serviceConfiguration = ServiceConfiguration::create($configData);
            $propertyConfigurations[$serviceKey] = $serviceConfiguration;
        }

        return $propertyConfigurations;
    }

    /**
     * Count total configurations created.
     * 
     * @param array<int, array<string, ServiceConfiguration>> $serviceConfigurations
     * 
     * @return int Total number of configurations
     */
    private function countTotalConfigurations(array $serviceConfigurations): int
    {
        return array_sum(array_map('count', $serviceConfigurations));
    }
}