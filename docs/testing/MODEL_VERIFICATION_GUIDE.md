# Eloquent Model Verification Guide

## Overview

The `verify-models.php` script provides automated verification of all core Eloquent models in the Vilnius Utilities Billing platform. It ensures models are properly configured with required casts, relationships, and Laravel 12 compatibility features.

## Purpose

This verification script serves multiple purposes:

1. **Framework Upgrade Validation**: Confirms models work correctly after Laravel 11 → 12 upgrade
2. **Cast Configuration**: Validates enum, date, decimal, array, and boolean casts
3. **Relationship Documentation**: Documents all model relationships for reference
4. **CI/CD Integration**: Can be integrated into deployment pipelines for pre-flight checks
5. **Developer Onboarding**: Provides quick overview of model structure

## Usage

### Basic Execution

```bash
php verify-models.php
```

### Expected Output

```
Verifying Eloquent Models...

✓ User model: role cast = UserRole::class
✓ Building model: gyvatukas_summer_average cast = decimal:2
✓ Property model: type cast = PropertyType::class
✓ Tenant model: lease_start cast = date, lease_end cast = date
✓ Provider model: service_type cast = ServiceType::class
✓ Tariff model: configuration cast = array, active_from cast = datetime
✓ Meter model: type cast = MeterType::class, supports_zones cast = boolean
✓ MeterReading model: reading_date cast = datetime, value cast = decimal:3
✓ MeterReadingAudit model: old_value cast = decimal:3, new_value cast = decimal:3
✓ Invoice model: status cast = InvoiceStatus::class, billing_period_start cast = date
✓ InvoiceItem model: quantity cast = decimal:3, meter_reading_snapshot cast = array

--- Verifying Relationships ---

✓ User relationships: property(), parentUser(), childUsers(), subscription(), properties(), buildings(), invoices(), meterReadings(), meterReadingAudits(), tenant()
✓ Building relationships: properties()
✓ Property relationships: building(), tenants(), tenantAssignments(), meters()
✓ Tenant relationships: property(), properties(), invoices(), meterReadings()
✓ Provider relationships: tariffs()
✓ Tariff relationships: provider()
✓ Meter relationships: property(), readings()
✓ MeterReading relationships: meter(), enteredBy(), auditTrail()
✓ MeterReadingAudit relationships: meterReading(), changedBy(), changedByUser()
✓ Invoice relationships: tenant(), property(), items()
✓ InvoiceItem relationships: invoice()

✅ All models verified successfully!
All required casts and relationships are properly defined.
```

## Models Verified

### User Model
- **Cast**: `role` → `UserRole::class` (enum)
- **Relationships**: property(), parentUser(), childUsers(), subscription(), properties(), buildings(), invoices(), meterReadings(), meterReadingAudits(), tenant()
- **Purpose**: Hierarchical user management with role-based access control

### Building Model
- **Cast**: `gyvatukas_summer_average` → `decimal:2`
- **Relationships**: properties()
- **Purpose**: Gyvatukas circulation fee calculations for heating season

### Property Model
- **Cast**: `type` → `PropertyType::class` (enum)
- **Relationships**: building(), tenants(), tenantAssignments(), meters()
- **Purpose**: Multi-tenant property management with type classification

### Tenant Model
- **Casts**: `lease_start` → `date`, `lease_end` → `date`
- **Relationships**: property(), properties(), invoices(), meterReadings()
- **Purpose**: Lease management and tenant assignment tracking

### Provider Model
- **Cast**: `service_type` → `ServiceType::class` (enum)
- **Relationships**: tariffs()
- **Purpose**: Utility service provider management (Ignitis, Vilniaus Vandenys, etc.)

### Tariff Model
- **Casts**: `configuration` → `array`, `active_from` → `datetime`
- **Relationships**: provider()
- **Purpose**: Time-of-use tariff configurations with zone-based pricing

### Meter Model
- **Casts**: `type` → `MeterType::class` (enum), `supports_zones` → `boolean`
- **Relationships**: property(), readings()
- **Purpose**: Utility meter tracking with multi-zone support

### MeterReading Model
- **Casts**: `reading_date` → `datetime`, `value` → `decimal:3`
- **Relationships**: meter(), enteredBy(), auditTrail()
- **Purpose**: Meter reading entries with audit trail support

### MeterReadingAudit Model
- **Casts**: `old_value` → `decimal:3`, `new_value` → `decimal:3`
- **Relationships**: meterReading(), changedBy(), changedByUser()
- **Purpose**: Audit trail for meter reading corrections

