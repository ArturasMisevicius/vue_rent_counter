# TenantInitializationService Documentation

## Overview

The `TenantInitializationService` is a core service responsible for initializing new tenants with default utility service templates and configurations in the multi-tenant utilities billing platform. It extends existing tenant creation to include universal service setup alongside existing heating initialization while maintaining backward compatibility.

## Purpose

This service creates the foundational utility services (electricity, water, heating, gas) for new tenants and handles property-level service assignments. It ensures that each tenant starts with a complete set of utility services configured according to their specific needs and regional requirements.

## Architecture

### Dependencies

The service uses dependency injection to manage its collaborators:

- `ServiceDefinitionProvider` - Provides default service definitions and templates
- `MeterConfigurationProvider` - Creates meter configurations for utility services  
- `PropertyServiceAssigner` - Handles assignment of services to properties
- `TenantValidator` - Validates tenant data before operations
- `SlugGeneratorService` - Generates unique slugs for utility services

### Key Components

```php
final readonly class TenantInitializationService
{
    use LogsTenantOperations;

    private const OPERATION_UNIVERSAL_SERVICES = 'universal_services_initialization';
    private const OPERATION_PROPERTY_ASSIGNMENT = 'property_service_assignment';
    private const OPERATION_HEATING_COMPATIBILITY = 'heating_compatibility_check';
}
```

## Core Methods

### initializeUniversalServices()

Initializes a new tenant with default utility service templates.

**Signature:**
```php
public function initializeUniversalServices(Organization $tenant): InitializationResult
```

**Parameters:**
- `$tenant` - The organization/tenant to initialize services for

**Returns:**
- `InitializationResult` - DTO containing created services and meter configurations

**Throws:**
- `TenantInitializationException` - If service initialization fails

**Example Usage:**
```php
$service = app(TenantInitializationService::class);
$result = $service->initializeUniversalServices($tenant);

// Access created services
$electricityService = $result->getUtilityService('electricity');
$heatingConfig = $result->getMeterConfiguration('heating');

echo "Created {$result->getServiceCount()} services";
echo "Created {$result->getMeterConfigurationCount()} meter configurations";
```

**Process Flow:**
1. Validates tenant data using `TenantValidator`
2. Starts database transaction
3. Creates default utility services (electricity, water, heating, gas)
4. Generates meter configurations for each service
5. Logs operation progress and results
6. Returns `InitializationResult` with created resources

### initializePropertyServiceAssignments()

Assigns utility services to existing properties owned by the tenant.

**Signature:**
```php
public function initializePropertyServiceAssignments(
    Organization $tenant, 
    Collection|array $utilityServices
): PropertyServiceAssignmentResult
```

**Parameters:**
- `$tenant` - The tenant whose properties to configure
- `$utilityServices` - Collection or array of utility services to assign

**Returns:**
- `PropertyServiceAssignmentResult` - Service configurations grouped by property ID

**Example Usage:**
```php
$result = $service->initializeUniversalServices($tenant);
$assignments = $service->initializePropertyServiceAssignments(
    $tenant, 
    $result->utilityServices
);

// Access configurations for specific property
$propertyConfigs = $assignments->getPropertyConfigurations($propertyId);
$electricityConfig = $assignments->getPropertyServiceConfiguration($propertyId, 'electricity');
```

### ensureHeatingCompatibility()

Validates backward compatibility with existing heating systems.

**Signature:**
```php
public function ensureHeatingCompatibility(Organization $tenant): bool
```

**Parameters:**
- `$tenant` - The tenant to check heating compatibility for

**Returns:**
- `bool` - True if heating service is compatible, false otherwise

**Example Usage:**
```php
$isCompatible = $service->ensureHeatingCompatibility($tenant);

if (!$isCompatible) {
    // Handle heating compatibility issues
    Log::warning("Heating compatibility check failed for tenant {$tenant->id}");
}
```

## Service Creation Process

### Default Services Created

The service creates four default utility services for each tenant:

1. **Electricity Service**
   - Pricing Model: Time-of-use or consumption-based
   - Supports zones (day/night rates)
   - Photo verification required

2. **Water Service** 
   - Pricing Model: Consumption-based
   - Monotonic reading validation
   - No photo verification required

3. **Heating Service**
   - Pricing Model: Hybrid (fixed + consumption)
   - Seasonal adjustments supported
   - Integration with existing heating calculator

4. **Gas Service**
   - Pricing Model: Tiered rates
   - Monotonic reading validation
   - Photo verification required

### Global Template System

The service supports a global template system:

```php
// Check for global template
$globalTemplate = UtilityService::where('is_global_template', true)
    ->where('service_type_bridge', $definition['service_type_bridge'])
    ->first();

if ($globalTemplate) {
    // Create tenant-specific copy from template
    return $globalTemplate->createTenantCopy($tenant->id, [
        'name' => $definition['name'],
        'description' => $definition['description'],
    ]);
}
```

## Error Handling

The service uses comprehensive error handling with specific exception types:

### Exception Types

- `TenantInitializationException::serviceCreationFailed()` - Service creation failure
- `TenantInitializationException::propertyAssignmentFailed()` - Property assignment failure  
- `TenantInitializationException::invalidTenantData()` - Invalid tenant data
- `TenantInitializationException::heatingCompatibilityFailed()` - Heating compatibility failure

