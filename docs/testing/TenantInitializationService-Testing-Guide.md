# TenantInitializationService Testing Guide

## Overview

This guide covers comprehensive testing strategies for the `TenantInitializationService`, including feature tests, performance tests, and property-based tests. The service is critical for tenant onboarding and requires thorough testing to ensure reliability and performance.

## Test Structure

### Test Files Location
- **Feature Tests**: `tests/Feature/Services/TenantInitializationServiceTest.php`
- **Performance Tests**: `tests/Performance/TenantInitializationPerformanceTest.php`
- **Property Tests**: `tests/Property/TenantInitializationPropertyTest.php`

### Test Categories

#### 1. Feature Tests (Integration Testing)
Tests the service with real database interactions and full Laravel application context.

#### 2. Performance Tests (Benchmarking)
Validates service performance under various loads and ensures acceptable response times.

#### 3. Property Tests (Invariant Testing)
Uses property-based testing to verify invariants hold across different input combinations.

## Feature Test Patterns

### Basic Service Initialization Test

```php
it('initializes universal services for a new tenant', function () {
    // Arrange
    $tenant = Organization::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Act
    $result = $this->service->initializeUniversalServices($tenant);
    
    // Assert - Verify services were created
    $services = UtilityService::where('tenant_id', $tenant->id)->get();
    expect($services)->toHaveCount(4);
    
    // Verify service types by checking service_type_bridge enum values
    $serviceTypes = $services->pluck('service_type_bridge')
        ->map(fn($type) => $type->value)
        ->toArray();
    expect($serviceTypes)->toContain('electricity', 'water', 'heating', 'gas');
    
    // Verify each service has proper configuration
    foreach ($services as $service) {
        expect($service->is_active)->toBeTrue();
        expect($service->configuration_schema)->toBeArray();
        expect($service->validation_rules)->toBeArray();
        expect($service->business_logic_config)->toBeArray();
    }
});
```

### Property Assignment Test

```php
it('assigns services to all tenant properties', function () {
    // Arrange
    $tenant = Organization::factory()->create();
    $property1 = Property::factory()->create(['tenant_id' => $tenant->id]);
    $property2 = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Act - Initialize universal services first
    $result = $this->service->initializeUniversalServices($tenant);
    
    // Initialize property service assignments
    $serviceConfigurations = $this->service->initializePropertyServiceAssignments(
        $tenant, 
        $result['utility_services']
    );
    
    // Assert - Verify property service assignments
    expect($serviceConfigurations)->toHaveCount(2); // 2 properties
    expect($serviceConfigurations[$property1->id])->toHaveCount(4); // 4 services per property
    expect($serviceConfigurations[$property2->id])->toHaveCount(4); // 4 services per property
    
    // Verify service configurations in database
    $dbConfigurations = ServiceConfiguration::whereIn('property_id', [$property1->id, $property2->id])->get();
    expect($dbConfigurations)->toHaveCount(8); // 4 services × 2 properties
    
    foreach ($dbConfigurations as $config) {
        expect($config->is_active)->toBeTrue();
        expect($config->effective_from)->not()->toBeNull();
    }
});
```

### Edge Case Testing

```php
it('handles tenants with no properties gracefully', function () {
    // Arrange
    $tenant = Organization::factory()->create();
    
    // Act & Assert - Should not throw an exception
    $result = $this->service->initializeUniversalServices($tenant);
    
    // Services should still be created
    $services = UtilityService::where('tenant_id', $tenant->id)->get();
    expect($services)->toHaveCount(4);
    
    // But no property services should exist when initializing property assignments
    $serviceConfigurations = $this->service->initializePropertyServiceAssignments(
        $tenant, 
        $result['utility_services']
    );
    expect($serviceConfigurations)->toHaveCount(0);
});
```

### Backward Compatibility Test

```php
it('maintains backward compatibility with existing heating initialization', function () {
    // Arrange
    $tenant = Organization::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Act
    $result = $this->service->initializeUniversalServices($tenant);
    
    // Assert - Verify heating service exists and has proper configuration
    $heatingService = UtilityService::where('tenant_id', $tenant->id)
        ->get()
        ->first(fn($service) => $service->service_type_bridge->value === 'heating');
    
    expect($heatingService)->not()->toBeNull();
    expect($heatingService->business_logic_config)->toHaveKey('supports_shared_distribution');
    expect($heatingService->business_logic_config['supports_shared_distribution'])->toBeTrue();
    
    // Test heating compatibility check
    $isCompatible = $this->service->ensureHeatingCompatibility($tenant);
    expect($isCompatible)->toBeTrue();
});
```

