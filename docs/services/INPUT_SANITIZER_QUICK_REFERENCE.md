# InputSanitizer Quick Reference

## Quick Usage

```php
use App\Services\InputSanitizer;

$sanitizer = app(InputSanitizer::class);
```

## Methods at a Glance

| Method | Purpose | Example Input | Example Output |
|--------|---------|---------------|----------------|
| `sanitizeText()` | Remove HTML/XSS | `<script>alert(1)</script>Hi` | `Hi` |
| `sanitizeNumeric()` | Validate numbers | `"123.45"` | `123.45` |
| `sanitizeIdentifier()` | Clean IDs | `"test@id#123.456"` | `"testid123.456"` |
| `sanitizeTime()` | Validate HH:MM | `"14:30"` | `"14:30"` |

## Common Patterns

### Filament Form Fields

```php
// Text field with sanitization
Forms\Components\TextInput::make('name')
    ->dehydrateStateUsing(fn (string $state): string => 
        app(\App\Services\InputSanitizer::class)->sanitizeText($state)
    );

// Identifier field with sanitization
Forms\Components\TextInput::make('remote_id')
    ->dehydrateStateUsing(fn (?string $state): ?string => 
        $state ? app(\App\Services\InputSanitizer::class)->sanitizeIdentifier($state) : null
    );
```

### Form Requests

```php
protected function prepareForValidation(): void
{
    $sanitizer = app(InputSanitizer::class);
    
    $this->merge([
        'name' => $sanitizer->sanitizeText($this->name),
        'remote_id' => $this->remote_id 
            ? $sanitizer->sanitizeIdentifier($this->remote_id) 
            : null,
    ]);
}
```

### Controllers

```php
public function __construct(
    protected InputSanitizer $sanitizer
) {}

public function store(Request $request)
{
    $cleanName = $this->sanitizer->sanitizeText($request->input('name'));
    // Use $cleanName...
}
```

## Allowed Characters

### sanitizeIdentifier()

✅ **Allowed:**
- Letters: `a-z`, `A-Z`
- Numbers: `0-9`
- Underscore: `_`
- Hyphen: `-`
- Dot: `.` *(added 2025-12-05)*

❌ **Blocked:**
- Special chars: `@`, `#`, `!`, `$`, `%`, `^`, `&`, `*`, `(`, `)`, etc.
- Spaces
- Quotes
- Slashes

### sanitizeText() with HTML

✅ **Allowed Tags:**
- `<p>`, `<br>`, `<strong>`, `<em>`, `<u>`

❌ **Blocked Tags:**
- `<script>`, `<iframe>`, `<object>`, `<embed>`, `<applet>`
- `<meta>`, `<link>`, `<style>`, `<form>`, `<input>`, `<button>`

❌ **Blocked Attributes:**
- All `on*` event handlers: `onclick`, `onload`, `onerror`, etc.

## Error Handling

```php
// Numeric overflow
try {
    $value = $sanitizer->sanitizeNumeric(1000000);
} catch (\InvalidArgumentException $e) {
    // Handle: "Value exceeds maximum allowed: 999999.9999"
}

// Negative numbers
try {
    $value = $sanitizer->sanitizeNumeric(-10);
} catch (\InvalidArgumentException $e) {
    // Handle: "Negative values not allowed"
}

// Identifier too long
try {
    $id = $sanitizer->sanitizeIdentifier(str_repeat('a', 256));
} catch (\InvalidArgumentException $e) {
    // Handle: "Identifier exceeds maximum length of 255 characters"
}

// Invalid time format
try {
    $time = $sanitizer->sanitizeTime('25:00');
} catch (\InvalidArgumentException $e) {
    // Handle: "Invalid time format. Expected HH:MM"
}
```

## Valid Identifier Examples

```php
// External system IDs
"system.id.456"           // ✅ Hierarchical ID
"provider-123"            // ✅ Hyphenated ID
"ABC-DEF_123.456"         // ✅ Mixed format
"provider_123"            // ✅ Underscore ID

// Invalid (will be cleaned)
"test@provider#123"       // ❌ → "testprovider123"
"id with spaces"          // ❌ → "idwithspaces"
"test/path/id"            // ❌ → "testpathid"
```

## Testing

```bash
# Run all InputSanitizer tests
php artisan test --filter=InputSanitizerTest

# Run specific test
php artisan test --filter=InputSanitizerTest::it_allows_dots_in_identifier
```

## Documentation

- **Full Documentation**: `docs/services/INPUT_SANITIZER_SERVICE.md`
- **Security Audit**: `docs/security/TARIFF_MANUAL_MODE_SECURITY_AUDIT.md`
- **Test Coverage**: `tests/Unit/Services/InputSanitizerTest.php`
