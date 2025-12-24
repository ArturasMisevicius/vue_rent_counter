# Translation Diagnostics API Reference

## Overview

The Translation Diagnostics API provides programmatic access to translation system validation, testing, and performance monitoring capabilities. This API is primarily used by the `test-translation.php` diagnostic tool and can be integrated into automated testing workflows.

## Core Functions

### `getTestLocale(): string`

Retrieves the test locale from command line arguments or application default.

**Signature**
```php
function getTestLocale(): string
```

**Returns**
- `string` - The locale code to test (e.g., 'en', 'lt')

**Usage**
```php
$locale = getTestLocale();
// Returns 'en' if no argument provided
// Returns 'lt' if 'lt' passed as CLI argument
```

**Implementation Details**
- Checks global `$argv` array for command line arguments
- Falls back to `app()->getLocale()` if no argument provided
- Validates locale format (2-character ISO code)

---

### `testTranslationKey(string $key, string $locale): array`

Tests translation key resolution and provides detailed diagnostics.

**Signature**
```php
function testTranslationKey(string $key, string $locale): array
```

**Parameters**
- `$key` (string) - The translation key to test (e.g., 'app.nav.dashboard')
- `$locale` (string) - The locale to test against (e.g., 'en', 'lt')

**Returns**
```php
[
    'exists' => bool,        // Whether the translation exists
    'value' => string,       // The translated value or fallback
    'fallback_used' => bool  // Whether fallback locale was used
]
```

**Usage Examples**
```php
// Test existing key
$result = testTranslationKey('app.nav.dashboard', 'en');
// Returns: ['exists' => true, 'value' => 'Dashboard', 'fallback_used' => false]

// Test missing key
$result = testTranslationKey('nonexistent.key', 'en');
// Returns: ['exists' => false, 'value' => 'nonexistent.key', 'fallback_used' => true]

// Test key in different locale
$result = testTranslationKey('app.nav.dashboard', 'lt');
// Returns: ['exists' => true, 'value' => 'Prietaisų skydelis', 'fallback_used' => false]
```

**Error Handling**
- Gracefully handles invalid locales by falling back to application default
- Preserves original locale after testing
- Returns consistent structure even for missing keys

**Performance Considerations**
- Temporarily switches locale for testing
- Restores original locale after completion
- Minimal overhead for single key tests

---

### `validateTranslationFiles(string $locale): array`

Validates translation file structure and accessibility for a given locale.

**Signature**
```php
function validateTranslationFiles(string $locale): array
```

**Parameters**
- `$locale` (string) - The locale to validate (e.g., 'en', 'lt')

**Returns**
```php
[
    'files' => array,        // Array of found translation files
    'missing' => array,      // Array of missing required files
    'accessible' => bool     // Whether the locale directory is accessible
]
```

**Usage Examples**
```php
// Validate English translations
$result = validateTranslationFiles('en');
// Returns: [
//     'files' => ['app.php', 'common.php', 'invoice.php'],
//     'missing' => [],
//     'accessible' => true
// ]

// Validate missing locale
$result = validateTranslationFiles('fr');
// Returns: [
//     'files' => [],
//     'missing' => ['app.php', 'common.php', 'invoice.php'],
//     'accessible' => false
// ]
```

**Required Files**
The function checks for these essential translation files:
- `app.php` - Application-specific translations
- `common.php` - Common UI elements
- `invoice.php` - Invoice-related translations

**File Structure Validation**
```php
// Expected directory structure
lang/
├── {locale}/
│   ├── app.php          ✓ Required
│   ├── common.php       ✓ Required  
│   ├── invoice.php      ✓ Required
│   └── ...              ○ Optional
```

**Error Scenarios**
- **Directory Not Found**: `accessible` = false, all files in `missing`
- **Permission Denied**: `accessible` = false, files may exist but not readable
- **Partial Files**: `accessible` = true, some files in `missing` array

---

## Diagnostic Output Functions

### Translation System Status

**Function**: Internal diagnostic reporting
**Purpose**: Generates formatted diagnostic output for console display

**Output Format**
```
=== Translation System Diagnostics ===
Test Locale: en
Default Locale: en
Fallback Locale: en

=== Core Translation Tests ===
✓ EXISTS: app.nav.dashboard = 'Dashboard'
✓ EXISTS: app.brand.name = 'Vilnius Utilities'
✗ MISSING: nonexistent.key = 'nonexistent.key' (fallback)

=== Translation System Status ===
Translator Service: ✓ LOADED
Translation Directory: ✓ ACCESSIBLE
Required Files Found: 3/3

=== Performance Metrics ===
Translation Resolution: 0.125ms average (100 iterations)
```

**Status Indicators**
- `✓` - Success/Found/Loaded
- `✗` - Failure/Missing/Error
- `(fallback)` - Fallback locale used

### Performance Benchmarking

**Function**: Translation resolution performance measurement
**Purpose**: Measures and reports translation system performance

