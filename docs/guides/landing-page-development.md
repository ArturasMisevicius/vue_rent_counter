# Landing Page Development Guide

## Overview

This guide provides developers with practical information for working with the landing page system, including translation management, content updates, and best practices.

## Quick Start

### Basic Translation Usage

```blade
{{-- In Blade templates --}}
<h1>{{ __('landing.hero.title') }}</h1>
<p>{{ __('landing.hero.tagline') }}</p>

{{-- With fallback locale --}}
<span>{{ __('landing.dashboard.healthy', [], 'en') }}</span>
```

### Configuration-Based Content

```php
// config/landing.php
'features' => [
    [
        'title' => 'landing.features.unified_metering.title',
        'description' => 'landing.features.unified_metering.description',
        'icon' => 'meter',
    ],
],
```

## Development Workflow

### 1. Adding New Content

#### Step 1: Add English Translation
```php
// lang/en/landing.php
'new_section' => [
    'title' => 'New Feature Title',
    'description' => 'Detailed description of the new feature.',
],
```

#### Step 2: Add Lithuanian Translation
```php
// lang/lt/landing.php
'new_section' => [
    'title' => 'Naujos funkcijos pavadinimas',
    'description' => 'Išsamus naujos funkcijos aprašymas.',
],
```

#### Step 3: Update Template
```blade
{{-- resources/views/welcome.blade.php --}}
<section class="new-section">
    <h2>{{ __('landing.new_section.title') }}</h2>
    <p>{{ __('landing.new_section.description') }}</p>
</section>
```

#### Step 4: Clear Cache
```bash
php artisan optimize:clear
```

### 2. Updating Existing Content

#### Modify Translation Files
```php
// Update both lang/en/landing.php and lang/lt/landing.php
'hero' => [
    'title' => 'Updated Hero Title',  // Changed content
    'tagline' => 'Updated tagline text',
],
```

#### Test Changes
```bash
# Clear cache
php artisan optimize:clear

# Run localization tests
php artisan test --filter=LocalizationTest
```

### 3. Adding New Features Section

#### Update Translation Files
```php
// lang/en/landing.php
'features' => [
    // ... existing features
    'new_feature' => [
        'title' => 'New Feature Name',
        'description' => 'Feature description text.',
    ],
],
```

#### Update Configuration
```php
// config/landing.php
'features' => [
    // ... existing features
    [
        'title' => 'landing.features.new_feature.title',
        'description' => 'landing.features.new_feature.description',
        'icon' => 'feature-icon',
    ],
],
```

## Best Practices

### Translation Key Organization

#### ✅ Good Structure
```php
'dashboard' => [
    'draft_invoices' => 'Draft Invoices',
    'draft_invoices_hint' => 'Invoices pending finalization',
    'meters_validated' => 'Meters Validated',
    'meters_validated_hint' => 'Meters with validated readings',
],
```

#### ❌ Poor Structure
```php
'draft_invoices' => 'Draft Invoices',
'draft_invoices_hint' => 'Invoices pending finalization',
'meters_validated' => 'Meters Validated',
'meters_validated_hint' => 'Meters with validated readings',
```

### Content Guidelines

#### English Content
- Use clear, professional language
- Focus on benefits and features
- Maintain consistent terminology
- Use active voice where possible

#### Lithuanian Content
- Maintain professional tone
- Use proper Lithuanian grammar
- Ensure technical accuracy
- Consider cultural context

### Performance Considerations

#### Efficient Translation Loading
```php
// ✅ Good: Load specific keys
$heroTitle = __('landing.hero.title');

// ❌ Avoid: Loading entire translation group unnecessarily
$allTranslations = Lang::get('landing');
```

#### Caching Strategy
```php
// Translations are automatically cached by Laravel
// Clear cache after updates:
php artisan optimize:clear

// In production, use config caching:
php artisan config:cache
```

## Common Patterns

### Conditional Content Display

```blade
{{-- Display content based on locale --}}
@if(app()->getLocale() === 'lt')
    <p class="text-sm">{{ __('landing.hero.tagline') }}</p>
@else
    <p class="text-lg">{{ __('landing.hero.tagline') }}</p>
@endif
```

### Dynamic Content with Configuration

```blade
{{-- Loop through configured features --}}
@foreach(config('landing.features') as $feature)
    <div class="feature-card">
        <h3>{{ __($feature['title']) }}</h3>
        <p>{{ __($feature['description']) }}</p>
        <i class="icon-{{ $feature['icon'] }}"></i>
    </div>
@endforeach
```

### Fallback Content

```blade
{{-- Provide fallback for missing translations --}}
<h1>{{ __('landing.hero.title', [], null, 'Default Title') }}</h1>
```

