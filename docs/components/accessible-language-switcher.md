# Accessible Language Switcher Component

## Overview

The `accessible-language-switcher` is a fully accessible Blade component that provides language switching functionality for the multi-tenant utilities billing platform. It integrates seamlessly with Laravel's localization system and follows WCAG 2.1 AA accessibility guidelines.

## Features

- **Full Accessibility**: ARIA attributes, screen reader support, keyboard navigation
- **Visual Indicators**: Check marks and styling for current language selection
- **Alpine.js Integration**: Smooth dropdown interactions and state management
- **Translation Validation**: Runtime validation of required translation keys
- **Responsive Design**: Works across all device sizes
- **Multi-tenant Aware**: Respects tenant-specific locale preferences

## Usage

### Basic Usage

```blade
<x-accessible-language-switcher />
```

### With Custom Props

```blade
<x-accessible-language-switcher 
    :current-locale="$customLocale"
    :available-locales="$customLocales"
/>
```

### In Navigation (Recommended)

The component is typically used through the NavigationComposer system:

```blade
{{-- In layouts/app.blade.php --}}
@if($showTopLocaleSwitcher)
    <x-accessible-language-switcher 
        :current-locale="$currentLocale"
        :available-locales="$languages"
    />
@endif
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `currentLocale` | `string` | `app()->getLocale()` | Current application locale code |
| `availableLocales` | `Collection` | `Localization::availableLocales()` | Available locales collection |

### Available Locales Structure

The `availableLocales` collection should contain objects with:

```php
[
    'code' => 'lt',           // Locale code
    'label' => 'common.lithuanian', // Translation key for display name
    'abbreviation' => 'LT'    // Short abbreviation
]
```

## Accessibility Features

### ARIA Attributes

- `aria-expanded`: Indicates dropdown state
- `aria-haspopup`: Identifies dropdown trigger
- `aria-current`: Marks currently selected language
- `aria-live`: Announces language changes to screen readers
- `aria-label`: Provides accessible button label

### Keyboard Navigation

- **Space/Enter**: Opens/closes dropdown
- **Escape**: Closes dropdown
- **Arrow Keys**: Navigate through options
- **Tab**: Moves focus to next element

### Screen Reader Support

- Announces current language selection
- Provides context for language options
- Announces language changes with live regions
- Includes descriptive labels for all interactive elements

## JavaScript Functionality

### Alpine.js Data

```javascript
{
    open: false,        // Dropdown state
    announcement: ''    // Screen reader announcements
}
```

### Events

The component dispatches custom events:

```javascript
// Language change event
$dispatch('language-changed', { 
    locale: 'lt', 
    name: 'Lithuanian' 
});
```

### Event Handling

```javascript
// Listen for language changes
document.addEventListener('language-changed', function(event) {
    const { locale, name } = event.detail;
    console.log(`Language changed to: ${name} (${locale})`);
});
```

## Translation Requirements

The component requires these translation keys in `lang/{locale}/common.php`:

```php
return [
    'language_switcher_label' => 'Change language',
    'current_language' => 'Current language',
    'language_changed_to' => 'Language changed to',
    'language' => 'Language',
    
    // Language names
    'english' => 'English',
    'lithuanian' => 'Lithuanian', 
    'russian' => 'Russian',
];
```

## Styling

The component uses Tailwind CSS classes for styling:

### Button Styles
- Base: `inline-flex items-center px-3 py-2 border border-gray-300`
- Hover: `hover:bg-gray-50`
- Focus: `focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500`

### Dropdown Styles
- Container: `absolute right-0 mt-2 w-48 rounded-md shadow-lg`
- Background: `bg-white ring-1 ring-black ring-opacity-5`
- Items: `px-4 py-2 text-sm text-gray-700 hover:bg-gray-100`

### Current Language Indicator
- Check mark: `text-indigo-600` SVG icon
- Spacing: `mr-3 h-4 w-4` for consistent alignment

## Integration Points

### NavigationComposer

The component integrates with `App\View\Composers\NavigationComposer`:

```php
// NavigationComposer provides:
'showTopLocaleSwitcher' => $this->shouldShowLocaleSwitcher($userRole),
'languages' => $this->getActiveLanguages($userRole),
'currentLocale' => app()->getLocale(),
```

### Language Controller

Language switching is handled by `App\Http\Controllers\LanguageController`:

```php
Route::get('/language/{locale}', [LanguageController::class, 'switch'])
    ->name('language.switch');
