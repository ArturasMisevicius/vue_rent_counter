# Translation Development Workflow

## Overview

This document outlines the complete workflow for developing, testing, and maintaining translations in the Vilnius Utilities Management Platform. It provides step-by-step procedures for developers working with the multi-locale system.

## Development Workflow

### 1. Adding New Translation Keys

#### Step 1: Identify Translation Domain
Determine the appropriate translation file based on the feature:

- `app.php` - Application navigation, branding, core UI
- `common.php` - Shared UI elements, dates, actions
- `invoice.php` - Invoice-specific terminology
- `dashboard.php` - Dashboard widgets and metrics
- `validation.php` - Form validation messages

#### Step 2: Add English Translation
```php
// lang/en/app.php
return [
    'nav' => [
        'dashboard' => 'Dashboard',
        'new_feature' => 'New Feature', // Add new key
    ],
    // ... existing keys
];
```

#### Step 3: Test English Translation
```bash
# Test the new key resolution (framework-agnostic)
php test-translations-simple.php en

# Look for the new key in output
# Should show: ✅ Present translations (sample):
#   • app.nav.new_feature = 'New Feature'

# Alternative: Use Laravel-dependent script for detailed diagnostics
php test-translation.php en
```

#### Step 4: Add Lithuanian Translation
```php
// lang/lt/app.php
return [
    'nav' => [
        'dashboard' => 'Prietaisų skydelis',
        'new_feature' => 'Nauja funkcija', // Add Lithuanian equivalent
    ],
    // ... existing keys
];
```

#### Step 5: Validate Both Locales
```bash
# Test Lithuanian translation (framework-agnostic)
php test-translations-simple.php lt

# Test both locales at once
php test-translations-simple.php

# Run comprehensive Laravel-based validation
php artisan test --filter=LocalizationTest
```

### 2. Updating Existing Translations

#### Step 1: Identify Impact
Before changing existing translations, assess the impact:

```bash
# Search for usage across codebase
grep -r "app.nav.dashboard" app/ resources/
```

#### Step 2: Update Translations
```php
// Update both locales simultaneously
// lang/en/app.php
'nav' => [
    'dashboard' => 'Control Panel', // Updated
],

// lang/lt/app.php
'nav' => [
    'dashboard' => 'Valdymo skydelis', // Updated
],
```

#### Step 3: Test Changes
```bash
# Test both locales with framework-agnostic script
php test-translations-simple.php en
php test-translations-simple.php lt

# Alternative: Test all locales at once
php test-translations-simple.php

# Run full Laravel test suite
php artisan test
```

### 3. Removing Deprecated Translations

#### Step 1: Verify No Usage
```bash
# Ensure key is not used anywhere
grep -r "old.translation.key" app/ resources/ tests/
```

#### Step 2: Remove from All Locales
```php
// Remove from lang/en/app.php and lang/lt/app.php
// Ensure consistent removal across all locale files
```

#### Step 3: Validate Removal
```bash
# Test that removal doesn't break anything
php artisan test
php test-translations-simple.php en
```

## Testing Procedures

### Pre-Development Testing

#### Environment Validation
```bash
# Verify translation system is working (framework-agnostic)
php test-translations-simple.php

# Check all supported locales with detailed output
for locale in en lt; do
    echo "Testing $locale..."
    php test-translations-simple.php $locale
done

# Laravel-based comprehensive diagnostics
php test-translation.php
```

#### Baseline Performance
```bash
# Establish performance baseline
php test-translation.php en | grep "Translation Resolution"
# Target: < 1ms average
```

### During Development Testing

#### Incremental Testing
```bash
# Test after each translation addition (fast, framework-agnostic)
php test-translations-simple.php en

# Verify specific keys with Laravel
php -r "echo __('app.nav.new_feature') . PHP_EOL;"
```

#### Cross-Locale Validation
```bash
# Ensure consistency across locales
php -r "
app()->setLocale('en');
echo 'EN: ' . __('app.nav.dashboard') . PHP_EOL;
app()->setLocale('lt');
echo 'LT: ' . __('app.nav.dashboard') . PHP_EOL;
"
```

### Post-Development Testing

#### Comprehensive Validation
```bash
# Run full translation test suite
php artisan test --filter=Translation

# Run framework-agnostic diagnostic for all locales
php test-translations-simple.php

# Run Laravel-based diagnostics for specific locale
php test-translation.php en
php test-translation.php lt
```

#### Performance Regression Testing
```bash
# Compare performance before/after changes
php test-translation.php en | grep "Translation Resolution"
# Ensure no significant performance degradation
```

## Integration with Development Tools

### IDE Integration

