# Translation Testing Scripts

## Overview

This document provides comprehensive guidance for testing and validating the translation system in the Vilnius Utilities Management Platform. It covers diagnostic scripts, testing procedures, and integration with the development workflow.

## Available Scripts

### Primary Diagnostic Script (Framework-Agnostic)

**Script**: `test-translations-simple.php`
**Location**: Project root
**Purpose**: Fast, framework-agnostic translation validation for CI/CD

#### Features
- **Framework-agnostic**: No Laravel bootstrap required
- **Critical key validation**: Tests essential platform translations
- **Coverage reporting**: Detailed metrics with 95% target
- **Multi-locale support**: English and Lithuanian
- **Syntax validation**: Checks PHP syntax in translation files
- **CI/CD friendly**: Proper exit codes and structured output
- **Error reporting**: Comprehensive error detection and reporting

#### Usage Patterns

```bash
# Test all supported locales
php test-translations-simple.php

# Test specific locale
php test-translations-simple.php en
php test-translations-simple.php lt

# CI/CD usage with exit code checking
php test-translations-simple.php && echo "Translations OK" || echo "Translation errors found"
```

### Secondary Diagnostic Script (Laravel-Dependent)

**Script**: `test-translation.php`
**Location**: Project root  
**Purpose**: Comprehensive Laravel-based translation diagnostics

#### Features
- Translation key resolution testing
- File structure validation
- Performance benchmarking
- System configuration validation
- Laravel service integration

#### Usage Patterns

```bash
# Basic diagnostics (default locale)
php test-translation.php

# Test specific locale with Laravel context
php test-translation.php lt

# Performance benchmarking
php test-translation.php en | grep "Translation Resolution"
```

### Integration Scripts

#### Batch Testing Script
Create `scripts/test-all-translations.bat` for Windows environments:

```batch
@echo off
echo Testing English translations...
php test-translations-simple.php en
if %ERRORLEVEL% neq 0 (
    echo Translation tests failed!
    pause
    exit /b 1
)

echo.
echo Testing Lithuanian translations...
php test-translations-simple.php lt
if %ERRORLEVEL% neq 0 (
    echo Translation tests failed!
    pause
    exit /b 1
)

echo.
echo All translation tests passed!
pause
```

#### PowerShell Testing Script
Create `scripts/Test-Translations.ps1`:

```powershell
#!/usr/bin/env pwsh

param(
    [string]$Locale = "all",
    [switch]$Verbose,
    [switch]$FrameworkAgnostic = $true
)

$supportedLocales = @("en", "lt")

function Test-Locale {
    param(
        [string]$LocaleCode,
        [bool]$UseFrameworkAgnostic = $true
    )
    
    Write-Host "Testing locale: $LocaleCode" -ForegroundColor Yellow
    
    if ($UseFrameworkAgnostic) {
        $output = & php test-translations-simple.php $LocaleCode 2>&1
    } else {
        $output = & php test-translation.php $LocaleCode 2>&1
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ $LocaleCode tests passed" -ForegroundColor Green
    } else {
        Write-Host "✗ $LocaleCode tests failed" -ForegroundColor Red
    }
    
    if ($Verbose) {
        Write-Host $output
    }
    
    return $LASTEXITCODE -eq 0
}

if ($Locale -eq "all") {
    $allPassed = $true
    foreach ($loc in $supportedLocales) {
        $result = Test-Locale -LocaleCode $loc -UseFrameworkAgnostic $FrameworkAgnostic
        $allPassed = $allPassed -and $result
    }
    
    if ($allPassed) {
        Write-Host "All translation tests passed!" -ForegroundColor Green
        exit 0
    } else {
        Write-Host "Some translation tests failed!" -ForegroundColor Red
        exit 1
    }
} else {
    $result = Test-Locale -LocaleCode $Locale -UseFrameworkAgnostic $FrameworkAgnostic
    exit ($result ? 0 : 1)
}
```

## Testing Procedures

### Development Workflow Integration

#### Pre-Commit Testing
Add to `.git/hooks/pre-commit`:

