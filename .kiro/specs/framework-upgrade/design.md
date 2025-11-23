# Design Document

## Overview

This design outlines the architecture and approach for upgrading the Vilnius Utilities Billing Platform to Laravel 12+ + Filament 4+, along with Tailwind CSS 4+ and all related dependencies. The upgrade will be performed in phases to minimize risk, with comprehensive testing at each stage to ensure system integrity.

The current stack includes:
- Laravel 12.x with PHP 8.3+ (8.2 minimum)
- Filament 4.x (Livewire 3) for admin panels
- Tailwind CSS (CDN-delivered)
- Spatie packages for backup and multi-tenancy
- Pest/PHPUnit for testing

The target stack will include:
- Laravel 12.x with PHP 8.3+ support (recommended runtime)
- Filament 4.x with improved performance and DX
- Tailwind CSS 4.x with modern CSS features
- Updated Spatie packages
- Latest testing tools

## Architecture

### Upgrade Strategy

The upgrade follows a **phased rollout** approach:

1. **Preparation Phase**: Backup, dependency analysis, and baseline metrics
2. **Core Framework Phase**: Laravel 12 upgrade with breaking change resolution
3. **Admin Panel Phase**: Filament 4 upgrade with resource updates
4. **Frontend Phase**: Tailwind 4 and asset pipeline updates
5. **Dependencies Phase**: Update all remaining PHP and Node packages
6. **Verification Phase**: Comprehensive testing and performance validation
7. **Documentation Phase**: Update docs and deployment guides

### Risk Mitigation

- **Incremental commits**: Commit after each successful phase on `main`
- **Test-driven**: Run full test suite after each phase
- **Rollback plan**: Document rollback steps for each phase
- **Staging deployment**: Test on staging environment before production

### Compatibility Matrix

| Component | Current | Target | Breaking Changes / Focus |
|-----------|---------|--------|--------------------------|
| Laravel | 12.x | 12.x (latest) | Verify middleware/config alignment, adopt new helpers |
| Filament | 4.x | 4.x (latest minor) | Livewire 3 hydration, form/table API tuning, asset/caching optimizations |
| PHP | 8.3+ | 8.3+ | Minor deprecations |
| Tailwind | 4.x (CDN) | 4.x (CDN) | Utility changes, CSS layering |
| Pest | 3.x | 3.x | Plugin API changes |
| Spatie Backup | 10.x | 10.x | Configuration updates |

## Components and Interfaces

### 1. Dependency Manager

**Responsibility**: Manage version constraints and resolve conflicts

**Key Operations**:
- Update `composer.json` with new version constraints
- Resolve dependency conflicts using `composer why-not`
- Update `package.json` with new Node package versions
- Lock versions after successful upgrade

**Interfaces**:
```php
// No code interface - uses Composer/NPM CLI
```

### 2. Configuration Migrator

**Responsibility**: Update configuration files to match new framework conventions

**Key Files**:
- `config/app.php` - Service providers, aliases
- `config/auth.php` - Authentication guards
- `config/database.php` - Connection settings
- `config/filesystems.php` - Storage configuration
- `bootstrap/app.php` - Application bootstrap (Laravel 12 changes)
- `bootstrap/providers.php` - Service provider registration
- `tailwind.config.js` - Tailwind 4 configuration

**Migration Strategy**:
- Compare current config with Laravel 12 skeleton
- Identify deprecated options
- Add new required options with defaults
- Preserve custom configuration values

### 3. Filament Resource Migrator

**Responsibility**: Update Filament resources to Filament 4 API

**Affected Resources**:
- `app/Filament/Resources/PropertyResource.php`
- `app/Filament/Resources/BuildingResource.php`
- `app/Filament/Resources/MeterResource.php`
- `app/Filament/Resources/MeterReadingResource.php`
- `app/Filament/Resources/InvoiceResource.php`
- `app/Filament/Resources/TariffResource.php`
- `app/Filament/Resources/ProviderResource.php`
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/SubscriptionResource.php`

**Breaking Changes**:
- Form schema builder API changes
- Table column builder API changes
- Action button API changes
- Navigation registration changes
- Widget API changes

**Migration Pattern**:
```php
// Filament 4 (current) - favor Livewire 3 features and lazy hydration for performance
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->live(onBlur: true), // reduces rerenders while still validating promptly
        ])
        ->columns(2); // keep layout lean for faster hydration
}

