# TenantInitializationService Documentation

## Overview

The `TenantInitializationService` is a core service responsible for initializing new tenants with default utility service templates and configurations in the multi-tenant utilities billing platform. It creates the foundational utility services (electricity, water, heating, gas) and handles property-level service assignments while maintaining backward compatibility with existing heating systems.

**Location**: `app/Services/TenantInitializationService.php`

**Dependencies**:
- `UtilityService` model - Universal utility service entity
- `ServiceConfiguration` model - Property-service configuration entity
- `Organization` model - Tenant/organization entity
- `Property` model - Property entity
- Various enums: `ServiceType`, `PricingModel`, `DistributionMethod`, `MeterType`

## Architecture

### Service Pattern
This service implements the Service Layer pattern, encapsulating complex business logic for tenant initialization operations. All operations are wrapped in database transactions to ensure data consistency.

### Universal Utility Framework
The service supports four core utility types:
1. **Electricity** - Time-of-use pricing with day/night zones
2. **Water** - Consumption-based billing
3. **Heating** - Hybrid pricing with seasonal adjustments and shared distribution
4. **Gas** - Tiered rate structure

### Multi-Tenancy
All operations enforce tenant isolation through:
- Tenant-scoped utility service creation
- Unique slug generation per tenant
- Property ownership validation
- Service configuration tenant inheritance

### Backward Compatibility
Maintains compatibility with existing heating systems by:
- Preserving heating calculator integration
- Supporting shared distribution methods
- Maintaining seasonal calculation logic
- Ensuring heating service configuration matches existing expectations

## Public Methods

### initializeUniversalServices()

Initialize a new tenant with default utility service templates and configurations.

**Signature**:
```php
public function initializeUniversalServices(Organization $tenant): array
```

**Parameters**:
- `$tenant` (Organization) - The organization/tenant to initialize services for

**Returns**: 
```php
array{
    utility_services: array<string, UtilityService>, 
    meter_configurations: array<string, array<string, mixed>>
}
```
- `utility_services` - Created utility services keyed by type (electricity, water, heating, gas)
- `meter_configurations` - Default meter configuration arrays keyed by service type

**Throws**:
- `RuntimeException` - If service initialization fails due to database errors
- `InvalidArgumentException` - If tenant data is invalid

**Requirements**: Universal Service Framework (1.1, 1.2, 1.3, 1.4, 1.5)

**Example**:
```php
$service = app(TenantInitializationService::class);
$result = $service->initializeUniversalServices($tenant);

// Access created services
$electricityService = $result['utility_services']['electricity'];
$heatingConfig = $result['meter_configurations']['heating'];

// Verify services were created
expect($result['utility_services'])->toHaveCount(4);
expect($result['meter_configurations'])->toHaveCount(4);
```

**Process Flow**:
1. Validates tenant exists and is accessible
2. Creates four default utility services with tenant-specific configurations
3. Generates default meter configurations for each service
4. Logs initialization success/failure
5. Returns structured array with services and configurations

**Service Types Created**:
- **Electricity**: `ServiceType::ELECTRICITY`, `PricingModel::TIME_OF_USE`
- **Water**: `ServiceType::WATER`, `PricingModel::CONSUMPTION_BASED`
- **Heating**: `ServiceType::HEATING`, `PricingModel::HYBRID`
- **Gas**: `ServiceType::GAS`, `PricingModel::TIERED_RATES`

---

### initializePropertyServiceAssignments()

Initialize property-level service assignments for existing properties.

**Signature**:
```php
public function initializePropertyServiceAssignments(
    Organization $tenant, 
    array $utilityServices
): array
```

**Parameters**:
- `$tenant` (Organization) - The tenant whose properties to configure
- `$utilityServices` (array<string, UtilityService>) - Array of utility services to assign

**Returns**: `array<int, array<string, ServiceConfiguration>>` - Service configurations grouped by property ID

**Throws**:
- `RuntimeException` - If configuration creation fails

**Requirements**: Service Assignment and Configuration Management (3.1, 3.2, 3.3)

**Example**:
```php
$result = $service->initializeUniversalServices($tenant);
$configurations = $service->initializePropertyServiceAssignments(
    $tenant, 
    $result['utility_services']
);

// Access configurations for specific property
$propertyConfigs = $configurations[$propertyId];
$electricityConfig = $propertyConfigs['electricity'];

// Verify configurations were created
expect($configurations)->toHaveCount($propertyCount);
foreach ($configurations as $propertyConfigs) {
    expect($propertyConfigs)->toHaveCount(4); // 4 services per property
}
```