## Performance Test Patterns

### Single Tenant Performance

```php
it('initializes services within acceptable time limits', function () {
    $tenant = Organization::factory()->create();
    
    $startTime = microtime(true);
    $result = $this->service->initializeUniversalServices($tenant);
    $executionTime = microtime(true) - $startTime;
    
    // Should complete within 500ms for single tenant
    expect($executionTime)->toBeLessThan(0.5);
    expect($result['utility_services'])->toHaveCount(4);
});
```

### Batch Performance Testing

```php
it('handles batch tenant initialization efficiently', function () {
    $tenantCount = 20;
    $tenants = Organization::factory()->count($tenantCount)->create();
    
    $startTime = microtime(true);
    
    foreach ($tenants as $tenant) {
        $this->service->initializeUniversalServices($tenant);
    }
    
    $executionTime = microtime(true) - $startTime;
    $averageTime = $executionTime / $tenantCount;
    
    // Average time per tenant should be reasonable
    expect($averageTime)->toBeLessThan(0.2); // 200ms per tenant
    expect($executionTime)->toBeLessThan(5.0); // Total under 5 seconds
    
    // Verify all services were created
    $totalServices = UtilityService::whereIn('tenant_id', $tenants->pluck('id'))->count();
    expect($totalServices)->toBe($tenantCount * 4);
});
```

### Scaling Performance

```php
it('scales linearly with property count', function () {
    $tenant = Organization::factory()->create();
    
    // Test different property counts
    $propertyCounts = [10, 50, 100];
    $times = [];
    
    foreach ($propertyCounts as $count) {
        // Clean up
        Property::where('tenant_id', $tenant->id)->delete();
        
        // Create properties
        Property::factory()->count($count)->forTenantId($tenant->id)->create();
        
        $result = $this->service->initializeUniversalServices($tenant);
        
        $startTime = microtime(true);
        $this->service->initializePropertyServiceAssignments($tenant, $result['utility_services']);
        $executionTime = microtime(true) - $startTime;
        
        $times[$count] = $executionTime;
        
        // Should complete within reasonable time
        expect($executionTime)->toBeLessThan($count * 0.01); // 10ms per property max
    }
    
    // Verify roughly linear scaling
    $ratio1 = $times[50] / $times[10];
    $ratio2 = $times[100] / $times[50];
    
    // Ratios should be roughly proportional (allowing for some overhead)
    expect($ratio1)->toBeLessThan(8); // Should be ~5x but allow overhead
    expect($ratio2)->toBeLessThan(3); // Should be ~2x but allow overhead
});
```

## Property-Based Test Patterns

### Service Creation Invariants

```php
it('always creates exactly 4 universal services for any valid tenant', function () {
    // Generate random tenant configurations
    $tenantCount = fake()->numberBetween(1, 10);
    
    for ($i = 0; $i < $tenantCount; $i++) {
        $tenant = Organization::factory()->create([
            'name' => fake()->company(),
            'max_properties' => fake()->numberBetween(1, 1000),
            'max_users' => fake()->numberBetween(1, 100),
        ]);
        
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Invariant: Always exactly 4 services
        expect($result['utility_services'])->toHaveCount(4);
        expect($result['meter_configurations'])->toHaveCount(4);
        
        // Invariant: All services are active
        foreach ($result['utility_services'] as $service) {
            expect($service->is_active)->toBeTrue();
            expect($service->tenant_id)->toBe($tenant->id);
        }
    }
});
```

### Tenant Isolation Invariants