```

### 4. Middleware Migrator

**Responsibility**: Update middleware to Laravel 12 conventions

**Key Changes**:
- Middleware registration moves to `bootstrap/app.php`
- Route middleware groups defined in bootstrap
- Global middleware stack configuration

**Current Structure** (`app/Http/Kernel.php` or bootstrap):
```php
protected $middleware = [
    // Global middleware
];

protected $middlewareGroups = [
    'web' => [...],
    'api' => [...],
];
```

**Target Structure** (Laravel 12 `bootstrap/app.php`):
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            // Custom web middleware
        ]);
    })
    ->create();
```

### 5. Route Migrator

**Responsibility**: Update routing to Laravel 12 conventions

**Key Files**:
- `routes/web.php`
- `routes/api.php` (if exists)

**Changes**:
- Route registration syntax updates
- Controller action references
- Route model binding updates

### 6. Test Suite Migrator

**Responsibility**: Update tests to work with upgraded frameworks

**Affected Tests**:
- Feature tests (100+ files)
- Unit tests
- Filament-specific tests
- Property-based tests

**Common Updates**:
- Pest 3.x API changes
- PHPUnit 11.x assertion updates
- Filament testing helpers
- Factory/seeder compatibility

### 7. View Migrator

**Responsibility**: Update Blade templates and Tailwind classes

**Key Changes**:
- Tailwind 4 utility class updates
- Filament Blade component updates
- Alpine.js compatibility (if CDN version changes)

**Affected Views**:
- `resources/views/layouts/app.blade.php` - CDN URLs
- `resources/views/components/**` - Tailwind classes
- Filament resource views (auto-generated)

## Data Models

No database schema changes are required for this upgrade. All existing models, migrations, and seeders will remain compatible with minor syntax updates if needed.

### Model Updates

Models may require updates for:
- Eloquent attribute casting syntax
- Relationship method return types
- Query builder method signatures

