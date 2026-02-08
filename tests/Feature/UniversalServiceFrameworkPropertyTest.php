<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\UtilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: universal-utility-management, Property 1: Universal Service Creation and Configuration
 * Validates: Requirements 1.1, 1.2, 1.5, 2.1, 2.2, 2.3, 2.4
 */
test('Universal service creation and configuration maintains data integrity and validation rules', function () {
    // Generate random tenant for testing
    $tenant = Tenant::factory()->create();
    
    // Generate random service attributes with unique identifier
    $uniqueId = uniqid();
    $serviceName = fake()->words(2, true) . ' Service ' . $uniqueId;
    $unitOfMeasurement = fake()->randomElement(['kWh', 'L', 'kW', 'm³', 'units', 'Gcal']);
    $pricingModel = fake()->randomElement(PricingModel::cases());
    $serviceType = fake()->randomElement(ServiceType::cases());
    $isGlobalTemplate = fake()->boolean();
    
    // Generate configuration schema based on pricing model complexity
    $configurationSchema = [
        'required' => ['rate_schedule'],
        'optional' => $pricingModel->supportsCustomFormulas() ? ['custom_formula', 'area_type'] : ['area_type'],
        'validation' => [
            'rate_schedule' => 'array',
            'custom_formula' => 'string|nullable',
        ],
    ];
    
    // Generate validation rules based on service type
    $validationRules = [
        'consumption_limits' => [
            'min' => fake()->numberBetween(0, 10),
            'max' => fake()->numberBetween(1000, 10000),
        ],
        'reading_frequency' => fake()->randomElement(['daily', 'weekly', 'monthly']),
        'data_quality_threshold' => fake()->randomFloat(2, 0.8, 1.0),
    ];
    
    // Generate business logic configuration
    $businessLogicConfig = [
        'auto_validation' => fake()->boolean(),
        'seasonal_adjustments' => fake()->boolean(),
        'distribution_rules' => [
            'default_method' => fake()->randomElement(['equal', 'area', 'by_consumption']),
            'allow_overrides' => fake()->boolean(),
        ],
    ];
    
    // Property: UtilityService creation should succeed with valid data
    $utilityService = UtilityService::create([
        'tenant_id' => $isGlobalTemplate ? null : $tenant->id,
        'name' => $serviceName,
        'slug' => \Illuminate\Support\Str::slug($serviceName),
        'unit_of_measurement' => $unitOfMeasurement,
        'default_pricing_model' => $pricingModel,
        'calculation_formula' => getCalculationFormula($pricingModel),
        'is_global_template' => $isGlobalTemplate,
        'created_by_tenant_id' => $isGlobalTemplate ? $tenant->id : null,
        'configuration_schema' => $configurationSchema,
        'validation_rules' => $validationRules,
        'business_logic_config' => $businessLogicConfig,
        'service_type_bridge' => $serviceType,
        'description' => fake()->sentence(),
        'is_active' => true,
    ]);
    
    // Verify service was created successfully
    expect($utilityService)->toBeInstanceOf(UtilityService::class);
    expect($utilityService->id)->toBeInt();
    expect($utilityService->name)->toBe($serviceName);
    expect($utilityService->unit_of_measurement)->toBe($unitOfMeasurement);
    expect($utilityService->default_pricing_model)->toBe($pricingModel);
    expect($utilityService->service_type_bridge)->toBe($serviceType);
    expect($utilityService->is_global_template)->toBe($isGlobalTemplate);
    expect($utilityService->is_active)->toBeTrue();
    
    // Property: Service configuration schema should be properly structured
    expect($utilityService->configuration_schema)->toBeArray();
    expect($utilityService->configuration_schema)->toHaveKey('required');
    expect($utilityService->configuration_schema)->toHaveKey('optional');
    expect($utilityService->configuration_schema)->toHaveKey('validation');
    expect($utilityService->configuration_schema['required'])->toContain('rate_schedule');
    
    // Property: Validation rules should be properly structured
    expect($utilityService->validation_rules)->toBeArray();
    expect($utilityService->validation_rules)->toHaveKey('consumption_limits');
    expect($utilityService->validation_rules)->toHaveKey('reading_frequency');
    expect($utilityService->validation_rules['consumption_limits'])->toHaveKey('min');
    expect($utilityService->validation_rules['consumption_limits'])->toHaveKey('max');
    
    // Property: Business logic configuration should be valid
    expect($utilityService->business_logic_config)->toBeArray();
    expect($utilityService->business_logic_config)->toHaveKey('distribution_rules');
    expect($utilityService->business_logic_config['distribution_rules'])->toHaveKey('default_method');
    
    // Property: Pricing model capabilities should be correctly identified
    expect($utilityService->supportsCustomFormulas())->toBe($pricingModel->supportsCustomFormulas());
    expect($utilityService->requiresConsumptionData())->toBe($pricingModel->requiresConsumptionData());
    
    // Property: Service type bridge should maintain backward compatibility
    expect($utilityService->getBridgeServiceType())->toBe($serviceType);
    
    // Test service configuration creation
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    $distributionMethod = fake()->randomElement(DistributionMethod::cases());
    
    // Generate rate schedule based on pricing model
    $rateSchedule = getRateSchedule($pricingModel);
    
    // Property: ServiceConfiguration creation should succeed with valid service
    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => $pricingModel,
        'rate_schedule' => $rateSchedule,
        'distribution_method' => $distributionMethod,
        'is_shared_service' => fake()->boolean(),
        'effective_from' => now()->subDays(fake()->numberBetween(1, 30)),
        'effective_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(30, 365)) : null,
        'configuration_overrides' => [
            'custom_setting' => fake()->word(),
            'override_value' => fake()->numberBetween(1, 100),
        ],
        'area_type' => $distributionMethod->requiresAreaData() ? fake()->randomElement(['total_area', 'heated_area', 'commercial_area']) : null,
        'custom_formula' => $pricingModel->supportsCustomFormulas() ? 'consumption * rate + base_fee' : null,
        'is_active' => true,
    ]);
    
    // Verify service configuration was created successfully
    expect($serviceConfiguration)->toBeInstanceOf(ServiceConfiguration::class);
    expect($serviceConfiguration->id)->toBeInt();
    expect($serviceConfiguration->tenant_id)->toBe($tenant->id);
    expect($serviceConfiguration->property_id)->toBe($property->id);
    expect($serviceConfiguration->utility_service_id)->toBe($utilityService->id);
    expect($serviceConfiguration->pricing_model)->toBe($pricingModel);
    expect($serviceConfiguration->distribution_method)->toBe($distributionMethod);
    expect($serviceConfiguration->is_active)->toBeTrue();
    
    // Property: Rate schedule should be properly structured for the pricing model
    expect($serviceConfiguration->rate_schedule)->toBeArray();
    
    switch ($pricingModel) {
        case PricingModel::FIXED_MONTHLY:
            expect($serviceConfiguration->rate_schedule)->toHaveKey('monthly_rate');
            expect($serviceConfiguration->rate_schedule['monthly_rate'])->toBeFloat();
            break;
            
        case PricingModel::CONSUMPTION_BASED:
            expect($serviceConfiguration->rate_schedule)->toHaveKey('rate_per_unit');
            expect($serviceConfiguration->rate_schedule['rate_per_unit'])->toBeFloat();
            break;
            
        case PricingModel::TIERED_RATES:
            expect($serviceConfiguration->rate_schedule)->toHaveKey('tiers');
            expect($serviceConfiguration->rate_schedule['tiers'])->toBeArray();
            expect($serviceConfiguration->rate_schedule['tiers'])->not->toBeEmpty();
            break;
            
        case PricingModel::HYBRID:
            expect($serviceConfiguration->rate_schedule)->toHaveKey('base_rate');
            expect($serviceConfiguration->rate_schedule)->toHaveKey('rate_per_unit');
            break;
            
        case PricingModel::TIME_OF_USE:
            expect($serviceConfiguration->rate_schedule)->toHaveKey('time_slots');
            expect($serviceConfiguration->rate_schedule['time_slots'])->toBeArray();
            break;
    }
    
    // Property: Configuration requirements should be correctly identified
    expect($serviceConfiguration->requiresAreaData())->toBe($distributionMethod->requiresAreaData());
    expect($serviceConfiguration->requiresConsumptionData())->toBe(
        $pricingModel->requiresConsumptionData() || $distributionMethod->requiresConsumptionData()
    );
    
    // Property: Configuration should be currently effective if no end date
    if (is_null($serviceConfiguration->effective_until)) {
        expect($serviceConfiguration->isEffectiveOn())->toBeTrue();
    }
    
    // Property: Merged configuration should include overrides
    $mergedConfig = $serviceConfiguration->getMergedConfiguration();
    expect($mergedConfig)->toBeArray();
    expect($mergedConfig)->toHaveKey('custom_setting');
    expect($mergedConfig['custom_setting'])->toBe($serviceConfiguration->configuration_overrides['custom_setting']);
    
    // Property: Configuration validation should work correctly
    $validationErrors = $serviceConfiguration->validateConfiguration();
    expect($validationErrors)->toBeArray();
    
    // Property: Configuration snapshot should contain all required data
    $snapshot = $serviceConfiguration->createSnapshot();
    expect($snapshot)->toBeArray();
    expect($snapshot)->toHaveKey('id');
    expect($snapshot)->toHaveKey('utility_service');
    expect($snapshot)->toHaveKey('pricing_model');
    expect($snapshot)->toHaveKey('rate_schedule');
    expect($snapshot)->toHaveKey('distribution_method');
    expect($snapshot)->toHaveKey('snapshot_date');
    expect($snapshot['id'])->toBe($serviceConfiguration->id);
    expect($snapshot['pricing_model'])->toBe($pricingModel->value);
    
    // Property: Relationships should be properly established
    expect($serviceConfiguration->utilityService)->toBeInstanceOf(UtilityService::class);
    expect($serviceConfiguration->utilityService->id)->toBe($utilityService->id);
    expect($serviceConfiguration->property)->toBeInstanceOf(Property::class);
    expect($serviceConfiguration->property->id)->toBe($property->id);
    
    // Property: Service should have configurations relationship
    expect($utilityService->serviceConfigurations)->toHaveCount(1);
    expect($utilityService->serviceConfigurations->first()->id)->toBe($serviceConfiguration->id);
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 1: Universal Service Creation and Configuration
 * Validates: Requirements 1.1, 1.2, 1.5
 */
