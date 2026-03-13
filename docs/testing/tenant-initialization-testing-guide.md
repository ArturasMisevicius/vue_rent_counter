# Tenant Initialization Testing Guide

## Overview

This guide provides comprehensive testing strategies for the Tenant Initialization Service, covering unit tests, integration tests, feature tests, and property-based testing patterns.

## Testing Architecture

### Test Structure

```
tests/
├── Unit/Services/
│   └── TenantInitializationServiceTest.php
├── Feature/Services/
│   └── TenantInitializationServiceEnhancedTest.php
├── Integration/
│   └── TenantInitializationIntegrationTest.php
└── Property/
    └── TenantInitializationPropertyTest.php
```

## Unit Testing

### Basic Service Testing

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TenantInitializationService;
use App\Services\TenantInitialization\ServiceDefinitionProvider;
use App\Services\TenantInitialization\MeterConfigurationProvider;
use App\Services\TenantInitialization\PropertyServiceAssigner;
use App\Models\Organization;
use Tests\TestCase;
use Mockery;

class TenantInitializationServiceTest extends TestCase
{
    private TenantInitializationService $service;
    private ServiceDefinitionProvider $serviceDefinitionProvider;
    private MeterConfigurationProvider $meterConfigurationProvider;
    private PropertyServiceAssigner $propertyServiceAssigner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceDefinitionProvider = Mockery::mock(ServiceDefinitionProvider::class);
        $this->meterConfigurationProvider = Mockery::mock(MeterConfigurationProvider::class);
        $this->propertyServiceAssigner = Mockery::mock(PropertyServiceAssigner::class);

        $this->service = new TenantInitializationService(
            $this->serviceDefinitionProvider,
            $this->meterConfigurationProvider,
            $this->propertyServiceAssigner,
        );
    }

    public function test_initializes_universal_services_successfully(): void
    {
        $tenant = Organization::factory()->create();
        
        $serviceDefinitions = [
            'electricity' => [
                'name' => 'Electricity Service',
                'service_type_bridge' => \App\Enums\ServiceType::ELECTRICITY,
                'description' => 'Test service',
            ],
        ];

        $this->serviceDefinitionProvider
            ->shouldReceive('getDefaultServiceDefinitions')
            ->once()
            ->andReturn($serviceDefinitions);

        $this->meterConfigurationProvider
            ->shouldReceive('createDefaultMeterConfigurations')
            ->once()
            ->andReturn(['electricity' => ['config' => 'data']]);

        $result = $this->service->initializeUniversalServices($tenant);

        $this->assertInstanceOf(\App\Data\TenantInitialization\InitializationResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(1, $result->getServiceCount());
    }

    public function test_validates_tenant_before_initialization(): void
    {
        $tenant = new Organization();
        $tenant->name = '';

        $this->expectException(\App\Exceptions\TenantInitializationException::class);
        $this->expectExceptionMessage('Tenant name is required');

        $this->service->initializeUniversalServices($tenant);
    }
}
```

### Testing Service Dependencies

```php
public function test_service_definition_provider_returns_all_services(): void
{
    $provider = new ServiceDefinitionProvider();
    $definitions = $provider->getDefaultServiceDefinitions();
    
    $this->assertArrayHasKey('electricity', $definitions);
    $this->assertArrayHasKey('water', $definitions);
    $this->assertArrayHasKey('heating', $definitions);
    $this->assertArrayHasKey('gas', $definitions);
    
    // Validate electricity service structure
    $electricity = $definitions['electricity'];
    $this->assertEquals('Electricity Service', $electricity['name']);
    $this->assertEquals('kWh', $electricity['unit_of_measurement']);
    $this->assertEquals(\App\Enums\PricingModel::TIME_OF_USE, $electricity['default_pricing_model']);
}