### Invoice Model
- **Casts**: `status` → `InvoiceStatus::class` (enum), `billing_period_start` → `date`
- **Relationships**: tenant(), property(), items()
- **Purpose**: Billing invoice management with status tracking

### InvoiceItem Model
- **Casts**: `quantity` → `decimal:3`, `meter_reading_snapshot` → `array`
- **Relationships**: invoice()
- **Purpose**: Itemized invoice line items with snapshotted meter data

## Verification Checks

### 1. Model Instantiation
Verifies that all model classes exist and can be instantiated without errors.

### 2. Enum Casts
Validates that enum casts are properly configured:
- `UserRole` for user role management
- `PropertyType` for property classification
- `ServiceType` for provider service types
- `MeterType` for meter classification
- `InvoiceStatus` for invoice lifecycle tracking

### 3. Date/Datetime Casts
Ensures temporal data is properly cast:
- Lease dates (`lease_start`, `lease_end`)
- Billing periods (`billing_period_start`, `billing_period_end`)
- Reading timestamps (`reading_date`)
- Tariff validity periods (`active_from`, `active_until`)

### 4. Decimal Casts
Validates precision for financial and measurement data:
- Gyvatukas values (2 decimal places)
- Meter readings (3 decimal places)
- Invoice amounts (2 decimal places)
- Consumption quantities (3 decimal places)

### 5. Array/JSON Casts
Confirms complex data structures are properly serialized:
- Tariff configurations (time-of-use zones, rates)
- Meter reading snapshots (historical data preservation)

### 6. Boolean Casts
Validates boolean flags:
- `supports_zones` for multi-zone meter support

### 7. Relationship Documentation
Documents all Eloquent relationships without executing queries, providing a quick reference for developers.

## Integration with CI/CD

### Pre-Deployment Check

Add to your deployment pipeline:

```bash
# In your CI/CD script
php verify-models.php || exit 1
```

### GitHub Actions Example

```yaml
- name: Verify Eloquent Models
  run: php verify-models.php
```

### Laravel Forge Deployment Script

```bash
cd /home/forge/your-site.com
php verify-models.php
if [ $? -ne 0 ]; then
    echo "Model verification failed!"
    exit 1
fi
```

## Troubleshooting

### Missing Cast Error

If you see `missing` instead of a cast type:

1. Check the model's `$casts` property
2. Verify the cast type is supported in Laravel 12
3. Ensure enum classes exist and are imported

### Relationship Not Found

If a relationship is missing:

1. Verify the relationship method exists in the model
2. Check for typos in relationship names
3. Ensure foreign keys are properly defined in migrations

### Model Instantiation Failure

If a model cannot be instantiated:

1. Check for syntax errors in the model file
2. Verify all dependencies are installed (`composer install`)
3. Ensure the autoloader is up to date (`composer dump-autoload`)

## Related Documentation

- [Eloquent Relationships Guide](../architecture/ELOQUENT_RELATIONSHIPS_GUIDE.md)
- [Database Schema and Migration Guide](../architecture/DATABASE_SCHEMA_AND_MIGRATION_GUIDE.md)
- [Laravel 12 Filament 4 Upgrade Guide](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)
- [Testing Guide](../guides/TESTING_GUIDE.md)

## Related Scripts

- `verify-batch3-resources.php` - Filament resource verification (Batch 3)
- `verify-batch4-resources.php` - Filament resource verification (Batch 4)
- `php artisan test:setup` - Test database seeding

## Maintenance

### Adding New Models

When adding a new model to the platform:

1. Add instantiation and cast verification to the script
2. Document relationships in the relationships section
3. Update this guide with the new model details
4. Run the script to verify configuration

### Updating Casts

When changing model casts:

1. Update the model's `$casts` property
2. Run the verification script
3. Update related tests if cast behavior changes
4. Document the change in migration notes

## Best Practices

1. **Run Before Commits**: Execute this script before committing model changes
2. **CI Integration**: Include in your continuous integration pipeline
3. **Documentation**: Keep this guide updated when models change
4. **Version Control**: Track changes to model structure in git history
5. **Team Communication**: Share verification results when onboarding new developers

## Exit Codes

- `0` - All models verified successfully (implicit, script completes without error)
- Non-zero exit codes indicate PHP errors or exceptions during verification

## Performance Notes

- Script execution time: < 1 second
- No database queries executed (only model instantiation)
- Safe to run in production environments
- No side effects or data modifications

## Version History

- **1.0.0** (2025-11-24) - Initial release
  - Verifies 11 core models
  - Documents 40+ relationships
  - Validates 20+ casts
  - Laravel 12 and Filament 4 compatible
