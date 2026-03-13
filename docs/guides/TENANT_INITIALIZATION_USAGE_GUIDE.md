# Tenant Initialization Usage Guide

## Quick Start

This guide provides practical examples for using the Tenant Initialization system in different scenarios.

## Basic Usage

### Initialize a New Tenant

```php
use App\Services\TenantInitializationService;
use App\Models\Organization;

// Get the service from the container
$initService = app(TenantInitializationService::class);

// Create or get your tenant
$tenant = Organization::create([
    'name' => 'Acme Property Management',
    'slug' => 'acme-properties',
    'email' => 'admin@acme.com',
]);

// Initialize with default utility services
try {
    $result = $initService->initializeUniversalServices($tenant);
    
    echo "✓ Created {$result->getServiceCount()} utility services\n";
    echo "✓ Generated {$result->getMeterConfigurationCount()} meter configurations\n";
    
    // Access specific services
    $electricityService = $result->getUtilityService('electricity');
    $waterService = $result->getUtilityService('water');
    
} catch (TenantInitializationException $e) {
    echo "✗ Initialization failed: {$e->getMessage()}\n";
}
```

### Assign Services to Existing Properties

```php
// If the tenant already has properties, assign services to them
$properties = Property::where('tenant_id', $tenant->id)->get();

if ($properties->isNotEmpty()) {
    $assignments = $initService->initializePropertyServiceAssignments(
        $tenant, 
        $result->utilityServices
    );
    
    echo "✓ Configured {$assignments->getPropertyCount()} properties\n";
    echo "✓ Created {$assignments->getTotalConfigurationCount()} service configurations\n";
    
    // Access configurations for a specific property
    $propertyId = $properties->first()->id;
    $propertyConfigs = $assignments->getPropertyConfigurations($propertyId);
    
    foreach ($propertyConfigs as $serviceType => $config) {
        echo "  - {$serviceType}: Configuration ID {$config->id}\n";
    }
}
```

### Check Heating Compatibility

```php
// Ensure backward compatibility with existing heating systems
$isCompatible = $initService->ensureHeatingCompatibility($tenant);

if ($isCompatible) {
    echo "✓ Heating system is compatible with universal services\n";
} else {
    echo "⚠ Heating compatibility issues detected\n";
}
```

## Filament Integration

### Resource Action

Add initialization action to your Organization resource:

```php
// In app/Filament/Resources/OrganizationResource.php

use App\Services\TenantInitializationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

protected function getHeaderActions(): array
{
    return [
        Action::make('initializeServices')
            ->label('Initialize Services')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('success')
            ->action(function (Organization $record) {
                $service = app(TenantInitializationService::class);
                
                try {
                    $result = $service->initializeUniversalServices($record);
                    
                    // Also assign to existing properties
                    $assignments = $service->initializePropertyServiceAssignments(
                        $record, 
                        $result->utilityServices
                    );
                    
                    Notification::make()
                        ->title('Services Initialized Successfully')
                        ->body("Created {$result->getServiceCount()} services and configured {$assignments->getPropertyCount()} properties")
                        ->success()
                        ->duration(5000)
                        ->send();
                        
                } catch (TenantInitializationException $e) {
                    Notification::make()
                        ->title('Initialization Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Initialize Utility Services')
            ->modalDescription('This will create default utility services (electricity, water, heating, gas) for this tenant.')
            ->modalSubmitActionLabel('Initialize')
            ->visible(fn (Organization $record) => 
                auth()->user()->can('initialize', $record) && 
                $record->utilityServices()->count() === 0
            );
    ];
}
```

### Bulk Action

Add bulk initialization for multiple tenants:

```php
// In app/Filament/Resources/OrganizationResource.php

use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

public static function table(Table $table): Table
{
    return $table
        ->bulkActions([
            BulkAction::make('initializeServices')
                ->label('Initialize Services')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('success')
                ->action(function (Collection $records) {
                    $service = app(TenantInitializationService::class);
                    $successful = 0;
                    $failed = 0;
                    $errors = [];
                    
                    foreach ($records as $tenant) {
                        try {
                            $result = $service->initializeUniversalServices($tenant);
                            $service->initializePropertyServiceAssignments(
                                $tenant, 
                                $result->utilityServices
                            );
                            $successful++;
                        } catch (TenantInitializationException $e) {
                            $failed++;
                            $errors[] = "{$tenant->name}: {$e->getMessage()}";
                        }
                    }
                    
                    if ($successful > 0) {
                        Notification::make()
                            ->title('Bulk Initialization Complete')
                            ->body("Successfully initialized {$successful} tenants" . 
                                   ($failed > 0 ? ", {$failed} failed" : ""))
                            ->success()
                            ->send();
                    }
                    
                    if ($failed > 0) {
                        Notification::make()
                            ->title('Some Initializations Failed')
                            ->body(implode("\n", array_slice($errors, 0, 3)) . 
                                   ($failed > 3 ? "\n... and " . ($failed - 3) . " more" : ""))
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                })
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation()
                ->modalHeading('Bulk Initialize Services')
                ->modalDescription('Initialize utility services for all selected tenants.')
        ]);
}
```

