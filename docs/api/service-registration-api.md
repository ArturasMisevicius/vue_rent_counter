# Service Registration API Documentation

## Overview

The Service Registration system provides a centralized, defensive approach to registering Laravel services, policies, observers, and events. All registry classes implement defensive patterns that gracefully handle missing classes and provide comprehensive statistics.

## PolicyRegistry API

### Class: `App\Support\ServiceRegistration\PolicyRegistry`

Centralizes policy registration and gate definitions with defensive class existence checking.

#### Methods

##### `registerModelPolicies(): array`

Registers all model policies with defensive class existence checking.

**Returns:**
```php
[
    'registered' => int,  // Number of successfully registered policies
    'skipped' => int,     // Number of skipped policies due to missing classes
    'errors' => array     // Associative array of model => error_message
]
```

**Example:**
```php
$registry = new PolicyRegistry();
$result = $registry->registerModelPolicies();

// Check results
if ($result['errors']) {
    Log::warning('Policy registration issues', $result['errors']);
}

Log::info("Registered {$result['registered']} policies, skipped {$result['skipped']}");
```

**Error Conditions:**
- Model class does not exist
- Policy class does not exist
- Gate registration throws exception

##### `registerSettingsGates(): array`

Registers all settings gates with defensive class and method existence checking.

**Returns:**
```php
[
    'registered' => int,  // Number of successfully registered gates
    'skipped' => int,     // Number of skipped gates due to missing classes/methods
    'errors' => array     // Associative array of gate => error_message
]
```

**Example:**
```php
$registry = new PolicyRegistry();
$result = $registry->registerSettingsGates();

// Monitor gate registration health
if ($result['skipped'] > 0) {
    Log::warning("Skipped {$result['skipped']} gates", $result['errors']);
}
```

**Error Conditions:**
- Policy class does not exist
- Policy method does not exist
- Gate definition throws exception

##### `getModelPolicies(): array`

Returns the complete model-to-policy mapping configuration.

**Returns:**
```php
[
    'App\Models\User' => 'App\Policies\UserPolicy',
    'App\Models\Invoice' => 'App\Policies\InvoicePolicy',
    // ... more mappings
]
```

##### `getSettingsGates(): array`

Returns the complete gate configuration.

**Returns:**
```php
[
    'viewSettings' => ['App\Policies\SettingsPolicy', 'viewSettings'],
    'updateSettings' => ['App\Policies\SettingsPolicy', 'updateSettings'],
    // ... more gates
]
```

##### `validateConfiguration(): array`

Validates the entire policy and gate configuration without registering anything.

**Returns:**
```php
[
    'valid' => bool,      // True if all configurations are valid
    'policies' => [
        'valid' => int,   // Number of valid policy configurations
        'invalid' => int, // Number of invalid policy configurations
        'errors' => array // Policy validation errors
    ],
    'gates' => [
        'valid' => int,   // Number of valid gate configurations
        'invalid' => int, // Number of invalid gate configurations
        'errors' => array // Gate validation errors
    ]
]
```

**Example:**
```php
$registry = new PolicyRegistry();
$validation = $registry->validateConfiguration();

if (!$validation['valid']) {
    Log::error('Policy configuration invalid', [
        'policy_errors' => $validation['policies']['errors'],
        'gate_errors' => $validation['gates']['errors']
    ]);
}
```

## ServiceRegistry API

### Class: `App\Support\ServiceRegistration\ServiceRegistry`

Centralizes service registration with dependency injection patterns.

#### Methods

##### `registerCoreServices(): void`

Registers all core application services including billing, security, validation, tenant, and utility services.

**Example:**
```php
$registry = new ServiceRegistry($app);
$registry->registerCoreServices();
```

**Registered Service Categories:**
- Billing services (BillingService, TariffResolver, etc.)
- Security services (InputSanitizer, SecurityHeaderService, etc.)
- Validation services (TimeRangeValidator, ServiceValidationEngine)
- Tenant services (TenantInitializationService, TenantManagementService)
- Utility services (SystemHealthService, QueryOptimizationService)
- Localization services (TranslationCacheService, TenantTranslationService)

##### `registerCompatibilityServices(): void`

Registers Laravel 12 compatibility services.

**Example:**
```php
$registry = new ServiceRegistry($app);
$registry->registerCompatibilityServices();
```

## ObserverRegistry API

### Class: `App\Support\ServiceRegistration\ObserverRegistry`

Centralizes Eloquent observer registration.

#### Methods

##### `registerModelObservers(): void`

Registers all model observers.

##### `registerSuperadminObservers(): void`

Registers superadmin audit observers.

##### `registerCacheInvalidationObservers(): void`

Registers cache invalidation observers.

## EventRegistry API

### Class: `App\Support\ServiceRegistration\EventRegistry`

Centralizes event listener, rate limiter, and view composer registration.

