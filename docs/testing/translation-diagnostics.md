# Translation System Diagnostics

## Overview

The translation diagnostic system provides comprehensive testing and debugging capabilities for the Laravel translation system in the Vilnius Utilities Management Platform. This system validates translation file loading, locale configuration, and key resolution across multiple locales.

## Components

### Primary Diagnostic Tool (Framework-Agnostic)

**File**: `test-translations-simple.php`
**Purpose**: Fast, framework-agnostic translation validation
**Usage**: `php test-translations-simple.php [locale]`

### Key Features

1. **Framework Independence**
   - No Laravel bootstrap required
   - Fast execution for CI/CD pipelines
   - Minimal dependencies

2. **Critical Key Validation**
   - Tests essential platform translations
   - Validates Filament admin navigation
   - Checks error handling translations

3. **Coverage Reporting**
   - Detailed coverage metrics
   - 95% coverage target
   - CI/CD friendly exit codes

4. **File Validation**
   - PHP syntax checking
   - File accessibility verification
   - Error reporting and diagnostics

### Secondary Diagnostic Tool (Laravel-Based)

**File**: `test-translation.php`
**Purpose**: Comprehensive Laravel-integrated diagnostics
**Usage**: `php test-translation.php [locale]`

### Key Features

1. **Laravel Integration**
   - Full framework bootstrap
   - Service container access
   - Configuration validation

2. **Performance Metrics**
   - Translation resolution benchmarking
   - Memory usage analysis
   - Cache performance testing

3. **System Validation**
   - Middleware configuration
   - Service provider registration
   - Translation loader validation

## Usage Examples

### Framework-Agnostic Testing
```bash
# Test all locales (recommended for CI/CD)
php test-translations-simple.php

# Test specific locale
php test-translations-simple.php en
php test-translations-simple.php lt

# Check exit code for automation
php test-translations-simple.php && echo "OK" || echo "FAILED"
```

### Laravel-Based Diagnostics
```bash
# Test default locale (English)
php test-translation.php

# Test specific locale with performance metrics
php test-translation.php lt

# Test English locale explicitly
php test-translation.php en
```

### Expected Output (Framework-Agnostic)
```
=== English (en) Translation Test ===
Files checked: app, common, dashboard, invoice
Present: 19
Missing: 0
Coverage: 100.0%

✅ Present translations (sample):
  • app.brand.name = 'Vilnius Utilities'
  • app.nav.dashboard = 'Dashboard'
  • app.nav.properties = 'Properties'
  • app.nav.buildings = 'Buildings'
  • app.nav.meters = 'Meters'

✅ Coverage: 100.0%
```

### Expected Output (Laravel-Based)
```
=== Translation System Diagnostics ===
Test Locale: en
Default Locale: en
Fallback Locale: en

=== Core Translation Tests ===
✓ EXISTS: app.nav.dashboard = 'Dashboard'
✓ EXISTS: app.brand.name = 'Vilnius Utilities'
✓ EXISTS: common.created_at = 'Created At'

=== Translation System Status ===
Translator Service: ✓ LOADED
Translation Directory: ✓ ACCESSIBLE
Required Files Found: 4/4

=== Performance Metrics ===
Translation Resolution: 0.125ms average (100 iterations)

=== Diagnostic Complete ===
```

## Integration with Project Standards

### Translation Key Testing

The diagnostic tool tests key translation patterns used throughout the platform:

```php
// Navigation keys (app.nav.*)
'app.nav.dashboard'
'app.nav.buildings'
'app.nav.invoices'

// Brand keys (app.brand.*)
'app.brand.name'
'app.brand.product'

// Common keys (common.*)
'common.created_at'
'common.updated_at'
```

### File Structure Validation

Validates the expected translation file structure:

```
lang/
├── en/
│   ├── app.php          # Application-specific translations
│   ├── common.php       # Common UI elements
│   ├── invoice.php      # Invoice-related translations
│   └── ...
├── lt/
│   ├── app.php
│   ├── common.php
│   ├── invoice.php
│   └── ...
└── vendor/              # Vendor translation overrides
```

