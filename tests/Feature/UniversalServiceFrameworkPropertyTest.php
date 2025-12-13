<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\UtilityService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: universal-utility-management, Property 1: Universal Service Creation and Configuration
 * Validates: Requirements 1.1, 1.2, 1.5, 2.1, 2.2, 2.3, 2.4
 */
test('Universal service creation and configuration maintains data integrity and supports all pricing models', function () {
    // Generate random tenant
    $tenant = Tenant::factory()->create();
    
    // Generate random utility service configuration
    $serviceName = fake()->words(2, true) . ' Service';
    $unitOfMeasurement = fake()->randomElement(['kWh', 'L', 'kW', 'm³', 'units']);
    $pricingModel = fake()->randomElement(PricingModel::cases());
    $isGlobalTemplate = fake()->boolean();
    
    // Create calculation formula based on pricing model
    $calculationFormula = match ($pricingModel) {
        PricingModel::FIXED_MONTHLY => [
            'type' => 'fixed',
            'monthly_rate' => fake()->randomFloat(2, 10, 500),
        ],
        PricingModel::CONSUMPTION_BASED => [
            'type' => 'consumption',
            'rate_per_unit' => fake()->randomFloat(4, 0.01, 5.0),
        ],
        PricingModel::TIERED_RATES => [
            'type' => 'tiered',
            'tiers' => [
                ['limit' => 100, 'rate' => fake()->randomFloat(4, 0.01, 1.0)],
                ['limit' => 500, 'rate' => fake()->randomFloat(4, 1.0, 2.0)],
                ['limit' => PHP_FLOAT_MAX, 'rate' => fake()->randomFloat(4, 2.0, 5.0)],
            ],
        ],
        PricingModel::HYBRID => [
            'type' => 'hybrid',
            'base_fee' => fake()->randomFloat(2, 5, 50),
            'rate_per_unit' => fake()->randomFloat(4, 0.01, 2.0),
        ],
        PricingModel::CUSTOM_FORMULA => [
            'type' => 'custom',
            'formula' => 'consumption * rate + base_fee',
            'variables' => [
                'rate' => fake()->randomFloat(4, 0.01, 3.0),
                'base_fee' => fake()->randomFloat(2, 0, 100),
            ],
        ],
        default => [
            'type' => 'flat',
            'rate' => fake()->randomFloat(4, 0.01, 2.0),
        ],
    };
    
    // Create configuration schema
    $configurationSchema = [
        'required' => ['rate_schedule'],
        'optional' => ['custom_formula', 'area_type'],
        'validation' => [
            'rate_schedule' => 'array',
            'custom_formula' => 'string|nullable',
        ],
    ];
    
    // Create validation rules
    $validationRules = [
        'consumption_limits' => [
            'min' => fake()->numberBetween(0, 10),
            'max' => fake()->numberBetween(1000, 10000),
        ],
        'reading_frequency' => fake()->randomElement(['daily', 'weekly', 'monthly']),
    ];
    
    // Create business logic configuration
    $businessLogicConfig = [
        'auto_validation' => fake()->boolean(),
        'seasonal_adjustments' => fake()->boolean(),
        'distribution_rules' => [
            'default_method' => fake()->randomElement(DistributionMethod::cases())->value,
            'allow_overrides' => fake()->boolean(),
        ],
    ];
    
    // Property: Creating a utility service should result in a properly configured service
    $utilityService = UtilityService::create([
        'tenant_id' => $isGlobalTemplate ? null : $tenant->id,
        'name' => $serviceName,
        'slug' => \Illuminate\Support\Str::slug($serviceName),
        'unit_of_measurement' => $unitOfMeasurement,
        'default_pricing_model' => $pricingModel,
        'calculation_formula' => $calculationFormula,
        'is_global_template' => $isGlobalTemplate,
        'created_by_tenant_id' => $tenant->id,
        'configuration_schema' => $configurationSchema,
        'validation_rules' => $validationRules,
        'business_logic_config' => $businessLogicConfig,
        'service_type_bridge' => fake()->randomElement(ServiceType::cases()),
        'description' => fake()->sentence(),
        'is_active' => true,
    ]);
    
    // Verify the service was created correctly
    expect($utilityService)->toBeInstanceOf(UtilityService::class);
    expect($utilityService->name)->toBe($serviceName);
    expect($utilityService->unit_of_measurement)->toBe($unitOfMeasurement);
    expect($utilityService->default_pricing_model)->toBe($pricingModel);
    expect($utilityService->calculation_formula)->toBe($calculationFormula);
    expect($utilityService->is_global_template)->toBe($isGlobalTemplate);
    expect($utilityService->configuration_schema)->toBe($configurationSchema);
    expect($utilityService->validation_rules)->toBe($validationRules);
    expect($utilityService->business_logic_config)->toBe($businessLogicConfig);
    expect($utilityService->is_active)->toBeTrue();
    
    // Verify tenant assignment is correct
    if ($isGlobalTemplate) {
        expect($utilityService->tenant_id)->toBeNull();
    } else {
        expect($utilityService->tenant_id)->toBe($tenant->id);
    }
    
    // Verify the service supports the expected capabilities
    if ($pricingModel->supportsCustomFormulas()) {
        expect($utilityService->supportsCustomFormulas())->toBeTrue();
    }
    
    if ($pricingModel->requiresConsumptionData()) {
        expect($utilityService->requiresConsumptionData())->toBeTrue();
    }
    
    // Verify configuration validation works
    $validationErrors = $utilityService->validateConfiguration([
        'rate_schedule' => ['monthly_rate' => 100],
    ]);
    expect($validationErrors)->toBeArray();
    
    // Test missing required field validation
    $validationErrors = $utilityService->validateConfiguration([]);
    expect($validationErrors)->toContain("Required field 'rate_schedule' is missing");
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 1: Universal Service Creation and Configuration
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4
 */
test('Service configuration creation links properly to properties and utility services', function () {
    // Generate random tenant
    $tenant = Tenant::factory()->create();
    
    // Create a property
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    
    // Create a utility service
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'is_global_template' => false,
    ]);
    
    // Generate random service configuration
    $pricingModel = fake()->randomElement(PricingModel::cases());
    $distributionMethod = fake()->randomElement(DistributionMethod::cases());
    $isSharedService = fake()->boolean();
    
    // Create rate schedule based on pricing model
    $rateSchedule = match ($pricingModel) {
        PricingModel::FIXED_MONTHLY => [
            'monthly_rate' => fake()->randomFloat(2, 10, 500),
        ],
        PricingModel::CONSUMPTION_BASED => [
            'rate_per_unit' => fake()->randomFloat(4, 0.01, 5.0),
        ],
        PricingModel::TIERED_RATES => [
            'tiers' => [
                ['limit' => 100, 'rate' => fake()->randomFloat(4, 0.01, 1.0)],
                ['limit' => 500, 'rate' => fake()->randomFloat(4, 1.0, 2.0)],
                ['limit' => PHP_FLOAT_MAX, 'rate' => fake()->randomFloat(4, 2.0, 5.0)],
            ],
        ],
        PricingModel::TIME_OF_USE => [
            'time_slots' => [
                [
                    'day_type' => 'weekday',
                    'start_hour' => 6,
                    'end_hour' => 22,
                    'rate' => fake()->randomFloat(4, 0.01, 2.0),
                ],
                [
                    'day_type' => 'weekday',
                    'start_hour' => 22,
                    'end_hour' => 6,
                    'rate' => fake()->randomFloat(4, 0.005, 1.0),
                ],
            ],
        ],
        default => [
            'rate' => fake()->randomFloat(4, 0.01, 2.0),
        ],
    };
    
    // Create configuration overrides
    $configurationOverrides = [
        'custom_setting' => fake()->word(),
        'override_value' => fake()->numberBetween(1, 100),
    ];
    
    // Property: Creating a service configuration should properly link all relationships
    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => $pricingModel,
        'rate_schedule' => $rateSchedule,
        'distribution_method' => $distributionMethod,
        'is_shared_service' => $isSharedService,
        'effective_from' => now()->subDays(fake()->numberBetween(1, 30)),
        'effective_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(30, 365)) : null,
        'configuration_overrides' => $configurationOverrides,
        'is_active' => true,
    ]);
    
    // Verify the configuration was created correctly
    expect($serviceConfiguration)->toBeInstanceOf(ServiceConfiguration::class);
    expect($serviceConfiguration->tenant_id)->toBe($tenant->id);
    expect($serviceConfiguration->property_id)->toBe($property->id);
    expect($serviceConfiguration->utility_service_id)->toBe($utilityService->id);
    expect($serviceConfiguration->pricing_model)->toBe($pricingModel);
    expect($serviceConfiguration->rate_schedule)->toBe($rateSchedule);
    expect($serviceConfiguration->distribution_method)->toBe($distributionMethod);
    expect($serviceConfiguration->is_shared_service)->toBe($isSharedService);
    expect($serviceConfiguration->configuration_overrides)->toBe($configurationOverrides);
    expect($serviceConfiguration->is_active)->toBeTrue();
    
    // Verify relationships work correctly
    expect($serviceConfiguration->property)->toBeInstanceOf(Property::class);
    expect($serviceConfiguration->property->id)->toBe($property->id);
    expect($serviceConfiguration->utilityService)->toBeInstanceOf(UtilityService::class);
    expect($serviceConfiguration->utilityService->id)->toBe($utilityService->id);
    
    // Verify the configuration is currently effective
    expect($serviceConfiguration->isEffectiveOn())->toBeTrue();
    
    // Verify capability checks work
    if ($distributionMethod->requiresAreaData()) {
        expect($serviceConfiguration->requiresAreaData())->toBeTrue();
    }
    
    if ($pricingModel->requiresConsumptionData() || $distributionMethod->requiresConsumptionData()) {
        expect($serviceConfiguration->requiresConsumptionData())->toBeTrue();
    }
    
    // Verify merged configuration includes overrides
    $mergedConfig = $serviceConfiguration->getMergedConfiguration();
    expect($mergedConfig)->toBeArray();
    expect($mergedConfig['custom_setting'])->toBe($configurationOverrides['custom_setting']);
    expect($mergedConfig['override_value'])->toBe($configurationOverrides['override_value']);
    
    // Verify configuration validation works
    $validationErrors = $serviceConfiguration->validateConfiguration();
    expect($validationErrors)->toBeArray();
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 1: Universal Service Creation and Configuration
 * Validates: Requirements 1.3, 1.4
 */