### Widget for Initialization Status

Create a widget to show initialization status:

```php
// app/Filament/Widgets/TenantInitializationStatusWidget.php

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Organization;

class TenantInitializationStatusWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTenants = Organization::count();
        $initializedTenants = Organization::whereHas('utilityServices')->count();
        $uninitializedTenants = $totalTenants - $initializedTenants;
        $initializationRate = $totalTenants > 0 ? ($initializedTenants / $totalTenants) * 100 : 0;
        
        return [
            Stat::make('Total Tenants', $totalTenants)
                ->description('All registered tenants')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
                
            Stat::make('Initialized Tenants', $initializedTenants)
                ->description('With utility services')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Pending Initialization', $uninitializedTenants)
                ->description('Need service setup')
                ->descriptionIcon('heroicon-m-clock')
                ->color($uninitializedTenants > 0 ? 'warning' : 'success'),
                
            Stat::make('Initialization Rate', number_format($initializationRate, 1) . '%')
                ->description('Completion percentage')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($initializationRate >= 90 ? 'success' : ($initializationRate >= 70 ? 'warning' : 'danger')),
        ];
    }
}
```

## Artisan Commands

### Basic Initialization Command

```php
// app/Console/Commands/InitializeTenantCommand.php

use Illuminate\Console\Command;
use App\Services\TenantInitializationService;
use App\Models\Organization;

class InitializeTenantCommand extends Command
{
    protected $signature = 'tenant:initialize 
                           {tenant : Tenant ID or slug}
                           {--force : Force reinitialize existing services}
                           {--properties : Also initialize property assignments}
                           {--heating-check : Check heating compatibility}';

    protected $description = 'Initialize utility services for a tenant';

    public function handle(TenantInitializationService $service): int
    {
        $tenantIdentifier = $this->argument('tenant');
        
        // Find tenant by ID or slug
        $tenant = Organization::where('id', $tenantIdentifier)
            ->orWhere('slug', $tenantIdentifier)
            ->first();
            
        if (!$tenant) {
            $this->error("Tenant not found: {$tenantIdentifier}");
            return 1;
        }
        
        // Check if already initialized
        if (!$this->option('force') && $tenant->utilityServices()->exists()) {
            $this->warn("Tenant {$tenant->name} already has utility services. Use --force to reinitialize.");
            return 1;
        }
        
        $this->info("Initializing services for tenant: {$tenant->name}");
        
        try {
            // Initialize services
            $result = $service->initializeUniversalServices($tenant);
            $this->info("✓ Created {$result->getServiceCount()} utility services");
            
            // Initialize property assignments if requested
            if ($this->option('properties')) {
                $assignments = $service->initializePropertyServiceAssignments(
                    $tenant, 
                    $result->utilityServices
                );
                $this->info("✓ Configured {$assignments->getPropertyCount()} properties");
            }
            
            // Check heating compatibility if requested
            if ($this->option('heating-check')) {
                $isCompatible = $service->ensureHeatingCompatibility($tenant);
                if ($isCompatible) {
                    $this->info("✓ Heating system is compatible");
                } else {
                    $this->warn("⚠ Heating compatibility issues detected");
                }
            }
            
            $this->info("Tenant initialization completed successfully!");
            return 0;
            
        } catch (TenantInitializationException $e) {
            $this->error("Initialization failed: {$e->getMessage()}");
            return 1;
        }
    }
}
```

### Bulk Initialization Command

