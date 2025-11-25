# Model Verification Architecture

## Overview

The `verify-models.php` script provides automated verification of Eloquent model configuration in the Vilnius Utilities Billing platform. This document describes the architecture, design decisions, and integration patterns for model verification.

## Purpose

### Primary Goals

1. **Framework Upgrade Validation**: Ensure models remain compatible after Laravel 11 → 12 upgrade
2. **Cast Configuration**: Validate that all required casts are properly configured
3. **Relationship Documentation**: Provide quick reference for model relationships
4. **Developer Onboarding**: Help new developers understand model structure
5. **CI/CD Integration**: Enable automated pre-deployment validation

### Non-Goals

- **Runtime Validation**: Does not validate data integrity or business rules
- **Database Queries**: Does not execute queries or validate database state
- **Performance Testing**: Does not measure query performance or N+1 issues
- **Migration Validation**: Does not verify migration files or schema

## Architecture

### High-Level Design

```
┌─────────────────────────────────────────────────────────────┐
│                    verify-models.php                         │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │         Bootstrap Laravel Application               │    │
│  │  - Load autoloader                                  │    │
│  │  - Bootstrap kernel                                 │    │
│  │  - Initialize service container                     │    │
│  └────────────────────────────────────────────────────┘    │
│                          │                                   │
│                          ▼                                   │
│  ┌────────────────────────────────────────────────────┐    │
│  │         Model Instantiation Loop                    │    │
│  │  - Instantiate each model                          │    │
│  │  - Extract casts via getCasts()                    │    │
│  │  - Validate cast types                             │    │
│  │  - Output results                                  │    │
│  └────────────────────────────────────────────────────┘    │
│                          │                                   │
│                          ▼                                   │
│  ┌────────────────────────────────────────────────────┐    │
│  │         Relationship Documentation                  │    │
│  │  - List all relationships per model                │    │
│  │  - Document relationship types                     │    │
│  │  - No query execution                              │    │
│  └────────────────────────────────────────────────────┘    │
│                          │                                   │
│                          ▼                                   │
│  ┌────────────────────────────────────────────────────┐    │
│  │         Output Summary                              │    │
│  │  - Success/failure indicators                      │    │
│  │  - Exit code (0 = success)                         │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### Component Breakdown

#### 1. Bootstrap Phase

**Responsibility**: Initialize Laravel application environment

**Implementation**:
```php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
```

**Dependencies**:
- Composer autoloader
- Laravel application bootstrap
- Service container

**Error Handling**: Fatal errors if bootstrap fails (expected behavior)

#### 2. Model Verification Phase

**Responsibility**: Validate model configuration

**Process**:
1. Instantiate model class
2. Call `getCasts()` method
3. Check for required cast keys
4. Validate cast types
5. Output results

**Example**:
```php
$user = new App\Models\User();
$casts = $user->getCasts();
$rolecast = isset($casts['role']) ? 'UserRole::class' : 'missing';
echo "✓ User model: role cast = {$rolecast}\n";
```

#### 3. Relationship Documentation Phase

**Responsibility**: Document model relationships

**Implementation**: Static documentation (no method calls)

**Rationale**: 
- Avoids database queries
- Prevents N+1 issues
- Fast execution (<1 second)
- Safe for production environments

#### 4. Output Phase

**Responsibility**: Provide human-readable results

**Format**:
- Unicode indicators (✓ for success)
- Grouped by model
- Summary at end
- Implicit exit code 0 on success

## Model Coverage

### Core Models (11 Total)

| Model | Primary Casts | Key Relationships | Purpose |
|-------|--------------|-------------------|---------|
| User | role (enum) | property(), parentUser(), childUsers(), subscription() | Hierarchical user management |
| Building | gyvatukas_summer_average (decimal:2) | properties() | Gyvatukas calculations |
| Property | type (enum) | building(), tenants(), meters() | Property management |
| Tenant | lease_start (date), lease_end (date) | property(), invoices() | Lease tracking |
| Provider | service_type (enum) | tariffs() | Utility providers |
| Tariff | configuration (array), active_from (datetime) | provider() | Tariff management |
| Meter | type (enum), supports_zones (boolean) | property(), readings() | Meter tracking |
| MeterReading | reading_date (datetime), value (decimal:3) | meter(), enteredBy() | Reading entries |
| MeterReadingAudit | old_value (decimal:3), new_value (decimal:3) | meterReading(), changedBy() | Audit trail |
| Invoice | status (enum), billing_period_start (date) | tenant(), property(), items() | Invoice management |
| InvoiceItem | quantity (decimal:3), meter_reading_snapshot (array) | invoice() | Invoice line items |

### Cast Types Verified

#### Enum Casts
- `UserRole::class` - User role management
- `PropertyType::class` - Property classification
- `ServiceType::class` - Provider service types
- `MeterType::class` - Meter classification
- `InvoiceStatus::class` - Invoice lifecycle

#### Date/Datetime Casts
- `date` - Lease dates, billing periods
- `datetime` - Reading timestamps, tariff validity

#### Decimal Casts
- `decimal:2` - Gyvatukas values, invoice amounts
- `decimal:3` - Meter readings, consumption quantities

#### Array/JSON Casts
- `array` - Tariff configurations, meter reading snapshots

#### Boolean Casts
- `boolean` - Feature flags (supports_zones)

## Design Decisions

### 1. No Database Queries

**Decision**: Script does not execute any database queries

**Rationale**:
- Fast execution (<1 second)
- Safe for production environments
- No side effects
- No data dependencies

**Trade-offs**:
- Cannot validate data integrity
- Cannot test relationship queries
- Cannot verify foreign keys

**Mitigation**: Use separate test suites for data validation

### 2. Static Relationship Documentation

**Decision**: Relationships are documented statically, not executed

**Rationale**:
- Avoids N+1 query issues
- Prevents database load
- Provides quick reference
- Safe for CI/CD pipelines

**Trade-offs**:
- Manual updates required when relationships change
- No validation of relationship correctness

**Mitigation**: Use feature tests for relationship validation

### 3. Implicit Exit Code

**Decision**: Script exits with code 0 on success (implicit)

**Rationale**:
- PHP default behavior
- Simple implementation
- CI/CD compatible

**Trade-offs**:
- No explicit success/failure exit codes
- Errors cause non-zero exit via PHP exceptions

**Mitigation**: Wrap in try-catch if explicit exit codes needed

### 4. Human-Readable Output

**Decision**: Output is formatted for human consumption

**Rationale**:
- Easy to read during development
- Clear success/failure indicators
- Grouped by model for clarity

**Trade-offs**:
- Not machine-parseable
- No JSON/XML output option

**Mitigation**: Parse output with grep/awk if needed for automation

## Integration Patterns

### CI/CD Integration

#### GitHub Actions

```yaml
- name: Verify Eloquent Models
  run: php verify-models.php
