# Landing Page Localization Documentation

## Overview

The landing page localization system provides multi-language support for the Vilnius Utilities Management Platform's public-facing landing page. This system follows Laravel's localization conventions and integrates with the platform's translation infrastructure.

## File Structure

### Translation Files

```
lang/
├── en/
│   └── landing.php          # English translations (base locale)
├── lt/
│   └── landing.php          # Lithuanian translations
└── {locale}/
    └── landing.php          # Additional locale translations
```

### Related Files

```
resources/views/welcome.blade.php    # Main landing page template
config/landing.php                   # Landing page configuration
config/locales.php                   # Supported locales configuration
```

## Translation Structure

The `landing.php` translation files contain the following sections:

### Core Sections

#### CTA Bar (`cta_bar`)
Call-to-action section at the top of the page:
```php
'cta_bar' => [
    'eyebrow' => 'Utilities Management',
    'title' => 'Streamline Your Property Operations',
],
```

#### Hero Section (`hero`)
Main hero section with primary messaging:
```php
'hero' => [
    'badge' => 'Vilnius Utilities Platform',
    'tagline' => 'Manage properties, meters, and invoices with confidence',
    'title' => 'Modern Utilities Management for Lithuanian Properties',
],
```

#### Dashboard Preview (`dashboard`)
Live dashboard preview section:
```php
'dashboard' => [
    'draft_invoices' => 'Draft Invoices',
    'draft_invoices_hint' => 'Invoices pending finalization',
    'electricity' => 'Electricity',
    'electricity_status' => 'Electricity System Status',
    // ... additional dashboard elements
],
```

#### Features Section (`features`)
Platform features and capabilities:
```php
'features' => [
    'unified_metering' => [
        'title' => 'Unified Meter Management',
        'description' => 'Manage all electricity, water, and heating meters...',
    ],
    // ... additional features
],
```

#### FAQ Section (`faq`)
Frequently asked questions:
```php
'faq' => [
    'validation' => [
        'question' => 'How does meter reading validation work?',
        'answer' => 'All meter readings are validated using...',
    ],
    // ... additional FAQ items
],
```

#### Performance Metrics (`metrics`, `metric_values`)
Platform performance indicators:
```php
'metrics' => [
    'cache' => 'Cache Performance',
    'isolation' => 'Tenant Isolation',
    'readings' => 'Meter Readings',
],
'metric_values' => [
    'five_minutes' => '< 5 minutes',
    'full' => '100%',
    'zero' => '0',
],
```

## Usage Patterns

### In Blade Templates

The landing page translations are used in `resources/views/welcome.blade.php`:

```blade
{{-- Hero Section --}}
<h1 class="font-display text-4xl sm:text-5xl font-bold text-white leading-tight">
    {{ __('landing.hero.title') }}
</h1>
<p class="text-lg text-slate-300 leading-relaxed">
    {{ __('landing.hero.tagline') }}
</p>

{{-- Dashboard Metrics --}}
<p class="text-sm text-slate-300">{{ __('landing.dashboard.draft_invoices') }}</p>
<p class="text-xs text-slate-400">{{ __('landing.dashboard.draft_invoices_hint') }}</p>
```

### In Configuration Files

Features and FAQ content are referenced in `config/landing.php`:

```php
'features' => [
    [
        'title' => 'landing.features.unified_metering.title',
        'description' => 'landing.features.unified_metering.description',
        'icon' => 'meter',
    ],
],
'faq' => [
    [
        'question' => 'landing.faq.validation.question',
        'answer' => 'landing.faq.validation.answer',
    ],
],
```

## Translation Guidelines

### Key Naming Conventions

Follow snake_case naming with logical nesting:

```php
// ✅ GOOD: Clear, nested structure
'dashboard' => [
    'draft_invoices' => 'Draft Invoices',
    'draft_invoices_hint' => 'Invoices pending finalization',
],

// ❌ WRONG: Flat structure without context
'draft_invoices' => 'Draft Invoices',
'draft_invoices_hint' => 'Invoices pending finalization',
```

### Content Guidelines

#### English (Base Locale)
- Use clear, professional language
- Focus on utilities management terminology
- Maintain consistency with platform features
- Use active voice where possible

#### Lithuanian Translations
- Maintain professional tone in Lithuanian
- Use proper Lithuanian utilities terminology
- Ensure cultural appropriateness for Lithuanian market
- Preserve technical accuracy in translations

### Translation Quality Checklist

- [ ] All English keys have corresponding Lithuanian translations
- [ ] Technical terms are accurately translated
- [ ] Cultural context is appropriate for Lithuanian market
- [ ] Character limits are respected for UI elements
- [ ] Tone and voice are consistent across languages

## Architecture Notes

### Integration Points

The landing page localization integrates with:

1. **Laravel Localization System**: Uses standard `__()` helper functions
2. **Locale Middleware**: `App\Http\Middleware\SetLocale` handles locale detection
3. **Language Switcher**: UI components for locale switching
4. **Configuration System**: `config/locales.php` defines available locales

### Data Flow

```
User Request → SetLocale Middleware → Session Locale → Blade Template → Translation Keys → Localized Content
```

### Caching Considerations

- Translation files are cached by Laravel's translation system
- Clear cache after translation updates: `php artisan optimize:clear`
- Consider using `php artisan config:cache` in production

## Maintenance Procedures

### Adding New Content

1. **Add English Translation**:
   ```php
   // lang/en/landing.php
   'new_section' => [
       'title' => 'New Section Title',
       'description' => 'New section description',
   ],
   ```

2. **Add Lithuanian Translation**:
   ```php
   // lang/lt/landing.php
   'new_section' => [
       'title' => 'Naujos sekcijos pavadinimas',
       'description' => 'Naujos sekcijos aprašymas',
   ],
   ```

3. **Update Template**:
   ```blade
   <h2>{{ __('landing.new_section.title') }}</h2>
   <p>{{ __('landing.new_section.description') }}</p>
   ```

### Translation Updates

1. Update translation files in both locales
2. Clear application cache: `php artisan optimize:clear`
3. Test in both languages
4. Run localization tests: `php artisan test --filter=LocalizationTest`

### Quality Assurance

Run the following tests after translation updates:

```bash
# Test translation completeness
php artisan test --filter=LocalizationTest

# Test Filament translations
php artisan test --filter=FilamentTranslationTest

# Manual testing
# 1. Switch between locales on landing page
# 2. Verify all content displays correctly
# 3. Check for missing translations (fallback to keys)
```

## Performance Considerations

### Optimization Strategies

1. **Translation Caching**: Laravel automatically caches translations
2. **Selective Loading**: Only load required translation groups
3. **CDN Integration**: Consider CDN for static translation assets

### Monitoring

- Monitor translation loading performance
- Track missing translation keys in logs
- Monitor locale switching performance

## Security Considerations

### Input Validation

- All translation content is static (no user input)
- Translation keys are validated by Laravel
- No XSS risks from translation content

### Content Security

- Review translation content for sensitive information
- Ensure translations don't expose internal system details
- Maintain consistent security messaging across locales

## Testing Strategy

### Automated Tests

```php
// Test translation key existence
it('has all required landing translation keys', function () {
    $requiredKeys = [
        'landing.hero.title',
        'landing.hero.tagline',
        'landing.dashboard.draft_invoices',
        // ... additional keys
    ];
    
    foreach ($requiredKeys as $key) {
        expect(__($key))->not->toBe($key);
    }
});

// Test Lithuanian translations
it('has Lithuanian translations for all landing keys', function () {
    app()->setLocale('lt');
    
    $requiredKeys = [
        'landing.hero.title',
        'landing.hero.tagline',
        // ... additional keys
    ];
    
    foreach ($requiredKeys as $key) {
        expect(__($key))->not->toBe($key);
    }
});
```

### Manual Testing

1. **Locale Switching**: Test language switcher functionality
2. **Content Verification**: Verify all content displays in correct language
3. **Layout Testing**: Ensure translations don't break layout
4. **Mobile Testing**: Test responsive behavior with different text lengths

## Troubleshooting

### Common Issues

#### Missing Translations
**Symptom**: Translation keys display instead of translated text
**Solution**: 
1. Check if key exists in translation file
2. Clear cache: `php artisan optimize:clear`
3. Verify locale is set correctly

#### Layout Breaking
**Symptom**: UI layout breaks with certain translations
**Solution**:
1. Check translation length vs. UI constraints
2. Consider using CSS text truncation
3. Adjust translation to fit UI constraints

#### Cache Issues
**Symptom**: Translation updates not appearing
**Solution**:
1. Clear all caches: `php artisan optimize:clear`
2. Restart application server
3. Check file permissions on cache directories

## Related Documentation

- [Translation Implementation Guide](.kiro/steering/translation-guide.md)
- [Frontend Documentation](FRONTEND.md)
- [Blade Guardrails](.kiro/steering/blade-guardrails.md)
- [Laravel Localization Documentation](https://laravel.com/docs/localization)

## Changelog

### 2024-12-24
- **ADDED**: Complete landing page translation structure
- **ADDED**: Lithuanian translations for all sections
- **ADDED**: Features and FAQ translation keys
- **UPDATED**: Translation content from placeholders to utilities-focused messaging
- **IMPROVED**: Translation key organization and nesting structure

---

**Note**: This documentation follows the project's translation guide and blade guardrails. All translations use the `__()` helper function and avoid inline PHP in Blade templates.