**Process Flow**:
1. Retrieves all properties belonging to the tenant
2. Creates ServiceConfiguration records for each property-service combination
3. Applies appropriate pricing models and configuration overrides
4. Sets effective dates and activation status
5. Logs assignment success with property and service counts

**Configuration Details**:
- Each property gets all four utility services assigned
- Configurations include pricing model, rate schedules, and meter type specifications
- Heating services configured for shared distribution by area
- All configurations set as active with current effective date

---

### ensureHeatingCompatibility()

Ensure backward compatibility with existing heating initialization.

**Signature**:
```php
public function ensureHeatingCompatibility(Organization $tenant): bool
```

**Parameters**:
- `$tenant` (Organization) - The tenant to check heating compatibility for

**Returns**: `bool` - True if heating service is compatible, false otherwise

**Requirements**: Heating Integration System (13.1, 13.2, 13.3, 13.4)

**Example**:
```php
$service->initializeUniversalServices($tenant);

// Verify heating compatibility
$isCompatible = $service->ensureHeatingCompatibility($tenant);
expect($isCompatible)->toBeTrue();

// Check heating service configuration
$heatingService = UtilityService::where('tenant_id', $tenant->id)
    ->where('service_type_bridge', ServiceType::HEATING)
    ->first();
    
expect($heatingService->business_logic_config['supports_shared_distribution'])
    ->toBeTrue();
```

**Process Flow**:
1. Locates heating service for the tenant
2. Validates service type bridge is correctly set to heating
3. Checks pricing model is hybrid (required for heating calculator)
4. Verifies shared distribution support is enabled
5. Logs compatibility check results

**Compatibility Checks**:
- Heating service exists with correct service type
- Pricing model set to `PricingModel::HYBRID`
- Business logic config includes `supports_shared_distribution: true`
- Configuration schema supports seasonal adjustments
- Distribution method defaults to `BY_AREA` for shared services

---

## Protected Methods

### createDefaultUtilityServices()

Create default utility service templates for a new tenant.

**Service Definitions**:

#### Electricity Service
- **Unit**: kWh
- **Pricing Model**: Time-of-use
- **Features**: Day/night zones, seasonal rates, meter zones required
- **Validation**: Monthly readings, 0-10,000 kWh range, 50% variance threshold

#### Water Service  
- **Unit**: m³
- **Pricing Model**: Consumption-based
- **Features**: Unit rate billing, connection fees, minimum charges
- **Validation**: Monthly readings, 0-1,000 m³ range, 30% variance threshold

#### Heating Service
- **Unit**: kWh
- **Pricing Model**: Hybrid (base fee + consumption)
- **Features**: Seasonal factors, shared distribution, building efficiency
- **Validation**: Monthly readings, 0-5,000 kWh range, 40% variance threshold

#### Gas Service
- **Unit**: m³
- **Pricing Model**: Tiered rates
- **Features**: Multiple rate tiers, connection fees, delivery charges
- **Validation**: Monthly readings, 0-2,000 m³ range, 40% variance threshold

---

### createDefaultMeterConfigurations()

Create default meter configurations for each utility service.

**Configuration Structure**:
```php
[
    'utility_service_id' => int,
    'pricing_model' => PricingModel,
    'is_active' => true,
    'effective_from' => Carbon,
    'distribution_method' => DistributionMethod,
    'is_shared_service' => bool,
    'rate_schedule' => array,
    'configuration_overrides' => array,
]
```

**Service-Specific Configurations**:
- **Electricity**: Equal distribution, zone rates (day: 0.15, night: 0.10)
- **Water**: Equal distribution, unit rate (2.50), connection fee (5.00)
- **Heating**: Area-based distribution, base fee (15.00), seasonal factors
- **Gas**: Equal distribution, tiered rates with connection fee (8.00)

---

### generateUniqueSlug()

Generate a unique slug for a utility service within a tenant.

**Algorithm**:
1. Creates base slug from service name using `Str::slug()`
2. Checks for existing slugs within tenant scope
3. Appends counter if collision detected
4. Returns unique slug within tenant

**Security Features**:
- Tenant-scoped uniqueness prevents cross-tenant conflicts
- Collision detection ensures no duplicate slugs
- Counter-based resolution handles multiple services with similar names

---

## Error Handling

### RuntimeException

Thrown when service initialization fails due to system errors.