```bash
#!/bin/bash
echo "Running translation tests..."

# Use framework-agnostic script for speed
php test-translations-simple.php en
if [ $? -ne 0 ]; then
    echo "English translation tests failed!"
    exit 1
fi

php test-translations-simple.php lt
if [ $? -ne 0 ]; then
    echo "Lithuanian translation tests failed!"
    exit 1
fi

echo "Translation tests passed!"
```

#### CI/CD Integration
Add to GitHub Actions workflow:

```yaml
name: Translation Tests

on: [push, pull_request]

jobs:
  translation-tests:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        
    - name: Install Dependencies
      run: composer install --no-dev --optimize-autoloader
      
    - name: Test English Translations (Framework-Agnostic)
      run: php test-translations-simple.php en
      
    - name: Test Lithuanian Translations (Framework-Agnostic)
      run: php test-translations-simple.php lt
      
    - name: Validate Translation Coverage
      run: php artisan test --filter=LocalizationTest
      
    - name: Run Laravel-based Translation Diagnostics
      run: |
        php test-translation.php en
        php test-translation.php lt
```

### Manual Testing Procedures

#### New Translation Key Validation

1. **Add Translation Key**
   ```php
   // lang/en/app.php
   'new_feature' => [
       'title' => 'New Feature',
       'description' => 'Feature description',
   ],
   ```

2. **Test Key Resolution**
   ```bash
   # Framework-agnostic testing (fast)
   php test-translations-simple.php en
   # Verify new keys appear in output
   ```

3. **Add Lithuanian Translation**
   ```php
   // lang/lt/app.php
   'new_feature' => [
       'title' => 'Nauja funkcija',
       'description' => 'Funkcijos aprašymas',
   ],
   ```

4. **Validate Both Locales**
   ```bash
   # Test all locales at once
   php test-translations-simple.php
   # Confirm Lithuanian translations work
   ```

#### Translation File Integrity Check

1. **Syntax Validation**
   ```bash
   # Check PHP syntax
   php -l lang/en/app.php
   php -l lang/lt/app.php
   
   # Check array structure
   php -r "var_dump(include 'lang/en/app.php');"
   ```

2. **Key Consistency Check**
   ```bash
   # Compare key structures between locales
   php -r "
   \$en = include 'lang/en/app.php';
   \$lt = include 'lang/lt/app.php';
   \$enKeys = array_keys(\$en);
   \$ltKeys = array_keys(\$lt);
   \$missing = array_diff(\$enKeys, \$ltKeys);
   if (!empty(\$missing)) {
       echo 'Missing LT keys: ' . implode(', ', \$missing) . PHP_EOL;
   }
   "
   ```

## API Reference

### Framework-Agnostic Script (`test-translations-simple.php`)

#### Command Line Interface

```bash
# Test all supported locales
php test-translations-simple.php

# Test specific locale
php test-translations-simple.php <locale>

# Examples
php test-translations-simple.php en    # Test English only
php test-translations-simple.php lt    # Test Lithuanian only
```

#### Exit Codes

- `0` - All tests passed successfully
- `1` - Translation errors found (missing keys, syntax errors, low coverage)

#### Core Functions

##### `testTranslations(string $locale): array`

Tests translation completeness for a specific locale.

**Parameters:**
- `$locale` (string) - The locale code to test ('en', 'lt')

**Returns:**
```php
[
    'locale' => string,                              // Tested locale
    'missing' => string[],                           // Missing translation keys
    'present' => array{key: string, value: string}[], // Found translations
    'files_checked' => string[],                     // Translation files validated
    'coverage' => float,                             // Coverage percentage (0-100)
    'errors' => string[]                             // File or syntax errors
]
```

**Throws:**
- `InvalidArgumentException` - If locale is not supported

##### `displayResults(array $results): void`

Displays comprehensive test results in a user-friendly format.

**Parameters:**
- `$results` (array) - Results from `testTranslations()`

**Output Format:**
```
=== English (en) Translation Test ===
Files checked: app, common, dashboard, invoice
Present: 18
Missing: 1
Coverage: 94.7%

❌ Missing translations:
  • app.nav.new_feature

✅ Present translations (sample):
  • app.brand.name = 'Vilnius Utilities'
  • app.nav.dashboard = 'Dashboard'
  ...

⚠️ Coverage: 94.7% (Target: 95%+)
```