public function test_meter_configuration_provider_creates_valid_configs(): void
{
    $utilityService = UtilityService::factory()->create([
        'service_type_bridge' => \App\Enums\ServiceType::ELECTRICITY,
    ]);
    
    $provider = new MeterConfigurationProvider();
    $configs = $provider->createDefaultMeterConfigurations(['electricity' => $utilityService]);
    
    $this->assertArrayHasKey('electricity', $configs);
    $electricityConfig = $configs['electricity'];
    
    $this->assertEquals($utilityService->id, $electricityConfig['utility_service_id']);
    $this->assertTrue($electricityConfig['is_active']);
    $this->assertArrayHasKey('rate_schedule', $electricityConfig);
}
```

## Integration Testing

### Database Integration

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\TenantInitializationService;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Enums\ServiceType;
use App\Enums\PropertyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantInitializationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private TenantInitializationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TenantInitializationService::class);
    }

    public function test_complete_tenant_initialization_flow(): void
    {
        // Create tenant with properties
        $tenant = Organization::factory()->create([
            'locale' => 'lt',
            'timezone' => 'Europe/Vilnius',
        ]);
        
        $properties = Property::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'type' => PropertyType::APARTMENT,
        ]);

        // Create providers
        Provider::factory()->create(['service_type' => ServiceType::ELECTRICITY]);
        Provider::factory()->create(['service_type' => ServiceType::WATER]);

        // Initialize services
        $result = $this->service->initializeUniversalServices($tenant);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(4, $result->getServiceCount());

        // Assign to properties
        $assignments = $this->service->initializePropertyServiceAssignments(
            $tenant, 
            $result->utilityServices
        );
        
        $this->assertEquals(3, $assignments->getPropertyCount());
        $this->assertEquals(12, $assignments->getTotalConfigurationCount()); // 3 properties × 4 services

        // Initialize meter configurations
        $meterConfigs = $this->service->initializeDefaultMeterConfigurations(
            $tenant, 
            $assignments
        );
        
        $this->assertCount(3, $meterConfigs); // 3 properties
        
        // Verify database state
        $this->assertDatabaseCount('utility_services', 4);
        $this->assertDatabaseCount('service_configurations', 12);
        
        // Verify tenant isolation
        $otherTenant = Organization::factory()->create();
        $otherServices = \App\Models\UtilityService::where('tenant_id', $otherTenant->id)->get();
        $this->assertCount(0, $otherServices);
    }
}
```

### Provider Integration Testing

```php
public function test_provider_assignment_with_active_tariffs(): void
{
    $tenant = Organization::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Create provider with active tariff
    $provider = Provider::factory()->create([
        'service_type' => ServiceType::ELECTRICITY,
    ]);
    
    $tariff = \App\Models\Tariff::factory()->create([
        'provider_id' => $provider->id,
        'rates' => [
            'day_rate' => 0.15,
            'night_rate' => 0.10,
        ],
        'active_from' => now()->subMonth(),
        'active_until' => null,
    ]);

    // Initialize services
    $result = $this->service->initializeUniversalServices($tenant);
    $assignments = $this->service->initializePropertyServiceAssignments($tenant, $result->utilityServices);

    // Verify provider assignment
    $electricityConfig = $assignments->getPropertyServiceConfiguration($property->id, 'electricity');
    $this->assertEquals($provider->id, $electricityConfig->provider_id);
    $this->assertEquals($tariff->id, $electricityConfig->tariff_id);
    $this->assertEquals($provider->name, $electricityConfig->provider_name);
}
```

## Feature Testing

