# Policy Registry Defensive Registration

## Overview

The PolicyRegistry implements defensive registration patterns to gracefully handle missing policy or model classes during development and deployment. This prevents runtime errors and provides comprehensive statistics about registration success/failure.

## Key Features

### Defensive Class Existence Checking
- Validates model and policy classes exist before registration
- Prevents `Class not found` errors during development
- Gracefully handles incomplete policy implementations

### Comprehensive Statistics Tracking
- Returns detailed arrays with `registered`, `skipped`, and `errors` counts
- Provides specific error messages for debugging
- Enables monitoring of policy registration health

### Robust Error Handling
- Catches and logs registration exceptions
- Continues processing even if individual policies fail
- Provides actionable error messages for developers

## Implementation Details

### Model Policy Registration

```php
public function registerModelPolicies(): array
{
    $registered = 0;
    $skipped = 0;
    $errors = [];
    
    foreach (self::MODEL_POLICIES as $model => $policy) {
        $modelName = class_basename($model);
        
        // Defensive checks
        if (!class_exists($model)) {
            $errors[$modelName] = "Model class {$model} does not exist";
            $skipped++;
            continue;
        }
        
        if (!class_exists($policy)) {
            $errors[$modelName] = "Policy class {$policy} does not exist";
            $skipped++;
            continue;
        }
        
        try {
            Gate::policy($model, $policy);
            $registered++;
        } catch (\Throwable $e) {
            $errors[$modelName] = "Failed to register policy: {$e->getMessage()}";
            $skipped++;
        }
    }
    
    return [
        'registered' => $registered,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}
```

### Settings Gate Registration

```php
public function registerSettingsGates(): array
{
    $registered = 0;
    $skipped = 0;
    $errors = [];
    
    foreach (self::SETTINGS_GATES as $gate => [$policy, $method]) {
        // Defensive checks
        if (!class_exists($policy)) {
            $errors[$gate] = "Policy class {$policy} does not exist";
            $skipped++;
            continue;
        }
        
        if (!method_exists($policy, $method)) {
            $errors[$gate] = "Method {$method} does not exist on {$policy}";
            $skipped++;
            continue;
        }
        
        try {
            Gate::define($gate, [$policy, $method]);
            $registered++;
        } catch (\Throwable $e) {
            $errors[$gate] = "Failed to register gate: {$e->getMessage()}";
            $skipped++;
        }
    }
    
    return [
        'registered' => $registered,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}
```

## Benefits

### Development Environment
- Prevents crashes when policies are being developed incrementally
- Allows partial policy implementation during feature development
- Provides clear feedback about missing components

### Production Environment
- Graceful degradation if policies are missing due to deployment issues
- Comprehensive logging for debugging authorization problems
- Statistics for monitoring policy registration health

### Testing Environment
- Enables testing with partial policy sets
- Provides statistics for test assertions
- Allows mocking of missing policies

## Usage Patterns

### In AppServiceProvider

```php
public function boot(): void
{
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
    
    // Alert on production issues
    if (app()->environment('production')) {
        if ($policyResults['errors'] || $gateResults['errors']) {
            logger()->warning('Policy registration issues detected', [
                'policy_errors' => $policyResults['errors'],
                'gate_errors' => $gateResults['errors'],
            ]);
        }
    }
}
```

### In Tests

```php
public function test_registers_all_available_policies(): void
{
    $registry = new PolicyRegistry();
    $result = $registry->registerModelPolicies();
    
    // Assert no errors occurred
    expect($result['errors'])->toBeEmpty();
    
    // Assert reasonable number of policies registered
    expect($result['registered'])->toBeGreaterThan(10);
    
    // Assert no policies were skipped in test environment
    expect($result['skipped'])->toBe(0);
}

public function test_handles_missing_policy_gracefully(): void
{
    // Test with missing policy class
    $registry = new PolicyRegistry();
    $result = $registry->registerModelPolicies();
    
    // Should not throw exception
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['registered', 'skipped', 'errors']);
}
```

## Configuration Validation

The registry includes validation methods to check configuration health:

```php
public function validateConfiguration(): array
{
    $policyValidation = $this->validatePolicies();
    $gateValidation = $this->validateGates();
    
    return [
        'valid' => $policyValidation['invalid'] === 0 && $gateValidation['invalid'] === 0,
        'policies' => $policyValidation,
        'gates' => $gateValidation,
    ];
}
```

## Monitoring and Alerting

### Development Monitoring
- Log all registration attempts with debug level
- Display statistics in development dashboard
- Alert developers to missing policies

### Production Monitoring
- Track registration success rates
- Alert on policy registration failures
- Monitor authorization coverage

## Best Practices

### DO:
- ✅ Always check registration statistics in tests
- ✅ Log registration results in development
- ✅ Monitor policy coverage in production
- ✅ Use validation methods before deployment
- ✅ Handle missing policies gracefully

### DON'T:
- ❌ Ignore registration errors in production
- ❌ Skip validation in CI/CD pipelines
- ❌ Assume all policies will always be available
- ❌ Fail silently on policy registration issues

## Related Documentation

- `app/Support/ServiceRegistration/PolicyRegistry.php` - Implementation
- `tests/Unit/Support/ServiceRegistration/PolicyRegistryTest.php` - Test coverage
- `docs/architecture/service-provider-refactoring-summary.md` - Overall architecture
- `docs/security/authorization-patterns.md` - Authorization best practices

## Changelog

### v1.1.0 - Defensive Registration
- Added class existence checks for models and policies
- Implemented comprehensive statistics tracking
- Added method existence validation for gates
- Enhanced error handling and logging
- Improved test coverage for edge cases

This defensive approach ensures robust policy registration while providing excellent observability and debugging capabilities.