#### Critical Translation Keys

The script validates these essential platform keys:

**Brand Identity:**
- `app.brand.name` - Platform name
- `app.brand.product` - Product identifier

**Navigation (Filament Admin):**
- `app.nav.dashboard` - Main dashboard
- `app.nav.properties` - Property management
- `app.nav.buildings` - Building management
- `app.nav.meters` - Meter management
- `app.nav.invoices` - Invoice management
- `app.nav.tenants` - Tenant management
- `app.nav.managers` - Manager management
- `app.nav.users` - User management
- `app.nav.settings` - System settings
- `app.nav.reports` - Reporting system
- `app.nav.audit` - Audit logging
- `app.nav.logout` - User logout

**Error Handling:**
- `app.errors.access_denied` - Authorization errors
- `app.errors.generic` - General error messages

**Common UI Elements:**
- `common.created_at` - Timestamp labels
- `common.updated_at` - Timestamp labels
- `common.none` - Empty state indicators

#### Configuration

**Supported Locales:**
```php
$supportedLocales = ['en', 'lt'];
```

**Translation Files Checked:**
```php
$translationFiles = ['app', 'common', 'dashboard', 'invoice'];
```

**Coverage Target:**
- Minimum: 95%
- Warning threshold: 80-94%
- Error threshold: <80%

### Benchmarking Translation Resolution

The diagnostic script includes performance metrics:

```php
// Example output
Translation Resolution: 0.125ms average (100 iterations)
```

### Performance Optimization Testing

1. **Baseline Measurement**
   ```bash
   php test-translation.php en | grep "Translation Resolution"
   ```

2. **Cache Performance Test**
   ```bash
   # Clear cache
   php artisan optimize:clear
   
   # Test cold performance
   php test-translation.php en
   
   # Test warm performance
   php test-translation.php en
   ```

3. **Load Testing**
   ```php
   // Create load-test-translations.php
   <?php
   require_once __DIR__.'/vendor/autoload.php';
   $app = require_once __DIR__.'/bootstrap/app.php';
   $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
   $kernel->bootstrap();
   
   $iterations = 1000;
   $keys = ['app.nav.dashboard', 'app.brand.name', 'common.created_at'];
   
   $startTime = microtime(true);
   for ($i = 0; $i < $iterations; $i++) {
       foreach ($keys as $key) {
           __($key);
       }
   }
   $endTime = microtime(true);
   
   $totalTime = ($endTime - $startTime) * 1000;
   $avgTime = $totalTime / ($iterations * count($keys));
   
   echo "Load test: {$avgTime}ms average per translation\n";
   echo "Total time: {$totalTime}ms for " . ($iterations * count($keys)) . " translations\n";
   ```

## Error Handling and Debugging

### Common Error Scenarios

#### Missing Translation Files
```
Translation Directory: ✗ INACCESSIBLE
Missing Files: app.php, common.php
```

**Debug Steps**:
1. Check file existence: `ls -la lang/en/`
2. Verify permissions: `stat lang/en/app.php`
3. Check Laravel configuration: `php artisan config:show app.locale`

#### Translation Key Not Found
```
✗ MISSING: app.new.key = 'app.new.key' (fallback)
```

**Debug Steps**:
1. Verify key exists in file: `grep -r "new" lang/en/`
2. Check array structure: `php -r "print_r(include 'lang/en/app.php');"`
3. Clear translation cache: `php artisan optimize:clear`

#### Performance Issues
```
Translation Resolution: 15.234ms average (100 iterations)
```

**Debug Steps**:
1. Profile translation loading: Enable query logging
2. Check file sizes: `du -h lang/en/*`
3. Optimize translation files: Remove unused keys
4. Enable translation caching

### Advanced Debugging

#### Translation Loading Debug
```php
// Add to test-translation.php for debugging
$translator = app('translator');
$loader = $translator->getLoader();

// Check loaded namespaces
$reflection = new ReflectionClass($loader);
$property = $reflection->getProperty('namespaces');
$property->setAccessible(true);
$namespaces = $property->getValue($loader);

echo "Loaded namespaces: " . implode(', ', array_keys($namespaces)) . "\n";
```