### API Endpoint Testing

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantInitializationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_initialize_tenant_services_via_api(): void
    {
        $tenant = Organization::factory()->create();
        $admin = User::factory()->create();
        
        $response = $this->actingAs($admin)
            ->postJson("/api/tenants/{$tenant->id}/initialize-services", [
                'services' => ['electricity', 'water', 'heating', 'gas'],
                'regional_settings' => [
                    'locale' => 'lt',
                    'timezone' => 'Europe/Vilnius',
                    'currency' => 'EUR',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'initialization_result' => [
                        'services_created' => 4,
                        'meter_configurations_created' => 4,
                    ],
                ],
            ]);

        // Verify database state
        $this->assertDatabaseCount('utility_services', 4);
        $this->assertDatabaseHas('utility_services', [
            'tenant_id' => $tenant->id,
            'service_type_bridge' => 'electricity',
        ]);
    }

    public function test_property_assignment_api_endpoint(): void
    {
        $tenant = Organization::factory()->create();
        $properties = Property::factory()->count(2)->create(['tenant_id' => $tenant->id]);
        
        // First initialize services
        $initResult = app(\App\Services\TenantInitializationService::class)
            ->initializeUniversalServices($tenant);

        $response = $this->actingAs($admin)
            ->postJson("/api/tenants/{$tenant->id}/properties/assign-services", [
                'property_ids' => $properties->pluck('id')->toArray(),
                'service_types' => ['electricity', 'water'],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'assignment_result' => [
                        'properties_configured' => 2,
                        'total_configurations' => 4, // 2 properties × 2 services
                    ],
                ],
            ]);
    }
}
```

### Filament Integration Testing

```php
use function Pest\Livewire\livewire;

it('can initialize tenant services from filament action', function () {
    $tenant = Organization::factory()->create();
    $admin = User::factory()->create();
    
    livewire(\App\Filament\Resources\OrganizationResource\Pages\EditOrganization::class, [
        'record' => $tenant->getRouteKey(),
    ])
    ->actingAs($admin)
    ->callAction('initializeServices')
    ->assertNotified('Services initialized successfully');
    
    expect($tenant->utilityServices)->toHaveCount(4);
});
```

## Property-Based Testing

### Tenant Isolation Properties

```php
use Tests\TestCase;
use App\Models\Organization;
use App\Models\Property;
use App\Services\TenantInitializationService;

class TenantInitializationPropertyTest extends TestCase
{
    /**
     * Property: Universal service data is properly isolated between tenants
     */
    public function test_tenant_isolation_property(): void
    {
        $this->forAll(
            Generator\Elements::fromArray([1, 2, 3, 4, 5]), // Number of tenants
            Generator\Elements::fromArray([1, 2, 3, 4]), // Properties per tenant
        )->then(function (int $tenantCount, int $propertiesPerTenant) {
            $service = app(TenantInitializationService::class);
            $tenants = [];
            
            // Create multiple tenants with properties
            for ($i = 0; $i < $tenantCount; $i++) {
                $tenant = Organization::factory()->create();
                Property::factory()->count($propertiesPerTenant)->create([
                    'tenant_id' => $tenant->id,
                ]);
                $tenants[] = $tenant;
                
                // Initialize services for each tenant
                $result = $service->initializeUniversalServices($tenant);
                $this->assertTrue($result->isSuccessful());
            }
            
            // Verify isolation: each tenant should only see their own services
            foreach ($tenants as $tenant) {
                $tenantServices = \App\Models\UtilityService::where('tenant_id', $tenant->id)->get();
                $this->assertCount(4, $tenantServices); // Each tenant has 4 services
                
                // Verify no cross-tenant access
                $otherTenantServices = \App\Models\UtilityService::where('tenant_id', '!=', $tenant->id)->get();
                foreach ($otherTenantServices as $otherService) {
                    $this->assertNotEquals($tenant->id, $otherService->tenant_id);
                }
            }
        });
    }

    /**
     * Property: Property service assignments respect tenant boundaries
     */
    public function test_property_assignment_isolation_property(): void
    {
        $this->forAll(
            Generator\Elements::fromArray([2, 3, 4]), // Number of tenants
            Generator\Elements::fromArray([1, 2, 3]), // Properties per tenant
        )->then(function (int $tenantCount, int $propertiesPerTenant) {
            $service = app(TenantInitializationService::class);
            $tenants = [];
            
            // Create tenants with properties and initialize services
            for ($i = 0; $i < $tenantCount; $i++) {
                $tenant = Organization::factory()->create();
                Property::factory()->count($propertiesPerTenant)->create([
                    'tenant_id' => $tenant->id,
                ]);
                
                $result = $service->initializeUniversalServices($tenant);
                $assignments = $service->initializePropertyServiceAssignments($tenant, $result->utilityServices);
                
                $tenants[] = ['tenant' => $tenant, 'assignments' => $assignments];
            }
            
            // Verify each tenant's configurations are isolated
            foreach ($tenants as $tenantData) {
                $tenant = $tenantData['tenant'];
                $assignments = $tenantData['assignments'];
                
                // Each tenant should have configurations for their properties only
                $this->assertEquals($propertiesPerTenant, $assignments->getPropertyCount());
                
                // Verify property ownership
                foreach ($assignments->getPropertyIds() as $propertyId) {
                    $property = Property::find($propertyId);
                    $this->assertEquals($tenant->id, $property->tenant_id);
                }
            }
        });
    }
}
```

### Configuration Customization Properties

```php
/**
 * Property: Commercial properties receive appropriate rate adjustments
 */
