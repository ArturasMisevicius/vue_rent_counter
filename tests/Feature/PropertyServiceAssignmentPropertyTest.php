<?php

declare(strict_types=1);

use App\Actions\AssignUtilityServiceAction;
use App\DTOs\AssignUtilityServiceDTO;
use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Exceptions\ServiceConfigurationException;
use App\Models\Meter;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\UtilityService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: universal-utility-management, Property 2: Property Service Assignment with Audit Trail
 * Validates: Requirements 3.1, 3.2, 3.3
 */
test('Assigning utility service to property creates proper configuration with audit trail', function () {
    // Authenticate a user for audit trail
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => \App\Enums\UserRole::ADMIN,
    ]);
    $this->actingAs($user);
    
    // Generate random property
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'area_sqm' => fake()->randomFloat(2, 30, 200), // Ensure area data for distribution methods
    ]);
    
    // Generate random utility service
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'is_global_template' => false,
        'default_pricing_model' => fake()->randomElement(PricingModel::cases()),
        'configuration_schema' => [
            'required' => ['rate_schedule'],
            'optional' => ['custom_formula'],
        ],
        'validation_rules' => [
            'consumption_limits' => [
                'min_monthly' => fake()->numberBetween(0, 10),
                'max_monthly' => fake()->numberBetween(1000, 10000),
            ],
        ],
    ]);
    
    // Generate random service assignment data
    $pricingModel = fake()->randomElement(PricingModel::cases());
    $distributionMethod = fake()->randomElement(DistributionMethod::cases());
    
    // Create rate schedule based on pricing model
    $rateSchedule = match ($pricingModel) {
        PricingModel::FIXED_MONTHLY => [
            'monthly_rate' => fake()->randomFloat(2, 10, 500),
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
            ],
            'default_rate' => fake()->randomFloat(4, 0.01, 1.5),
        ],
        default => [
            'rate' => fake()->randomFloat(4, 0.01, 2.0),
        ],
    };
    
    // Create DTO
    $dto = AssignUtilityServiceDTO::fromArray([
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => $pricingModel,
        'rate_schedule' => $rateSchedule,
        'distribution_method' => $distributionMethod,
        'is_shared_service' => fake()->boolean(),
        'effective_from' => now()->subDays(fake()->numberBetween(0, 10)),
        'effective_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(30, 365)) : null,
        'configuration_overrides' => [
            'custom_setting' => fake()->word(),
        ],
        'is_active' => true,
    ]);
    
    // Property: Assigning service should create configuration with proper relationships and audit trail
    $action = new AssignUtilityServiceAction();
    $configuration = $action->execute($dto);
    
    // Verify configuration was created
    expect($configuration)->toBeInstanceOf(ServiceConfiguration::class);
    expect($configuration->property_id)->toBe($property->id);
    expect($configuration->utility_service_id)->toBe($utilityService->id);
    expect($configuration->tenant_id)->toBe($tenant->id);
    expect($configuration->pricing_model)->toBe($pricingModel);
    expect($configuration->rate_schedule)->toBe($rateSchedule);
    expect($configuration->distribution_method)->toBe($distributionMethod);
    expect($configuration->is_active)->toBeTrue();
    
    // Verify relationships work
    expect($configuration->property)->toBeInstanceOf(Property::class);
    expect($configuration->property->id)->toBe($property->id);
    expect($configuration->utilityService)->toBeInstanceOf(UtilityService::class);
    expect($configuration->utilityService->id)->toBe($utilityService->id);
    
    // Verify configuration is effective
    expect($configuration->isEffectiveOn())->toBeTrue();
    
    // Verify audit trail exists (activity log)
    $this->assertDatabaseHas('activity_log', [
        'subject_type' => ServiceConfiguration::class,
        'subject_id' => $configuration->id,
        'description' => 'utility_service_assigned',
        'causer_id' => $user->id,
    ]);
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 2: Property Service Assignment with Audit Trail
 * Validates: Requirements 3.2, 3.3
 */
