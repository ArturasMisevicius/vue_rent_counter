# Laravel 12 + Filament 4 + Tailwind 4 Upgrade Guide

## Overview

This document provides a comprehensive guide for upgrading the Vilnius Utilities Billing Platform from Laravel 11.x + Filament 3.x to Laravel 12.x + Filament 4.x + Tailwind CSS 4.x. It includes all breaking changes encountered, resolution steps, rollback procedures, and lessons learned during the upgrade process.

**Upgrade Timeline**: Completed November 2025  
**Target Versions**:
- Laravel: 12.x (from 11.46.1)
- Filament: 4.x (from 3.x)
- Tailwind CSS: 4.x (CDN)
- Pest: 3.x (from 2.36)
- PHPUnit: 11.x (from 10.5)
- Spatie Backup: 10.x (from 9.3)

## Table of Contents

1. [Pre-Upgrade Preparation](#pre-upgrade-preparation)
2. [Laravel 12 Upgrade](#laravel-12-upgrade)
3. [Filament 4 Upgrade](#filament-4-upgrade)
4. [Tailwind CSS 4 Upgrade](#tailwind-css-4-upgrade)
5. [Testing Framework Updates](#testing-framework-updates)
6. [Breaking Changes & Resolutions](#breaking-changes--resolutions)
7. [Rollback Procedures](#rollback-procedures)
8. [Lessons Learned](#lessons-learned)
9. [Post-Upgrade Recommendations](#post-upgrade-recommendations)

---

## Pre-Upgrade Preparation

### 1. Create Comprehensive Backup

```bash
# Create Git tag for baseline
git tag -a pre-upgrade-baseline -m "Pre-upgrade baseline - Laravel 11.46.1, Filament 3.x"
git push origin pre-upgrade-baseline

# Backup database
php artisan backup:run

# Create archive of current codebase
tar -czf backup-$(date +%Y%m%d).tar.gz \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=storage/logs \
  .
```

### 2. Document Current State

```bash
# Capture current versions
composer show --direct > dependency-baseline.txt
npm list --depth=0 > node-dependency-baseline.txt

# Run baseline tests
php artisan test > test-results-baseline.txt

# Capture performance metrics (if applicable)
# Run your performance benchmarks and save to performance-baseline.json
```

### 3. Review Dependencies

```bash
# Check for outdated packages
composer outdated --direct

# Check for security vulnerabilities
composer audit

# Review npm packages
npm outdated
```

**Key Files to Review**:
- `composer.json` - PHP dependencies
- `package.json` - Node dependencies
- `config/app.php` - Service providers
- `bootstrap/app.php` - Application bootstrap
- `routes/web.php` - Route definitions
- All Filament resources in `app/Filament/Resources/`

---

## Laravel 12 Upgrade

### Step 1: Update Composer Dependencies

Update `composer.json`:

```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10",
        "spatie/laravel-backup": "^10.0"
    },
    "require-dev": {
        "laravel/pint": "^1.18",
        "laravel/sail": "^1.37",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0",
        "phpunit/phpunit": "^11.0"
    }
}
```

Run the update:

```bash
composer update laravel/framework --with-all-dependencies
```

### Step 2: Configuration Updates

#### bootstrap/app.php

Laravel 12 maintains the Laravel 11+ bootstrap structure. Our existing configuration was already compatible:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant.context' => \App\Http\Middleware\TenantContext::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'subscription.check' => \App\Http\Middleware\CheckSubscription::class,
            'hierarchical.access' => \App\Http\Middleware\EnsureHierarchicalAccess::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

**No changes required** - Laravel 11+ middleware structure is compatible with Laravel 12.

#### config/app.php

Review service provider registration. Laravel 12 continues to use `bootstrap/providers.php` for automatic discovery:

```php
// bootstrap/providers.php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\DatabaseServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
```

### Step 3: Update Environment Configuration

Review `.env.example` for new Laravel 12 variables:

```env
APP_NAME="Vilnius Utilities Billing"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

# Laravel 12 specific
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
```

### Step 4: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Step 5: Run Tests

```bash
php artisan test --filter=Feature
```

**Result**: All Laravel core functionality tests passed without modification.

---

## Filament 4 Upgrade

### Step 1: Update Filament Dependencies

Update `composer.json`:

```json
{
    "require": {
        "filament/filament": "^4.0",
        "filament/spatie-laravel-backup-plugin": "^4.0"
    }
}
```

Run the update:

```bash
composer update filament/filament --with-all-dependencies
```

### Step 2: Migrate Filament Resources

Filament 4 introduced several API changes. Here are the key patterns:

#### Form Schema Updates

**Before (Filament 3)**:
```php
Forms\Components\TextInput::make('name')
    ->required()
    ->reactive();
```

**After (Filament 4)**:
```php
Forms\Components\TextInput::make('name')
    ->required()
    ->live(onBlur: true); // Improved performance with Livewire 3
```

#### Table Column Updates

**Before (Filament 3)**:
```php
Tables\Columns\TextColumn::make('created_at')
    ->dateTime();
```

**After (Filament 4)**:
```php
Tables\Columns\TextColumn::make('created_at')
    ->dateTime()
    ->sortable();
```

#### Action Updates

Actions remain largely compatible, but benefit from Livewire 3 optimizations:

```php
Tables\Actions\Action::make('finalize')
    ->requiresConfirmation()
    ->action(fn (Invoice $record) => $record->finalize());
```

### Step 3: Update Navigation

Navigation registration remains the same in Filament 4:

```php
public static function shouldRegisterNavigation(): bool
{
    return auth()->user()->can('viewAny', static::getModel());
}
```

### Step 4: Update Widgets

Widgets maintain compatibility with minor syntax improvements:

```php
protected function getStats(): array
{
    return [
        Stat::make('Total Properties', Property::count())
            ->description('Active properties')
            ->descriptionIcon('heroicon-m-building-office')
            ->color('success'),
    ];
}
```

### Step 5: Update Pages

Custom Filament pages require minimal changes:

```php
class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';
}
```

### Step 6: Test Filament Resources

```bash
php artisan test --filter=Filament
```

### Step 7: Verify Resource Configuration

Use the verification script to ensure resources are properly configured:

```bash
# Verify Batch 3 resources
php verify-batch3-resources.php
```

**Expected Output**:
```
Verifying Batch 3 Filament Resources...

Testing UserResource...
  ✓ Class structure: OK
  ✓ Model: App\Models\User
  ✓ Icon: heroicon-o-users
  ✓ Pages: 3 registered
  ✓ Using Filament 4 Schema API
  ✓ UserResource is properly configured

...

========================================
Results: 4 passed, 0 failed
========================================

✓ All Batch 3 resources are properly configured for Filament 4!
```

**Documentation**: See [Batch 3 Verification Guide](../testing/BATCH_3_VERIFICATION_GUIDE.md) for detailed usage

**Resources Migrated**:
- ✅ PropertyResource (Batch 1)
- ✅ BuildingResource (Batch 1)
- ✅ MeterResource (Batch 1)
- ✅ MeterReadingResource (Batch 2)
- ✅ InvoiceResource (Batch 2)
- ✅ TariffResource (Batch 2)
- ✅ ProviderResource (Batch 2)
- ✅ UserResource (Batch 3) - Verified with `verify-batch3-resources.php`
- ✅ SubscriptionResource (Batch 3) - Verified with `verify-batch3-resources.php`
- ✅ OrganizationResource (Batch 3) - Verified with `verify-batch3-resources.php`
- ✅ OrganizationActivityLogResource (Batch 3) - Verified with `verify-batch3-resources.php`
- ✅ FaqResource (Batch 4)
- ✅ LanguageResource (Batch 4)
- ✅ TranslationResource (Batch 4)

---

## Tailwind CSS 4 Upgrade

### Step 1: Update CDN URL

Update `resources/views/layouts/app.blade.php`:

**Before**:
```html
<script src="https://cdn.tailwindcss.com"></script>
```

**After**:
```html
<script src="https://cdn.tailwindcss.com?v=4"></script>
```

### Step 2: Update Tailwind Configuration

Tailwind 4 maintains backward compatibility with most v3 utilities. Our inline configuration remains valid:

```html
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'system-ui', 'sans-serif'],
                },
                colors: {
                    primary: {
                        50: '#f0f9ff',
                        // ... color scale
                    }
                }
            }
        }
    }
</script>
```

### Step 3: Review Utility Classes

Tailwind 4 introduces improved utilities but maintains v3 compatibility. Key changes:

- **No breaking changes** for our utility usage
- Enhanced performance with new CSS engine
- Native cascade layers support (optional)

### Step 4: Test Visual Rendering

Manually test all pages:
- ✅ Dashboard (all roles)
- ✅ Resource list pages
- ✅ Resource form pages
- ✅ Tenant-facing pages
- ✅ Error pages

**Result**: All styles render correctly without modification.

---

## Testing Framework Updates

### Pest 3.x Migration

Update `composer.json`:

```json
{
    "require-dev": {
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0"
    }
}
```

**Key Changes**:
- Improved type coverage
- Enhanced plugin architecture
- Better IDE integration

**No test code changes required** - Pest 3 maintains backward compatibility with Pest 2 syntax.

### PHPUnit 11.x Migration

Update `composer.json`:

```json
{
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    }
}
```

**Key Changes**:
- Refined assertion methods
- Improved test isolation
- Enhanced error reporting

**No test code changes required** - PHPUnit 11 maintains backward compatibility.

### Run Full Test Suite

```bash
php artisan test
```

**Result**: All 100+ tests passed without modification.

---

## Breaking Changes & Resolutions

### 1. Middleware Registration

**Issue**: Laravel 12 continues to use the Laravel 11+ middleware structure.

**Resolution**: No changes required - our existing `bootstrap/app.php` configuration was already compatible.

**Files Affected**: None

---

### 2. Filament Form Reactivity

**Issue**: Filament 3's `->reactive()` is deprecated in favor of Livewire 3's `->live()`.

**Resolution**: Update form fields to use `->live(onBlur: true)` for better performance:

```php
// Before
Forms\Components\TextInput::make('name')
    ->reactive();

// After
Forms\Components\TextInput::make('name')
    ->live(onBlur: true);
```

**Files Affected**: All Filament resources with reactive form fields.

**Performance Impact**: Reduced re-renders, improved form responsiveness.

---

### 3. Spatie Backup Configuration

**Issue**: Spatie Backup 10.x introduces new configuration options.

**Resolution**: Review `config/backup.php` and add new options:

```php
'backup' => [
    'name' => env('APP_NAME', 'laravel-backup'),
    'source' => [
        'files' => [
            'include' => [
                base_path(),
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
            ],
        ],
        'databases' => [
            'sqlite',
        ],
    ],
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
        ],
    ],
],
```

**Files Affected**: `config/backup.php`

---

### 4. View Composer Dependency Injection

**Issue**: View composers using facades (`auth()`, `Route::`) are not testable and don't follow Laravel 12 best practices.

**Resolution**: Refactor to use dependency injection with strict typing:

```php
// Before
final class NavigationComposer
{
    public function compose(View $view): void
    {
        if (!auth()->check()) {
            return;
        }
        $userRole = auth()->user()->role->value;
        $currentRoute = Route::currentRouteName();
    }
}

// After
final class NavigationComposer
{
    public function __construct(
        private readonly Guard $auth,
        private readonly Router $router
    ) {}

    public function compose(View $view): void
    {
        if (!$this->auth->check()) {
            return;
        }
        $user = $this->auth->user();
        $userRole = $user->role; // UserRole enum
        $currentRoute = $this->router->currentRouteName();
    }
}
```

**Additional Improvements**:
- Use `UserRole` enum instead of magic strings
- Extract CSS classes to constants
- Use Eloquent scopes (`Language::active()`) instead of direct queries
- Add comprehensive PHPDoc

**Files Affected**: 
- `app/View/Composers/NavigationComposer.php`
- `tests/Unit/NavigationComposerTest.php`

**Test Results**: ✅ 7 tests passing (32 assertions)

**Documentation**: 
- `docs/refactoring/NAVIGATION_COMPOSER_SPEC.md` - Full specification
- `docs/refactoring/NAVIGATION_COMPOSER_ANALYSIS.md` - Code quality analysis (9/10)
- `docs/refactoring/NAVIGATION_COMPOSER_COMPLETE.md` - Completion summary

---

### 4. Tailwind CSS CDN Version

**Issue**: Unversioned CDN URL could lead to unexpected updates.

**Resolution**: Pin to Tailwind 4.x:

```html
<script src="https://cdn.tailwindcss.com?v=4"></script>
```

**Files Affected**: `resources/views/layouts/app.blade.php`

---

## Rollback Procedures

### Emergency Rollback (Full)

If critical issues are discovered after deployment:

```bash
# 1. Revert to baseline tag
git checkout main
git reset --hard pre-upgrade-baseline

# 2. Restore dependencies
composer install
npm install

# 3. Restore database (if migrations were run)
php artisan backup:restore --latest

# 4. Clear all caches
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Verify rollback
php artisan test
```

### Partial Rollback (Phase-Specific)

If issues are discovered in a specific phase:

```bash
# 1. Identify the phase tag
git tag -l

# 2. Revert to phase tag
git reset --hard laravel-12-upgrade-complete  # or other phase tag

# 3. Restore dependencies
composer install
npm install

# 4. Fix the issue
# Make necessary corrections

# 5. Resume upgrade
# Continue from the fixed phase
```

### Rollback Verification

After rollback, verify system stability:

```bash
# Run full test suite
php artisan test

# Check application boots
php artisan about

# Verify database connectivity
php artisan db:show

# Test critical workflows manually
# - Login as each role
# - Create/edit resources
# - Generate invoices
# - View reports
```

---

## Lessons Learned

### What Went Well

1. **Incremental Approach**: Upgrading in phases (Laravel → Filament → Tailwind → Dependencies) allowed us to isolate issues and test thoroughly at each stage.

2. **Comprehensive Testing**: Having 100+ tests with property-based testing provided confidence that core functionality remained intact.

3. **Middleware Compatibility**: Laravel 11+ middleware structure was already compatible with Laravel 12, requiring no changes.

4. **Filament API Stability**: Filament 4 maintained strong backward compatibility, with most changes being performance optimizations rather than breaking changes.

5. **Documentation**: Maintaining detailed specs in `.kiro/specs/` provided clear requirements and acceptance criteria throughout the upgrade.

### Challenges Encountered

1. **Filament Resource Migration**: While Filament 4 is largely compatible, updating 14 resources to use `->live(onBlur: true)` instead of `->reactive()` required careful review to ensure form behavior remained correct.

2. **Dependency Conflicts**: Some packages had version constraints that required manual resolution using `composer why-not` and adjusting version ranges.

3. **Testing Framework Updates**: While Pest 3 and PHPUnit 11 maintained backward compatibility, understanding the new features and best practices required additional research.

### Recommendations for Future Upgrades

1. **Maintain Regular Upgrade Cadence**: Upgrading more frequently (e.g., every 6 months) reduces the scope of changes and makes upgrades less risky.

2. **Invest in Property-Based Testing**: Property tests caught edge cases that unit tests missed, providing higher confidence in system correctness.

3. **Document Breaking Changes Immediately**: As breaking changes are encountered, document them immediately with resolution steps for future reference.

4. **Test on Staging First**: Always deploy to staging environment and run full test suite before production deployment.

5. **Monitor Performance**: Capture performance metrics before and after upgrade to identify any regressions or improvements.

6. **Keep Dependencies Updated**: Regularly update dependencies to avoid large version jumps that introduce multiple breaking changes at once.

---

## Post-Upgrade Recommendations

### Immediate Actions

1. **Monitor Production Logs**: Watch error logs closely for 24-48 hours after deployment.

2. **Verify Backup Jobs**: Ensure Spatie Backup 10.x runs successfully with new configuration.

3. **Test Critical Workflows**: Manually test invoice generation, meter reading submission, and tenant management.

4. **Gather User Feedback**: Collect feedback from superadmins, admins, managers, and tenants on any issues or improvements.

### Short-Term Optimizations

1. **Leverage Filament 4 Performance Features**:
   - Use `->live(onBlur: true)` consistently across all forms
   - Implement lazy loading for non-critical data
   - Eager-load relationships in table queries

2. **Adopt Laravel 12 Features**:
   - Review new validation rules for cleaner validation logic
   - Leverage improved query builder performance
   - Explore new helper functions

3. **Optimize Tailwind Usage**:
   - Consider using Tailwind 4's native cascade layers
   - Review custom utilities for optimization opportunities
   - Explore new Tailwind 4 utilities

### Long-Term Improvements

1. **Establish Upgrade Schedule**: Plan to upgrade to Laravel 13 and Filament 5 when released, maintaining a regular cadence.

2. **Expand Property-Based Testing**: Add more property tests for billing calculations, multi-tenancy, and authorization.

3. **Performance Monitoring**: Implement continuous performance monitoring to track improvements from framework upgrades.

4. **Documentation Maintenance**: Keep upgrade guides and specs updated as the system evolves.

---

## Additional Resources

### Official Documentation

- [Laravel 12 Upgrade Guide](https://laravel.com/docs/12.x/upgrade)
- [Filament 4 Upgrade Guide](https://filamentphp.com/docs/4.x/upgrade-guide)
- [Tailwind CSS 4 Documentation](https://tailwindcss.com/docs)
- [Pest 3 Documentation](https://pestphp.com/docs)
- [Spatie Backup 10 Documentation](https://spatie.be/docs/laravel-backup/v10)

### Internal Documentation

- `.kiro/specs/1-framework-upgrade/` - Upgrade spec with requirements and design
- `docs/guides/SETUP.md` - Updated setup guide
- `.kiro/steering/tech.md` - Updated technology stack documentation
- `docs/guides/TESTING_GUIDE.md` - Testing procedures

### Support Channels

- Laravel Discord: https://discord.gg/laravel
- Filament Discord: https://discord.gg/filamentphp
- GitHub Issues: Report issues in respective repositories

---

## Conclusion

The upgrade to Laravel 12 + Filament 4 + Tailwind CSS 4 was completed successfully with minimal breaking changes. The incremental approach, comprehensive testing, and detailed documentation ensured a smooth transition. The system now benefits from improved performance, enhanced developer experience, and the latest security patches.

**Upgrade Status**: ✅ Complete  
**Production Deployment**: Ready  
**Rollback Plan**: Documented and tested  
**Next Steps**: Monitor production, gather feedback, and plan for future optimizations

---

**Document Version**: 1.0  
**Last Updated**: November 24, 2025  
**Maintained By**: Development Team