public function test_commercial_property_configuration_property(): void
{
    $this->forAll(
        Generator\Elements::fromArray(['office', 'retail', 'warehouse', 'commercial']),
        Generator\IntRange(200, 1000), // Property area
    )->then(function (string $propertyType, int $area) {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => $propertyType,
            'area_sqm' => $area,
        ]);
        
        $service = app(TenantInitializationService::class);
        $result = $service->initializeUniversalServices($tenant);
        $assignments = $service->initializePropertyServiceAssignments($tenant, $result->utilityServices);
        
        // Verify commercial adjustments
        $electricityConfig = $assignments->getPropertyServiceConfiguration($property->id, 'electricity');
        $this->assertNotNull($electricityConfig);
        
        $rateSchedule = $electricityConfig->rate_schedule;
        $this->assertGreaterThan(0.15, $rateSchedule['zone_rates']['day']); // Higher commercial rates
        $this->assertArrayHasKey('demand_charge', $rateSchedule); // Commercial demand charge
        
        // Large properties get enhanced monitoring
        if ($area > 200) {
            $overrides = $electricityConfig->configuration_overrides;
            $this->assertTrue($overrides['large_property']);
            $this->assertTrue($overrides['enhanced_monitoring']);
        }
    });
}
```

### Regional Configuration Properties

```php
/**
 * Property: Lithuanian tenants receive correct regional defaults
 */
public function test_lithuanian_regional_defaults_property(): void
{
    $this->forAll(
        Generator\Elements::fromArray(['lt', 'en']), // Locales
        Generator\Elements::fromArray(['Europe/Vilnius', 'Europe/London']), // Timezones
    )->then(function (string $locale, string $timezone) {
        $tenant = Organization::factory()->create([
            'locale' => $locale,
            'timezone' => $timezone,
        ]);
        
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        
        $service = app(TenantInitializationService::class);
        $result = $service->initializeUniversalServices($tenant);
        $assignments = $service->initializePropertyServiceAssignments($tenant, $result->utilityServices);
        
        $electricityConfig = $assignments->getPropertyServiceConfiguration($property->id, 'electricity');
        $configOverrides = $electricityConfig->configuration_overrides;
        
        // Lithuanian settings
        if ($locale === 'lt' || str_contains($timezone, 'Vilnius')) {
            $this->assertEquals('LT', $configOverrides['regulatory_region']);
            $this->assertEquals(0.21, $configOverrides['vat_rate']); // Lithuanian VAT
            
            $rateSchedule = $electricityConfig->rate_schedule;
            $this->assertEquals(0.1547, $rateSchedule['zone_rates']['day']); // Lithuanian day rate
            $this->assertEquals(0.1047, $rateSchedule['zone_rates']['night']); // Lithuanian night rate
        } else {
            // EU defaults
            $this->assertEquals('EU', $configOverrides['regulatory_region']);
            $this->assertEquals(0.20, $configOverrides['vat_rate']); // EU VAT
        }
    });
}
```

## Performance Testing

### Load Testing

```php
public function test_handles_multiple_properties_efficiently(): void
{
    $tenant = Organization::factory()->create();
    
    // Create many properties
    $properties = Property::factory()->count(50)->create([
        'tenant_id' => $tenant->id,
    ]);
    
    $startTime = microtime(true);
    
    $service = app(TenantInitializationService::class);
    $result = $service->initializeUniversalServices($tenant);
    $assignments = $service->initializePropertyServiceAssignments($tenant, $result->utilityServices);
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    
    // Should complete within reasonable time (adjust threshold as needed)
    $this->assertLessThan(5.0, $duration, 'Initialization took too long');
    
    // Verify all properties were configured
    $this->assertEquals(50, $assignments->getPropertyCount());
    $this->assertEquals(200, $assignments->getTotalConfigurationCount()); // 50 × 4 services
}
```

### Memory Usage Testing

```php
public function test_memory_usage_within_limits(): void
{
    $tenant = Organization::factory()->create();
    Property::factory()->count(100)->create(['tenant_id' => $tenant->id]);
    
    $memoryBefore = memory_get_usage(true);
    
    $service = app(TenantInitializationService::class);
    $result = $service->initializeUniversalServices($tenant);
    $assignments = $service->initializePropertyServiceAssignments($tenant, $result->utilityServices);
    
    $memoryAfter = memory_get_usage(true);
    $memoryUsed = $memoryAfter - $memoryBefore;
    
    // Should not use excessive memory (adjust threshold as needed)
    $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage too high'); // 50MB limit
}
```

## Error Scenario Testing

### Exception Handling

```php
public function test_handles_invalid_tenant_gracefully(): void
{
    $tenant = new Organization(); // Not saved to database
    
    $service = app(TenantInitializationService::class);
    
    $this->expectException(\App\Exceptions\TenantInitializationException::class);
    $this->expectExceptionMessage('Tenant must be persisted to database');
    
    $service->initializeUniversalServices($tenant);
}

