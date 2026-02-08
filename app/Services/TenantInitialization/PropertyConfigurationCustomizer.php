<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\Organization;
use App\Models\Property;
use App\Models\UtilityService;

/**
 * Customizes service configurations based on property characteristics.
 * 
 * This class applies property-specific adjustments to utility service configurations,
 * taking into account property type, size, location, and tenant preferences to
 * create optimized service configurations for each property.
 * 
 * @package App\Services\TenantInitialization
 * @author Laravel Development Team
 * @since 1.0.0
 */
final readonly class PropertyConfigurationCustomizer
{
    /**
     * Get property-specific configuration for a utility service.
     * 
     * @param string $serviceKey The service type key (electricity, water, heating, gas)
     * @param UtilityService $utilityService The utility service to configure
     * @param Property $property The property to configure for
     * @param Organization $tenant The tenant organization
     * 
     * @return array<string, mixed> Property-specific service configuration
     */
    public function getPropertySpecificConfiguration(
        string $serviceKey,
        UtilityService $utilityService,
        Property $property,
        Organization $tenant
    ): array {
        $baseConfiguration = [
            'utility_service_id' => $utilityService->id,
            'pricing_model' => $utilityService->default_pricing_model,
            'distribution_method' => $this->getDefaultDistributionMethod($serviceKey, $property),
            'is_shared_service' => $this->isSharedService($serviceKey, $property),
            'effective_from' => now(),
            'effective_until' => null,
            'is_active' => true,
        ];

        // Apply service-specific customizations
        $serviceSpecificConfig = match ($serviceKey) {
            'electricity' => $this->customizeElectricityConfiguration($property, $tenant),
            'water' => $this->customizeWaterConfiguration($property, $tenant),
            'heating' => $this->customizeHeatingConfiguration($property, $tenant),
            'gas' => $this->customizeGasConfiguration($property, $tenant),
            default => [],
        };

        // Apply property-type specific adjustments
        $propertyTypeConfig = $this->getPropertyTypeAdjustments($property, $serviceKey);

        // Apply regional/location specific adjustments
        $locationConfig = $this->getLocationAdjustments($property, $serviceKey);

        return array_merge(
            $baseConfiguration,
            $serviceSpecificConfig,
            $propertyTypeConfig,
            $locationConfig
        );
    }

    /**
     * Get default distribution method for a service and property.
     */
    private function getDefaultDistributionMethod(string $serviceKey, Property $property): DistributionMethod
    {
        return match ($serviceKey) {
            'heating' => $this->getHeatingDistributionMethod($property),
            'water' => DistributionMethod::BY_CONSUMPTION,
            'electricity' => DistributionMethod::BY_CONSUMPTION,
            'gas' => DistributionMethod::BY_CONSUMPTION,
            default => DistributionMethod::EQUAL,
        };
    }

    /**
     * Determine if a service should be configured as shared for a property.
     */
    private function isSharedService(string $serviceKey, Property $property): bool
    {
        // Heating is typically shared in multi-unit buildings
        if ($serviceKey === 'heating' && $this->isMultiUnitProperty($property)) {
            return true;
        }

        // Other services are typically individual
        return false;
    }

    /**
     * Customize electricity configuration for a property.
     * 
     * @return array<string, mixed>
     */
    private function customizeElectricityConfiguration(Property $property, Organization $tenant): array
    {
        $config = [
            'rate_schedule' => [
                'zone_rates' => [
                    'day' => $this->getElectricityDayRate($property, $tenant),
                    'night' => $this->getElectricityNightRate($property, $tenant),
                ],
            ],
            'configuration_overrides' => [
                'supports_zones' => true,
                'meter_type' => 'electricity',
                'reading_structure' => [
                    'zones' => ['day', 'night'],
                    'requires_total' => true,
                ],
            ],
        ];

        // Adjust for property size
        if ($this->isLargeProperty($property)) {
            $config['rate_schedule']['demand_charge'] = $this->getDemandCharge($property);
        }

        return $config;
    }

    /**
     * Customize water configuration for a property.
     * 
     * @return array<string, mixed>
     */
    private function customizeWaterConfiguration(Property $property, Organization $tenant): array
    {
        $config = [
            'rate_schedule' => [
                'unit_rate' => $this->getWaterUnitRate($property, $tenant),
                'fixed_fee' => $this->getWaterFixedFee($property, $tenant),
            ],
            'configuration_overrides' => [
                'supports_hot_cold_split' => $this->supportsHotColdSplit($property),
                'meter_type' => 'water',
                'reading_structure' => [
                    'fields' => $this->getWaterReadingFields($property),
                ],
            ],
        ];

        // Apply tiered pricing for large properties
        if ($this->isLargeProperty($property)) {
            $config['pricing_model'] = PricingModel::TIERED_RATES;
            $config['rate_schedule']['tiers'] = $this->getWaterTiers($property);
        }

        return $config;
    }

    /**
     * Customize heating configuration for a property.
     * 
     * @return array<string, mixed>
     */
    private function customizeHeatingConfiguration(Property $property, Organization $tenant): array
    {
        $config = [
            'rate_schedule' => [
                'fixed_fee' => $this->getHeatingFixedFee($property, $tenant),
                'unit_rate' => $this->getHeatingUnitRate($property, $tenant),
            ],
            'configuration_overrides' => [
                'seasonal_adjustment' => true,
                'distribution_method' => $this->getHeatingDistributionMethod($property)->value,
                'meter_type' => 'heating',
                'reading_structure' => [
                    'fields' => ['energy_consumption'],
                    'supports_temperature' => true,
                ],
            ],
        ];

        // Configure shared service settings for multi-unit properties
        if ($this->isMultiUnitProperty($property)) {
            $config['shared_service_config'] = [
                'distribution_factors' => $this->getHeatingDistributionFactors($property),
                'common_area_allocation' => $this->getCommonAreaAllocation($property),
            ];
        }

        return $config;
    }

    /**
     * Customize gas configuration for a property.
     * 
     * @return array<string, mixed>
     */
    private function customizeGasConfiguration(Property $property, Organization $tenant): array
    {
        return [
            'rate_schedule' => [
                'tiers' => [
                    ['limit' => 100, 'rate' => $this->getGasTier1Rate($property, $tenant)],
                    ['limit' => 500, 'rate' => $this->getGasTier2Rate($property, $tenant)],
                    ['limit' => null, 'rate' => $this->getGasTier3Rate($property, $tenant)],
                ],
                'connection_fee' => $this->getGasConnectionFee($property, $tenant),
            ],
            'configuration_overrides' => [
                'meter_type' => 'gas',
                'safety_requirements' => true,
                'reading_structure' => [
                    'fields' => ['volume_consumption'],
                    'requires_pressure' => $this->requiresPressureReading($property),
                ],
            ],
        ];
    }

    /**
     * Get property type specific adjustments.
     * 
     * @return array<string, mixed>
     */
    private function getPropertyTypeAdjustments(Property $property, string $serviceKey): array
    {
        $propertyType = $this->getPropertyType($property);

        return match ($propertyType) {
            'residential' => $this->getResidentialAdjustments($serviceKey),
            'commercial' => $this->getCommercialAdjustments($serviceKey),
            'industrial' => $this->getIndustrialAdjustments($serviceKey),
            'mixed_use' => $this->getMixedUseAdjustments($serviceKey),
            default => [],
        };
    }

    /**
     * Get location-specific adjustments.
     * 
     * @return array<string, mixed>
     */
    private function getLocationAdjustments(Property $property, string $serviceKey): array
    {
        // This could be expanded to include regional pricing differences,
        // climate adjustments, local regulations, etc.
        return [
            'location_factors' => [
                'climate_zone' => $this->getClimateZone($property),
                'utility_region' => $this->getUtilityRegion($property),
                'regulatory_zone' => $this->getRegulatoryZone($property),
            ],
        ];
    }

    // Helper methods for property analysis

    private function isMultiUnitProperty(Property $property): bool
    {
        return ($property->total_area ?? 0) > 500 || 
               str_contains(strtolower($property->address ?? ''), 'apartment') ||
               str_contains(strtolower($property->address ?? ''), 'building');
    }

    private function isLargeProperty(Property $property): bool
    {
        return ($property->total_area ?? 0) > 1000;
    }

    private function getPropertyType(Property $property): string
    {
        // This would typically be a field on the property model
        // For now, we'll infer from available data
        if (str_contains(strtolower($property->address ?? ''), 'commercial')) {
            return 'commercial';
        }
        
        return 'residential'; // Default assumption
    }

    private function getHeatingDistributionMethod(Property $property): DistributionMethod
    {
        if ($this->isMultiUnitProperty($property)) {
            return DistributionMethod::AREA; // Area-based for multi-unit
        }
        
        return DistributionMethod::EQUAL; // Equal for single units
    }

    private function supportsHotColdSplit(Property $property): bool
    {
        // Most modern properties support separate hot/cold water metering
        return true;
    }

    // Rate calculation methods (these would typically fetch from configuration or external sources)

    private function getElectricityDayRate(Property $property, Organization $tenant): float
    {
        return 0.15; // €0.15 per kWh - Lithuanian average day rate
    }

    private function getElectricityNightRate(Property $property, Organization $tenant): float
    {
        return 0.08; // €0.08 per kWh - Lithuanian average night rate
    }

    private function getWaterUnitRate(Property $property, Organization $tenant): float
    {
        return 1.20; // €1.20 per m³ - Lithuanian average water rate
    }

    private function getWaterFixedFee(Property $property, Organization $tenant): float
    {
        return 5.00; // €5.00 monthly fixed fee
    }

    private function getHeatingFixedFee(Property $property, Organization $tenant): float
    {
        return 15.00; // €15.00 monthly fixed fee for heating
    }

    private function getHeatingUnitRate(Property $property, Organization $tenant): float
    {
        return 0.06; // €0.06 per kWh for heating
    }

    private function getGasTier1Rate(Property $property, Organization $tenant): float
    {
        return 0.45; // €0.45 per m³ for first tier
    }

    private function getGasTier2Rate(Property $property, Organization $tenant): float
    {
        return 0.50; // €0.50 per m³ for second tier
    }

    private function getGasTier3Rate(Property $property, Organization $tenant): float
    {
        return 0.55; // €0.55 per m³ for third tier
    }

    private function getGasConnectionFee(Property $property, Organization $tenant): float
    {
        return 8.00; // €8.00 monthly connection fee
    }

    // Additional helper methods

    private function getDemandCharge(Property $property): float
    {
        return 10.00; // €10.00 per kW demand charge for large properties
    }

    private function getWaterReadingFields(Property $property): array
    {
        if ($this->supportsHotColdSplit($property)) {
            return ['cold_water', 'hot_water'];
        }
        
        return ['total_water'];
    }

    private function getWaterTiers(Property $property): array
    {
        return [
            ['limit' => 50, 'rate' => 1.00],
            ['limit' => 200, 'rate' => 1.20],
            ['limit' => null, 'rate' => 1.50],
        ];
    }

    private function getHeatingDistributionFactors(Property $property): array
    {
        return [
            'area_factor' => 0.7,
            'consumption_factor' => 0.3,
        ];
    }

    private function getCommonAreaAllocation(Property $property): float
    {
        return 0.15; // 15% allocation for common areas
    }

    private function requiresPressureReading(Property $property): bool
    {
        return $this->isLargeProperty($property); // Large properties need pressure monitoring
    }

    private function getResidentialAdjustments(string $serviceKey): array
    {
        return ['residential_discount' => 0.05]; // 5% residential discount
    }

    private function getCommercialAdjustments(string $serviceKey): array
    {
        return ['commercial_surcharge' => 0.10]; // 10% commercial surcharge
    }

    private function getIndustrialAdjustments(string $serviceKey): array
    {
        return ['industrial_rate' => true]; // Special industrial rates
    }

    private function getMixedUseAdjustments(string $serviceKey): array
    {
        return ['mixed_use_factor' => 1.0]; // No adjustment for mixed use
    }

    private function getClimateZone(Property $property): string
    {
        return 'temperate'; // Lithuanian climate zone
    }

    private function getUtilityRegion(Property $property): string
    {
        return 'vilnius'; // Default to Vilnius region
    }

    private function getRegulatoryZone(Property $property): string
    {
        return 'lithuania'; // Lithuanian regulatory zone
    }
}