test('Global template services can be customized by tenants', function () {
    // Create two different tenants
    $superAdminTenant = Tenant::factory()->create();
    $regularTenant = Tenant::factory()->create();
    
    // Generate random service attributes for global template
    $uniqueId = uniqid();
    $templateName = fake()->words(2, true) . ' Global Service ' . $uniqueId;
    $pricingModel = fake()->randomElement(PricingModel::cases());
    
    // Property: SuperAdmin can create global template services
    $globalTemplate = UtilityService::create([
        'tenant_id' => null, // Global template has no tenant
        'name' => $templateName,
        'slug' => \Illuminate\Support\Str::slug($templateName),
        'unit_of_measurement' => fake()->randomElement(['kWh', 'L', 'm³']),
        'default_pricing_model' => $pricingModel,
        'calculation_formula' => getCalculationFormula($pricingModel),
        'is_global_template' => true,
        'created_by_tenant_id' => $superAdminTenant->id,
        'configuration_schema' => [
            'required' => ['rate_schedule'],
            'optional' => ['custom_formula'],
            'validation' => ['rate_schedule' => 'array'],
        ],
        'validation_rules' => [
            'consumption_limits' => ['min' => 0, 'max' => 10000],
            'reading_frequency' => 'monthly',
        ],
        'business_logic_config' => [
            'auto_validation' => true,
            'seasonal_adjustments' => false,
        ],
        'service_type_bridge' => fake()->randomElement(ServiceType::cases()),
        'description' => fake()->sentence(),
        'is_active' => true,
    ]);
    
    // Verify global template was created correctly
    expect($globalTemplate->is_global_template)->toBeTrue();
    expect($globalTemplate->tenant_id)->toBeNull();
    expect($globalTemplate->created_by_tenant_id)->toBe($superAdminTenant->id);
    
    // Property: Tenant can create customized copy of global template
    $customizedName = $templateName . ' - Customized ' . uniqid();
    $customizations = [
        'name' => $customizedName,
        'slug' => \Illuminate\Support\Str::slug($customizedName),
        'description' => 'Customized version for our organization',
        'validation_rules' => [
            'consumption_limits' => ['min' => 5, 'max' => 5000], // Stricter limits
            'reading_frequency' => 'weekly', // More frequent readings
        ],
    ];
    
    $tenantCopy = $globalTemplate->createTenantCopy($regularTenant->id, $customizations);
    
    // Verify tenant copy was created correctly
    expect($tenantCopy)->toBeInstanceOf(UtilityService::class);
    expect($tenantCopy->id)->not->toBe($globalTemplate->id);
    expect($tenantCopy->tenant_id)->toBe($regularTenant->id);
    expect($tenantCopy->is_global_template)->toBeFalse();
    expect($tenantCopy->created_by_tenant_id)->toBe($superAdminTenant->id);
    expect($tenantCopy->name)->toBe($customizations['name']);
    expect($tenantCopy->description)->toBe($customizations['description']);
    
    // Property: Customizations should be applied correctly
    expect($tenantCopy->validation_rules['consumption_limits']['min'])->toBe(5);
    expect($tenantCopy->validation_rules['consumption_limits']['max'])->toBe(5000);
    expect($tenantCopy->validation_rules['reading_frequency'])->toBe('weekly');
    
    // Property: Non-customized fields should inherit from template
    expect($tenantCopy->unit_of_measurement)->toBe($globalTemplate->unit_of_measurement);
    expect($tenantCopy->default_pricing_model)->toBe($globalTemplate->default_pricing_model);
    expect($tenantCopy->service_type_bridge)->toBe($globalTemplate->service_type_bridge);
    expect($tenantCopy->configuration_schema)->toBe($globalTemplate->configuration_schema);
    
    // Property: Both services should be independently configurable
    $property1 = Property::factory()->create(['tenant_id' => $regularTenant->id]);
    
    $config1 = ServiceConfiguration::create([
        'tenant_id' => $regularTenant->id,
        'property_id' => $property1->id,
        'utility_service_id' => $tenantCopy->id,
        'pricing_model' => $pricingModel,
        'rate_schedule' => getRateSchedule($pricingModel),
        'distribution_method' => fake()->randomElement(DistributionMethod::cases()),
        'is_shared_service' => false,
        'effective_from' => now()->subDays(1),
        'is_active' => true,
    ]);
    
    expect($config1->utility_service_id)->toBe($tenantCopy->id);
    expect($config1->utilityService->tenant_id)->toBe($regularTenant->id);
    
    // Property: Global template should remain unchanged
    $globalTemplate->refresh();
    expect($globalTemplate->name)->toBe($templateName);
    expect($globalTemplate->validation_rules['reading_frequency'])->toBe('monthly');
    expect($globalTemplate->is_global_template)->toBeTrue();
    
})->repeat(50);

