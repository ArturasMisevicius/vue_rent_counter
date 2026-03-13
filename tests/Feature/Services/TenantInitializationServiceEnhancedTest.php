<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\DistributionMethod;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\UtilityService;
use App\Services\TenantInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for enhanced TenantInitializationService functionality.
 * 
 * Tests UC-6.2.2: Tenant-specific service configuration initialization
 * - Property-level service assignments based on tenant type
 * - Default meter configurations for each utility service
 * - Rate schedule initialization with regional defaults
 * - Provider assignments based on tenant location
 */
class TenantInitializationServiceEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private TenantInitializationService $service;
    private Organization $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(TenantInitializationService::class);
        
        // Create a test tenant with Lithuanian locale
        $this->tenant = Organization::factory()->create([
            'name' => 'Test Property Management',
            'locale' => 'lt',
            'timezone' => 'Europe/Vilnius',
            'currency' => 'EUR',
        ]);
    }

    public function test_initializes_property_service_assignments_for_residential_properties(): void
    {
        // Create residential properties
        $apartment = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PropertyType::APARTMENT,
            'area_sqm' => 75.5,
        ]);
        
        $house = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PropertyType::HOUSE,
            'area_sqm' => 150.0,
        ]);

        // Initialize universal services
        $result = $this->service->initializeUniversalServices($this->tenant);
        $utilityServices = $result->utilityServices;

        // Initialize property service assignments
        $configurations = $this->service->initializePropertyServiceAssignments($this->tenant, $utilityServices);

        // Verify configurations were created for both properties
        $this->assertEquals(2, $configurations->getPropertyCount());
        $this->assertNotNull($configurations->getPropertyConfigurations($apartment->id));
        $this->assertNotNull($configurations->getPropertyConfigurations($house->id));

        // Verify all service types are configured for each property
        foreach ([$apartment->id, $house->id] as $propertyId) {
            $propertyConfigs = $configurations->getPropertyConfigurations($propertyId);
            $this->assertNotNull($propertyConfigs);
            $this->assertArrayHasKey('electricity', $propertyConfigs->toArray());
            $this->assertArrayHasKey('water', $propertyConfigs->toArray());
            $this->assertArrayHasKey('heating', $propertyConfigs->toArray());
            $this->assertArrayHasKey('gas', $propertyConfigs->toArray());
        }

        // Verify apartment has shared heating service
        $apartmentHeatingConfig = $configurations->getPropertyServiceConfiguration($apartment->id, 'heating');
        $this->assertNotNull($apartmentHeatingConfig);
        $this->assertTrue($apartmentHeatingConfig->is_shared_service);
        $this->assertEquals(DistributionMethod::BY_AREA, $apartmentHeatingConfig->distribution_method);

        // Verify house has individual heating service
        $houseHeatingConfig = $configurations->getPropertyServiceConfiguration($house->id, 'heating');
        $this->assertNotNull($houseHeatingConfig);
        $this->assertFalse($houseHeatingConfig->is_shared_service);
    }

    public function test_applies_commercial_property_adjustments(): void
    {
        // Create commercial property
        $office = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PropertyType::OFFICE,
            'area_sqm' => 300.0,
        ]);

        // Initialize services and assignments
        $result = $this->service->initializeUniversalServices($this->tenant);
        $configurations = $this->service->initializePropertyServiceAssignments($this->tenant, $result->utilityServices);

        // Verify commercial electricity rates
        $electricityConfig = $configurations->getPropertyServiceConfiguration($office->id, 'electricity');
        $this->assertNotNull($electricityConfig);
        $rateSchedule = $electricityConfig->rate_schedule;
        
        $this->assertEquals(0.18, $rateSchedule['zone_rates']['day']); // Higher commercial day rate
        $this->assertEquals(0.12, $rateSchedule['zone_rates']['night']); // Higher commercial night rate
        $this->assertEquals(15.00, $rateSchedule['demand_charge']); // Commercial demand charge

        // Verify commercial water rates
        $waterConfig = $configurations->getPropertyServiceConfiguration($office->id, 'water');
        $this->assertNotNull($waterConfig);
        $waterRates = $waterConfig->rate_schedule;
        
        $this->assertEquals(3.20, $waterRates['unit_rate']); // Higher commercial water rate
        $this->assertEquals(2.80, $waterRates['sewer_rate']); // Commercial sewer charges

        // Verify enhanced monitoring for large property
        $configOverrides = $electricityConfig->configuration_overrides;
        $this->assertTrue($configOverrides['large_property']);
        $this->assertTrue($configOverrides['enhanced_monitoring']);
    }

    public function test_applies_lithuanian_regional_defaults(): void
    {
        // Create property
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PropertyType::APARTMENT,
        ]);

        // Initialize services and assignments
        $result = $this->service->initializeUniversalServices($this->tenant);
        $configurations = $this->service->initializePropertyServiceAssignments($this->tenant, $result->utilityServices);

        // Verify Lithuanian electricity rates
        $electricityConfig = $configurations->getPropertyServiceConfiguration($property->id, 'electricity');
        $this->assertNotNull($electricityConfig);
        $rateSchedule = $electricityConfig->rate_schedule;
        
        $this->assertEquals(0.1547, $rateSchedule['zone_rates']['day']); // Lithuanian day rate
        $this->assertEquals(0.1047, $rateSchedule['zone_rates']['night']); // Lithuanian night rate
        $this->assertEquals(0.0234, $rateSchedule['network_fee']); // Lithuanian network fee

        // Verify regulatory region and VAT
        $configOverrides = $electricityConfig->configuration_overrides;
        $this->assertEquals('LT', $configOverrides['regulatory_region']);
        $this->assertEquals(0.21, $configOverrides['vat_rate']); // Lithuanian VAT
        $this->assertEquals('EUR', $configOverrides['currency']);

        // Verify Lithuanian water rates
        $waterConfig = $configurations->getPropertyServiceConfiguration($property->id, 'water');
        $this->assertNotNull($waterConfig);
        $waterRates = $waterConfig->rate_schedule;
        
        $this->assertEquals(1.89, $waterRates['unit_rate']); // Lithuanian water rate
        $this->assertEquals(1.45, $waterRates['wastewater_rate']); // Lithuanian wastewater rate

        // Verify Lithuanian heating configuration
        $heatingConfig = $configurations->getPropertyServiceConfiguration($property->id, 'heating');
        $this->assertNotNull($heatingConfig);
        $heatingRates = $heatingConfig->rate_schedule;
        
        $this->assertEquals(12.50, $heatingRates['base_fee']); // Lithuanian heating base fee
        $this->assertEquals(0.0687, $heatingRates['unit_rate']); // Lithuanian heating rate
        $this->assertEquals(1.3, $heatingRates['seasonal_factors']['winter']); // Higher winter factor
        $this->assertEquals(0.6, $heatingRates['seasonal_factors']['summer']); // Lower summer factor

        // Verify heating season configuration
        $heatingOverrides = $heatingConfig->configuration_overrides;
        $this->assertEquals(['october', 'april'], $heatingOverrides['heating_season']);
    }

    public function test_assigns_providers_based_on_service_type(): void
    {
        // Create providers for different service types
        $electricityProvider = Provider::factory()->create([
            'name' => 'Lietuvos Energija',
            'service_type' => ServiceType::ELECTRICITY,
            'contact_info' => ['phone' => '+370 700 12345'],
        ]);

        $waterProvider = Provider::factory()->create([
            'name' => 'Vilniaus Vandenys',
            'service_type' => ServiceType::WATER,
            'contact_info' => ['phone' => '+370 5 252 2222'],
        ]);

        // Create tariffs for providers
        $electricityTariff = Tariff::factory()->create([
            'provider_id' => $electricityProvider->id,
            'type' => \App\Enums\TariffType::TIME_OF_USE,
            'rates' => [
                'day_rate' => 0.1547,
                'night_rate' => 0.1047,
                'network_fee' => 0.0234,
            ],
            'active_from' => now()->subMonth(),
            'active_until' => null,
        ]);

        $waterTariff = Tariff::factory()->create([
            'provider_id' => $waterProvider->id,
            'type' => \App\Enums\TariffType::FLAT,
            'rates' => [
                'unit_rate' => 1.89,
                'wastewater_rate' => 1.45,
            ],
            'active_from' => now()->subMonth(),
            'active_until' => null,
        ]);

        // Create property
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PropertyType::APARTMENT,
        ]);

        // Initialize services and assignments
        $result = $this->service->initializeUniversalServices($this->tenant);
        $configurations = $this->service->initializePropertyServiceAssignments($this->tenant, $result->utilityServices);

        // Verify electricity provider assignment
        $electricityConfig = $configurations->getPropertyServiceConfiguration($property->id, 'electricity');
        $this->assertNotNull($electricityConfig);
        $this->assertEquals($electricityProvider->id, $electricityConfig->provider_id);
        $this->assertEquals('Lietuvos Energija', $electricityConfig->provider_name);
        $this->assertEquals($electricityTariff->id, $electricityConfig->tariff_id);

        // Verify water provider assignment
        $waterConfig = $configurations->getPropertyServiceConfiguration($property->id, 'water');
        $this->assertNotNull($waterConfig);
        $this->assertEquals($waterProvider->id, $waterConfig->provider_id);
        $this->assertEquals('Vilniaus Vandenys', $waterConfig->provider_name);
        $this->assertEquals($waterTariff->id, $waterConfig->tariff_id);
    }

    public function test_initializes_default_meter_configurations(): void
    {
        // Create property
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PropertyType::HOUSE,
        ]);

        // Initialize services and assignments
        $result = $this->service->initializeUniversalServices($this->tenant);
        $serviceConfigurations = $this->service->initializePropertyServiceAssignments($this->tenant, $result->utilityServices);

        // Initialize meter configurations
        $meterConfigurations = $this->service->initializeDefaultMeterConfigurations($this->tenant, $serviceConfigurations);

        // Verify meter configurations were created
        $this->assertArrayHasKey($property->id, $meterConfigurations);
        $propertyMeterConfigs = $meterConfigurations[$property->id];

        // Verify electricity meter configuration
        $electricityMeterConfig = $propertyMeterConfigs['electricity'];
        $this->assertEquals(MeterType::ELECTRICITY, $electricityMeterConfig['meter_type']);
        $this->assertTrue($electricityMeterConfig['supports_zones']);
        $this->assertEquals(['day', 'night'], $electricityMeterConfig['reading_structure']['zones']);
        $this->assertEquals(['day_reading', 'night_reading'], $electricityMeterConfig['reading_structure']['required_fields']);
        $this->assertTrue($electricityMeterConfig['requires_photo_verification']);

        // Verify water meter configuration
        $waterMeterConfig = $propertyMeterConfigs['water'];
        $this->assertEquals(MeterType::WATER, $waterMeterConfig['meter_type']);
        $this->assertFalse($waterMeterConfig['supports_zones']);
        $this->assertEquals(['total_reading'], $waterMeterConfig['reading_structure']['required_fields']);
        $this->assertFalse($waterMeterConfig['requires_photo_verification']);

        // Verify heating meter configuration
        $heatingMeterConfig = $propertyMeterConfigs['heating'];
        $this->assertEquals(MeterType::HEATING, $heatingMeterConfig['meter_type']);
        $this->assertFalse($heatingMeterConfig['supports_zones']);
        $this->assertEquals(['consumption_reading'], $heatingMeterConfig['reading_structure']['required_fields']);
        $this->assertContains('temperature_reading', $heatingMeterConfig['reading_structure']['optional_fields']);

        // Verify gas meter configuration
        $gasMeterConfig = $propertyMeterConfigs['gas'];
        $this->assertEquals(MeterType::GAS, $gasMeterConfig['meter_type']);
        $this->assertTrue($gasMeterConfig['requires_photo_verification']);
    }

    public function test_ensures_heating_compatibility(): void
    {
        // Initialize universal services
        $this->service->initializeUniversalServices($this->tenant);

        // Check heating compatibility
        $isCompatible = $this->service->ensureHeatingCompatibility($this->tenant);

        $this->assertTrue($isCompatible);

        // Verify heating service exists with proper configuration
        $heatingService = UtilityService::where('tenant_id', $this->tenant->id)
            ->where('service_type_bridge', ServiceType::HEATING)
            ->first();

        $this->assertNotNull($heatingService);
        $this->assertEquals(ServiceType::HEATING, $heatingService->service_type_bridge);
        $this->assertEquals(PricingModel::HYBRID, $heatingService->default_pricing_model);
        $this->assertTrue($heatingService->business_logic_config['supports_shared_distribution']);
    }

    public function test_handles_tenant_without_properties(): void
    {
        // Create tenant without properties
        $emptyTenant = Organization::factory()->create([
            'name' => 'Empty Tenant',
            'locale' => 'en',
        ]);

        // Initialize services
        $result = $this->service->initializeUniversalServices($emptyTenant);
        $utilityServices = $result->utilityServices;

        // Try to initialize property assignments
        $configurations = $this->service->initializePropertyServiceAssignments($emptyTenant, $utilityServices);

        // Should return empty result without errors
        $this->assertFalse($configurations->hasConfigurations());
    }

    public function test_handles_missing_providers_gracefully(): void
    {
        // Create property without any providers in the system
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PropertyType::APARTMENT,
        ]);

        // Initialize services and assignments (should not fail)
        $result = $this->service->initializeUniversalServices($this->tenant);
        $configurations = $this->service->initializePropertyServiceAssignments($this->tenant, $result->utilityServices);

        // Verify configurations were still created
        $this->assertTrue($configurations->hasConfigurations());
        
        // Verify no provider assignments were made
        $electricityConfig = $configurations->getPropertyServiceConfiguration($property->id, 'electricity');
        $this->assertNotNull($electricityConfig);
        $this->assertNull($electricityConfig->provider_id);
    }

    public function test_applies_correct_validation_rules_by_service_type(): void
    {
        // Create property
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PropertyType::HOUSE,
        ]);

        // Initialize services and meter configurations
        $result = $this->service->initializeUniversalServices($this->tenant);
        $serviceConfigurations = $this->service->initializePropertyServiceAssignments($this->tenant, $result->utilityServices);
        $meterConfigurations = $this->service->initializeDefaultMeterConfigurations($this->tenant, $serviceConfigurations);

        $propertyMeterConfigs = $meterConfigurations[$property->id];

        // Verify electricity validation rules
        $electricityRules = $propertyMeterConfigs['electricity']['validation_rules'];
        $this->assertEquals(10000, $electricityRules['max_consumption']);
        $this->assertEquals(0.5, $electricityRules['variance_threshold']);
        $this->assertTrue($electricityRules['require_monotonic']);

        // Verify water validation rules
        $waterRules = $propertyMeterConfigs['water']['validation_rules'];
        $this->assertEquals(1000, $waterRules['max_consumption']);
        $this->assertEquals(0.3, $waterRules['variance_threshold']);
        $this->assertTrue($waterRules['require_monotonic']);

        // Verify heating validation rules
        $heatingRules = $propertyMeterConfigs['heating']['validation_rules'];
        $this->assertEquals(5000, $heatingRules['max_consumption']);
        $this->assertEquals(0.4, $heatingRules['variance_threshold']);
        $this->assertTrue($heatingRules['seasonal_adjustment']);

        // Verify gas validation rules
        $gasRules = $propertyMeterConfigs['gas']['validation_rules'];
        $this->assertEquals(2000, $gasRules['max_consumption']);
        $this->assertEquals(0.4, $gasRules['variance_threshold']);
        $this->assertTrue($gasRules['require_monotonic']);
    }
}