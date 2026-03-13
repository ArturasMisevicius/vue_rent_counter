# Verification Scripts Quick Reference

## Available Scripts

| Script | Purpose | Exit Codes | Execution Time |
|--------|---------|------------|----------------|
| `verify-batch3-resources.php` | Filament 4 resource compliance (Batch 3) | 0=success, 1=failure | <1s |
| `verify-models.php` | Eloquent model configuration | 0=success (implicit) | <1s |
| `verify-multi-tenancy.php` | Multi-tenancy implementation | 0=success, 1=failure | <1s |

## Quick Commands

### Run All Verifications

```bash
# Individual scripts
php verify-batch3-resources.php
php verify-models.php
php verify-multi-tenancy.php

# Via Composer (if configured)
composer verify:all
```

### CI/CD Integration

```bash
# Pre-deployment check
php verify-batch3-resources.php && \
php verify-models.php && \
php verify-multi-tenancy.php && \
echo "✓ All verifications passed"
```

### GitHub Actions

```yaml
- name: Verify System
  run: |
    php verify-batch3-resources.php
    php verify-models.php
    php verify-multi-tenancy.php
```

## Batch 3 Resources

### Verified Resources
- UserResource
- SubscriptionResource
- OrganizationResource
- OrganizationActivityLogResource

### Key Checks
1. ✓ Class existence
2. ✓ Extends Resource
3. ✓ Model configured
4. ✓ Navigation icon set
5. ✓ Pages registered
6. ✓ form() method exists
7. ✓ table() method exists
8. ✓ Filament 4 Schema API

### Common Fixes

**Missing form() method:**
```php
use Filament\Schemas\Schema;

public static function form(Schema $schema): Schema
{
    return $schema->schema([
        // Form fields
    ]);
}
```

**Wrong API (Filament 3 → 4):**
```php
// Old (Filament 3)
public static function form(Form $form): Form

// New (Filament 4)
public static function form(Schema $schema): Schema
```

## Models Verification

### Verified Models
- User, Building, Property, Tenant
- Provider, Tariff, Meter, MeterReading
- MeterReadingAudit, Invoice, InvoiceItem

### Key Checks
- Enum casts (UserRole, PropertyType, etc.)
- Date/datetime casts
- Decimal casts (2-3 decimal places)
- Array/JSON casts
- Boolean casts
- Relationship documentation

## Multi-Tenancy Verification

### Key Checks
- TenantScope class
- BelongsToTenant trait
- EnsureTenantContext middleware
- Authentication event listener
- TenantContext service
- Model trait usage

## Troubleshooting

### Script Won't Run

```bash
# Update autoloader
composer dump-autoload

# Check PHP version
php -v  # Should be 8.3+

# Verify Laravel bootstrap
php artisan --version
```

### All Checks Failing

```bash
# Clear caches
php artisan optimize:clear

# Reinstall dependencies
composer install --no-interaction

# Re-run verification
php verify-batch3-resources.php
```

### Specific Resource Failing

1. Check file exists: `app/Filament/Resources/UserResource.php`
2. Verify namespace: `namespace App\Filament\Resources;`
3. Check imports: `use Filament\Resources\Resource;`
4. Validate syntax: `php -l app/Filament/Resources/UserResource.php`

## Documentation Links

- [Batch 3 Verification Guide](BATCH_3_VERIFICATION_GUIDE.md)
- [Model Verification Guide](MODEL_VERIFICATION_GUIDE.md)
- [Verification Scripts API](../api/VERIFICATION_SCRIPTS_API.md)
- [Multi-Tenancy Verification](../architecture/MULTI_TENANCY_IMPLEMENTATION_VERIFICATION.md)

## Performance Notes

- All scripts execute in <1 second
- No database queries (reflection-based)
- Safe for production environments
- Minimal memory footprint (<50MB)

---

**Last Updated**: November 24, 2025  
**Version**: 1.0.0