```php
it('maintains strict tenant isolation across multiple tenants', function () {
    $tenantCount = fake()->numberBetween(3, 8);
    $tenants = Organization::factory()->count($tenantCount)->create();
    
    $allResults = [];
    
    // Initialize services for all tenants
    foreach ($tenants as $tenant) {
        $allResults[$tenant->id] = $this->service->initializeUniversalServices($tenant);
    }
    
    // Verify isolation invariants
    foreach ($tenants as $tenant) {
        $tenantServices = UtilityService::where('tenant_id', $tenant->id)->get();
        
        // Invariant: Each tenant has exactly their services
        expect($tenantServices)->toHaveCount(4);
        
        // Invariant: No cross-tenant contamination
        foreach ($tenantServices as $service) {
            expect($service->tenant_id)->toBe($tenant->id);
        }
        
        // Invariant: Unique slugs within tenant
        $slugs = $tenantServices->pluck('slug')->toArray();
        expect($slugs)->toHaveCount(count(array_unique($slugs)));
    }
    
    // Invariant: Total services = tenant count * 4
    $totalServices = UtilityService::whereIn('tenant_id', $tenants->pluck('id'))->count();
    expect($totalServices)->toBe($tenantCount * 4);
});
```

## Test Setup and Helpers

### BeforeEach Setup

```php
beforeEach(function () {
    $this->service = app(TenantInitializationService::class);
});
```

### Custom Test Helpers

```php
// In TestCase.php or test helper trait
protected function createTenantWithProperties(int $propertyCount = 3): array
{
    $tenant = Organization::factory()->create();
    $properties = Property::factory()
        ->count($propertyCount)
        ->create(['tenant_id' => $tenant->id]);
    
    return [$tenant, $properties];
}

protected function assertServiceConfigurationStructure(array $config): void
{
    expect($config)->toHaveKeys([
        'utility_service_id',
        'pricing_model',
        'is_active',
        'effective_from'
    ]);
    expect($config['is_active'])->toBeTrue();
    expect($config['effective_from'])->not()->toBeNull();
}
```

## Database Testing Considerations

### Using RefreshDatabase

```php
uses(RefreshDatabase::class);
```

### Transaction Testing

For tests that need to verify transaction behavior:

```php
it('rolls back on failure', function () {
    $tenant = Organization::factory()->create();
    
    // Mock a service to throw an exception
    $mockService = Mockery::mock(TenantInitializationService::class);
    $mockService->shouldReceive('initializeUniversalServices')
        ->andThrow(new RuntimeException('Database error'));
    
    $this->app->instance(TenantInitializationService::class, $mockService);
    
    expect(fn() => $mockService->initializeUniversalServices($tenant))
        ->toThrow(RuntimeException::class);
    
    // Verify no services were created
    $services = UtilityService::where('tenant_id', $tenant->id)->get();
    expect($services)->toHaveCount(0);
});
```

## Mocking and Stubbing

### Mocking External Dependencies

```php
it('handles external service failures gracefully', function () {
    // Mock the cache to simulate failure
    Cache::shouldReceive('flush')
        ->andThrow(new Exception('Cache service unavailable'));
    
    $tenant = Organization::factory()->create();
    
    // Service should still work without cache
    $result = $this->service->initializeUniversalServices($tenant);
    expect($result['utility_services'])->toHaveCount(4);
});
```

## Test Data Management

### Factory Usage

```php
// Create tenant with specific attributes
$tenant = Organization::factory()->create([
    'name' => 'Test Organization',
    'max_properties' => 100,
]);

// Create properties for tenant
$properties = Property::factory()
    ->count(5)
    ->forTenantId($tenant->id)
    ->create();
```

### Test Data Cleanup

```php
afterEach(function () {
    // Clean up any cached data
    Cache::flush();
    
    // Reset any global state
    DB::statement('DELETE FROM utility_services WHERE tenant_id > 1000');
});
```

## Assertion Patterns

### Service Structure Assertions

```php
protected function assertServiceStructure(UtilityService $service): void
{
    expect($service->is_active)->toBeTrue();
    expect($service->configuration_schema)->toBeArray();
    expect($service->validation_rules)->toBeArray();
    expect($service->business_logic_config)->toBeArray();
    expect($service->tenant_id)->not()->toBeNull();
    expect($service->service_type_bridge)->toBeInstanceOf(ServiceType::class);
    expect($service->default_pricing_model)->toBeInstanceOf(PricingModel::class);
}
```

### Configuration Assertions

