# PolicyRegistry Quick Reference

## Overview

The `PolicyRegistry` provides secure, defensive policy registration for Laravel authorization with comprehensive error handling and performance optimization.

## Basic Usage

### Registration
```php
$registry = new PolicyRegistry();

// Register all model policies
$policyResults = $registry->registerModelPolicies();

// Register settings gates
$gateResults = $registry->registerSettingsGates();
```

### Validation
```php
// Validate configuration without registration
$validation = $registry->validateConfiguration();

if (!$validation['valid']) {
    // Handle configuration issues
}
```

### Statistics
```php
$results = $registry->registerModelPolicies();
// Returns: ['registered' => int, 'skipped' => int, 'errors' => array]
```

## Key Features

### ✅ Security Features
- **Authorization Control**: Only super_admin or app boot can register
- **Secure Logging**: Hashes sensitive data before logging
- **Data Protection**: Sanitizes error messages
- **Secure Caching**: SHA-256 hashed cache keys

### ✅ Performance Features
- **Cached Class Checks**: 1-hour TTL for class existence
- **Batch Operations**: Efficient registration processing
- **Performance Metrics**: Timing and success rate monitoring
- **Memory Efficient**: Readonly class with minimal footprint

### ✅ Defensive Programming
- **Graceful Degradation**: Continues with partial failures
- **Error Collection**: Comprehensive error tracking
- **Comprehensive Validation**: Pre-registration validation
- **Fail-Safe Operation**: System continues under adverse conditions

## Configuration

### Model Policies
```php
private const MODEL_POLICIES = [
    \App\Models\User::class => \App\Policies\UserPolicy::class,
    \App\Models\Tariff::class => \App\Policies\TariffPolicy::class,
    \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
    // ... more mappings
];
```

### Settings Gates
```php
private const SETTINGS_GATES = [
    'viewSettings' => [\App\Policies\SettingsPolicy::class, 'viewSettings'],
    'updateSettings' => [\App\Policies\SettingsPolicy::class, 'updateSettings'],
    'runBackup' => [\App\Policies\SettingsPolicy::class, 'runBackup'],
    'clearCache' => [\App\Policies\SettingsPolicy::class, 'clearCache'],
];
```

## Authorization

### Authorized Contexts
- ✅ **Application Boot**: No authenticated user (during startup)
- ✅ **Super Admin**: Users with `super_admin` role
- ❌ **Regular Users**: All other authenticated users

### Authorization Check
```php
private function isAuthorizedForPolicyRegistration(): bool
{
    // Allow during application boot
    if (!app()->bound('auth') || !auth()->hasUser()) {
        return true;
    }
    
    // Allow for superadmin users only
    $user = auth()->user();
    return $user && method_exists($user, 'hasRole') && $user->hasRole('super_admin');
}
```

## Error Handling

### Error Types
- **Configuration Errors**: Missing classes or methods
- **Runtime Errors**: Registration failures
- **Authorization Errors**: Unauthorized access attempts

### Error Response Format
```php
[
    'registered' => 10,    // Successfully registered
    'skipped' => 2,        // Skipped due to errors
    'errors' => [          // Error details (sanitized)
        'ModelName' => 'configuration invalid'
    ]
]
```

### Secure Logging
```php
// Sensitive data is hashed
Log::warning('Policy registration: Model class missing', [
    'model_hash' => hash('sha256', $model),
    'context' => 'policy_registration'
]);
```

## Performance

### Caching
- **Cache Key**: `policy_registry_class_exists.{sha256_hash}`
- **TTL**: 3600 seconds (1 hour)
- **Purpose**: Avoid repeated class existence checks

### Metrics
```php
Log::debug("Policy registration completed", [
    'registered' => $registered,
    'skipped' => $skipped,
    'errors_count' => count($errors),
    'duration_ms' => round($duration * 1000, 2),
]);
```

## Integration

### AppServiceProvider
```php
private function bootPolicies(): void
{
    $policyRegistry = new PolicyRegistry();
    
    $policyResults = $policyRegistry->registerModelPolicies();
    $gateResults = $policyRegistry->registerSettingsGates();
    
    // Environment-specific logging
    if (app()->environment('local', 'testing')) {
        logger()->info('Policy registration completed', [
            'policies' => $policyResults,
            'gates' => $gateResults,
        ]);
    }
}
```

### Laravel Gate
```php
// Registers with Laravel's Gate system
Gate::policy($model, $policy);
Gate::define($gate, [$policy, $method]);
```

## Testing

### Test Expectations (Defensive Approach)
```php
// Allows graceful degradation
$this->assertGreaterThanOrEqual(0, $result['registered']);
$this->assertIsArray($result['errors']);
```

### Security Tests
```php
// Authorization tests
$this->expectException(AuthorizationException::class);
$this->registry->registerModelPolicies(); // As regular user

// Data protection tests
$this->assertStringNotContainsString('App\\Models\\', $error);
```

## Troubleshooting

### Common Issues

1. **Authorization Failures**
   ```php
   // Check user role
   $user = auth()->user();
   $hasRole = $user->hasRole('super_admin');
   ```

2. **Missing Classes**
   ```php
   // Validate configuration
   $validation = $registry->validateConfiguration();
   dd($validation['policies']['errors']);
   ```

3. **Performance Issues**
   ```php
   // Check cache status
   $cacheKey = 'policy_registry_class_exists.' . hash('sha256', $class);
   $cached = Cache::has($cacheKey);
   ```

### Debug Commands
```php
// Validation check
$registry = new PolicyRegistry();
$validation = $registry->validateConfiguration();

// Registration statistics
$results = $registry->registerModelPolicies();
logger()->info('Registration stats', $results);

// Cache inspection
Cache::flush(); // Clear policy registry cache
```

## Best Practices

### DO:
- ✅ Use validation before deployment
- ✅ Monitor registration statistics
- ✅ Handle errors gracefully
- ✅ Respect authorization boundaries
- ✅ Cache class existence checks
- ✅ Log performance metrics

### DON'T:
- ❌ Skip authorization checks
- ❌ Expose sensitive data in logs
- ❌ Ignore error statistics
- ❌ Bypass defensive patterns
- ❌ Assume perfect conditions
- ❌ Skip performance monitoring

## Environment Considerations

### Development
- Verbose logging enabled
- All errors logged for debugging
- Performance metrics collected

### Testing
- Relaxed error expectations
- Defensive behavior validation
- Security boundary testing

### Production
- Secure logging only
- Error rate monitoring
- Performance alerting

## Related Commands

```bash
# Run policy registry tests
php artisan test --filter=PolicyRegistryTest

# Clear policy cache
php artisan cache:clear

# Validate configuration (custom command)
php artisan policy:validate

# Monitor registration (logs)
tail -f storage/logs/laravel.log | grep "Policy registration"
```

## Related Documentation

- [PolicyRegistry Security Guide](POLICY_REGISTRY_SECURITY_GUIDE.md)
- [PolicyRegistry Testing Guide](../testing/POLICY_REGISTRY_TESTING_GUIDE.md)
- [PolicyRegistry Architecture](../architecture/POLICY_REGISTRY_ARCHITECTURE.md)
- [Authorization Quick Reference](AUTHORIZATION_QUICK_REFERENCE.md)