test('Global template utility services can be copied to tenant-specific services with customizations', function () {
    // Create two different tenants
    $creatorTenant = Tenant::factory()->create();
    $targetTenant = Tenant::factory()->create();
    
    // Create a global template utility service
    $globalService = UtilityService::create([
        'tenant_id' => null, // Global template
        'name' => fake()->words(2, true) . ' Service',
        'slug' => fake()->slug(),
        'unit_of_measurement' => fake()->randomElement(['kWh', 'L', 'm³']),
        'default_pricing_model' => fake()->randomElement(PricingModel::cases()),
        'calculation_formula' => [
            'type' => 'consumption',
            'rate_per_unit' => fake()->randomFloat(4, 0.01, 2.0),
        ],
        'is_global_template' => true,
        'created_by_tenant_id' => $creatorTenant->id,
        'configuration_schema' => [
            'required' => ['rate_schedule'],
            'optional' => ['custom_formula'],
        ],
        'validation_rules' => [
            'consumption_limits' => [
                'min' => 0,
                'max' => 10000,
            ],
        ],
        'business_logic_config' => [
            'auto_validation' => true,
        ],
        'service_type_bridge' => fake()->randomElement(ServiceType::cases()),
        'is_active' => true,
    ]);
    
    // Generate customizations for the tenant copy
    $customizations = [
        'name' => $globalService->name . ' (Customized)',
        'description' => fake()->sentence(),
        'validation_rules' => [
            'consumption_limits' => [
                'min' => fake()->numberBetween(0, 5),
                'max' => fake()->numberBetween(5000, 15000),
            ],
        ],
    ];
    
    // Property: Creating a tenant copy should preserve the original while applying customizations
    $tenantCopy = $globalService->createTenantCopy($targetTenant->id, $customizations);
    
    // Verify the copy was created correctly
    expect($tenantCopy)->toBeInstanceOf(UtilityService::class);
    expect($tenantCopy->id)->not->toBe($globalService->id);
    expect($tenantCopy->tenant_id)->toBe($targetTenant->id);
    expect($tenantCopy->is_global_template)->toBeFalse();
    expect($tenantCopy->created_by_tenant_id)->toBe($globalService->tenant_id);
    
    // Verify customizations were applied
    expect($tenantCopy->name)->toBe($customizations['name']);
    expect($tenantCopy->description)->toBe($customizations['description']);
    expect($tenantCopy->validation_rules)->toBe($customizations['validation_rules']);
    
    // Verify inherited properties remain the same
    expect($tenantCopy->unit_of_measurement)->toBe($globalService->unit_of_measurement);
    expect($tenantCopy->default_pricing_model)->toBe($globalService->default_pricing_model);
    expect($tenantCopy->calculation_formula)->toBe($globalService->calculation_formula);
    expect($tenantCopy->configuration_schema)->toBe($globalService->configuration_schema);
    expect($tenantCopy->service_type_bridge)->toBe($globalService->service_type_bridge);
    
    // Verify the original global template is unchanged
    $globalService->refresh();
    expect($globalService->is_global_template)->toBeTrue();
    expect($globalService->tenant_id)->toBeNull();
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 1: Universal Service Creation and Configuration
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4
 */
test('Service configuration rate calculation works correctly for all pricing models', function () {
    // Generate random tenant and property
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Test each pricing model
    $pricingModel = fake()->randomElement(PricingModel::cases());
    
    // Create utility service with the pricing model
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => $pricingModel,
    ]);
    
    // Create rate schedule based on pricing model
    $rateSchedule = match ($pricingModel) {
        PricingModel::FIXED_MONTHLY => [
            'monthly_rate' => fake()->randomFloat(2, 50, 500),
        ],
        PricingModel::CONSUMPTION_BASED => [
            'rate_per_unit' => fake()->randomFloat(4, 0.01, 5.0),
        ],
        PricingModel::TIERED_RATES => [
            'base_rate' => fake()->randomFloat(4, 0.01, 1.0),
            'tiers' => [
                ['limit' => 100, 'rate' => fake()->randomFloat(4, 0.01, 1.0)],
                ['limit' => 500, 'rate' => fake()->randomFloat(4, 1.0, 2.0)],
                ['limit' => PHP_FLOAT_MAX, 'rate' => fake()->randomFloat(4, 2.0, 5.0)],
            ],
        ],
        PricingModel::TIME_OF_USE => [
            'time_slots' => [
                [
                    'day_type' => 'weekday',
                    'start_hour' => 6,
                    'end_hour' => 22,
                    'zone' => 'day',
                    'rate' => fake()->randomFloat(4, 0.01, 2.0),
                ],
                [
                    'day_type' => 'weekday',
                    'start_hour' => 22,
                    'end_hour' => 6,
                    'zone' => 'night',
                    'rate' => fake()->randomFloat(4, 0.005, 1.0),
                ],
            ],
            'default_rate' => fake()->randomFloat(4, 0.01, 1.5),
        ],
        PricingModel::HYBRID => [
            'base_rate' => fake()->randomFloat(2, 10, 100),
            'rate_per_unit' => fake()->randomFloat(4, 0.01, 2.0),
        ],
        default => [
            'rate' => fake()->randomFloat(4, 0.01, 2.0),
        ],
    };
    
    // Create service configuration
    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => $pricingModel,
        'rate_schedule' => $rateSchedule,
        'distribution_method' => fake()->randomElement(DistributionMethod::cases()),
        'is_shared_service' => fake()->boolean(),
        'effective_from' => now()->subDays(fake()->numberBetween(1, 30)),
        'is_active' => true,
    ]);
    
    // Property: Rate calculation should work correctly for the configured pricing model
    $testDateTime = now();
    $testZone = fake()->randomElement(['day', 'night']);
    
    $effectiveRate = $serviceConfiguration->getEffectiveRate($testDateTime, $testZone);
    
    // Verify rate calculation based on pricing model
    switch ($pricingModel) {
        case PricingModel::FIXED_MONTHLY:
            expect($effectiveRate)->toBe($rateSchedule['monthly_rate']);
            break;
            
        case PricingModel::CONSUMPTION_BASED:
            expect($effectiveRate)->toBe($rateSchedule['rate_per_unit']);
            break;
            
        case PricingModel::TIME_OF_USE:
            // For time-of-use, the rate should match the time slot or default
            expect($effectiveRate)->toBeFloat();
            expect($effectiveRate)->toBeGreaterThan(0);
            break;
            
        case PricingModel::TIERED_RATES:
            expect($effectiveRate)->toBe($rateSchedule['base_rate']);
            break;
            
        case PricingModel::HYBRID:
            expect($effectiveRate)->toBe($rateSchedule['base_rate']);
            break;
            
        default:
            expect($effectiveRate)->toBeFloat();
    }
    
    // Test tiered rate calculation if applicable
    if ($pricingModel === PricingModel::TIERED_RATES) {
        $testConsumption = fake()->randomFloat(2, 50, 1000);
        $tieredCost = $serviceConfiguration->calculateTieredRate($testConsumption);
        
        expect($tieredCost)->toBeFloat();
        expect($tieredCost)->toBeGreaterThanOrEqual(0);
    }
    
    // Verify configuration snapshot creation
    $snapshot = $serviceConfiguration->createSnapshot();
    expect($snapshot)->toBeArray();
    expect($snapshot['id'])->toBe($serviceConfiguration->id);
    expect($snapshot['pricing_model'])->toBe($pricingModel->value);
    expect($snapshot['rate_schedule'])->toBe($rateSchedule);
    expect($snapshot)->toHaveKey('snapshot_date');
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 1: Universal Service Creation and Configuration
 * Validates: Requirements 1.2, 1.5
 */