/**
 * Feature: universal-utility-management, Property 1: Universal Service Creation and Configuration
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4
 */
test('Service configurations support all pricing models with proper validation', function () {
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Test each pricing model
    foreach (PricingModel::cases() as $pricingModel) {
        $utilityService = UtilityService::factory()->create([
            'tenant_id' => $tenant->id,
            'default_pricing_model' => $pricingModel,
            'is_active' => true,
        ]);
        
        $rateSchedule = getRateSchedule($pricingModel);
        $distributionMethod = fake()->randomElement(DistributionMethod::cases());
        
        // Property: Each pricing model should support proper configuration
        $serviceConfiguration = ServiceConfiguration::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'utility_service_id' => $utilityService->id,
            'pricing_model' => $pricingModel,
            'rate_schedule' => $rateSchedule,
            'distribution_method' => $distributionMethod,
            'is_shared_service' => fake()->boolean(),
            'effective_from' => now()->subDays(fake()->numberBetween(1, 30)),
            'effective_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(30, 365)) : null,
            'area_type' => $distributionMethod->requiresAreaData() ? fake()->randomElement(['total_area', 'heated_area', 'commercial_area']) : null,
            'custom_formula' => $pricingModel->supportsCustomFormulas() ? 'consumption * rate + base_fee' : null,
            'is_active' => true,
        ]);
        
        expect($serviceConfiguration->pricing_model)->toBe($pricingModel);
        expect($serviceConfiguration->rate_schedule)->toBeArray();
        
        // Property: Rate schedule structure should match pricing model requirements
        switch ($pricingModel) {
            case PricingModel::FIXED_MONTHLY:
                expect($serviceConfiguration->rate_schedule)->toHaveKey('monthly_rate');
                expect($serviceConfiguration->getEffectiveRate())->toBeFloat();
                break;
                
            case PricingModel::CONSUMPTION_BASED:
                expect($serviceConfiguration->rate_schedule)->toHaveKey('rate_per_unit');
                expect($serviceConfiguration->getEffectiveRate())->toBeFloat();
                break;
                
            case PricingModel::TIERED_RATES:
                expect($serviceConfiguration->rate_schedule)->toHaveKey('tiers');
                expect($serviceConfiguration->rate_schedule['tiers'])->toBeArray();
                
                // Test tiered rate calculation
                $testConsumption = fake()->randomFloat(2, 100, 1000);
                $tieredCost = $serviceConfiguration->calculateTieredRate($testConsumption);
                expect($tieredCost)->toBeFloat();
                expect($tieredCost)->toBeGreaterThanOrEqual(0);
                break;
                
            case PricingModel::HYBRID:
                expect($serviceConfiguration->rate_schedule)->toHaveKey('base_rate');
                expect($serviceConfiguration->rate_schedule)->toHaveKey('rate_per_unit');
                break;
                
            case PricingModel::TIME_OF_USE:
                expect($serviceConfiguration->rate_schedule)->toHaveKey('time_slots');
                
                // Test time-of-use rate retrieval
                $testDateTime = fake()->dateTimeBetween('-1 month', '+1 month');
                $testZone = fake()->randomElement(['day', 'night']);
                $touRate = $serviceConfiguration->getEffectiveRate(Carbon::parse($testDateTime), $testZone);
                expect($touRate)->toBeFloat();
                break;
                
            case PricingModel::CUSTOM_FORMULA:
                expect($serviceConfiguration->custom_formula)->not->toBeNull();
                expect($serviceConfiguration->custom_formula)->toBeString();
                break;
        }
        
        // Property: Configuration requirements should be correctly identified
        expect($serviceConfiguration->requiresConsumptionData())->toBe(
            $pricingModel->requiresConsumptionData() || $distributionMethod->requiresConsumptionData()
        );
        
        // Property: Area type should be set only when required
        if ($distributionMethod->requiresAreaData()) {
            expect($serviceConfiguration->area_type)->not->toBeNull();
            expect($serviceConfiguration->area_type)->toBeIn(['total_area', 'heated_area', 'commercial_area']);
        } else {
            expect($serviceConfiguration->area_type)->toBeNull();
        }
        
        // Property: Custom formula should be set only for custom formula pricing
        if ($pricingModel->supportsCustomFormulas()) {
            expect($serviceConfiguration->custom_formula)->not->toBeNull();
        } else {
            expect($serviceConfiguration->custom_formula)->toBeNull();
        }
    }
    
})->repeat(20);