## API Reference

### Core Functions

#### `getTestLocale(): string`
Retrieves the test locale from command line arguments or uses the application default.

**Returns**: The locale code to test (e.g., 'en', 'lt')

#### `testTranslationKey(string $key, string $locale): array`
Tests translation key resolution and provides detailed diagnostics.

**Parameters**:
- `$key`: The translation key to test
- `$locale`: The locale to test against

**Returns**: Array with structure:
```php
[
    'exists' => bool,        // Whether the translation exists
    'value' => string,       // The translated value
    'fallback_used' => bool  // Whether fallback was used
]
```

#### `validateTranslationFiles(string $locale): array`
Validates translation file structure and accessibility.

**Parameters**:
- `$locale`: The locale to validate

**Returns**: Array with structure:
```php
[
    'files' => array,        // Found translation files
    'missing' => array,      // Missing required files
    'accessible' => bool     // Directory accessibility
]
```

## Error Handling

### Common Issues and Solutions

1. **Missing Translation Files**
   ```
   Missing Files: app.php, common.php
   ```
   **Solution**: Ensure translation files exist in `lang/{locale}/` directory

2. **Inaccessible Translation Directory**
   ```
   Translation Directory: ✗ INACCESSIBLE
   ```
   **Solution**: Check directory permissions and path configuration

3. **Translator Service Failed**
   ```
   Translator Service: ✗ FAILED
   ```
   **Solution**: Verify Laravel application bootstrap and service provider registration

4. **High Translation Resolution Time**
   ```
   Translation Resolution: 5.234ms average
   ```
   **Solution**: Consider translation caching or file optimization

## Integration with Testing Suite

### Automated Testing Integration

The diagnostic tool complements the existing test suite:

```php
// tests/Feature/LocalizationTest.php
public function test_translation_diagnostics_pass(): void
{
    $output = shell_exec('php test-translation.php en');
    $this->assertStringContains('✓ EXISTS: app.nav.dashboard', $output);
    $this->assertStringContains('Translator Service: ✓ LOADED', $output);
}
```

### CI/CD Integration

Include in continuous integration pipeline:

```yaml
# .github/workflows/tests.yml
- name: Run Translation Diagnostics
  run: |
    php test-translation.php en
    php test-translation.php lt
```

## Performance Considerations

### Benchmarking

The tool includes performance benchmarking to identify translation bottlenecks:

- **Target**: < 1ms average resolution time
- **Acceptable**: 1-5ms average resolution time
- **Concerning**: > 5ms average resolution time

### Optimization Strategies

1. **Translation Caching**
   ```php
   // config/cache.php
   'translation' => [
       'driver' => 'redis',
       'ttl' => 3600,
   ],
   ```

2. **File Optimization**
   - Minimize translation file size
   - Remove unused translation keys
   - Optimize array structure

3. **Lazy Loading**
   - Load translations on-demand
   - Cache frequently used translations

## Troubleshooting Guide

### Debug Mode

Enable verbose output for detailed debugging:

```bash
# Set debug environment
APP_DEBUG=true php test-translation.php
```

### Common Debugging Steps

1. **Verify File Permissions**
   ```bash
   ls -la lang/en/
   ```

2. **Check Laravel Configuration**
   ```bash
   php artisan config:show app.locale
   php artisan config:show app.fallback_locale
   ```

3. **Clear Translation Cache**
   ```bash
   php artisan optimize:clear
   php artisan config:clear
   ```

4. **Validate Translation Syntax**
   ```bash
   php -l lang/en/app.php
   ```

## Related Documentation

- [Translation Implementation Guide](../.kiro/steering/translation-guide.md)
- [Localization Testing](../testing/localization-testing.md)
- [Multi-language System](../functionality/multilanguage-system.md)
- [Laravel Localization Documentation](https://laravel.com/docs/localization)

## Changelog

### Version 1.0.0
- Initial implementation with comprehensive diagnostic capabilities
- Support for multiple locale testing
- Performance benchmarking integration
- File structure validation
- Integration with existing test suite