test('Service assignment validates pricing model requirements correctly', function () {
    // Authenticate a user
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => \App\Enums\UserRole::ADMIN,
    ]);
    $this->actingAs($user);
    
    // Create property and utility service
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'area_sqm' => fake()->randomFloat(2, 30, 200),
    ]);
    
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'configuration_schema' => [
            'required' => ['rate_schedule'],
        ],
    ]);
    
    // Test with consumption-based pricing but missing rate schedule
    $dto = AssignUtilityServiceDTO::fromArray([
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => null, // Missing required rate schedule
        'effective_from' => now(),
        'is_active' => true,
    ]);
    
    // Property: Assignment should fail when required configuration is missing
    $action = new AssignUtilityServiceAction();
    
    try {
        $configuration = $action->execute($dto);
        expect(false)->toBeTrue('Should have thrown ServiceConfigurationException');
    } catch (ServiceConfigurationException $e) {
        expect($e->getMessage())->toContain('rate_schedule');
    }
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 2: Property Service Assignment with Audit Trail
 * Validates: Requirements 3.3
 */
test('Service assignment validates distribution method requirements correctly', function () {
    // Authenticate a user
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => \App\Enums\UserRole::ADMIN,
    ]);
    $this->actingAs($user);
    
    // Create property WITHOUT area data
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'area_sqm' => null, // No area data
    ]);
    
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::FIXED_MONTHLY,
    ]);
    
    // Test with area-based distribution but missing area data
    $dto = AssignUtilityServiceDTO::fromArray([
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::FIXED_MONTHLY,
        'rate_schedule' => ['monthly_rate' => 100],
        'distribution_method' => DistributionMethod::BY_AREA, // Requires area data
        'effective_from' => now(),
        'is_active' => true,
    ]);
    
    // Property: Assignment should fail when area data is missing for area-based distribution
    $action = new AssignUtilityServiceAction();
    
    try {
        $configuration = $action->execute($dto);
        expect(false)->toBeTrue('Should have thrown ServiceConfigurationException');
    } catch (ServiceConfigurationException $e) {
        expect($e->getMessage())->toContain('area');
    }
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 2: Property Service Assignment with Audit Trail
 * Validates: Requirements 3.3
 */
test('Service assignment prevents overlapping configurations for same utility service', function () {
    // Authenticate a user
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => \App\Enums\UserRole::ADMIN,
    ]);
    $this->actingAs($user);
    
    // Create property and utility service
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'area_sqm' => fake()->randomFloat(2, 30, 200),
    ]);
    
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::FIXED_MONTHLY,
    ]);
    
    // Create existing configuration
    $existingConfig = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::FIXED_MONTHLY,
        'rate_schedule' => ['monthly_rate' => 100],
        'effective_from' => now()->subDays(30),
        'effective_until' => now()->addDays(30),
        'is_active' => true,
    ]);
    
    // Try to create overlapping configuration
    $dto = AssignUtilityServiceDTO::fromArray([
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::FIXED_MONTHLY,
        'rate_schedule' => ['monthly_rate' => 150],
        'effective_from' => now(), // Overlaps with existing config
        'effective_until' => now()->addDays(60),
        'is_active' => true,
    ]);
    
    // Property: Assignment should fail when configurations overlap
    $action = new AssignUtilityServiceAction();
    
    try {
        $configuration = $action->execute($dto);
        expect(false)->toBeTrue('Should have thrown ServiceConfigurationException');
    } catch (ServiceConfigurationException $e) {
        expect($e->getMessage())->toContain('overlapping');
    }
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 2: Property Service Assignment with Audit Trail
 * Validates: Requirements 3.3
 */