/**
 * Helper function to generate calculation formula based on pricing model
 */
function getCalculationFormula(PricingModel $pricingModel): array
{
    return match ($pricingModel) {
        PricingModel::FIXED_MONTHLY => [
            'type' => 'fixed',
            'monthly_rate' => (float) fake()->randomFloat(2, 10, 500),
        ],
        PricingModel::CONSUMPTION_BASED => [
            'type' => 'consumption',
            'rate_per_unit' => (float) fake()->randomFloat(4, 0.01, 5.0),
        ],
        PricingModel::TIERED_RATES => [
            'type' => 'tiered',
            'tiers' => [
                ['limit' => 100, 'rate' => (float) fake()->randomFloat(4, 0.01, 1.0)],
                ['limit' => 500, 'rate' => (float) fake()->randomFloat(4, 1.0, 2.0)],
                ['limit' => PHP_FLOAT_MAX, 'rate' => (float) fake()->randomFloat(4, 2.0, 5.0)],
            ],
        ],
        PricingModel::HYBRID => [
            'type' => 'hybrid',
            'base_fee' => (float) fake()->randomFloat(2, 5, 50),
            'rate_per_unit' => (float) fake()->randomFloat(4, 0.01, 2.0),
        ],
        PricingModel::CUSTOM_FORMULA => [
            'type' => 'custom',
            'formula' => 'consumption * rate + base_fee',
            'variables' => [
                'rate' => (float) fake()->randomFloat(4, 0.01, 3.0),
                'base_fee' => (float) fake()->randomFloat(2, 0, 100),
            ],
        ],
        default => [
            'type' => 'flat',
            'rate' => (float) fake()->randomFloat(4, 0.01, 2.0),
        ],
    };
}