**Benchmark Process**
1. Execute 100 translation resolutions
2. Measure total execution time
3. Calculate average time per resolution
4. Report results in milliseconds

**Performance Thresholds**
- **Excellent**: < 1ms average
- **Good**: 1-2ms average
- **Acceptable**: 2-5ms average
- **Poor**: > 5ms average

**Usage in Monitoring**
```php
// Performance monitoring integration
$startTime = microtime(true);
for ($i = 0; $i < 100; $i++) {
    __('app.nav.dashboard');
}
$endTime = microtime(true);
$avgTime = ($endTime - $startTime) / 100 * 1000;

// Log performance metrics
Log::info('Translation Performance', [
    'average_time_ms' => $avgTime,
    'threshold_exceeded' => $avgTime > 5.0
]);
```

## Integration APIs

### Laravel Service Integration

**Service Resolution**
```php
// Access translator service
$translator = app('translator');

// Check service availability
$isLoaded = is_object($translator);

// Get translation loader
$loader = $translator->getLoader();
```

**Configuration Access**
```php
// Get locale configuration
$defaultLocale = app()->getLocale();
$fallbackLocale = config('app.fallback_locale');

// Get available locales (if configured)
$availableLocales = config('locales.available', ['en']);
```

### File System Integration

**Path Resolution**
```php
// Get language directory path
$langPath = lang_path();
// Returns: /path/to/project/lang

// Get locale-specific path
$localePath = lang_path('en');
// Returns: /path/to/project/lang/en

// Get specific file path
$filePath = lang_path('en/app.php');
// Returns: /path/to/project/lang/en/app.php
```

**File Validation**
```php
// Check file existence
$exists = file_exists(lang_path('en/app.php'));

// Check directory accessibility
$accessible = is_dir(lang_path('en')) && is_readable(lang_path('en'));

// Validate file syntax
$syntaxValid = (include lang_path('en/app.php')) !== false;
```

## Error Handling

### Exception Types

**Translation Key Not Found**
```php
try {
    $result = testTranslationKey('invalid.key', 'en');
    if (!$result['exists']) {
        throw new TranslationKeyNotFoundException("Key 'invalid.key' not found");
    }
} catch (TranslationKeyNotFoundException $e) {
    // Handle missing translation
}
```

**Locale Not Supported**
```php
try {
    $result = validateTranslationFiles('unsupported');
    if (!$result['accessible']) {
        throw new LocaleNotSupportedException("Locale 'unsupported' not available");
    }
} catch (LocaleNotSupportedException $e) {
    // Handle unsupported locale
}
```

**File System Errors**
```php
try {
    $langPath = lang_path('en');
    if (!is_readable($langPath)) {
        throw new TranslationFileException("Cannot read translation directory");
    }
} catch (TranslationFileException $e) {
    // Handle file system issues
}
```

### Error Recovery Strategies

**Graceful Degradation**
```php
function safeTranslationTest(string $key, string $locale): array
{
    try {
        return testTranslationKey($key, $locale);
    } catch (Exception $e) {
        Log::warning('Translation test failed', [
            'key' => $key,
            'locale' => $locale,
            'error' => $e->getMessage()
        ]);
        
        return [
            'exists' => false,
            'value' => $key,
            'fallback_used' => true,
            'error' => $e->getMessage()
        ];
    }
}
```

**Fallback Mechanisms**
```php
function getTranslationWithFallback(string $key, array $locales): string
{
    foreach ($locales as $locale) {
        $result = testTranslationKey($key, $locale);
        if ($result['exists']) {
            return $result['value'];
        }
    }
    
    // Ultimate fallback
    return $key;
}
```

## Performance Optimization

### Caching Strategies

**Translation Cache**
```php
// Enable translation caching
config(['cache.stores.translation' => [
    'driver' => 'redis',
    'connection' => 'cache',
    'ttl' => 3600,
]]);

// Cache translation results
$cacheKey = "translation.{$locale}.{$key}";
$result = Cache::remember($cacheKey, 3600, function () use ($key, $locale) {
    return testTranslationKey($key, $locale);
});
```

**Batch Testing**
```php
function batchTestTranslationKeys(array $keys, string $locale): array
{
    $results = [];
    $originalLocale = app()->getLocale();
    
    // Switch locale once for all tests
    app()->setLocale($locale);
    
    foreach ($keys as $key) {
        $translated = __($key);
        $results[$key] = [
            'exists' => $translated !== $key,
            'value' => $translated,
            'fallback_used' => $translated === $key
        ];
    }
    
    // Restore original locale
    app()->setLocale($originalLocale);
    
    return $results;
}
```

### Memory Optimization

**Efficient File Loading**
```php
function validateTranslationFilesEfficient(string $locale): array
{
    $langPath = lang_path($locale);
    $requiredFiles = ['app.php', 'common.php', 'invoice.php'];
    
    // Use glob for efficient file discovery
    $existingFiles = array_map('basename', glob($langPath . '/*.php'));
    
    return [
        'files' => array_intersect($requiredFiles, $existingFiles),
        'missing' => array_diff($requiredFiles, $existingFiles),
        'accessible' => is_dir($langPath) && is_readable($langPath)
    ];
}
```

