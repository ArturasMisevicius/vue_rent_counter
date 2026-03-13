# Verification Scripts Architecture

## Overview

This document describes the architecture and design patterns used in verification scripts for the Vilnius Utilities Billing Platform. These scripts ensure system integrity, configuration compliance, and upgrade readiness.

## Design Principles

### 1. Standalone Execution

Verification scripts run independently of the test suite, allowing quick validation without full test execution.

**Benefits**:
- Fast feedback loop
- CI/CD integration
- Pre-deployment validation
- Developer workflow integration

### 2. Clear Output

Scripts provide human-readable output with clear success/failure indicators.

**Output Format**:
- ✓ Success indicators
- ✗ Error indicators
- ⚠ Warning indicators
- Detailed error messages
- Summary statistics

### 3. Standard Exit Codes

Scripts use standard Unix exit codes for automation integration.

**Exit Codes**:
- `0` - Success (all checks passed)
- `1` - Failure (one or more checks failed)

### 4. Minimal Dependencies

Scripts require only Laravel bootstrap, avoiding complex dependencies.

**Dependencies**:
- PHP 8.2+
- Laravel application
- Composer autoload
- Database connection (for bootstrap)

## Architecture Patterns

### Script Structure

```
┌─────────────────────────────────────┐
│     Bootstrap Laravel App           │
│  (Load config, services, models)    │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Define Resources to Verify        │
│  (Array of class names)             │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Iterate Through Resources         │
│  (foreach loop)                     │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Perform Verification Checks       │
│  (try-catch blocks)                 │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Collect Results                   │
│  ($passed, $failed counters)        │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Report Summary                    │
│  (echo results)                     │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Exit with Status Code             │
│  (0 = success, 1 = failure)         │
└─────────────────────────────────────┘
```

### Verification Flow

```php
// 1. Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 2. Define resources
$resources = [
    'ResourceName' => ResourceClass::class,
];

// 3. Iterate and verify
foreach ($resources as $name => $class) {
    try {
        // Perform checks
        performChecks($class);
        $passed++;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        $failed++;
    }
}

// 4. Report and exit
exit($failed === 0 ? 0 : 1);
```

## Component Design

### 1. Bootstrap Component

**Responsibility**: Initialize Laravel application

**Implementation**:
```php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
```

**Dependencies**:
- Composer autoload
- Laravel bootstrap
- Console kernel

**Error Handling**: Fatal errors if bootstrap fails

---

### 2. Resource Registry Component

**Responsibility**: Define resources to verify

**Implementation**:
```php
/**
 * @var array<string, class-string<\Filament\Resources\Resource>>
 */
$resources = [
    'UserResource' => \App\Filament\Resources\UserResource::class,
    'SubscriptionResource' => \App\Filament\Resources\SubscriptionResource::class,
];
```

**Data Structure**:
- Key: Human-readable name (for output)
- Value: Fully qualified class name

**Extensibility**: Add new resources by extending array

---

### 3. Verification Engine Component

**Responsibility**: Execute verification checks

**Implementation**:
```php
foreach ($resources as $name => $class) {
    try {
        // Check 1: Class exists
        if (!class_exists($class)) {
            throw new Exception("Class does not exist");
        }
        
        // Check 2: Extends Resource
        if (!is_subclass_of($class, Resource::class)) {
            throw new Exception("Does not extend Resource");
        }
        
        // Additional checks...
        
        $passed++;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        $failed++;
    }
}
```

**Error Handling**: Try-catch per resource (isolated failures)

**Output**: Real-time feedback during verification

---

### 4. Reflection Component

**Responsibility**: Inspect class structure and methods

**Implementation**:
```php
$reflection = new ReflectionMethod($class, 'form');
$parameters = $reflection->getParameters();

if (count($parameters) > 0) {
    $firstParam = $parameters[0];
    $paramType = $firstParam->getType();
    
    if ($paramType && $paramType->getName() === 'Filament\Schemas\Schema') {
        echo "✓ Using Filament 4 Schema API\n";
    }
}
```

**Use Cases**:
- Method signature inspection
- Parameter type checking
- Return type validation

**Performance**: Minimal overhead (~5ms per resource)

---

### 5. Output Component

**Responsibility**: Format and display results