```php
protected function assertMeterConfiguration(array $config, string $serviceType): void
{
    expect($config)->toHaveKeys([
        'utility_service_id',
        'pricing_model',
        'is_active',
        'effective_from',
        'distribution_method',
        'is_shared_service',
        'rate_schedule',
        'configuration_overrides',
    ]);
    
    expect($config['is_active'])->toBeTrue();
    expect($config['pricing_model'])->toBeInstanceOf(PricingModel::class);
    expect($config['distribution_method'])->toBeInstanceOf(DistributionMethod::class);
    
    // Service-specific assertions
    match ($serviceType) {
        'heating' => expect($config['is_shared_service'])->toBeTrue(),
        'electricity', 'water', 'gas' => expect($config['is_shared_service'])->toBeFalse(),
    };
}
```

## Performance Benchmarking

### Memory Usage Testing

```php
it('maintains reasonable memory usage during batch operations', function () {
    $initialMemory = memory_get_usage(true);
    
    $tenants = Organization::factory()->count(50)->create();
    
    foreach ($tenants as $tenant) {
        $this->service->initializeUniversalServices($tenant);
    }
    
    $finalMemory = memory_get_usage(true);
    $memoryIncrease = $finalMemory - $initialMemory;
    
    // Memory increase should be reasonable (less than 50MB for 50 tenants)
    expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024);
});
```

### Query Count Testing

```php
it('minimizes database queries', function () {
    $tenant = Organization::factory()->create();
    
    DB::enableQueryLog();
    
    $this->service->initializeUniversalServices($tenant);
    
    $queries = DB::getQueryLog();
    $queryCount = count($queries);
    
    // Should use reasonable number of queries (not N+1)
    expect($queryCount)->toBeLessThan(20); // Adjust based on actual needs
    
    DB::disableQueryLog();
});
```

## Error Testing

### Exception Handling Tests

```php
it('handles database transaction failures gracefully', function () {
    $tenant = Organization::factory()->create();
    
    // Mock a database failure scenario
    DB::shouldReceive('transaction')
        ->once()
        ->andThrow(new Exception('Database connection failed'));
    
    expect(fn() => $this->service->initializeUniversalServices($tenant))
        ->toThrow(RuntimeException::class, 'Failed to initialize universal services');
});
```

## Test Coverage Goals

### Coverage Targets
- **Line Coverage**: > 95%
- **Branch Coverage**: > 90%
- **Method Coverage**: 100%

### Critical Paths to Test
1. Service creation for all four utility types
2. Property assignment with various property counts
3. Error handling and rollback scenarios
4. Tenant isolation and security
5. Performance under load
6. Backward compatibility preservation

## Continuous Integration

### Test Commands

```bash
# Run all TenantInitializationService tests
php artisan test --filter=TenantInitializationService

# Run with coverage
php artisan test --filter=TenantInitializationService --coverage

# Run performance tests specifically
php artisan test tests/Performance/TenantInitializationPerformanceTest.php

# Run property tests
php artisan test tests/Property/TenantInitializationPropertyTest.php
```

### CI Pipeline Integration

```yaml
# Example GitHub Actions step
- name: Run Tenant Initialization Tests
  run: |
    php artisan test --filter=TenantInitializationService --coverage-clover=coverage.xml
    php artisan test tests/Performance/TenantInitializationPerformanceTest.php
```

## Best Practices

### DO:
- ✅ Test all public methods with various input combinations
- ✅ Use property-based testing for invariant verification
- ✅ Test performance under realistic load conditions
- ✅ Verify tenant isolation in multi-tenant scenarios
- ✅ Test error handling and rollback scenarios
- ✅ Use factories for consistent test data
- ✅ Assert on both database state and return values

### DON'T:
- ❌ Skip testing edge cases (no properties, invalid tenants)
- ❌ Ignore performance implications of changes
- ❌ Test implementation details instead of behavior
- ❌ Use hardcoded IDs or data in tests
- ❌ Forget to test backward compatibility
- ❌ Skip cleanup between tests
- ❌ Test multiple concerns in a single test

## Related Documentation

- [TenantInitializationService Documentation](../services/TenantInitializationService.md)
- [Testing Standards](.kiro/steering/testing-standards.md)
- [Property-Based Testing Guide](property-based-testing-guide.md)
- [Performance Testing Guidelines](./performance-testing.md)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)