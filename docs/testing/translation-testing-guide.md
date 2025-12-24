# Translation Testing Guide

## Overview

The Vilnius Utilities Platform supports multiple locales (English, Lithuanian, Russian) for its multi-tenant utilities billing system. This guide covers the translation testing infrastructure and best practices for maintaining translation completeness.

## Translation Testing Script

### Location
- **Script**: `test-translations.php` (project root)
- **Purpose**: Validate translation completeness across all supported locales
- **Target**: Critical UI strings for dashboard, navigation, and user-facing content

### Usage

#### Basic Usage
```bash
# Test all locales
php test-translations.php

# Test specific locale
php test-translations.php --locale=ru

# Show only missing translations
php test-translations.php --missing-only

# Verbose output with translation values
php test-translations.php --verbose

# JSON output for CI/CD integration
php test-translations.php --json
```

#### Command Line Options

| Option | Description | Example |
|--------|-------------|---------|
| `--locale=xx` | Test only specific locale (en, lt, ru) | `--locale=ru` |
| `--verbose` | Show detailed output with translation values | `--verbose` |
| `--json` | Output results in JSON format | `--json` |
| `--missing-only` | Show only missing translations | `--missing-only` |
| `--help` | Show help message | `--help` |

### Supported Locales

| Code | Language | Native Name |
|------|----------|-------------|
| `en` | English | English |
| `lt` | Lithuanian | Lietuvių |
| `ru` | Russian | Русский |

## Critical Translation Keys

The testing script validates these essential translation keys:

### Dashboard Translations
- `dashboard.manager.title` - Manager dashboard title
- `dashboard.manager.description` - Manager dashboard description
- `dashboard.manager.stats.total_properties` - Total properties metric
- `dashboard.manager.stats.active_meters` - Active meters metric
- `dashboard.admin.title` - Admin dashboard title
- `dashboard.tenant.title` - Tenant dashboard title

### Landing Page Translations
- `landing.hero.title` - Main hero section title
- `landing.hero.tagline` - Hero section tagline
- `landing.features_title` - Features section title

### Application Branding
- `app.brand.name` - Application brand name
- `app.brand.product` - Product name

### Common UI Elements
- `common.yes` - Yes button/label
- `common.no` - No button/label
- `common.view` - View action
- `common.edit` - Edit action
- `common.delete` - Delete action

### Navigation
- `app.nav.dashboard` - Dashboard navigation
- `app.nav.properties` - Properties navigation
- `app.nav.meters` - Meters navigation
- `app.nav.invoices` - Invoices navigation

### Superadmin Interface
- `superadmin.dashboard.title` - Superadmin dashboard title
- `superadmin.navigation.tenants` - Tenants navigation

### Error Handling
- `app.errors.access_denied` - Access denied message
- `app.errors.generic` - Generic error message

## Translation File Structure

### Directory Layout
```
lang/
├── en/                 # English (base locale)
│   ├── app.php
│   ├── dashboard.php
│   ├── landing.php
│   ├── common.php
│   └── superadmin.php
├── lt/                 # Lithuanian
│   ├── app.php
│   ├── dashboard.php
│   ├── landing.php
│   ├── common.php
│   └── superadmin.php
└── ru/                 # Russian
    ├── app.php
    ├── dashboard.php
    ├── landing.php
    ├── common.php
    └── superadmin.php
```

### File Organization

#### `app.php` - Application-wide translations
- Branding and product names
- Navigation labels
- Common error messages
- Authentication messages

#### `dashboard.php` - Dashboard-specific translations
- Role-specific dashboard content (admin, manager, tenant)
- Statistics and metrics labels
- Widget descriptions
- Quick action labels

#### `landing.php` - Landing page translations
- Hero section content
- Feature descriptions
- FAQ content
- Call-to-action text

#### `common.php` - Shared UI elements
- Button labels (yes, no, save, cancel)
- Action labels (view, edit, delete)
- Status indicators
- Form validation messages

#### `superadmin.php` - Superadmin interface
- Organization management
- Tenant administration
- System configuration
- Audit and monitoring

## Testing Integration

### CI/CD Integration

Add to your CI pipeline:

```yaml
# .github/workflows/tests.yml
- name: Test Translations
  run: |
    php test-translations.php --json > translation-results.json
    # Parse results and fail if coverage < 95%
```

### Pre-commit Hook

```bash
#!/bin/sh
# .git/hooks/pre-commit
php test-translations.php --missing-only
if [ $? -ne 0 ]; then
    echo "❌ Translation tests failed. Please add missing translations."
    exit 1
fi
```