Example:
```php
// Current (Laravel 12)
protected $casts = [
    'published_at' => 'datetime',
];

// Target (Laravel 12) - likely unchanged, but verify
protected function casts(): array
{
    return [
        'published_at' => 'datetime',
    ];
}
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, the following properties provide unique validation value without redundancy:

- **Functional Regression Prevention**: Ensures all existing features work after upgrade (subsumes individual feature tests)
- **Filament Resource Integrity**: Ensures all admin resources render and function correctly (covers navigation, widgets, forms)
- **Visual Regression Prevention**: Ensures UI renders correctly with Tailwind 4
- **Multi-Tenancy Preservation**: Ensures tenant scoping still works (critical for data isolation)
- **Database Compatibility**: Ensures migrations and seeders work across all supported drivers
- **Performance Boundaries**: Ensures resource usage stays within acceptable limits

### Property 1: Functional regression prevention

*For any* existing feature test in the test suite, running the test after the upgrade should produce the same pass/fail result as before the upgrade (excluding tests that explicitly test deprecated behavior).

**Validates: Requirements 1.2, 7.1, 7.2, 7.3**

### Property 2: Filament resource integrity

*For any* Filament resource (Property, Building, Meter, MeterReading, Invoice, Tariff, Provider, User, Subscription), when accessed by an authorized user, the resource should render without errors and all CRUD operations should function correctly.

**Validates: Requirements 2.2, 2.4, 2.5, 7.4**

### Property 3: Visual regression prevention

*For any* page in the application, when rendered after the Tailwind 4 upgrade, all UI elements should display correctly without missing styles, broken layouts, or visual artifacts.

**Validates: Requirements 3.2**

### Property 4: Multi-tenancy preservation

*For any* tenant-scoped model (Building, Property, Meter, MeterReading, Invoice), when queried by a user with a specific tenant_id, the results should only include records belonging to that tenant, matching the behavior before the upgrade.

**Validates: Requirements 1.2, 4.2**

### Property 5: API request functionality

*For any* HTTP request made through Axios or the application's API layer, the request should complete successfully with the expected response format and status code after the Axios upgrade.

**Validates: Requirements 5.3**

### Property 6: Database driver compatibility

*For any* supported database driver (SQLite, MySQL, PostgreSQL), all migrations should run successfully, all seeders should execute without errors, and all queries should return consistent results across drivers.

**Validates: Requirements 9.5**

### Property 7: Memory usage boundaries

*For any* typical user operation (loading a dashboard, generating an invoice, running a report), the memory usage should not exceed 150% of the pre-upgrade baseline, ensuring the application remains within acceptable resource limits.

**Validates: Requirements 10.5**

## Error Handling

### Upgrade Failure Scenarios

1. **Dependency Conflict**
   - Detection: `composer update` fails with version conflict
   - Resolution: Use `composer why-not` to identify conflicts, adjust version constraints
   - Rollback: Restore `composer.lock` from backup

2. **Breaking API Change**
   - Detection: Tests fail after framework upgrade
   - Resolution: Update code to match new API, consult upgrade guides
   - Rollback: Revert code changes, restore previous framework version

3. **Configuration Incompatibility**
   - Detection: Application fails to boot or throws configuration errors
   - Resolution: Compare config with framework skeleton, update deprecated options
   - Rollback: Restore config files from backup

4. **Database Migration Failure**
   - Detection: `php artisan migrate` fails
   - Resolution: Review migration code for deprecated methods, update syntax
   - Rollback: Run `php artisan migrate:rollback`, restore database backup

5. **Test Suite Failure**
   - Detection: Tests fail after upgrade
   - Resolution: Update test code to match framework changes, verify test intent preserved
   - Rollback: Revert test changes, investigate root cause

6. **Performance Degradation**
   - Detection: Benchmarks show >20% performance decrease
   - Resolution: Profile application, identify bottlenecks, optimize or report issue
   - Rollback: Revert upgrade if degradation is unacceptable

### Error Recovery Strategy

- Maintain comprehensive backups before each phase
- Use Git tags to mark successful phase completions
- Document all manual interventions required
- Keep rollback scripts ready for each phase
- Test rollback procedures in staging environment

## Testing Strategy

### Dual Testing Approach

The upgrade will use both unit testing and property-based testing to ensure comprehensive coverage:

- **Unit tests** verify specific examples, edge cases, and error conditions
- **Property tests** verify universal properties that should hold across all inputs
- Together they provide comprehensive coverage: unit tests catch concrete bugs, property tests verify general correctness

### Unit Testing

Unit tests will cover:

- **Version verification**: Confirm Laravel 12.x, Filament 4.x, Tailwind 4.x are installed
- **Configuration validation**: Verify config files contain required options
- **Migration execution**: Test migrations run successfully on fresh database
- **Seeder execution**: Test seeders populate database correctly
- **Specific breaking changes**: Test known breaking changes are resolved

Example unit tests:
```php
test('Laravel version is 12.x or higher', function () {
    $version = app()->version();
    expect($version)->toMatch('/^12\./');
});

test('Filament version is 4.x or higher', function () {
    $version = \Filament\Facades\Filament::getVersion();
    expect($version)->toMatch('/^4\./');
});

test('all migrations run successfully', function () {
    Artisan::call('migrate:fresh');
    expect(Artisan::output())->not->toContain('error');
});
```

### Property-Based Testing

Property-based tests will verify:

- **Functional regression**: All existing feature tests pass
- **Filament resources**: All resources render and function correctly
- **Multi-tenancy**: Tenant scoping works across all models
- **Database compatibility**: Queries work across all supported drivers
- **Performance**: Resource usage stays within bounds

We will use **Pest** (already in the project) for property-based testing. Pest supports property-based testing through its built-in features and can be extended with custom generators.

Each property-based test will:
- Run a minimum of 100 iterations to ensure statistical confidence
- Be tagged with a comment referencing the correctness property from this design document
- Use the format: `**Feature: framework-upgrade, Property {number}: {property_text}**`

Example property test:
```php
/**
 * Feature: framework-upgrade, Property 2: Filament resource integrity
 * 
 * For any Filament resource, when accessed by an authorized user,
 * the resource should render without errors and all CRUD operations
 * should function correctly.
 */