```php
// app/Console/Commands/InitializeAllTenantsCommand.php

class InitializeAllTenantsCommand extends Command
{
    protected $signature = 'tenants:initialize-all 
                           {--uninitializedOnly : Only initialize tenants without services}
                           {--batch-size=10 : Number of tenants to process in each batch}
                           {--dry-run : Show what would be done without executing}';

    protected $description = 'Initialize utility services for all tenants';

    public function handle(TenantInitializationService $service): int
    {
        $query = Organization::query();
        
        if ($this->option('uninitializedOnly')) {
            $query->whereDoesntHave('utilityServices');
        }
        
        $tenants = $query->get();
        
        if ($tenants->isEmpty()) {
            $this->info('No tenants found to initialize.');
            return 0;
        }
        
        if ($this->option('dry-run')) {
            $this->info("Would initialize {$tenants->count()} tenants:");
            foreach ($tenants as $tenant) {
                $this->line("  - {$tenant->name} (ID: {$tenant->id})");
            }
            return 0;
        }
        
        $batchSize = (int) $this->option('batch-size');
        $batches = $tenants->chunk($batchSize);
        
        $successful = 0;
        $failed = 0;
        
        foreach ($batches as $batchIndex => $batch) {
            $this->info("Processing batch " . ($batchIndex + 1) . " of {$batches->count()}");
            
            $bar = $this->output->createProgressBar($batch->count());
            
            foreach ($batch as $tenant) {
                try {
                    $result = $service->initializeUniversalServices($tenant);
                    $service->initializePropertyServiceAssignments(
                        $tenant, 
                        $result->utilityServices
                    );
                    $successful++;
                } catch (TenantInitializationException $e) {
                    $this->newLine();
                    $this->error("Failed to initialize {$tenant->name}: {$e->getMessage()}");
                    $failed++;
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine(2);
        }
        
        $this->info("Initialization complete!");
        $this->info("Successful: {$successful}");
        if ($failed > 0) {
            $this->warn("Failed: {$failed}");
        }
        
        return $failed > 0 ? 1 : 0;
    }
}
```

## API Usage

### Initialize via API

```php
// POST /api/tenants/{tenant}/initialize-services

$response = Http::withToken($apiToken)
    ->post("/api/tenants/{$tenantId}/initialize-services", [
        'services' => ['electricity', 'water', 'heating', 'gas'],
        'initialize_properties' => true,
        'check_heating_compatibility' => true,
    ]);

if ($response->successful()) {
    $data = $response->json('data');
    echo "Created {$data['services_created']} services\n";
    echo "Configured {$data['properties_configured']} properties\n";
} else {
    echo "Error: " . $response->json('message') . "\n";
}
```

### Check Initialization Status

```php
// GET /api/tenants/{tenant}/initialization-status

$response = Http::withToken($apiToken)
    ->get("/api/tenants/{$tenantId}/initialization-status");

if ($response->successful()) {
    $data = $response->json('data');
    
    if ($data['is_initialized']) {
        echo "Tenant is initialized with {$data['services_count']} services\n";
    } else {
        echo "Tenant is not initialized\n";
        echo "Missing services: " . implode(', ', $data['missing_services']) . "\n";
    }
}
```

## Event Handling

### Listen for Initialization Events

```php
// app/Listeners/TenantInitializationListener.php

use App\Events\TenantServicesInitialized;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class TenantInitializationListener
{
    public function handle(TenantServicesInitialized $event): void
    {
        $tenant = $event->tenant;
        $result = $event->result;
        
        // Send welcome email
        Mail::to($tenant->email)->send(new TenantWelcomeMail($tenant, $result));
        
        // Create audit log entry
        AuditLog::create([
            'tenant_id' => $tenant->id,
            'action' => 'services_initialized',
            'details' => [
                'services_created' => $result->getServiceCount(),
                'service_types' => $result->getServiceTypes(),
            ],
            'user_id' => auth()->id(),
        ]);
        
        // Update tenant status
        $tenant->update([
            'initialization_status' => 'completed',
            'services_initialized_at' => now(),
        ]);
    }
}
```

### Register Event Listeners

```php
// app/Providers/EventServiceProvider.php

protected $listen = [
    TenantServicesInitialized::class => [
        TenantInitializationListener::class,
        SendWelcomeEmailListener::class,
        UpdateTenantStatusListener::class,
    ],
    PropertyServicesAssigned::class => [
        CreateDefaultMetersListener::class,
        NotifyPropertyManagersListener::class,
    ],
];
```

## Testing Examples

### Feature Test

