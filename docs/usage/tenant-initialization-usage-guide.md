# Tenant Initialization Usage Guide

## Quick Start

This guide provides practical examples for using the Tenant Initialization Service in common scenarios.

## Basic Usage

### 1. Initialize a New Tenant

```php
use App\Services\TenantInitializationService;
use App\Models\Organization;

// Get the service instance
$initService = app(TenantInitializationService::class);

// Create or get your tenant
$tenant = Organization::find(1);

// Initialize universal services
$result = $initService->initializeUniversalServices($tenant);

if ($result->isSuccessful()) {
    echo "Created {$result->getServiceCount()} services";
    echo "Created {$result->getMeterConfigurationCount()} meter configurations";
}
```

### 2. Assign Services to Properties

```php
// After initializing services, assign them to properties
$assignments = $initService->initializePropertyServiceAssignments(
    $tenant, 
    $result->utilityServices
);

echo "Configured {$assignments->getPropertyCount()} properties";
echo "Total configurations: {$assignments->getTotalConfigurationCount()}";
```

### 3. Set Up Meter Configurations

```php
// Initialize meter configurations for properties
$meterConfigs = $initService->initializeDefaultMeterConfigurations(
    $tenant, 
    $assignments
);

foreach ($meterConfigs as $propertyId => $configs) {
    echo "Property {$propertyId} has " . count($configs) . " meter configurations";
}
```

## Advanced Usage

### Custom Service Selection

```php
// Initialize only specific services
$serviceDefinitions = [
    'electricity' => $serviceProvider->getElectricityServiceDefinition(),
    'water' => $serviceProvider->getWaterServiceDefinition(),
];

// Create services manually for custom scenarios
foreach ($serviceDefinitions as $key => $definition) {
    $service = UtilityService::create([
        'tenant_id' => $tenant->id,
        'name' => $definition['name'],
        'slug' => Str::slug($definition['name']),
        // ... other fields
    ]);
}
```

### Property-Specific Configuration

```php
use App\Services\TenantInitialization\PropertyConfigurationCustomizer;

$customizer = app(PropertyConfigurationCustomizer::class);

// Get customized configuration for a specific property
$config = $customizer->getPropertySpecificConfiguration(
    'electricity',
    $electricityService,
    $property,
    $tenant
);

// The configuration will include:
// - Property type adjustments (commercial vs residential)
// - Regional defaults (Lithuanian vs EU)
// - Provider assignments
// - Rate schedules
```

## Common Scenarios

### Scenario 1: Lithuanian Property Management Company

```php
// Create tenant with Lithuanian settings
$tenant = Organization::create([
    'name' => 'Vilnius Property Management',
    'locale' => 'lt',
    'timezone' => 'Europe/Vilnius',
    'currency' => 'EUR',
]);

// Initialize services (will apply Lithuanian defaults)
$result = $initService->initializeUniversalServices($tenant);

// Services will have Lithuanian rates:
// - Electricity: 0.1547 EUR/kWh (day), 0.1047 EUR/kWh (night)
// - Water: 1.89 EUR/m³
// - Heating: 12.50 EUR base fee, 0.0687 EUR/kWh
// - Gas: Tiered rates starting at 0.3890 EUR/m³
```

### Scenario 2: Commercial Property Setup

```php
// Create commercial property
$office = Property::create([
    'tenant_id' => $tenant->id,
    'name' => 'Downtown Office Building',
    'type' => PropertyType::OFFICE,
    'area_sqm' => 500.0,
]);

// Assign services (will apply commercial rates)
$assignments = $initService->initializePropertyServiceAssignments($tenant, $services);

// Commercial adjustments applied:
// - Higher electricity rates with demand charges
// - Commercial water rates with sewer charges
// - Enhanced monitoring for large properties
```

### Scenario 3: Apartment Building with Shared Services

```php
// Create apartment building
$building = Building::create([
    'tenant_id' => $tenant->id,
    'name' => 'Residential Complex A',
]);

$apartment = Property::create([
    'tenant_id' => $tenant->id,
    'building_id' => $building->id,
    'type' => PropertyType::APARTMENT,
    'area_sqm' => 75.0,
]);

// Assign services (heating and water will be shared)
$assignments = $initService->initializePropertyServiceAssignments($tenant, $services);

// Shared service configuration:
// - Heating: shared service with area-based distribution
// - Water: may be shared depending on building setup
// - Electricity: individual meters
```