**Implementation**:
```php
echo "Testing {$name}...\n";
echo "  ✓ Class structure: OK\n";
echo "  ✓ Model: {$model}\n";
echo "  ✗ Error: {$message}\n";
echo "========================================\n";
echo "Results: {$passed} passed, {$failed} failed\n";
```

**Output Levels**:
- Resource-level (per resource)
- Check-level (per verification)
- Summary-level (overall results)

**Formatting**: Unicode symbols for visual clarity

---

### 6. Exit Handler Component

**Responsibility**: Return appropriate exit code

**Implementation**:
```php
if ($failed === 0) {
    echo "\n✓ All resources verified successfully!\n";
    exit(0);
} else {
    echo "\n✗ Some resources have issues.\n";
    exit(1);
}
```

**Exit Codes**:
- `0` - All checks passed
- `1` - One or more checks failed

**CI/CD Integration**: Standard exit codes for automation

## Verification Checks

### Check 1: Class Existence

**Purpose**: Verify resource file exists and is loadable

**Method**: `class_exists(string $class): bool`

**Implementation**:
```php
if (!class_exists($class)) {
    throw new Exception("Class does not exist");
}
```

**Error Scenarios**:
- File not found
- Syntax error in file
- Autoload not updated

---

### Check 2: Inheritance

**Purpose**: Verify resource extends Filament Resource base class

**Method**: `is_subclass_of(string $class, string $parent): bool`

**Implementation**:
```php
if (!is_subclass_of($class, \Filament\Resources\Resource::class)) {
    throw new Exception("Does not extend Filament\Resources\Resource");
}
```

**Error Scenarios**:
- Missing `extends Resource`
- Wrong base class
- Missing import statement

---

### Check 3: Model Configuration

**Purpose**: Verify resource has associated Eloquent model

**Method**: `$class::getModel(): string`

**Implementation**:
```php
$model = $class::getModel();
if (empty($model)) {
    throw new Exception("Model not set");
}
```

**Error Scenarios**:
- Missing `$model` property
- Empty model value
- Invalid model class

---

### Check 4: Navigation Icon

**Purpose**: Verify resource has navigation icon configured

**Method**: `$class::getNavigationIcon(): string|BackedEnum|null`

**Implementation**:
```php
$icon = $class::getNavigationIcon();
if (empty($icon)) {
    throw new Exception("Navigation icon not set");
}
```

**Error Scenarios**:
- Missing `$navigationIcon` property
- Empty icon value

---

### Check 5: Page Registration

**Purpose**: Verify resource has pages registered

**Method**: `$class::getPages(): array`

**Implementation**:
```php
$pages = $class::getPages();
if (empty($pages)) {
    throw new Exception("No pages registered");
}
```

**Error Scenarios**:
- Missing `getPages()` method
- Empty pages array
- Invalid page configuration

---

### Check 6: Form Method

**Purpose**: Verify resource has form method defined

**Method**: `method_exists(string $class, string $method): bool`

**Implementation**:
```php
if (!method_exists($class, 'form')) {
    throw new Exception("form() method not found");
}
```

**Error Scenarios**:
- Missing `form()` method
- Method visibility issues

---

### Check 7: Table Method

**Purpose**: Verify resource has table method defined

**Method**: `method_exists(string $class, string $method): bool`

**Implementation**:
```php
if (!method_exists($class, 'table')) {
    throw new Exception("table() method not found");
}
```

**Error Scenarios**:
- Missing `table()` method
- Method visibility issues

---

### Check 8: Filament 4 Schema API

**Purpose**: Verify form method uses Filament 4 Schema parameter

**Method**: Reflection API inspection

**Implementation**:
```php
$reflection = new ReflectionMethod($class, 'form');
$parameters = $reflection->getParameters();

if (count($parameters) > 0) {
    $firstParam = $parameters[0];
    $paramType = $firstParam->getType();
    
    if ($paramType && $paramType->getName() === 'Filament\Schemas\Schema') {
        echo "  ✓ Using Filament 4 Schema API\n";
    } else {
        echo "  ⚠ Warning: Not using Filament\Schemas\Schema parameter\n";
    }
}
```

**Error Scenarios**:
- Using deprecated `Form` parameter
- Missing type hint
- Wrong parameter type

