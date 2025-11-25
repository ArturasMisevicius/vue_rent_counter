# Verification Scripts API Documentation

## Overview

This document provides API-level documentation for verification scripts used in the Vilnius Utilities Billing Platform. These scripts validate system configuration, resource compliance, and upgrade readiness.

## Scripts

### verify-batch3-resources.php

**Purpose**: Verifies Filament 4 API compliance for Batch 3 resources (User, Subscription, Organization, OrganizationActivityLog).

**Location**: `verify-batch3-resources.php` (project root)

**Version**: 1.0.0

**Since**: Laravel 12.x, Filament 4.x

**Command Line Interface**:
```bash
php verify-batch3-resources.php
```

**Parameters**: None

**Exit Codes**:
- `0` - All resources verified successfully
- `1` - One or more resources have issues

**Verification Checks**:
1. Class existence and inheritance
2. Model configuration (`protected static ?string $model`)
3. Navigation icon setup (`protected static ?string $navigationIcon`)
4. Page registration (`getPages()` method)
5. Form method presence (`form(Schema $schema): Schema`)
6. Table method presence (`table(Table $table): Table`)
7. Filament 4 Schema API usage (validates parameter type)

**Resources Verified**:
- **UserResource**: Hierarchical user management with role-based access control
- **SubscriptionResource**: Subscription lifecycle management with quota enforcement
- **OrganizationResource**: Multi-tenant organization management
- **OrganizationActivityLogResource**: Audit trail for organization-level actions

**Performance**:
- Execution time: <1 second
- Memory usage: <50MB
- Database queries: 0 (reflection-based checks only)

**Output Format**:
```
Verifying Batch 3 Filament Resources...

Testing UserResource...
  ✓ Class structure: OK
  ✓ Model: App\Models\User
  ✓ Icon: heroicon-o-users
  ✓ Pages: 3 registered
  ✓ Using Filament 4 Schema API
  ✓ UserResource is properly configured

========================================
Results: 4 passed, 0 failed
========================================

✓ All Batch 3 resources are properly configured for Filament 4!
```

**Related Documentation**:
- [Batch 3 Verification Guide](../testing/BATCH_3_VERIFICATION_GUIDE.md)
- [Batch 3 Resources Migration](../upgrades/BATCH_3_RESOURCES_MIGRATION.md)
- [Laravel 12 + Filament 4 Upgrade](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)

---

### verify-models.php

**Purpose**: Verifies Eloquent model configuration including casts, relationships, and Laravel 12 compatibility.

**Location**: `verify-models.php` (project root)

**Version**: 1.0.0

**Since**: Laravel 12.x, Filament 4.x

**Models Verified**: User, Building, Property, Tenant, Provider, Tariff, Meter, MeterReading, MeterReadingAudit, Invoice, InvoiceItem

**Command Line Interface**:
```bash
php verify-models.php
```

**Parameters**: None

**Exit Codes**:
- `0` - All models verified successfully (implicit)

**Verification Checks**:
1. Model instantiation (class existence)
2. Enum casts (UserRole, PropertyType, ServiceType, MeterType, InvoiceStatus)
3. Date/datetime casts (lease dates, billing periods, reading dates)
4. Decimal casts (gyvatukas values, meter readings, invoice amounts)
5. Array/JSON casts (tariff configuration, meter reading snapshots)
6. Boolean casts (supports_zones)
7. Relationship documentation (40+ relationships)

**Performance**:
- Execution time: <1 second
- Memory usage: <10MB
- Database queries: 0 (no queries executed)

**Output Format**:
```
Verifying Eloquent Models...

✓ User model: role cast = UserRole::class
✓ Building model: gyvatukas_summer_average cast = decimal:2
...

--- Verifying Relationships ---

✓ User relationships: property(), parentUser(), childUsers(), ...
...

✅ All models verified successfully!
```

**Related Documentation**:
- [Model Verification Guide](../testing/MODEL_VERIFICATION_GUIDE.md)
- [Eloquent Relationships Guide](../architecture/ELOQUENT_RELATIONSHIPS_GUIDE.md)