## Working with Results

### InitializationResult

```php
$result = $initService->initializeUniversalServices($tenant);

// Check success
if ($result->isSuccessful()) {
    // Access individual services
    $electricityService = $result->getUtilityService('electricity');
    $waterService = $result->getUtilityService('water');
    
    // Access meter configurations
    $electricityConfig = $result->getMeterConfiguration('electricity');
    
    // Get counts
    $serviceCount = $result->getServiceCount();
    $configCount = $result->getMeterConfigurationCount();
    
    // Convert to array for API responses
    $data = $result->toArray();
}
```

### PropertyServiceAssignmentResult

```php
$assignments = $initService->initializePropertyServiceAssignments($tenant, $services);

// Access configurations by property
$propertyConfigs = $assignments->getPropertyConfigurations($propertyId);

// Access specific service configuration
$electricityConfig = $assignments->getPropertyServiceConfiguration($propertyId, 'electricity');

// Get all property IDs
$propertyIds = $assignments->getPropertyIds();

// Get summary information
$propertyCount = $assignments->getPropertyCount();
$totalConfigs = $assignments->getTotalConfigurationCount();
```

## Error Handling

### Basic Error Handling

```php
use App\Exceptions\TenantInitializationException;

try {
    $result = $initService->initializeUniversalServices($tenant);
} catch (TenantInitializationException $e) {
    // Handle specific initialization errors
    Log::error('Tenant initialization failed', [
        'tenant_id' => $e->getTenantId(),
        'operation' => $e->getOperation(),
        'error' => $e->getMessage(),
    ]);
    
    // Get error context
    $context = $e->getContext();
}
```

### Specific Error Types

```php
try {
    $result = $initService->initializeUniversalServices($tenant);
} catch (TenantInitializationException $e) {
    switch ($e->getOperation()) {
        case 'service_creation':
            // Handle service creation failure
            break;
        case 'property_assignment':
            // Handle property assignment failure
            break;
        case 'validation':
            // Handle validation errors
            break;
        case 'heating_compatibility':
            // Handle heating compatibility issues
            break;
    }
}
```

## Integration with Controllers

### API Controller Example

```php
use App\Http\Controllers\Controller;
use App\Services\TenantInitializationService;
use App\Http\Resources\InitializationResultResource;

class TenantInitializationController extends Controller
{
    public function __construct(
        private readonly TenantInitializationService $initService
    ) {}
    
    public function initializeServices(Organization $tenant)
    {
        try {
            $result = $this->initService->initializeUniversalServices($tenant);
            
            return response()->json([
                'success' => true,
                'data' => new InitializationResultResource($result),
            ], 201);
        } catch (TenantInitializationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INITIALIZATION_FAILED',
                    'message' => $e->getMessage(),
                    'context' => $e->getContext(),
                ],
            ], 422);
        }
    }
    
    public function assignProperties(Organization $tenant, Request $request)
    {
        $services = UtilityService::where('tenant_id', $tenant->id)->get();
        
        $assignments = $this->initService->initializePropertyServiceAssignments(
            $tenant, 
            $services
        );
        
        return response()->json([
            'success' => true,
            'data' => [
                'properties_configured' => $assignments->getPropertyCount(),
                'total_configurations' => $assignments->getTotalConfigurationCount(),
            ],
        ]);
    }
}
```

### Filament Action Example

```php
use Filament\Actions\Action;
use App\Services\TenantInitializationService;

Action::make('initializeServices')
    ->label('Initialize Utility Services')
    ->action(function (Organization $record, TenantInitializationService $service) {
        try {
            $result = $service->initializeUniversalServices($record);
            
            Notification::make()
                ->title('Services Initialized')
                ->body("Created {$result->getServiceCount()} utility services")
                ->success()
                ->send();
                
        } catch (TenantInitializationException $e) {
            Notification::make()
                ->title('Initialization Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    })
    ->requiresConfirmation()
    ->modalDescription('This will create default utility services for this tenant.');
```

## Artisan Commands

### Create Initialization Command