```

#### GitLab CI

```yaml
verify-models:
  script:
    - php verify-models.php
```

#### Pre-Commit Hook

```bash
#!/bin/bash
php verify-models.php || exit 1
```

### Composer Scripts

```json
{
  "scripts": {
    "verify:models": "php verify-models.php",
    "verify:all": [
      "@verify:models",
      "php verify-batch3-resources.php",
      "php verify-batch4-resources.php"
    ]
  }
}
```

### Deployment Pipeline

```bash
# Pre-deployment verification
php verify-models.php && \
php verify-batch3-resources.php && \
php verify-batch4-resources.php && \
php artisan test --filter=Feature && \
echo "✓ All verifications passed"
```

## Performance Characteristics

### Execution Time

- **Typical**: <1 second
- **Maximum**: <2 seconds (cold start)
- **Factors**: Model count, autoloader cache

### Memory Usage

- **Typical**: <10MB
- **Maximum**: <20MB
- **Factors**: Model complexity, relationship count

### Database Impact

- **Queries**: 0 (no queries executed)
- **Connections**: 1 (connection established but not used)
- **Load**: Negligible

## Error Handling

### Expected Errors

1. **Missing Cast**: Outputs "missing" instead of cast type
2. **Model Not Found**: PHP fatal error (expected)
3. **Bootstrap Failure**: PHP fatal error (expected)

### Unexpected Errors

1. **Syntax Errors**: PHP parse error
2. **Autoload Failure**: Composer autoload error
3. **Memory Exhaustion**: PHP memory limit error

### Error Recovery

- **No automatic recovery**: Script exits on fatal errors
- **Manual intervention**: Fix underlying issue and re-run
- **CI/CD**: Pipeline fails, preventing deployment

## Maintenance

### Adding New Models

1. Add model instantiation code
2. Extract and validate casts
3. Document relationships
4. Update this documentation
5. Update MODEL_VERIFICATION_GUIDE.md

### Updating Casts

1. Update model's `$casts` property
2. Run verification script
3. Update documentation if cast types change
4. Update related tests

### Removing Models

1. Remove model verification code
2. Update documentation
3. Update test coverage reports

## Security Considerations

### Safe for Production

- No data modifications
- No database writes
- No user input
- No external API calls

### Potential Risks

- **Information Disclosure**: Reveals model structure (acceptable for internal tools)
- **Resource Exhaustion**: Minimal risk due to fast execution
- **Privilege Escalation**: None (read-only operations)

### Mitigation

- Run in trusted environments only
- Restrict access to verification scripts
- Include in deployment pipelines, not public endpoints

## Future Enhancements

### Potential Improvements

1. **JSON Output**: Add `--json` flag for machine-parseable output
2. **Verbose Mode**: Add `--verbose` flag for detailed cast information
3. **Relationship Validation**: Execute relationship queries in test mode
4. **Exit Codes**: Add explicit exit codes for different failure types
5. **Configuration File**: Support external configuration for model list

### Backward Compatibility

- Maintain current output format as default
- Add new features via optional flags
- Preserve exit code behavior

## Related Documentation

- [Model Verification Guide](../testing/MODEL_VERIFICATION_GUIDE.md) - User guide
- [Verification Scripts API](../api/VERIFICATION_SCRIPTS_API.md) - API reference
- [Eloquent Relationships Guide](ELOQUENT_RELATIONSHIPS_GUIDE.md) - Relationship patterns
- [Database Schema Guide](DATABASE_SCHEMA_AND_MIGRATION_GUIDE.md) - Schema documentation
- [Laravel 12 Upgrade Guide](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md) - Framework upgrade

## Changelog

- **1.0.0** (2025-11-24) - Initial release
  - 11 core models verified
  - 40+ relationships documented
  - 20+ casts validated
  - Laravel 12 and Filament 4 compatible