## Testing Integration

### PHPUnit Integration

**Test Case Example**
```php
class TranslationDiagnosticsTest extends TestCase
{
    public function testTranslationKeyResolution(): void
    {
        $result = testTranslationKey('app.nav.dashboard', 'en');
        
        $this->assertTrue($result['exists']);
        $this->assertEquals('Dashboard', $result['value']);
        $this->assertFalse($result['fallback_used']);
    }
    
    public function testTranslationFileValidation(): void
    {
        $result = validateTranslationFiles('en');
        
        $this->assertTrue($result['accessible']);
        $this->assertContains('app.php', $result['files']);
        $this->assertEmpty($result['missing']);
    }
}
```

### Pest Integration

**Pest Test Example**
```php
it('resolves translation keys correctly', function () {
    $result = testTranslationKey('app.nav.dashboard', 'en');
    
    expect($result['exists'])->toBeTrue();
    expect($result['value'])->toBe('Dashboard');
    expect($result['fallback_used'])->toBeFalse();
});

it('validates translation files', function () {
    $result = validateTranslationFiles('en');
    
    expect($result['accessible'])->toBeTrue();
    expect($result['files'])->toContain('app.php');
    expect($result['missing'])->toBeEmpty();
});
```

## CLI Integration

### Command Line Usage

**Basic Usage**
```bash
# Test default locale
php test-translation.php

# Test specific locale
php test-translation.php en
php test-translation.php lt
```

**Advanced Usage**
```bash
# Test with verbose output
APP_DEBUG=true php test-translation.php en

# Test with performance profiling
XDEBUG_MODE=profile php test-translation.php en

# Test with memory monitoring
php -d memory_limit=128M test-translation.php en
```

### Exit Codes

**Standard Exit Codes**
- `0` - Success, all tests passed
- `1` - General failure, tests failed
- `2` - Invalid arguments or configuration
- `3` - File system errors
- `4` - Performance threshold exceeded

**Usage in Scripts**
```bash
#!/bin/bash
php test-translation.php en
if [ $? -eq 0 ]; then
    echo "Translation tests passed"
else
    echo "Translation tests failed"
    exit 1
fi
```

## Related APIs

### Laravel Translation API

**Core Laravel Functions**
```php
// Basic translation
__('app.nav.dashboard')
trans('app.nav.dashboard')

// Translation with parameters
__('welcome.message', ['name' => 'John'])

// Pluralization
trans_choice('messages.notifications', $count)

// Check if translation exists
Lang::has('app.nav.dashboard')
```

### Filament Integration API

**Filament Translation Patterns**
```php
// Resource navigation
protected static ?string $navigationLabel = null;

public static function getNavigationLabel(): string
{
    return __('customer.navigation');
}

// Form field labels
TextInput::make('name')->label(__('customer.fields.name'))

// Table column labels
TextColumn::make('name')->label(__('customer.fields.name'))
```

## Security Considerations

### Input Validation

**Locale Validation**
```php
function validateLocale(string $locale): bool
{
    // Validate locale format (2-character ISO code)
    if (!preg_match('/^[a-z]{2}$/', $locale)) {
        return false;
    }
    
    // Check if locale is supported
    $supportedLocales = config('locales.available', ['en']);
    return in_array($locale, $supportedLocales);
}
```

**Key Validation**
```php
function validateTranslationKey(string $key): bool
{
    // Validate key format (dot notation)
    if (!preg_match('/^[a-z0-9_.]+$/', $key)) {
        return false;
    }
    
    // Prevent path traversal
    if (strpos($key, '..') !== false) {
        return false;
    }
    
    return true;
}
```

### File System Security

**Path Sanitization**
```php
function sanitizeLangPath(string $locale): string
{
    // Remove any path traversal attempts
    $locale = str_replace(['..', '/', '\\'], '', $locale);
    
    // Ensure locale is alphanumeric
    $locale = preg_replace('/[^a-z0-9]/', '', strtolower($locale));
    
    return lang_path($locale);
}
```

## Changelog

### Version 1.0.0 (Current)
- Initial API implementation
- Core diagnostic functions
- Performance benchmarking
- File validation capabilities
- CLI integration
- Error handling framework

### Planned Features
- **v1.1.0**: Translation coverage analysis
- **v1.2.0**: Automated translation validation
- **v1.3.0**: Performance regression detection
- **v2.0.0**: Multi-project translation management

## Related Documentation

- [Translation Implementation Guide](../.kiro/steering/translation-guide.md)
- [Translation System Diagnostics](../testing/translation-diagnostics.md)
- [Translation Testing Scripts](../scripts/translation-testing.md)
- [Translation Development Workflow](../development/translation-workflow.md)
- [Laravel Localization Documentation](https://laravel.com/docs/localization)