public function test_handles_service_creation_failure(): void
{
    $tenant = Organization::factory()->create();
    
    // Mock service definition provider to throw exception
    $mockProvider = Mockery::mock(\App\Services\TenantInitialization\ServiceDefinitionProvider::class);
    $mockProvider->shouldReceive('getDefaultServiceDefinitions')
        ->andThrow(new \Exception('Service definition error'));
    
    $service = new \App\Services\TenantInitializationService(
        $mockProvider,
        app(\App\Services\TenantInitialization\MeterConfigurationProvider::class),
        app(\App\Services\TenantInitialization\PropertyServiceAssigner::class),
    );
    
    $this->expectException(\App\Exceptions\TenantInitializationException::class);
    
    $service->initializeUniversalServices($tenant);
}
```

### Transaction Rollback Testing

```php
public function test_rolls_back_on_partial_failure(): void
{
    $tenant = Organization::factory()->create();
    
    // Create a scenario where service creation partially fails
    DB::shouldReceive('transaction')
        ->once()
        ->andThrow(new \Exception('Database error'));
    
    $service = app(TenantInitializationService::class);
    
    try {
        $service->initializeUniversalServices($tenant);
        $this->fail('Expected exception was not thrown');
    } catch (\App\Exceptions\TenantInitializationException $e) {
        // Verify no partial data was created
        $this->assertDatabaseCount('utility_services', 0);
    }
}
```

## Test Data Factories

### Enhanced Factories for Testing

```php
// In database/factories/OrganizationFactory.php
public function lithuanian(): static
{
    return $this->state(fn (array $attributes) => [
        'locale' => 'lt',
        'timezone' => 'Europe/Vilnius',
        'currency' => 'EUR',
    ]);
}

public function commercial(): static
{
    return $this->state(fn (array $attributes) => [
        'type' => 'commercial',
        'business_registration' => 'LT' . fake()->numerify('##########'),
    ]);
}

// Usage in tests
$tenant = Organization::factory()->lithuanian()->create();
$commercialTenant = Organization::factory()->commercial()->create();
```

### Property Factory Extensions

```php
// In database/factories/PropertyFactory.php
public function commercial(): static
{
    return $this->state(fn (array $attributes) => [
        'type' => fake()->randomElement(['office', 'retail', 'warehouse', 'commercial']),
        'area_sqm' => fake()->numberBetween(200, 1000),
    ]);
}

public function apartment(): static
{
    return $this->state(fn (array $attributes) => [
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->numberBetween(30, 150),
        'building_id' => Building::factory(),
    ]);
}