**Common Causes**:
- Database connection failures
- Transaction rollback errors
- Invalid tenant state
- Missing required dependencies

**Example**:
```php
try {
    $result = $service->initializeUniversalServices($tenant);
} catch (RuntimeException $e) {
    Log::error('Tenant initialization failed', [
        'tenant_id' => $tenant->id,
        'error' => $e->getMessage(),
    ]);
    // Handle gracefully - perhaps retry or alert administrators
}
```

### InvalidArgumentException

Thrown when tenant data is invalid or inaccessible.

**Common Causes**:
- Null or invalid tenant object
- Tenant without required properties
- Malformed configuration data

---

## Usage Examples

### Complete Tenant Onboarding Flow

```php
use App\Services\TenantInitializationService;

$service = app(TenantInitializationService::class);

// 1. Create new tenant organization
$tenant = Organization::factory()->create([
    'name' => 'Downtown Properties LLC',
    'max_properties' => 50,
    'max_users' => 100,
]);

// 2. Initialize universal services
$result = $service->initializeUniversalServices($tenant);

// 3. Verify services were created
expect($result['utility_services'])->toHaveCount(4);
expect($result['meter_configurations'])->toHaveCount(4);

// 4. Create properties for the tenant
$properties = Property::factory()
    ->count(3)
    ->create(['tenant_id' => $tenant->id]);

// 5. Assign services to properties
$configurations = $service->initializePropertyServiceAssignments(
    $tenant, 
    $result['utility_services']
);

// 6. Verify property assignments
expect($configurations)->toHaveCount(3); // 3 properties
foreach ($configurations as $propertyConfigs) {
    expect($propertyConfigs)->toHaveCount(4); // 4 services each
}

// 7. Verify heating compatibility
$isCompatible = $service->ensureHeatingCompatibility($tenant);
expect($isCompatible)->toBeTrue();
```

---

### Handling Tenants with No Properties

```php
// Create tenant without properties
$tenant = Organization::factory()->create();

// Initialize services (should work without properties)
$result = $service->initializeUniversalServices($tenant);
expect($result['utility_services'])->toHaveCount(4);

// Property assignments should return empty array
$configurations = $service->initializePropertyServiceAssignments(
    $tenant, 
    $result['utility_services']
);
expect($configurations)->toHaveCount(0);

// Later, when properties are added
$property = Property::factory()->create(['tenant_id' => $tenant->id]);
$newConfigurations = $service->initializePropertyServiceAssignments(
    $tenant, 
    $result['utility_services']
);
expect($newConfigurations)->toHaveCount(1);
```

---

### Bulk Tenant Initialization