### Error Context

All errors include contextual information:
- Tenant ID and name
- Operation being performed
- User ID (if authenticated)
- Timestamp
- Stack trace and error details

## Logging

The service uses the `LogsTenantOperations` trait for comprehensive logging:

### Log Levels

- **Info** - Operation start/success, informational messages
- **Warning** - Non-critical issues (e.g., no properties found)
- **Error** - Operation failures with full exception details
- **Debug** - Detailed operation progress (development only)

### Log Structure

```php
[
    'tenant_id' => $tenant->id,
    'tenant_name' => $tenant->name,
    'operation' => $operation,
    'user_id' => auth()->id(),
    'timestamp' => now()->toISOString(),
    'status' => 'success|error|warning|info',
    // Additional context...
]
```

## Performance Considerations

### Database Transactions

All operations are wrapped in database transactions to ensure data consistency:

```php
return DB::transaction(function () use ($tenant) {
    // All service creation operations
});
```

### Slug Generation Caching

Unique slug generation is cached to improve performance:

```php
$cacheKey = "tenant_slugs:{$tenantId}:{$baseSlug}";
return Cache::remember($cacheKey, self::CACHE_TTL, function () {
    // Slug uniqueness check
});
```

### Batch Operations

The service processes multiple services and properties in batches to minimize database queries.

## Integration Points

### Filament Resources

The service integrates with Filament resources for tenant management:

```php
// In TenantResource
Action::make('initializeServices')
    ->action(function (Organization $record) {
        $service = app(TenantInitializationService::class);
        $result = $service->initializeUniversalServices($record);
        
        Notification::make()
            ->title("Initialized {$result->getServiceCount()} services")
            ->success()
            ->send();
    });
```

### Artisan Commands

Can be used in Artisan commands for bulk tenant initialization:

```php
// In InitializeTenantsCommand
foreach ($tenants as $tenant) {
    $result = $this->initializationService->initializeUniversalServices($tenant);
    $this->info("Initialized tenant {$tenant->name} with {$result->getServiceCount()} services");
}
```

### Event Integration

The service can dispatch events for external integrations:

```php
// After successful initialization
event(new TenantServicesInitialized($tenant, $result));
```

## Testing

### Unit Tests

Test individual methods with mocked dependencies:

```php
public function test_initializes_universal_services_successfully(): void
{
    $tenant = Organization::factory()->create();
    
    $result = $this->service->initializeUniversalServices($tenant);
    
    expect($result->getServiceCount())->toBe(4);
    expect($result->hasService('electricity'))->toBeTrue();
    expect($result->hasService('water'))->toBeTrue();
    expect($result->hasService('heating'))->toBeTrue();
    expect($result->hasService('gas'))->toBeTrue();
}
```

### Integration Tests

Test complete workflows with real database:

```php
public function test_complete_tenant_initialization_workflow(): void
{
    $tenant = Organization::factory()->create();
    $properties = Property::factory()->count(3)->create(['tenant_id' => $tenant->id]);
    
    // Initialize services
    $result = $this->service->initializeUniversalServices($tenant);
    
    // Assign to properties
    $assignments = $this->service->initializePropertyServiceAssignments(
        $tenant, 
        $result->utilityServices
    );
    
    expect($assignments->getPropertyCount())->toBe(3);
    expect($assignments->getTotalConfigurationCount())->toBe(12); // 3 properties × 4 services
}
```

## Configuration

### Service Definitions

Service definitions are provided by `ServiceDefinitionProvider`:

```php
[
    'electricity' => [
        'name' => 'Electricity Service',
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::TIME_OF_USE,
        'service_type_bridge' => ServiceType::ELECTRICITY,
        // ... additional configuration
    ],
    // ... other services
]
```

### Validation Rules

Each service type has specific validation rules:

```php
'electricity' => [
    'max_consumption' => 10000,
    'variance_threshold' => 0.5,
    'require_monotonic' => true,
    'photo_verification_required' => true,
],
```

## Best Practices

### Service Registration

Register the service in `AppServiceProvider`:

```php
$this->app->singleton(TenantInitializationService::class);
```

### Error Handling

Always handle exceptions when using the service:

```php
try {
    $result = $service->initializeUniversalServices($tenant);
} catch (TenantInitializationException $e) {
    Log::error('Tenant initialization failed', [
        'tenant_id' => $tenant->id,
        'error' => $e->getMessage(),
    ]);
    
    // Handle error appropriately
}
```

### Validation

Always validate tenant data before initialization:

```php
// The service handles this internally, but you can also validate beforehand
if (!$tenant->exists || empty($tenant->name)) {
    throw new InvalidArgumentException('Invalid tenant data');
}
```

## Related Documentation

- [ServiceDefinitionProvider Documentation](SERVICE_DEFINITION_PROVIDER.md)
- [MeterConfigurationProvider Documentation](METER_CONFIGURATION_PROVIDER.md)
- [PropertyServiceAssigner Documentation](PROPERTY_SERVICE_ASSIGNER.md)
- [Universal Utility Management Specification](.kiro/specs/universal-utility-management/)
- [Multi-Tenant Architecture Guide](../architecture/MULTI_TENANT_ARCHITECTURE.md)