#### PhpStorm Configuration
Add to `.idea/php.xml`:
```xml
<framework name="Laravel">
    <configuration>
        <option name="translation_keys_inspection" value="true" />
        <option name="translation_files_path" value="lang" />
    </configuration>
</framework>
```

#### VS Code Configuration
Add to `.vscode/settings.json`:
```json
{
    "laravel.translation.enabled": true,
    "laravel.translation.path": "lang",
    "files.associations": {
        "*.php": "php"
    }
}
```

### Git Hooks Integration

#### Pre-Commit Hook
Create `.git/hooks/pre-commit`:
```bash
#!/bin/bash

echo "Running translation validation..."

# Test English translations (framework-agnostic, fast)
php test-translations-simple.php en
if [ $? -ne 0 ]; then
    echo "❌ English translation tests failed"
    exit 1
fi

# Test Lithuanian translations
php test-translations-simple.php lt
if [ $? -ne 0 ]; then
    echo "❌ Lithuanian translation tests failed"
    exit 1
fi

echo "✅ Translation tests passed"
```

#### Pre-Push Hook
Create `.git/hooks/pre-push`:
```bash
#!/bin/bash

echo "Running comprehensive translation tests..."

# Run full localization test suite
php artisan test --filter=LocalizationTest --quiet
if [ $? -ne 0 ]; then
    echo "❌ Localization tests failed"
    exit 1
fi

echo "✅ All translation tests passed"
```

## Filament Integration Workflow

### Resource Translation Pattern

#### Step 1: Identify Filament Strings
```php
// Before: Hardcoded strings
class CustomerResource extends Resource
{
    protected static ?string $navigationLabel = 'Customers';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Customer Name'),
        ]);
    }
}
```

#### Step 2: Add Translation Keys
```php
// lang/en/customer.php
return [
    'navigation' => 'Customers',
    'fields' => [
        'name' => 'Customer Name',
    ],
];

// lang/lt/customer.php
return [
    'navigation' => 'Klientai',
    'fields' => [
        'name' => 'Kliento vardas',
    ],
];
```

#### Step 3: Update Resource
```php
// After: Using translations
class CustomerResource extends Resource
{
    protected static ?string $navigationLabel = null;
    
    public static function getNavigationLabel(): string
    {
        return __('customer.navigation');
    }
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('customer.fields.name')),
        ]);
    }
}
```

#### Step 4: Test Filament Integration
```bash
# Test resource translations
php artisan test --filter=CustomerResourceTest

# Validate in browser
# Switch locales and verify UI updates
```

### Widget Translation Pattern

#### Step 1: Add Widget Translations
```php
// lang/en/dashboard.php
return [
    'widgets' => [
        'stats' => [
            'total_customers' => 'Total Customers',
            'active_invoices' => 'Active Invoices',
        ],
    ],
];
```

#### Step 2: Update Widget
```php
class StatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('dashboard.widgets.stats.total_customers'), Customer::count()),
            Stat::make(__('dashboard.widgets.stats.active_invoices'), Invoice::active()->count()),
        ];
    }
}
```

## Quality Assurance Procedures

### Translation Review Checklist

#### Content Quality
- [ ] Translations are contextually appropriate
- [ ] Terminology is consistent across the application
- [ ] Grammar and spelling are correct
- [ ] Cultural considerations are addressed

#### Technical Quality
- [ ] All translation keys resolve correctly
- [ ] No hardcoded strings remain in code
- [ ] Performance impact is acceptable
- [ ] All locales have equivalent key structures

#### Testing Quality
- [ ] Diagnostic script passes for all locales
- [ ] Automated tests pass
- [ ] Manual UI testing completed
- [ ] Performance benchmarks maintained

### Code Review Guidelines

#### Translation-Related Changes
When reviewing PRs with translation changes:

1. **Verify Key Structure**
   ```bash
   # Check key consistency
   php -r "
   \$en = include 'lang/en/app.php';
   \$lt = include 'lang/lt/app.php';
   print_r(array_diff_key(\$en, \$lt));
   "
   ```

2. **Test Translation Resolution**
   ```bash
   # Test new keys (framework-agnostic, fast)
   php test-translations-simple.php en
   php test-translations-simple.php lt
   
   # Alternative: Laravel-based detailed diagnostics
   php test-translation.php en
   php test-translation.php lt
   ```

3. **Validate Usage**
   ```bash
   # Ensure keys are used in code
   grep -r "new.translation.key" app/ resources/
   ```

## Troubleshooting Common Issues

### Missing Translation Keys

#### Symptom
```
✗ MISSING: app.new.key = 'app.new.key' (fallback)
```

#### Solution Steps
1. **Verify File Structure**
   ```bash
   ls -la lang/en/app.php
   ls -la lang/lt/app.php
   ```