test('Utility service caching and performance optimizations work correctly', function () {
    // Create multiple utility services
    $tenant = Tenant::factory()->create();
    $globalServices = [];
    $tenantServices = [];
    
    // Create global template services
    for ($i = 0; $i < fake()->numberBetween(3, 8); $i++) {
        $globalServices[] = UtilityService::create([
            'tenant_id' => null,
            'name' => fake()->words(2, true) . ' Global Service',
            'slug' => fake()->unique()->slug(),
            'unit_of_measurement' => fake()->randomElement(['kWh', 'L', 'm³']),
            'default_pricing_model' => fake()->randomElement(PricingModel::cases()),
            'calculation_formula' => ['type' => 'consumption', 'rate' => fake()->randomFloat(4, 0.01, 2.0)],
            'is_global_template' => true,
            'created_by_tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
    }
    
    // Create tenant-specific services
    for ($i = 0; $i < fake()->numberBetween(2, 5); $i++) {
        $tenantServices[] = UtilityService::create([
            'tenant_id' => $tenant->id,
            'name' => fake()->words(2, true) . ' Tenant Service',
            'slug' => fake()->unique()->slug(),
            'unit_of_measurement' => fake()->randomElement(['kWh', 'L', 'm³']),
            'default_pricing_model' => fake()->randomElement(PricingModel::cases()),
            'calculation_formula' => ['type' => 'consumption', 'rate' => fake()->randomFloat(4, 0.01, 2.0)],
            'is_global_template' => false,
            'is_active' => true,
        ]);
    }
    
    // Property: Cached options should return correct services based on parameters
    $allOptions = UtilityService::getCachedOptions(false);
    $globalOptions = UtilityService::getCachedOptions(true);
    
    // Verify all options include both global and tenant services
    expect($allOptions->count())->toBe(count($globalServices) + count($tenantServices));
    
    // Verify global options include only global templates
    expect($globalOptions->count())->toBe(count($globalServices));
    
    // Verify option format includes name and unit
    $firstOption = $allOptions->first();
    expect($firstOption)->toBeString();
    expect($firstOption)->toContain('('); // Should contain unit in parentheses
    expect($firstOption)->toContain(')');
    
    // Test cache clearing
    UtilityService::clearCachedOptions();
    
    // Create a new service to trigger cache refresh
    $newService = UtilityService::create([
        'tenant_id' => $tenant->id,
        'name' => 'New Test Service',
        'slug' => 'new-test-service',
        'unit_of_measurement' => 'units',
        'default_pricing_model' => PricingModel::FIXED_MONTHLY,
        'calculation_formula' => ['type' => 'fixed', 'monthly_rate' => 100],
        'is_global_template' => false,
        'is_active' => true,
    ]);
    
    // Verify the new service appears in cached options
    $updatedOptions = UtilityService::getCachedOptions(false);
    expect($updatedOptions->count())->toBe(count($globalServices) + count($tenantServices) + 1);
    expect($updatedOptions->values()->contains(fn($option) => str_contains($option, 'New Test Service')))->toBeTrue();
    
})->repeat(100);