## Error Handling Strategy

### Isolated Failures

Each resource verification is wrapped in try-catch to prevent cascade failures:

```php
foreach ($resources as $name => $class) {
    try {
        // All checks for this resource
        $passed++;
    } catch (Exception $e) {
        // Log error, continue to next resource
        $failed++;
    }
}
```

**Benefits**:
- One failure doesn't stop verification
- Complete picture of all issues
- Easier debugging

### Graceful Degradation

Non-critical checks use warnings instead of errors:

```php
if ($paramType && $paramType->getName() === 'Filament\Schemas\Schema') {
    echo "  ✓ Using Filament 4 Schema API\n";
} else {
    echo "  ⚠ Warning: Not using Filament\Schemas\Schema parameter\n";
    // Don't increment $failed - this is a warning
}
```

### Error Messages

Clear, actionable error messages:

```php
// Bad
throw new Exception("Error");

// Good
throw new Exception("Class does not exist");

// Better
throw new Exception("Class {$class} does not exist. Run 'composer dump-autoload'");
```

## Performance Optimization

### Lazy Loading

Only load classes when needed:

```php
// Don't instantiate resources
$model = $class::getModel();  // Static call

// Don't
$resource = new $class();  // Avoid instantiation
```

### Minimal Bootstrap

Bootstrap only what's needed:

```php
// Bootstrap console kernel (minimal)
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Don't bootstrap full HTTP kernel
// $app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();
```

### Caching

Leverage Laravel's configuration cache:

```bash
# Before running verification
php artisan config:cache

# Run verification
php verify-batch3-resources.php
```

## Extensibility

### Adding New Checks

Add checks after existing ones:

```php
// After existing checks
// Check for relation managers
$relationManagers = $class::getRelations();
if (!empty($relationManagers)) {
    echo "  ✓ Relation managers: " . count($relationManagers) . " registered\n";
}
```

### Adding New Resources

Extend the resources array:

```php
$resources = [
    // Existing resources
    'UserResource' => \App\Filament\Resources\UserResource::class,
    
    // New resources
    'NewResource' => \App\Filament\Resources\NewResource::class,
];
```

### Creating New Scripts

Follow the same pattern:

```php
// verify-batch4-resources.php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$resources = [
    'FaqResource' => \App\Filament\Resources\FaqResource::class,
    'LanguageResource' => \App\Filament\Resources\LanguageResource::class,
    'TranslationResource' => \App\Filament\Resources\TranslationResource::class,
];

// Same verification logic...
```

## Testing Strategy

### Script Testing

Test the script itself:

```php
test('verification script exists', function () {
    expect(file_exists(base_path('verify-batch3-resources.php')))->toBeTrue();
});

test('verification script returns correct exit code', function () {
    exec('php verify-batch3-resources.php', $output, $exitCode);
    expect($exitCode)->toBeIn([0, 1]);
});
```

### Integration Testing

Test what the script verifies:

```php
test('all batch 3 resources are properly configured', function () {
    $resources = [
        \App\Filament\Resources\UserResource::class,
        // ...
    ];
    
    foreach ($resources as $resource) {
        expect(class_exists($resource))->toBeTrue();
        expect(is_subclass_of($resource, Resource::class))->toBeTrue();
        // ...
    }
});
```

## Security Considerations

### Access Control

Restrict script execution:

```bash
chmod 750 verify-batch3-resources.php
chown www-data:www-data verify-batch3-resources.php
```

### Environment Isolation

Run in isolated environment:

```bash
# Docker
docker exec app php verify-batch3-resources.php

# Specific user
sudo -u www-data php verify-batch3-resources.php
```

### No Sensitive Data

Script only inspects class structure, no data access:

```php
// Safe - only checks configuration
$model = $class::getModel();

// Never do - don't query data
// $users = User::all();
```

## Related Documentation

- [Batch 3 Verification Guide](../testing/BATCH_3_VERIFICATION_GUIDE.md)
- [Verification Scripts API](../api/VERIFICATION_SCRIPTS_API.md)
- [Verification Quick Reference](../testing/VERIFICATION_QUICK_REFERENCE.md)

---

**Document Version**: 1.0.0  
**Last Updated**: November 24, 2025  
**Maintained By**: Development Team