#### Methods

##### `registerSecurityEvents(): void`

Registers security event listeners.

##### `registerAuthenticationEvents(): void`

Registers authentication event listeners.

##### `registerViewComposers(): void`

Registers view composers for navigation and shared data.

##### `registerRateLimiters(): void`

Registers rate limiters for admin and API routes.

##### `registerCollectionMacros(): void`

Registers custom collection macros.

## CompatibilityRegistry API

### Class: `App\Support\ServiceRegistration\CompatibilityRegistry`

Handles Filament v4 and Laravel 12 compatibility.

#### Methods

##### `registerFilamentCompatibility(): void`

Registers Filament v4 class aliases for backward compatibility.

##### `registerTranslationCompatibility(): void`

Registers translation compatibility for backup package and Laravel 12.

##### `getFilamentAliases(): array`

Returns all registered Filament aliases.

## Usage Patterns

### In AppServiceProvider

```php
use App\Support\ServiceRegistration\{
    ServiceRegistry,
    PolicyRegistry,
    ObserverRegistry,
    EventRegistry,
    CompatibilityRegistry
};

public function register(): void
{
    // Register services
    $serviceRegistry = new ServiceRegistry($this->app);
    $serviceRegistry->registerCoreServices();
    $serviceRegistry->registerCompatibilityServices();
}

public function boot(): void
{
    // Register compatibility
    $compatibilityRegistry = new CompatibilityRegistry();
    $compatibilityRegistry->registerTranslationCompatibility();
    $compatibilityRegistry->registerFilamentCompatibility();
    
    // Register observers
    $observerRegistry = new ObserverRegistry();
    $observerRegistry->registerModelObservers();
    $observerRegistry->registerSuperadminObservers();
    $observerRegistry->registerCacheInvalidationObservers();
    
    // Register policies with statistics tracking
    $policyRegistry = new PolicyRegistry();
    $policyResults = $policyRegistry->registerModelPolicies();
    $gateResults = $policyRegistry->registerSettingsGates();
    
    // Log results in development
    if (app()->environment('local', 'testing')) {
        logger()->info('Policy registration completed', [
            'policies' => $policyResults,
            'gates' => $gateResults,
        ]);
    }
    
    // Register events
    $eventRegistry = new EventRegistry();
    $eventRegistry->registerSecurityEvents();
    $eventRegistry->registerAuthenticationEvents();
    $eventRegistry->registerViewComposers();
    $eventRegistry->registerRateLimiters();
    $eventRegistry->registerCollectionMacros();
}
```

### In Tests

```php
public function test_policy_registration_statistics(): void
{
    $registry = new PolicyRegistry();
    $result = $registry->registerModelPolicies();
    
    // Assert structure
    expect($result)->toHaveKeys(['registered', 'skipped', 'errors']);
    
    // Assert no errors in test environment
    expect($result['errors'])->toBeEmpty();
    
    // Assert policies were registered
    expect($result['registered'])->toBeGreaterThan(0);
}

public function test_service_registration_integration(): void
{
    $registry = new ServiceRegistry($this->app);
    $registry->registerCoreServices();
    
    // Assert services are bound
    expect($this->app->bound(BillingService::class))->toBeTrue();
    expect($this->app->bound(TariffResolver::class))->toBeTrue();
}
```

### Monitoring and Alerting

```php
// In production monitoring
$registry = new PolicyRegistry();
$validation = $registry->validateConfiguration();

if (!$validation['valid']) {
    // Alert operations team
    Log::critical('Policy configuration invalid', $validation);
    
    // Send notification
    Notification::route('slack', config('logging.slack.webhook'))
        ->notify(new PolicyConfigurationAlert($validation));
}
```

## Error Handling

All registry methods implement defensive patterns:

1. **Class Existence Checks**: Verify classes exist before registration
2. **Method Existence Checks**: Verify methods exist on policy classes
3. **Exception Handling**: Catch and log registration exceptions
4. **Statistics Tracking**: Return detailed success/failure statistics
5. **Graceful Degradation**: Continue processing even if individual registrations fail

## Best Practices

### DO:
- ✅ Always check registration statistics in production
- ✅ Log registration results for debugging
- ✅ Use validation methods in CI/CD pipelines
- ✅ Monitor policy coverage and registration health
- ✅ Handle missing classes gracefully

### DON'T:
- ❌ Ignore registration errors or statistics
- ❌ Skip validation in deployment processes
- ❌ Assume all classes will always be available
- ❌ Fail silently on registration issues

## Related Documentation

- [Policy Registry Defensive Registration](../architecture/policy-registry-defensive-registration.md)
- [Service Provider Refactoring Summary](../architecture/service-provider-refactoring-summary.md)
- [Authorization Patterns](../security/authorization-patterns.md)
- [Testing Service Registration](../testing/service-registration-testing.md)