/**
 * Helper function to generate rate schedule based on pricing model
 */
function getRateSchedule(PricingModel $pricingModel): array
{
    return match ($pricingModel) {
        PricingModel::FIXED_MONTHLY => [
            'monthly_rate' => (float) fake()->randomFloat(2, 10, 500),
        ],
        PricingModel::CONSUMPTION_BASED => [
            'rate_per_unit' => (float) fake()->randomFloat(4, 0.01, 5.0),
        ],
        PricingModel::TIERED_RATES => [
            'tiers' => [
                ['limit' => 100, 'rate' => (float) fake()->randomFloat(4, 0.01, 1.0)],
                ['limit' => 500, 'rate' => (float) fake()->randomFloat(4, 1.0, 2.0)],
                ['limit' => PHP_FLOAT_MAX, 'rate' => (float) fake()->randomFloat(4, 2.0, 5.0)],
            ],
        ],
        PricingModel::TIME_OF_USE => [
            'time_slots' => [
                [
                    'day_type' => 'weekday',
                    'start_hour' => 6,
                    'end_hour' => 22,
                    'zone' => 'day',
                    'rate' => (float) fake()->randomFloat(4, 0.01, 2.0),
                ],
                [
                    'day_type' => 'weekday',
                    'start_hour' => 22,
                    'end_hour' => 6,
                    'zone' => 'night',
                    'rate' => (float) fake()->randomFloat(4, 0.005, 1.0),
                ],
            ],
            'default_rate' => (float) fake()->randomFloat(4, 0.01, 1.5),
        ],
        PricingModel::HYBRID => [
            'base_rate' => (float) fake()->randomFloat(2, 10, 100),
            'rate_per_unit' => (float) fake()->randomFloat(4, 0.01, 2.0),
        ],
        default => [
            'rate' => (float) fake()->randomFloat(4, 0.01, 2.0),
        ],
    };
}