```

### Localization Support

Uses `App\Support\Localization` for locale configuration:

```php
// Available locales
Localization::availableLocales();

// Locale validation
Localization::isAvailable($locale);
```

## Security Considerations

- **XSS Prevention**: All output is properly escaped
- **CSRF Protection**: Language switching uses GET requests (safe)
- **Locale Validation**: Only configured locales are accepted
- **Input Sanitization**: Locale codes are validated server-side

## Performance

- **Minimal JavaScript**: Uses Alpine.js for lightweight interactions
- **Cached Locales**: Available locales are cached for performance
- **Lazy Loading**: Dropdown content loads on demand
- **Optimized Rendering**: Minimal DOM manipulation

## Testing

### Component Testing

```php
// Test component rendering
it('renders language switcher correctly', function () {
    $view = view('components.accessible-language-switcher', [
        'currentLocale' => 'en',
        'availableLocales' => collect([
            ['code' => 'en', 'label' => 'common.english', 'abbreviation' => 'EN'],
            ['code' => 'lt', 'label' => 'common.lithuanian', 'abbreviation' => 'LT'],
        ]),
    ]);
    
    expect($view->render())
        ->toContain('aria-expanded')
        ->toContain('English')
        ->toContain('Lithuanian');
});
```

### Accessibility Testing

```php
// Test ARIA attributes
it('includes proper ARIA attributes', function () {
    $rendered = view('components.accessible-language-switcher')->render();
    
    expect($rendered)
        ->toContain('aria-expanded')
        ->toContain('aria-haspopup')
        ->toContain('aria-current')
        ->toContain('aria-live');
});
```

### Integration Testing

```php
// Test with NavigationComposer
it('integrates with navigation composer', function () {
    $user = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->actingAs($user);
    
    $view = view('layouts.app');
    $data = $view->getData();
    
    expect($data['showTopLocaleSwitcher'])->toBeTrue();
    expect($data['languages'])->toHaveCount(3);
});
```

## Troubleshooting

### Common Issues

1. **Missing Translation Keys**
   - Check `lang/{locale}/common.php` for required keys
   - Verify translation validation script output in console

2. **Dropdown Not Opening**
   - Ensure Alpine.js is loaded
   - Check for JavaScript errors in console
   - Verify `x-data` and `@click` directives

3. **Accessibility Issues**
   - Test with screen reader
   - Verify ARIA attributes are present
   - Check keyboard navigation

4. **Styling Problems**
   - Ensure Tailwind CSS is compiled
   - Check for conflicting CSS rules
   - Verify responsive breakpoints

### Debug Mode

Enable debug mode to see translation validation:

```javascript
// Check console for missing translations
console.log('Translation validation enabled');
```

## Related Documentation

- [Localization System](../localization/system.md)
- [NavigationComposer](../view-composers/navigation-composer.md)
- [Language Controller](../controllers/language-controller.md)
- [Multi-tenant Localization](../multi-tenancy/localization.md)
- [Accessibility Guidelines](../accessibility/guidelines.md)

## Changelog

### v2.0.0 (Current)
- Enhanced accessibility with proper ARIA attributes
- Added visual indicators for current language
- Improved screen reader support
- Added translation validation
- Better Alpine.js integration

### v1.0.0
- Initial implementation
- Basic dropdown functionality
- Translation support