---

## API Reference

### Script Execution

#### Command Line Interface

```bash
php verify-batch3-resources.php
```

**Parameters**: None

**Environment Requirements**:
- PHP 8.2+ (8.3+ recommended)
- Laravel 12.x application bootstrapped
- Database connection configured
- Composer autoload available

**Exit Codes**:
- `0` - All resources verified successfully
- `1` - One or more resources have issues

---

### Verification Checks

The script performs the following checks on each resource:

#### 1. Class Existence Check

**Method**: `class_exists(string $class): bool`

**Validates**: Resource class file exists and is loadable

**Error Message**: `"Class does not exist"`

**Resolution**: Ensure resource file exists at correct path

---

#### 2. Inheritance Check

**Method**: `is_subclass_of(string $class, string $parent): bool`

**Validates**: Resource extends `Filament\Resources\Resource`

**Error Message**: `"Does not extend Filament\Resources\Resource"`

**Resolution**: Update class declaration to extend Resource base class

---

#### 3. Model Configuration Check

**Method**: `$class::getModel(): string`

**Validates**: Resource has associated Eloquent model

**Error Message**: `"Model not set"`

**Resolution**: Define `protected static ?string $model` property

---

#### 4. Navigation Icon Check

**Method**: `$class::getNavigationIcon(): string|BackedEnum|null`

**Validates**: Resource has navigation icon configured

**Error Message**: `"Navigation icon not set"`

**Resolution**: Define `protected static ?string $navigationIcon` property

---

#### 5. Page Registration Check

**Method**: `$class::getPages(): array`

**Validates**: Resource has pages registered (List, Create, Edit, View)

**Error Message**: `"No pages registered"`

**Resolution**: Implement `getPages()` method with page routes

---

#### 6. Form Method Check

**Method**: `method_exists(string $class, string $method): bool`

**Validates**: Resource has `form()` method defined

**Error Message**: `"form() method not found"`

**Resolution**: Implement `form(Schema $schema): Schema` method

---

#### 7. Table Method Check

**Method**: `method_exists(string $class, string $method): bool`

**Validates**: Resource has `table()` method defined

**Error Message**: `"table() method not found"`

**Resolution**: Implement `table(Table $table): Table` method

---

#### 8. Filament 4 Schema API Check

**Method**: Reflection API inspection of method signature

**Validates**: Form method uses `Filament\Schemas\Schema` parameter

**Warning Message**: `"Warning: Not using Filament\Schemas\Schema parameter"`

**Resolution**: Update method signature to use Schema type hint

**Example**:
```php
// Filament 3 (deprecated)
public static function form(Form $form): Form

// Filament 4 (correct)
public static function form(Schema $schema): Schema
```

---

## Output Format

### Success Output

```
Verifying Batch 3 Filament Resources...

Testing {ResourceName}...
  ✓ Class structure: OK
  ✓ Model: {ModelClass}
  ✓ Icon: {IconName}
  ✓ Pages: {Count} registered
  ✓ Using Filament 4 Schema API
  ✓ {ResourceName} is properly configured

========================================
Results: {passed} passed, {failed} failed
========================================

✓ All Batch 3 resources are properly configured for Filament 4!
```

### Failure Output

```
Verifying Batch 3 Filament Resources...

Testing {ResourceName}...
  ✗ Error: {ErrorMessage}

========================================
Results: {passed} passed, {failed} failed
========================================

✗ Some resources have issues that need to be addressed.
```

---

## Data Structures

### Resource Configuration Array

```php
/**
 * @var array<string, class-string<\Filament\Resources\Resource>>
 */
$resources = [
    'UserResource' => \App\Filament\Resources\UserResource::class,
    'SubscriptionResource' => \App\Filament\Resources\SubscriptionResource::class,
    'OrganizationResource' => \App\Filament\Resources\OrganizationResource::class,
    'OrganizationActivityLogResource' => \App\Filament\Resources\OrganizationActivityLogResource::class,
];
```