## Testing

### Unit Tests

```php
// tests/Unit/LandingTranslationTest.php
use Illuminate\Support\Facades\Lang;

it('has all required landing translation keys', function () {
    $requiredKeys = [
        'landing.hero.title',
        'landing.hero.tagline',
        'landing.dashboard.draft_invoices',
    ];
    
    foreach ($requiredKeys as $key) {
        expect(Lang::has($key))->toBeTrue();
    }
});

it('has Lithuanian translations for all English keys', function () {
    $englishKeys = array_keys(Arr::dot(Lang::get('landing', [], 'en')));
    
    app()->setLocale('lt');
    
    foreach ($englishKeys as $key) {
        expect(Lang::has("landing.{$key}"))->toBeTrue();
    }
});
```

### Feature Tests

```php
// tests/Feature/LandingPageTest.php
it('displays landing page content in English', function () {
    $response = $this->get('/');
    
    $response->assertSee(__('landing.hero.title'));
    $response->assertSee(__('landing.hero.tagline'));
});

it('displays landing page content in Lithuanian', function () {
    session(['locale' => 'lt']);
    
    $response = $this->get('/');
    
    app()->setLocale('lt');
    $response->assertSee(__('landing.hero.title'));
    $response->assertSee(__('landing.hero.tagline'));
});
```

### Manual Testing Checklist

- [ ] All content displays in both English and Lithuanian
- [ ] Language switcher works correctly
- [ ] No translation keys are displayed (missing translations)
- [ ] Layout remains intact with different text lengths
- [ ] All links and buttons work in both languages

## Troubleshooting

### Common Issues

#### Translation Keys Display Instead of Text

**Problem**: Seeing `landing.hero.title` instead of translated text

**Solutions**:
1. Check if key exists in translation file
2. Clear cache: `php artisan optimize:clear`
3. Verify correct locale is set
4. Check for typos in key names

#### Layout Breaking with Long Translations

**Problem**: UI layout breaks with Lithuanian text

**Solutions**:
1. Use CSS text truncation: `text-overflow: ellipsis`
2. Adjust container widths
3. Consider shorter translations
4. Use responsive design patterns

#### Missing Translations in Production

**Problem**: Translations work locally but not in production

**Solutions**:
1. Ensure translation files are deployed
2. Clear production cache: `php artisan optimize:clear`
3. Check file permissions
4. Verify locale configuration

### Debug Commands

```bash
# Check current locale
php artisan tinker
>>> app()->getLocale()

# Test specific translation
>>> __('landing.hero.title')

# Check if translation exists
>>> Lang::has('landing.hero.title')

# Get all landing translations
>>> Lang::get('landing')
```

## Integration Points

### Language Switcher Integration

The landing page integrates with the platform's language switcher:

```blade
{{-- Language switcher component --}}
@include('components.language.toggle')
```

### Middleware Integration

Locale detection is handled by `App\Http\Middleware\SetLocale`:

```php
// Middleware automatically sets locale based on:
// 1. Session preference
// 2. User preference (if authenticated)
// 3. Browser language
// 4. Default locale (en)
```

### Configuration Integration

Landing page content is configured in `config/landing.php`:

```php
return [
    'features' => [
        // Feature definitions using translation keys
    ],
    'faq' => [
        // FAQ definitions using translation keys
    ],
];
```

## Deployment Considerations

### Pre-Deployment Checklist

- [ ] All translation files updated
- [ ] Tests passing: `php artisan test --filter=LocalizationTest`
- [ ] Cache cleared locally: `php artisan optimize:clear`
- [ ] Manual testing in both locales completed

### Production Deployment

```bash
# Standard deployment commands
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Post-Deployment Verification

1. Test landing page in both locales
2. Verify language switcher functionality
3. Check for any missing translations
4. Monitor error logs for translation-related issues

## Performance Optimization

### Translation Caching

Laravel automatically caches translations, but you can optimize further:

```php
// Use translation caching in production
'cache' => [
    'translation' => env('CACHE_TRANSLATIONS', true),
],
```

### Selective Loading

Only load required translation groups:

```php
// Load only landing translations
$landingTranslations = Lang::get('landing');

// Avoid loading all translations
$allTranslations = Lang::get(); // Avoid this
```

### CDN Integration

Consider using CDN for static translation assets in high-traffic scenarios.

## Related Resources

- [Landing Page Localization Documentation](../frontend/landing-page-localization.md)
- [Translation Keys Reference](../reference/landing-translation-keys.md)
- [Translation Implementation Guide](.kiro/steering/translation-guide.md)
- [Laravel Localization Documentation](https://laravel.com/docs/localization)

---

**Last Updated**: 2024-12-24  
**Maintainer**: Development Team