public function large(): static
{
    return $this->state(fn (array $attributes) => [
        'area_sqm' => fake()->numberBetween(200, 500),
    ]);
}
```

## Test Utilities

### Custom Assertions

```php
// In tests/TestCase.php
protected function assertTenantServicesInitialized(Organization $tenant): void
{
    $services = \App\Models\UtilityService::where('tenant_id', $tenant->id)->get();
    
    $this->assertCount(4, $services, 'Tenant should have 4 utility services');
    
    $serviceTypes = $services->pluck('service_type_bridge')->toArray();
    $this->assertContains('electricity', $serviceTypes);
    $this->assertContains('water', $serviceTypes);
    $this->assertContains('heating', $serviceTypes);
    $this->assertContains('gas', $serviceTypes);
}

protected function assertPropertyConfigurationsCreated(Organization $tenant, int $expectedCount): void
{
    $configurations = \App\Models\ServiceConfiguration::where('tenant_id', $tenant->id)->get();
    
    $this->assertCount($expectedCount, $configurations);
    
    // Verify all configurations belong to tenant properties
    foreach ($configurations as $config) {
        $property = Property::find($config->property_id);
        $this->assertEquals($tenant->id, $property->tenant_id);
    }
}
```

### Test Helpers

```php
// In tests/TestCase.php
protected function createTenantWithProperties(int $propertyCount = 3, array $tenantAttributes = []): array
{
    $tenant = Organization::factory()->create($tenantAttributes);
    $properties = Property::factory()->count($propertyCount)->create([
        'tenant_id' => $tenant->id,
    ]);
    
    return [$tenant, $properties];
}

protected function initializeTenantServices(Organization $tenant): \App\Data\TenantInitialization\InitializationResult
{
    $service = app(\App\Services\TenantInitializationService::class);
    return $service->initializeUniversalServices($tenant);
}
```

## Continuous Integration

### Test Pipeline Configuration

```yaml
# .github/workflows/tests.yml
- name: Run Tenant Initialization Tests
  run: |
    php artisan test --filter=TenantInitialization --parallel
    php artisan test tests/Unit/Services/TenantInitializationServiceTest.php
    php artisan test tests/Feature/Services/TenantInitializationServiceEnhancedTest.php
```

### Performance Benchmarks

```php
// In tests/Performance/TenantInitializationPerformanceTest.php
public function test_initialization_performance_benchmark(): void
{
    $tenant = Organization::factory()->create();
    Property::factory()->count(20)->create(['tenant_id' => $tenant->id]);
    
    $service = app(TenantInitializationService::class);
    
    $startTime = microtime(true);
    $result = $service->initializeUniversalServices($tenant);
    $assignments = $service->initializePropertyServiceAssignments($tenant, $result->utilityServices);
    $endTime = microtime(true);
    
    $duration = $endTime - $startTime;
    
    // Log performance metrics
    Log::info('Tenant initialization performance', [
        'duration' => $duration,
        'properties_count' => 20,
        'services_created' => $result->getServiceCount(),
        'configurations_created' => $assignments->getTotalConfigurationCount(),
    ]);
    
    // Assert performance within acceptable limits
    $this->assertLessThan(2.0, $duration, 'Initialization should complete within 2 seconds');
}
```

## Test Coverage Requirements

### Minimum Coverage Targets
- **Unit Tests**: 95% line coverage
- **Integration Tests**: All major workflows covered
- **Feature Tests**: All API endpoints tested
- **Property Tests**: All business invariants validated

### Coverage Verification

```bash
# Run tests with coverage
php artisan test --coverage --min=95

# Generate coverage report
php artisan test --coverage-html=coverage-report

# Check specific service coverage
php artisan test --filter=TenantInitialization --coverage
```

## Related Documentation

- [Tenant Initialization Service Documentation](../services/tenant-initialization-service.md)
- [Testing Standards Guide](../../.kiro/steering/testing-standards.md)
- [Property-Based Testing Patterns](../testing/property-based-testing.md)
- [Multi-Tenant Testing Strategies](../testing/multi-tenant-testing.md)