**Structure**:
- **Key**: Human-readable resource name (string)
- **Value**: Fully qualified class name (class-string)

---

### Verification Result Counters

```php
$passed = 0;  // Count of resources that passed all checks
$failed = 0;  // Count of resources that failed any check
```

---

## Error Handling

### Exception Handling

The script uses try-catch blocks to handle verification errors gracefully:

```php
try {
    // Perform verification checks
    $passed++;
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
    $failed++;
}
```

**Error Types**:
- `Exception` - Generic verification failure
- `ReflectionException` - Method reflection failure (caught implicitly)

---

## Integration Patterns

### CI/CD Integration

```bash
#!/bin/bash
# Example CI/CD script

echo "Running Batch 3 resource verification..."
php verify-batch3-resources.php

if [ $? -eq 0 ]; then
    echo "✓ Verification passed"
    exit 0
else
    echo "✗ Verification failed"
    exit 1
fi
```

### Pre-Deployment Hook

```bash
#!/bin/bash
# .git/hooks/pre-push

echo "Verifying Filament resources before push..."
php verify-batch3-resources.php || exit 1
```

### Composer Script

Add to `composer.json`:

```json
{
    "scripts": {
        "verify:batch3": "php verify-batch3-resources.php",
        "verify:all": [
            "@verify:batch3"
        ]
    }
}
```

Usage:
```bash
composer verify:batch3
composer verify:all
```

---

## Extension API

### Adding New Resources

To verify additional resources, extend the `$resources` array:

```php
$resources = [
    // Existing resources
    'UserResource' => \App\Filament\Resources\UserResource::class,
    
    // Add new resources
    'NewResource' => \App\Filament\Resources\NewResource::class,
];
```

### Adding Custom Checks

To add custom verification logic:

```php
// After existing checks, before success message
// Check for relation managers
$relationManagers = $class::getRelations();
if (!empty($relationManagers)) {
    echo "  ✓ Relation managers: " . count($relationManagers) . " registered\n";
} else {
    echo "  ⚠ Warning: No relation managers registered\n";
}

// Check for custom authorization
if (method_exists($class, 'canViewAny')) {
    echo "  ✓ Authorization methods defined\n";
}

// Check for widgets
if (method_exists($class, 'getWidgets')) {
    $widgets = $class::getWidgets();
    if (!empty($widgets)) {
        echo "  ✓ Widgets: " . count($widgets) . " registered\n";
    }
}
```

---

## Performance Considerations

### Execution Time

**Typical Runtime**: < 1 second for 4 resources

**Factors Affecting Performance**:
- Number of resources to verify
- Reflection API overhead
- Laravel bootstrap time
- Database connection latency

### Memory Usage

**Typical Memory**: < 50MB

**Memory Profile**:
- Laravel bootstrap: ~30MB
- Reflection API: ~5MB per resource
- Script overhead: ~5MB

### Optimization Tips

1. **Disable Debug Mode**: Set `APP_DEBUG=false` for faster bootstrap
2. **Cache Configuration**: Run `php artisan config:cache` before verification
3. **Optimize Autoloader**: Run `composer dump-autoload --optimize`
4. **Minimal Database**: Use SQLite for faster connection

---

## Security Considerations

### Access Control

**Recommendation**: Restrict script execution to authorized users only

```bash
# Set appropriate permissions
chmod 750 verify-batch3-resources.php
chown www-data:www-data verify-batch3-resources.php
```

### Environment Isolation

**Recommendation**: Run in isolated environment with minimal privileges

```bash
# Run as specific user
sudo -u www-data php verify-batch3-resources.php

# Run in Docker container
docker exec app php verify-batch3-resources.php
```

### Sensitive Data

**Note**: Script does not access or display sensitive data. It only inspects class structure and configuration.

---

## Testing

### Unit Testing the Script

While the script itself is a verification tool, you can test its behavior:

```php
// tests/Unit/VerificationScriptTest.php
test('verification script exists', function () {
    expect(file_exists(base_path('verify-batch3-resources.php')))->toBeTrue();
});

test('verification script is executable', function () {
    $output = shell_exec('php verify-batch3-resources.php 2>&1');
    expect($output)->toContain('Verifying Batch 3 Filament Resources');
});

test('verification script returns correct exit code', function () {
    exec('php verify-batch3-resources.php', $output, $exitCode);
    expect($exitCode)->toBeIn([0, 1]);
});
```

### Integration Testing

```php
// tests/Feature/Filament/ResourceVerificationTest.php
test('all batch 3 resources are properly configured', function () {
    $resources = [
        \App\Filament\Resources\UserResource::class,
        \App\Filament\Resources\SubscriptionResource::class,
        \App\Filament\Resources\OrganizationResource::class,
        \App\Filament\Resources\OrganizationActivityLogResource::class,
    ];
    
    foreach ($resources as $resource) {
        expect(class_exists($resource))->toBeTrue();
        expect(is_subclass_of($resource, \Filament\Resources\Resource::class))->toBeTrue();
        expect($resource::getModel())->not->toBeEmpty();
        expect($resource::getNavigationIcon())->not->toBeEmpty();
        expect($resource::getPages())->not->toBeEmpty();
        expect(method_exists($resource, 'form'))->toBeTrue();
        expect(method_exists($resource, 'table'))->toBeTrue();
    }
});
```

---

## Troubleshooting

### Common Issues

#### Issue: "Class does not exist"

**Cause**: Resource file missing or autoload not updated

**Solution**:
```bash
composer dump-autoload
php verify-batch3-resources.php
```

#### Issue: "Does not extend Filament\Resources\Resource"

**Cause**: Incorrect base class or missing import

**Solution**: Check class declaration:
```php
use Filament\Resources\Resource;

class UserResource extends Resource
{
    // ...
}
```

#### Issue: "Model not set"

**Cause**: Missing or empty `$model` property

**Solution**: Add model property:
```php
protected static ?string $model = User::class;
```

#### Issue: Database connection errors

**Cause**: Invalid database configuration

**Solution**: Verify `.env` configuration:
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

---

## Related Documentation

- [Batch 3 Verification Guide](../testing/BATCH_3_VERIFICATION_GUIDE.md) - User guide
- [Batch 3 Resources Migration](../upgrades/BATCH_3_RESOURCES_MIGRATION.md) - Migration report
- [Laravel 12 + Filament 4 Upgrade](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md) - Upgrade guide
- [Framework Upgrade Tasks](../../.kiro/specs/1-framework-upgrade/tasks.md) - Task checklist

---

## Changelog

### Version 1.0.0 (2025-11-24)

**Initial Release**:
- Basic resource verification
- Class existence checks
- Inheritance validation
- Model configuration validation
- Navigation icon validation
- Page registration validation
- Form/table method validation
- Filament 4 Schema API validation
- Exit code support for CI/CD
- Detailed output formatting

---

## Future Enhancements

### Planned Features

1. **JSON Output Mode**: Support `--json` flag for machine-readable output
2. **Verbose Mode**: Support `--verbose` flag for detailed debugging
3. **Selective Verification**: Support `--resource=UserResource` to verify specific resources
4. **Batch Support**: Support verification of multiple batches (Batch 1, 2, 3, 4)
5. **Performance Metrics**: Report execution time and memory usage
6. **Configuration File**: Support external configuration for custom checks

### Example Future Usage

```bash
# JSON output for CI/CD
php verify-batch3-resources.php --json

# Verbose debugging
php verify-batch3-resources.php --verbose

# Verify specific resource
php verify-batch3-resources.php --resource=UserResource

# Verify all batches
php verify-batch3-resources.php --batch=all

# Performance profiling
php verify-batch3-resources.php --profile
```

---

**Document Version**: 1.0.0  
**Last Updated**: November 24, 2025  
**Maintained By**: Development Team  
**API Stability**: Stable