test('Service assignment validates meter assignments do not conflict', function () {
    // Authenticate a user
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => \App\Enums\UserRole::ADMIN,
    ]);
    $this->actingAs($user);
    
    // Create property and utility services
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'area_sqm' => fake()->randomFloat(2, 30, 200),
    ]);
    
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
    ]);
    
    // Create existing configuration with meter
    $existingConfig = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['rate_per_unit' => 0.15],
        'effective_from' => now()->subDays(30),
        'is_active' => true,
    ]);
    
    // Create meter linked to existing configuration
    $meter = Meter::factory()->create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'service_configuration_id' => $existingConfig->id,
    ]);
    
    // Try to create new configuration for same utility service
    $dto = AssignUtilityServiceDTO::fromArray([
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['rate_per_unit' => 0.20],
        'effective_from' => now()->addDays(31), // Non-overlapping dates
        'is_active' => true,
    ]);
    
    // Property: Assignment should fail when meters are already assigned to active configuration
    $action = new AssignUtilityServiceAction();
    
    try {
        $configuration = $action->execute($dto);
        expect(false)->toBeTrue('Should have thrown ServiceConfigurationException');
    } catch (ServiceConfigurationException $e) {
        expect($e->getMessage())->toContain('meter');
    }
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 2: Property Service Assignment with Audit Trail
 * Validates: Requirements 3.1, 3.2
 */
test('Service assignment supports pricing overrides with proper validation', function () {
    // Authenticate a user
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => \App\Enums\UserRole::ADMIN,
    ]);
    $this->actingAs($user);
    
    // Create property and utility service
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'area_sqm' => fake()->randomFloat(2, 30, 200),
    ]);
    
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'configuration_schema' => [
            'required' => ['rate_schedule'],
            'optional' => ['discount_percentage', 'minimum_charge'],
        ],
    ]);
    
    // Create configuration with pricing overrides
    $configurationOverrides = [
        'discount_percentage' => fake()->randomFloat(2, 0, 25),
        'minimum_charge' => fake()->randomFloat(2, 5, 50),
        'custom_adjustment' => fake()->randomFloat(2, -10, 10),
    ];
    
    $dto = AssignUtilityServiceDTO::fromArray([
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['rate_per_unit' => fake()->randomFloat(4, 0.01, 2.0)],
        'configuration_overrides' => $configurationOverrides,
        'effective_from' => now(),
        'is_active' => true,
    ]);
    
    // Property: Assignment with overrides should create configuration with merged settings
    $action = new AssignUtilityServiceAction();
    $configuration = $action->execute($dto);
    
    // Verify configuration was created with overrides
    expect($configuration)->toBeInstanceOf(ServiceConfiguration::class);
    expect($configuration->configuration_overrides)->toBe($configurationOverrides);
    
    // Verify merged configuration includes overrides
    $mergedConfig = $configuration->getMergedConfiguration();
    expect($mergedConfig)->toBeArray();
    expect($mergedConfig['discount_percentage'])->toBe($configurationOverrides['discount_percentage']);
    expect($mergedConfig['minimum_charge'])->toBe($configurationOverrides['minimum_charge']);
    expect($mergedConfig['custom_adjustment'])->toBe($configurationOverrides['custom_adjustment']);
    
    // Verify audit trail captures overrides
    $this->assertDatabaseHas('activity_log', [
        'subject_type' => ServiceConfiguration::class,
        'subject_id' => $configuration->id,
        'description' => 'utility_service_assigned',
    ]);
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 2: Property Service Assignment with Audit Trail
 * Validates: Requirements 3.1
 */
test('Service assignment validates custom formulas for security', function () {
    // Authenticate a user
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => \App\Enums\UserRole::ADMIN,
    ]);
    $this->actingAs($user);
    
    // Create property and utility service
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'area_sqm' => fake()->randomFloat(2, 30, 200),
    ]);
    
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::CUSTOM_FORMULA,
    ]);
    
    // Test with dangerous formula
    $dangerousFormulas = ['eval(consumption)', 'exec(consumption)', 'system(consumption)'];
    $dangerousFormula = fake()->randomElement($dangerousFormulas);
    
    $dto = AssignUtilityServiceDTO::fromArray([
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CUSTOM_FORMULA,
        'rate_schedule' => ['rate' => 1.0],
        'custom_formula' => $dangerousFormula,
        'effective_from' => now(),
        'is_active' => true,
    ]);
    
    // Property: Assignment should reject formulas with dangerous functions
    $action = new AssignUtilityServiceAction();
    
    try {
        $configuration = $action->execute($dto);
        expect(false)->toBeTrue('Should have thrown ServiceConfigurationException');
    } catch (ServiceConfigurationException $e) {
        expect($e->getMessage())->toContain('dangerous');
    }
    
})->repeat(100);