```php
$tenants = Organization::factory()->count(10)->create();

foreach ($tenants as $tenant) {
    try {
        $result = $service->initializeUniversalServices($tenant);
        
        Log::info('Tenant initialized successfully', [
            'tenant_id' => $tenant->id,
            'services_created' => count($result['utility_services']),
        ]);
        
        // Create sample properties
        $properties = Property::factory()
            ->count(rand(1, 5))
            ->create(['tenant_id' => $tenant->id]);
            
        // Assign services to properties
        $configurations = $service->initializePropertyServiceAssignments(
            $tenant, 
            $result['utility_services']
        );
        
    } catch (RuntimeException $e) {
        Log::error('Tenant initialization failed', [
            'tenant_id' => $tenant->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

## Testing

### Feature Tests

Located in: `tests/Feature/Services/TenantInitializationServiceTest.php`

**Test Coverage**:
- Universal service initialization for new tenants
- Default meter configuration creation
- Property service assignment for multiple properties
- Graceful handling of tenants with no properties
- Duplicate service prevention
- Backward compatibility with heating systems

**Example Test**:
```php
it('initializes universal services for a new tenant', function () {
    $tenant = Organization::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    $result = $this->service->initializeUniversalServices($tenant);
    
    // Verify services were created
    $services = UtilityService::where('tenant_id', $tenant->id)->get();
    expect($services)->toHaveCount(4);
    
    // Verify service types
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

---

### Performance Tests

Located in: `tests/Performance/TenantInitializationPerformanceTest.php`

**Performance Benchmarks**:
- Single tenant initialization: < 500ms
- Batch tenant initialization: < 200ms per tenant average
- Property assignment scaling: Linear with property count
- Database query optimization: < 20 queries per tenant

**Example Performance Test**:
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

---

### Property Tests

Located in: `tests/Property/TenantInitializationPropertyTest.php`

**Property-Based Testing**:
- Service creation invariants across different tenant configurations
- Tenant isolation verification with multiple tenants
- Configuration consistency across property counts
- Heating compatibility preservation

**Example Property Test**:
```php
it('always creates exactly 4 universal services for any valid tenant', function () {
    $tenantCount = fake()->numberBetween(1, 10);
    
    for ($i = 0; $i < $tenantCount; $i++) {
        $tenant = Organization::factory()->create([
            'name' => fake()->company(),
            'max_properties' => fake()->numberBetween(1, 1000),
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

---

## Security Considerations

### Tenant Isolation
- All utility services scoped to specific tenant ID
- Slug uniqueness enforced within tenant scope only
- Property assignments validated for tenant ownership
- Cross-tenant service access prevented

### Data Integrity
- All operations wrapped in database transactions
- Rollback on any failure ensures consistent state
- Unique constraints prevent duplicate services
- Foreign key constraints maintain referential integrity

### Input Validation
- Tenant existence validated before processing
- Service configuration schemas validated
- Enum values validated for pricing models and service types
- Rate schedules validated for numeric values and required fields

---

## Performance Considerations

### Database Optimization
- Uses `exists()` instead of `count()` for collision checking
- Eager loads relationships where needed
- Batch operations for multiple property assignments
- Indexes on tenant_id and slug columns for fast lookups

### Memory Management
- Processes properties in batches for large tenants
- Clears unnecessary object references
- Uses readonly service class to prevent state mutations
- Garbage collection friendly object lifecycle

### Caching Strategy
- Service definitions cached for repeated initializations
- Configuration templates cached per service type
- Slug collision checks optimized with database indexes
- Clear relevant caches after service creation

---

## Integration Points

### Universal Billing Calculator
- Heating service integrates with existing heating calculator
- Service configurations provide pricing model data
- Rate schedules feed into billing calculations
- Seasonal adjustments preserved for heating compatibility

### Meter Reading System
- Meter configurations define reading structure requirements
- Validation rules applied to meter reading inputs
- Zone support configured per service type
- Input methods specified in configuration overrides

### Filament Admin Interface
- Services appear in UtilityService resource automatically
- Configurations manageable through ServiceConfiguration resource
- Tenant-scoped queries ensure proper isolation
- Bulk operations available for service management

---

## Related Documentation

- [Universal Utility Management Spec](.kiro/specs/universal-utility-management/)
- [UtilityService Model Documentation](../models/UtilityService.md)
- [ServiceConfiguration Model Documentation](../models/ServiceConfiguration.md)
- [Multi-Tenancy Architecture](../architecture/multi-tenancy.md)
- [Universal Billing Calculator](./UniversalBillingCalculator.md)
- [Heating Calculator Integration](../integration/heating-calculator.md)

---

## Changelog

### 2024-12-23 - Initial Implementation
- Created TenantInitializationService with universal service support
- Implemented four core utility services (electricity, water, heating, gas)
- Added property-level service assignment functionality
- Ensured backward compatibility with existing heating systems
- Added comprehensive test coverage (feature, performance, property tests)

### 2024-12-23 - Documentation Enhancement
- Added comprehensive DocBlocks to all public and protected methods
- Created detailed service documentation with examples
- Documented testing patterns and performance benchmarks
- Added security considerations and integration points

---

## API Reference

### Method Summary

| Method | Purpose | Transaction | Returns | Throws |
|--------|---------|-------------|---------|---------|
| `initializeUniversalServices()` | Create utility services for tenant | Yes | Services + configurations | RuntimeException |
| `initializePropertyServiceAssignments()` | Assign services to properties | Yes | Property configurations | RuntimeException |
| `ensureHeatingCompatibility()` | Verify heating integration | No | Boolean compatibility | None |

### Service Type Mapping

| Service Key | ServiceType Enum | PricingModel | Distribution Method |
|-------------|------------------|--------------|-------------------|
| electricity | ELECTRICITY | TIME_OF_USE | EQUAL |
| water | WATER | CONSUMPTION_BASED | EQUAL |
| heating | HEATING | HYBRID | BY_AREA |
| gas | GAS | TIERED_RATES | EQUAL |

---

## Support

For questions or issues:
- Review the [Universal Utility Management Spec](.kiro/specs/universal-utility-management/)
- Check [Feature Tests](../../tests/Feature/Services/TenantInitializationServiceTest.php)
- Consult [Multi-Tenancy Documentation](../architecture/multi-tenancy.md)
- Reference [Testing Documentation](../testing/README.md)