2. **Check Key Syntax**
   ```bash
   php -l lang/en/app.php
   php -l lang/lt/app.php
   ```

3. **Validate Array Structure**
   ```bash
   php -r "print_r(include 'lang/en/app.php');"
   ```

4. **Clear Caches**
   ```bash
   php artisan optimize:clear
   php artisan config:clear
   ```

### Performance Issues

#### Symptom
```
Translation Resolution: 15.234ms average (100 iterations)
```

#### Solution Steps
1. **Profile Translation Loading**
   ```bash
   # Enable query logging
   APP_DEBUG=true php test-translation.php en
   ```

2. **Optimize Translation Files**
   ```bash
   # Check file sizes
   du -h lang/en/*
   
   # Remove unused keys
   # Optimize array structure
   ```

3. **Enable Caching**
   ```php
   // config/cache.php
   'stores' => [
       'translation' => [
           'driver' => 'redis',
           'connection' => 'cache',
       ],
   ],
   ```

### Locale Switching Issues

#### Symptom
UI doesn't update when switching locales

#### Solution Steps
1. **Verify Middleware**
   ```bash
   php artisan route:list --middleware=SetLocale
   ```

2. **Check Session Storage**
   ```bash
   # Test session persistence
   php -r "
   session_start();
   \$_SESSION['locale'] = 'lt';
   echo 'Session locale: ' . \$_SESSION['locale'];
   "
   ```

3. **Validate Controller Logic**
   ```php
   // Check LanguageController@switch method
   // Ensure proper session handling
   ```

## Automation and CI/CD

### Automated Translation Validation

#### GitHub Actions Workflow
```yaml
name: Translation Validation

on:
  push:
    paths:
      - 'lang/**'
      - 'app/**'
      - 'resources/**'

jobs:
  validate-translations:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        
    - name: Install Dependencies
      run: composer install --no-dev
      
    - name: Run Translation Diagnostics
      run: |
        php test-translation.php en
        php test-translation.php lt
        
    - name: Run Localization Tests
      run: php artisan test --filter=LocalizationTest
      
    - name: Check Translation Performance
      run: |
        PERF=$(php test-translation.php en | grep "Translation Resolution" | grep -o '[0-9.]*ms')
        echo "Performance: $PERF"
        # Add performance threshold check if needed
```

#### Translation Coverage Report
```bash
#!/bin/bash
# scripts/translation-coverage.sh

echo "Translation Coverage Report"
echo "=========================="

# Count total keys in English
EN_KEYS=$(php -r "
\$files = glob('lang/en/*.php');
\$total = 0;
foreach (\$files as \$file) {
    \$data = include \$file;
    \$total += count(\$data, COUNT_RECURSIVE) - count(\$data);
}
echo \$total;
")

# Count total keys in Lithuanian
LT_KEYS=$(php -r "
\$files = glob('lang/lt/*.php');
\$total = 0;
foreach (\$files as \$file) {
    \$data = include \$file;
    \$total += count(\$data, COUNT_RECURSIVE) - count(\$data);
}
echo \$total;
")

COVERAGE=$(echo "scale=2; $LT_KEYS / $EN_KEYS * 100" | bc)

echo "English keys: $EN_KEYS"
echo "Lithuanian keys: $LT_KEYS"
echo "Coverage: $COVERAGE%"

if (( $(echo "$COVERAGE < 95" | bc -l) )); then
    echo "❌ Translation coverage below 95%"
    exit 1
else
    echo "✅ Translation coverage acceptable"
fi
```

## Best Practices Summary

### Development Best Practices

1. **Always Add Both Locales**
   - Never add keys to only one locale
   - Maintain structural consistency

2. **Use Descriptive Keys**
   - Follow snake_case convention
   - Group related keys logically

3. **Test Early and Often**
   - Run diagnostic script after each change
   - Use automated testing in CI/CD

4. **Performance Awareness**
   - Monitor translation resolution times
   - Optimize large translation files

5. **Documentation**
   - Document translation decisions
   - Maintain changelog for major changes

### Quality Assurance Best Practices

1. **Comprehensive Testing**
   - Test all supported locales
   - Validate UI in different languages
   - Check performance impact

2. **Code Review Standards**
   - Review translation changes carefully
   - Verify key usage in codebase
   - Check for hardcoded strings

3. **Maintenance Procedures**
   - Regular translation audits
   - Performance monitoring
   - Cleanup of unused keys

## Related Documentation

- [Translation Implementation Guide](../.kiro/steering/translation-guide.md)
- [Translation System Diagnostics](../testing/translation-diagnostics.md)
- [Translation Testing Scripts](../scripts/translation-testing.md)
- [Filament Conventions](../.kiro/steering/filament-conventions.md)
- [Laravel Localization Documentation](https://laravel.com/docs/localization)