```php
// tests/Feature/TenantInitializationTest.php

use App\Services\TenantInitializationService;
use App\Models\Organization;
use App\Models\Property;

public function test_complete_tenant_initialization_workflow(): void
{
    // Create tenant with properties
    $tenant = Organization::factory()->create();
    $properties = Property::factory()->count(3)->create(['tenant_id' => $tenant->id]);
    
    $service = app(TenantInitializationService::class);
    
    // Initialize services
    $result = $service->initializeUniversalServices($tenant);
    
    // Verify services created
    expect($result->getServiceCount())->toBe(4);
    expect($result->hasService('electricity'))->toBeTrue();
    expect($result->hasService('water'))->toBeTrue();
    expect($result->hasService('heating'))->toBeTrue();
    expect($result->hasService('gas'))->toBeTrue();
    
    // Assign to properties
    $assignments = $service->initializePropertyServiceAssignments(
        $tenant, 
        $result->utilityServices
    );
    
    // Verify property assignments
    expect($assignments->getPropertyCount())->toBe(3);
    expect($assignments->getTotalConfigurationCount())->toBe(12); // 3 properties × 4 services
    
    // Verify database state
    $this->assertDatabaseCount('utility_services', 4);
    $this->assertDatabaseCount('service_configurations', 12);
    
    // Verify heating compatibility
    $isCompatible = $service->ensureHeatingCompatibility($tenant);
    expect($isCompatible)->toBeTrue();
}
```

### Unit Test

```php
// tests/Unit/TenantInitializationServiceTest.php

public function test_creates_utility_services_with_correct_attributes(): void
{
    $tenant = Organization::factory()->create();
    
    $service = app(TenantInitializationService::class);
    $result = $service->initializeUniversalServices($tenant);
    
    $electricityService = $result->getUtilityService('electricity');
    
    expect($electricityService)->not->toBeNull();
    expect($electricityService->tenant_id)->toBe($tenant->id);
    expect($electricityService->service_type_bridge->value)->toBe('electricity');
    expect($electricityService->is_active)->toBeTrue();
    expect($electricityService->slug)->toMatch('/^electricity-service(-\d+)?$/');
}
```

## Troubleshooting

### Common Issues

#### 1. Tenant Already Initialized
```php
// Check if tenant already has services
if ($tenant->utilityServices()->exists()) {
    // Handle existing services
    $this->warn('Tenant already has utility services');
    
    // Option 1: Skip initialization
    return;
    
    // Option 2: Reinitialize (be careful!)
    $tenant->utilityServices()->delete();
    $result = $service->initializeUniversalServices($tenant);
}
```

#### 2. Missing Dependencies
```php
// Ensure all required services are registered
try {
    $service = app(TenantInitializationService::class);
} catch (BindingResolutionException $e) {
    // Register missing services in AppServiceProvider
    $this->app->singleton(TenantInitializationService::class);
}
```

#### 3. Database Transaction Issues
```php
// Handle transaction rollback
DB::beginTransaction();
try {
    $result = $service->initializeUniversalServices($tenant);
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    Log::error('Tenant initialization failed', [
        'tenant_id' => $tenant->id,
        'error' => $e->getMessage(),
    ]);
    throw $e;
}
```

### Debugging

#### Enable Debug Logging
```php
// In config/logging.php
'channels' => [
    'tenant_initialization' => [
        'driver' => 'single',
        'path' => storage_path('logs/tenant-initialization.log'),
        'level' => 'debug',
    ],
],

// Use in service
Log::channel('tenant_initialization')->debug('Service creation started', [
    'tenant_id' => $tenant->id,
    'service_type' => $serviceType,
]);
```

#### Performance Monitoring
```php
// Monitor initialization performance
$startTime = microtime(true);
$result = $service->initializeUniversalServices($tenant);
$duration = microtime(true) - $startTime;

if ($duration > 5.0) { // More than 5 seconds
    Log::warning('Slow tenant initialization', [
        'tenant_id' => $tenant->id,
        'duration' => $duration,
        'services_created' => $result->getServiceCount(),
    ]);
}
```

## Best Practices

1. **Always use try-catch blocks** when calling initialization methods
2. **Check tenant state** before initialization to avoid conflicts
3. **Use database transactions** for data consistency
4. **Log all operations** for debugging and auditing
5. **Handle partial failures** gracefully with appropriate rollback
6. **Monitor performance** for large tenants with many properties
7. **Test thoroughly** with different tenant configurations
8. **Use events** for decoupled post-initialization tasks

## Related Documentation

- [TenantInitializationService API](../services/TENANT_INITIALIZATION_SERVICE.md)
- [Tenant Initialization Architecture](../architecture/TENANT_INITIALIZATION_ARCHITECTURE.md)
- [Multi-Tenant Development Guide](MULTI_TENANT_DEVELOPMENT.md)
- [Filament Resource Development](FILAMENT_RESOURCE_DEVELOPMENT.md)