#### Memory Usage Analysis
```php
// Add memory tracking to diagnostic script
$memoryBefore = memory_get_usage();
$translator = app('translator');
$translations = $translator->get('app');
$memoryAfter = memory_get_usage();

$memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;
echo "Memory used for translations: " . number_format($memoryUsed, 2) . " MB\n";
```

## Integration with Existing Tests

### PHPUnit Integration

```php
// tests/Feature/TranslationDiagnosticsTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class TranslationDiagnosticsTest extends TestCase
{
    public function test_translation_diagnostic_script_runs_successfully(): void
    {
        $output = shell_exec('php test-translation.php en 2>&1');
        
        $this->assertStringContains('Translation System Diagnostics', $output);
        $this->assertStringContains('✓ EXISTS: app.nav.dashboard', $output);
        $this->assertStringContains('Translator Service: ✓ LOADED', $output);
    }
    
    public function test_all_supported_locales_work(): void
    {
        $locales = ['en', 'lt'];
        
        foreach ($locales as $locale) {
            $output = shell_exec("php test-translation.php {$locale} 2>&1");
            
            $this->assertStringContains('Translation System Diagnostics', $output);
            $this->assertStringNotContains('✗ FAILED', $output);
        }
    }
    
    public function test_translation_performance_is_acceptable(): void
    {
        $output = shell_exec('php test-translation.php en 2>&1');
        
        // Extract performance metric
        preg_match('/Translation Resolution: ([\d.]+)ms/', $output, $matches);
        $this->assertNotEmpty($matches);
        
        $avgTime = (float) $matches[1];
        $this->assertLessThan(5.0, $avgTime, 'Translation resolution should be under 5ms');
    }
}
```

### Pest Integration

```php
// tests/Feature/TranslationDiagnostics.php
<?php

use function Pest\Laravel\artisan;

it('runs translation diagnostics successfully', function () {
    $output = shell_exec('php test-translation.php en 2>&1');
    
    expect($output)
        ->toContain('Translation System Diagnostics')
        ->toContain('✓ EXISTS: app.nav.dashboard')
        ->toContain('Translator Service: ✓ LOADED');
});

it('validates all supported locales', function () {
    $locales = ['en', 'lt'];
    
    foreach ($locales as $locale) {
        $output = shell_exec("php test-translation.php {$locale} 2>&1");
        
        expect($output)
            ->toContain('Translation System Diagnostics')
            ->not->toContain('✗ FAILED');
    }
});

it('maintains acceptable translation performance', function () {
    $output = shell_exec('php test-translation.php en 2>&1');
    
    preg_match('/Translation Resolution: ([\d.]+)ms/', $output, $matches);
    expect($matches)->not->toBeEmpty();
    
    $avgTime = (float) $matches[1];
    expect($avgTime)->toBeLessThan(5.0);
});
```

## Best Practices

### Script Maintenance

1. **Regular Updates**
   - Update test keys when adding new translation domains
   - Maintain performance benchmarks
   - Update file validation lists

2. **Documentation Sync**
   - Keep script documentation current
   - Update usage examples
   - Maintain troubleshooting guides

3. **Performance Monitoring**
   - Track performance trends over time
   - Set up alerts for performance degradation
   - Regular optimization reviews

### Development Guidelines

1. **Before Adding New Translations**
   - Run diagnostic script to establish baseline
   - Test new keys in isolation
   - Validate across all supported locales

2. **Before Deployment**
   - Run full translation test suite
   - Validate performance metrics
   - Check for missing translations

3. **Regular Maintenance**
   - Weekly translation validation
   - Monthly performance reviews
   - Quarterly translation file optimization

## Related Documentation

- [Translation Implementation Guide](../.kiro/steering/translation-guide.md)
- [Translation System Diagnostics](../testing/translation-diagnostics.md)
- [Localization Testing](../testing/localization-testing.md)
- [Laravel Localization](https://laravel.com/docs/localization)