### Automated Testing with Pest

```php
// tests/Feature/TranslationCompletenessTest.php
it('has complete translations for all critical keys', function () {
    $locales = ['en', 'lt', 'ru'];
    $criticalKeys = [
        'dashboard.manager.title',
        'landing.hero.title',
        'app.brand.name',
        // ... other critical keys
    ];
    
    foreach ($locales as $locale) {
        app()->setLocale($locale);
        
        foreach ($criticalKeys as $key) {
            $translation = __($key);
            expect($translation)->not->toBe($key, "Missing translation for {$key} in {$locale}");
        }
    }
});
```

## Output Formats

### Standard Output
```
Testing Russian (Русский) translations:
==================================================
dashboard.manager.title                   ❌ MISSING
dashboard.admin.title                     ✅ OK
landing.hero.title                        ✅ OK → Современное управление коммунальными услугами
app.brand.name                           ✅ OK → Вильнюсские коммунальные услуги

============================================================
TRANSLATION COVERAGE SUMMARY
============================================================
✅ English              25/25 keys (100.0%) - 0 missing
⚠️  Lithuanian (Lietuvių) 23/25 keys ( 92.0%) - 2 missing
❌ Russian (Русский)     20/25 keys ( 80.0%) - 5 missing
------------------------------------------------------------
OVERALL: ⚠️  68/75 keys ( 90.7%) - 7 missing across all locales

Status: Minor translation gaps detected
```

### JSON Output
```json
{
  "timestamp": "2024-12-24T10:30:00+00:00",
  "total_locales_tested": 3,
  "total_keys_per_locale": 25,
  "results": [
    {
      "locale": "en",
      "locale_name": "English",
      "total_keys": 25,
      "missing_keys": [],
      "present_keys": [...],
      "coverage_percentage": 100.0
    }
  ],
  "overall_status": {
    "status": "good",
    "icon": "⚠️",
    "message": "Minor translation gaps detected"
  }
}
```

## Best Practices

### Adding New Translation Keys

1. **Add to English first** (base locale)
2. **Update critical keys list** in `test-translations.php` if essential
3. **Add to all other locales** (lt, ru)
4. **Run translation tests** to verify completeness
5. **Update Filament resources** to use new keys

### Translation Key Naming

- Use **dot notation** for nesting: `dashboard.manager.title`
- Use **snake_case** for key names: `total_properties`
- Group by **functional area**: `dashboard.*`, `landing.*`, `common.*`
- Keep keys **descriptive**: `stats.active_meters` not `stats.am`

### Quality Assurance

1. **Test regularly** during development
2. **Validate before releases** using CI/CD
3. **Review translations** with native speakers
4. **Check UI layout** with longer translations (German, Russian)
5. **Verify context** - ensure translations fit the UI context

## Troubleshooting

### Common Issues

#### Missing Translation Files
```bash
❌ Error testing locale ru: file_get_contents(lang/ru/dashboard.php): failed to open stream
```
**Solution**: Create missing translation files by copying from English and translating.

#### Laravel Bootstrap Errors
```bash
❌ Failed to bootstrap Laravel application: Class 'App\Providers\AppServiceProvider' not found
```
**Solution**: Run `composer install` and ensure Laravel is properly configured.

#### Locale Not Supported
```bash
❌ Unsupported locale: de
```
**Solution**: Add the locale to `SUPPORTED_LOCALES` constant or use existing locales.

### Debugging Translation Issues

1. **Check file existence**: Ensure translation files exist for all locales
2. **Verify key structure**: Ensure nested keys match across all files
3. **Test individual keys**: Use `php artisan tinker` to test specific translations
4. **Clear cache**: Run `php artisan config:clear` if translations aren't updating

## Related Documentation

- [Landing Page Localization](../frontend/landing-page-localization.md)
- [Translation Implementation Guide](../guides/translation-implementation.md)
- [Filament Localization](../filament/localization-patterns.md)
- [Laravel Localization Docs](https://laravel.com/docs/localization)

## Maintenance

### Regular Tasks

- **Weekly**: Run translation tests during development
- **Before releases**: Ensure 100% coverage for critical keys
- **Quarterly**: Review and update critical keys list
- **When adding features**: Add new translation keys to test suite

### Monitoring

- Set up **CI/CD alerts** for translation coverage drops
- Monitor **user feedback** for translation quality issues
- Track **missing translations** in production logs
- Review **analytics** for locale usage patterns

---

**Last Updated**: 2024-12-24  
**Version**: 1.0.0  
**Maintainer**: Development Team