# SVG Icon Helper

## Overview

The icon system provides multiple ways to render SVG icons using the `blade-heroicons` package. Icons are type-safe, cached automatically, and easy to maintain.

## Architecture

- **Enum**: `app/Enums/IconType.php` - Type-safe icon references
- **Helper**: `app/Support/helpers.php` - Backward-compatible helper function
- **Component**: `app/View/Components/Icon.php` - Reusable Blade component
- **Package**: `blade-ui-kit/blade-heroicons` - 292+ Heroicons available

## Usage

### Recommended: Blade Component

```blade
<x-icon name="meter" />
<x-icon name="chart" class="h-6 w-6" />
<x-icon :name="$feature['icon'] ?? 'default'" />
```

### Alternative: Helper Function (Backward Compatible)

```blade
{!! svgIcon('meter') !!}
{!! svgIcon($feature['icon'] ?? 'default') !!}
```

**Important**: Always use `{!! !!}` (unescaped output) for helper function.

### Advanced: Direct Heroicons

```blade
@svg('heroicon-o-cpu-chip', 'h-5 w-5')
```

### Available Icons

| Key | Description | Use Case |
|-----|-------------|----------|
| `meter` | Utility meter icon | Meter reading features |
| `invoice` | Document/invoice icon | Billing and invoicing |
| `shield` | Security shield icon | Security features |
| `chart` | Bar chart icon | Analytics and reporting |
| `rocket` | Rocket icon | Performance/speed features |
| `users` | Multiple users icon | Multi-user/tenant features |
| `default` | Checkmark in circle | Fallback for unknown keys |

### Example in Blade

```blade
<div class="h-11 w-11 rounded-xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white inline-flex items-center justify-center shadow-glow">
    {!! svgIcon($feature['icon'] ?? 'default') !!}
</div>
```

### Example in PHP

```php
$meterIcon = svgIcon('meter');
$defaultIcon = svgIcon('unknown-key'); // Returns default checkmark icon
```

## Implementation Details

### Function Signature

```php
function svgIcon(string $key): string
```

### Return Value

Returns a complete SVG element as a string with:
- `xmlns="http://www.w3.org/2000/svg"`
- `class="h-5 w-5"` (Tailwind sizing)
- `fill="none"`
- `viewBox="0 0 24 24"`
- `stroke="currentColor"`
- `stroke-width="1.8"`

### Match Expression

Uses PHP 8.0+ `match` expression for clean, type-safe icon mapping with automatic fallback to the default icon for unknown keys.

## Standards Compliance

### ✅ Blade Guardrails

- **Compliant**: Helper is defined in `app/Support/helpers.php`, not in Blade templates
- **Previous Issue**: Duplicate definition existed in `resources/views/welcome.blade.php` inside a `@php` block (removed)
- **Rule**: "Never use `@php` (or raw `<?php ?>`) inside Blade templates"

### ✅ Security

- **XSS Safe**: SVG markup is hardcoded and not user-generated
- **No Dynamic Content**: All SVG paths are static strings
- **Proper Usage**: Must use `{!! !!}` in Blade for intentional unescaped output

### ✅ Performance

- **No External Requests**: All icons are inline SVG
- **No Asset Loading**: No separate icon files to load
- **Minimal Size**: Each icon is ~200-300 bytes
- **Cacheable**: Blade views are cached with `php artisan view:cache`

## Testing

### Unit Tests

Location: `tests/Unit/SvgIconHelperTest.php`

```bash
php artisan test tests/Unit/SvgIconHelperTest.php
```

Tests cover:
- ✅ Each icon returns valid SVG markup
- ✅ Unknown keys return default icon
- ✅ All SVGs contain required attributes
- ✅ Welcome page renders with icons

### Manual Testing

```bash
# Test in Tinker
php artisan tinker --execute="echo svgIcon('meter');"

# Clear and rebuild view cache
php artisan view:clear
php artisan view:cache

# Visit landing page
php artisan serve
# Navigate to http://localhost:8000
```

## Migration Notes

### Before (Incorrect)

```blade
@php
    function svgIcon(string $key): string {
        return match($key) {
            'meter' => '<svg>...</svg>',
            // ...
        };
    }
@endphp

{!! svgIcon('meter') !!}
```

**Issues**:
- ❌ Violates Blade guardrails
- ❌ Code duplication
- ❌ Not reusable across views

### After (Correct)

```php
// app/Support/helpers.php
function svgIcon(string $key): string {
    return match ($key) {
        'meter' => '<svg>...</svg>',
        // ...
    };
}
```

```blade
{!! svgIcon('meter') !!}
```

**Benefits**:
- ✅ Follows Blade guardrails
- ✅ Single source of truth
- ✅ Reusable across all views
- ✅ Testable

## Adding New Icons

To add a new icon:

1. **Add to helper** (`app/Support/helpers.php`):
```php
function svgIcon(string $key): string
{
    return match ($key) {
        // ... existing icons ...
        'new-icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="YOUR_PATH_DATA"/></svg>',
        default => '...',
    };
}
```

2. **Add test** (`tests/Unit/SvgIconHelperTest.php`):
```php
it('returns new-icon SVG', function () {
    $svg = svgIcon('new-icon');
    
    expect($svg)
        ->toContain('<svg')
        ->toContain('YOUR_PATH_DATA');
});
```

3. **Run tests**:
```bash
php artisan test tests/Unit/SvgIconHelperTest.php
./vendor/bin/pint --test app/Support/helpers.php
```

4. **Use in Blade**:
```blade
{!! svgIcon('new-icon') !!}
```

## Related Files

- `app/Support/helpers.php` - Helper definition
- `bootstrap/app.php` - Autoloading
- `resources/views/welcome.blade.php` - Primary usage
- `tests/Unit/SvgIconHelperTest.php` - Test coverage

## References

- [Blade Guardrails](.kiro/steering/blade-guardrails.md)
- [Operating Principles](.kiro/steering/operating-principles.md)
- [Frontend Documentation](./FRONTEND.md)

---

**Status**: ✅ Implemented and tested  
**Created**: 2025-11-23  
**Last Updated**: 2025-11-23