test('all Filament resources render without errors', function () {
    $resources = [
        \App\Filament\Resources\PropertyResource::class,
        \App\Filament\Resources\BuildingResource::class,
        \App\Filament\Resources\MeterResource::class,
        // ... all resources
    ];
    
    $admin = User::factory()->admin()->create();
    
    foreach ($resources as $resource) {
        actingAs($admin)
            ->get($resource::getUrl('index'))
            ->assertSuccessful();
    }
})->repeat(100);
```

### Test Execution Plan

1. **Pre-upgrade baseline**: Run full test suite, capture results
2. **After each phase**: Run relevant test subset
3. **Post-upgrade verification**: Run full test suite, compare with baseline
4. **Property test execution**: Run all property tests with 100+ iterations
5. **Performance benchmarks**: Compare before/after metrics

### Test Coverage Requirements

- All existing tests must pass (or be updated to match new framework behavior)
- New tests added for upgrade-specific validations
- Property tests cover all critical correctness properties
- Integration tests verify end-to-end workflows
- Performance tests validate resource usage

## Implementation Phases

### Phase 1: Preparation (Day 1)

**Objectives**:
- Create backup of current codebase and database
- Document current versions of all dependencies
- Run baseline test suite and capture results
- Run performance benchmarks and capture metrics

**Deliverables**:
- Git tag: `pre-upgrade-baseline`
- Backup files: `backup-{date}.tar.gz`
- Test results: `test-results-baseline.txt`
- Performance metrics: `performance-baseline.json`

### Phase 2: Laravel 12 Upgrade (Days 2-3)

**Objectives**:
- Update `composer.json` to Laravel 12.x
- Run `composer update laravel/framework`
- Resolve breaking changes in middleware, routing, validation
- Update configuration files
- Update service providers and bootstrap files
- Run test suite and fix failures

**Breaking Changes to Address**:
- Middleware registration in `bootstrap/app.php`
- Route registration syntax
- Validation rule changes
- Configuration file structure
- Service provider registration

**Deliverables**:
- Updated `composer.json` and `composer.lock`
- Updated configuration files
- Updated middleware and routing
- Passing test suite
- Git tag: `laravel-12-upgrade-complete`

### Phase 3: Filament 4 Upgrade (Days 4-6)

**Objectives**:
- Update `composer.json` to Filament 4.x
- Run `composer update filament/filament`
- Update all Filament resources to Filament 4 API
- Update form schemas, table columns, actions
- Update navigation and widgets
- Test all admin panel functionality

**Resources to Update**:
- PropertyResource
- BuildingResource
- MeterResource
- MeterReadingResource
- InvoiceResource
- TariffResource
- ProviderResource
- UserResource
- SubscriptionResource

**Deliverables**:
- Updated Filament resources
- Updated widgets and pages
- Passing Filament tests
- Git tag: `filament-4-upgrade-complete`

### Phase 4: Tailwind 4 Upgrade (Day 7)

**Objectives**:
- Update Tailwind CDN URLs to version 4.x
- Review and update custom Tailwind classes
- Update `tailwind.config.js` if using build process
- Test visual rendering across all pages
- Fix any broken styles or layouts

**Files to Update**:
- `resources/views/layouts/app.blade.php` (CDN URLs)
- Any custom Tailwind configuration
- Blade components with Tailwind classes

**Deliverables**:
- Updated CDN URLs
- Updated Tailwind classes
- Visual regression test results
- Git tag: `tailwind-4-upgrade-complete`

### Phase 5: Dependency Updates (Days 8-9)

**Objectives**:
- Update all PHP dependencies to latest stable versions
- Update all Node dependencies to latest stable versions
- Resolve any version conflicts
- Update Pest, PHPUnit, and testing tools
- Update Spatie packages
- Run full test suite

**Key Dependencies**:
- Pest 3.x
- PHPUnit 11.x
- Spatie Laravel Backup 10.x
- Laravel Pint (latest)
- Laravel Sail (latest)
- Vite (latest)
- Axios (latest)

**Deliverables**:
- Updated `composer.json` and `package.json`
- Resolved dependency conflicts
- Passing test suite
- Git tag: `dependencies-upgrade-complete`

### Phase 6: Verification (Days 10-11)

**Objectives**:
- Run complete test suite (Feature, Unit, Property tests)
- Run performance benchmarks
- Compare metrics with baseline
- Test on staging environment
- Verify all user workflows
- Document any issues or regressions

**Test Categories**:
- Feature tests (100+ files)
- Unit tests
- Property-based tests (7 properties, 100+ iterations each)
- Filament tests
- Integration tests
- Performance tests

**Deliverables**:
- Test results comparison
- Performance metrics comparison
- Staging deployment verification
- Issue log (if any)
- Git tag: `upgrade-verification-complete`

### Phase 7: Documentation (Day 12)

**Objectives**:
- Update README with new version requirements
- Update deployment documentation
- Document breaking changes and resolutions
- Update developer setup guide
- Create upgrade guide for future reference
- Update `.kiro/steering` files if needed

**Documentation to Update**:
- `README.md`
- `docs/guides/SETUP.md`
- `.kiro/steering/tech.md`
- `.kiro/steering/structure.md`
- Create `docs/upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md`

**Deliverables**:
- Updated documentation
- Upgrade guide
- Git tag: `upgrade-complete`

## Rollback Procedures

### Emergency Rollback

If critical issues are discovered after deployment:

1. **Revert repository**: `git checkout main && git reset --hard pre-upgrade-baseline`
2. **Restore dependencies**: `composer install && npm install`
3. **Restore database**: Restore from backup if migrations were run
4. **Clear caches**: `php artisan cache:clear && php artisan config:clear`
5. **Verify rollback**: Run test suite to confirm system is stable

### Partial Rollback

If issues are discovered in a specific phase:

1. **Identify phase**: Determine which phase introduced the issue
2. **Revert to phase tag**: `git reset --hard {phase-tag}`
3. **Restore dependencies**: `composer install && npm install`
4. **Fix issue**: Address the problem in isolation
5. **Resume upgrade**: Continue from the fixed phase

## Performance Benchmarks

### Metrics to Capture

1. **Response Times**:
   - Dashboard load time (superadmin, admin, manager, tenant)
   - Resource list page load time (all Filament resources)
   - Invoice generation time
   - Report generation time

2. **Database Performance**:
   - Query execution time (top 10 slowest queries)
   - Migration execution time
   - Seeder execution time

3. **Memory Usage**:
   - Peak memory usage per request type
   - Average memory usage
   - Memory usage during batch operations

4. **Test Suite Performance**:
   - Total test execution time
   - Individual test execution time (slowest 10)

### Acceptance Criteria

- Response times: No more than 10% increase
- Database queries: No more than 15% increase
- Memory usage: No more than 50% increase
- Test suite: No more than 20% increase

### Benchmark Tools

- Laravel Telescope for request profiling
- `php artisan test --profile` for test performance
- Custom benchmark scripts for specific operations
- Memory profiling with Xdebug or Blackfire

## Deployment Strategy

### Staging Deployment

1. Deploy main to staging environment
2. Run full test suite on staging
3. Perform manual testing of critical workflows
4. Run performance benchmarks
5. Verify with stakeholders

### Production Deployment

1. Schedule maintenance window (if needed)
2. Create production database backup
3. Deploy main to production
4. Run migrations (if any)
5. Clear all caches
6. Verify deployment with smoke tests
7. Monitor error logs and performance metrics
8. Keep rollback plan ready

### Post-Deployment Monitoring

- Monitor error logs for 24 hours
- Track performance metrics
- Gather user feedback
- Address any issues immediately
- Document lessons learned

## Dependencies

### External Dependencies

- Laravel 12.x documentation
- Filament 4.x upgrade guide
- Tailwind CSS 4.x migration guide
- Pest 3.x documentation
- Spatie package upgrade guides

### Internal Dependencies

- Access to staging environment
- Database backup procedures
- Deployment pipeline
- Test infrastructure
- Performance monitoring tools

## Success Criteria

The upgrade is considered successful when:

1. All framework versions meet target requirements (Laravel 12+, Filament 4+, Tailwind 4+)
2. All existing tests pass (or are updated to match new framework behavior)
3. All 7 correctness properties are verified through property-based tests
4. Performance metrics are within acceptable ranges (no more than 10-15% degradation)
5. All Filament resources render and function correctly
6. Multi-tenancy and data isolation remain intact
7. Staging deployment is successful and verified
8. Documentation is updated and complete
9. Rollback procedures are tested and documented
10. Production deployment is successful with no critical issues

## Future Considerations

After the upgrade is complete, consider:

- Adopting new Laravel 12 features (e.g., improved validation, new helpers)
- Leveraging Filament 4 performance improvements
- Utilizing Tailwind 4 modern CSS features
- Refactoring deprecated patterns
- Optimizing based on new framework capabilities
- Planning for future upgrades (establish regular upgrade cadence)