```php
use Illuminate\Console\Command;
use App\Services\TenantInitializationService;

class InitializeTenantCommand extends Command
{
    protected $signature = 'tenant:initialize {tenant_id} {--services=*} {--properties}';
    protected $description = 'Initialize utility services for a tenant';
    
    public function handle(TenantInitializationService $service)
    {
        $tenantId = $this->argument('tenant_id');
        $tenant = Organization::findOrFail($tenantId);
        
        $this->info("Initializing services for tenant: {$tenant->name}");
        
        // Initialize services
        $result = $service->initializeUniversalServices($tenant);
        $this->info("Created {$result->getServiceCount()} services");
        
        // Initialize properties if requested
        if ($this->option('properties')) {
            $assignments = $service->initializePropertyServiceAssignments(
                $tenant, 
                $result->utilityServices
            );
            $this->info("Configured {$assignments->getPropertyCount()} properties");
        }
        
        $this->info('Tenant initialization completed successfully');
    }
}
```

## Testing Examples

### Unit Test Example

```php
use Tests\TestCase;
use App\Services\TenantInitializationService;
use App\Models\Organization;

class TenantInitializationServiceTest extends TestCase
{
    public function test_initializes_services_successfully()
    {
        $tenant = Organization::factory()->create([
            'locale' => 'lt',
            'timezone' => 'Europe/Vilnius',
        ]);
        
        $service = app(TenantInitializationService::class);
        $result = $service->initializeUniversalServices($tenant);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(4, $result->getServiceCount());
        
        // Verify Lithuanian rates applied
        $electricityService = $result->getUtilityService('electricity');
        $this->assertNotNull($electricityService);
    }
}
```

### Feature Test Example

```php
public function test_api_initializes_tenant_services()
{
    $tenant = Organization::factory()->create();
    
    $response = $this->actingAs($this->admin)
        ->postJson("/api/tenants/{$tenant->id}/initialize-services", [
            'services' => ['electricity', 'water', 'heating', 'gas'],
        ]);
    
    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'initialization_result' => [
                    'services_created' => 4,
                ],
            ],
        ]);
}
```

## Performance Tips

### Batch Processing

```php
// For multiple tenants, process in batches
$tenants = Organization::where('needs_initialization', true)->get();

foreach ($tenants->chunk(10) as $tenantBatch) {
    foreach ($tenantBatch as $tenant) {
        try {
            $result = $initService->initializeUniversalServices($tenant);
            $tenant->update(['needs_initialization' => false]);
        } catch (TenantInitializationException $e) {
            Log::error("Failed to initialize tenant {$tenant->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    // Small delay between batches
    usleep(100000); // 100ms
}
```

### Caching Optimization

```php
// Pre-warm caches for better performance
Cache::remember('service_definitions', 3600, function () {
    return app(ServiceDefinitionProvider::class)->getDefaultServiceDefinitions();
});

// Use cached provider lookups
$providers = Cache::remember('utility_providers', 1800, function () {
    return Provider::with('tariffs')->get()->groupBy('service_type');
});
```

## Troubleshooting

### Common Issues

1. **Tenant Validation Errors**
   ```php
   // Ensure tenant is properly saved
   $tenant->save();
   $result = $initService->initializeUniversalServices($tenant);
   ```

2. **Missing Provider Assignments**
   ```php
   // Check if providers exist for service types
   $providers = Provider::whereIn('service_type', [
       ServiceType::ELECTRICITY,
       ServiceType::WATER,
       ServiceType::HEATING,
       ServiceType::GAS,
   ])->get();
   ```

3. **Heating Compatibility Issues**
   ```php
   // Check heating compatibility before initialization
   $compatible = $initService->ensureHeatingCompatibility($tenant);
   if (!$compatible) {
       // Handle compatibility issues
   }
   ```

### Debug Mode

```php
// Enable detailed logging for debugging
Log::info('Starting tenant initialization', [
    'tenant_id' => $tenant->id,
    'tenant_name' => $tenant->name,
    'locale' => $tenant->locale,
]);

$result = $initService->initializeUniversalServices($tenant);

Log::info('Tenant initialization completed', [
    'services_created' => $result->getServiceCount(),
    'configurations_created' => $result->getMeterConfigurationCount(),
]);
```

## Related Documentation

- [Tenant Initialization Service Documentation](../services/tenant-initialization-service.md)
- [Tenant Initialization API Documentation](../api/tenant-initialization-api.md)
- [Universal Utility Management Specification](../../.kiro/specs/universal-utility-management/)
- [Service Configuration Guide](../services/service-configuration.md)