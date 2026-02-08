# InputSanitizer API Reference

## Class: `App\Services\InputSanitizer`

**Namespace**: `App\Services`  
**Type**: Service (Singleton)  
**Purpose**: Comprehensive input sanitization with XSS, SQL injection, and path traversal prevention

## Methods

### `sanitizeText()`

Sanitizes text input with comprehensive XSS prevention.

```php
public function sanitizeText(string $input, bool $allowBasicHtml = false): string
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$input` | `string` | Required | The text to sanitize |
| `$allowBasicHtml` | `bool` | `false` | Whether to allow safe HTML tags |

#### Returns

`string` - Sanitized text

#### Allowed HTML Tags (when `$allowBasicHtml = true`)

- `<p>` - Paragraph
- `<br>` - Line break
- `<strong>` - Bold text
- `<em>` - Italic text
- `<u>` - Underlined text

#### Security Features

- Removes JavaScript protocol handlers (`javascript:`, `vbscript:`, `data:text/html`)
- Strips dangerous HTML tags (`<script>`, `<iframe>`, `<object>`, etc.)
- Removes event handlers (`onclick`, `onerror`, etc.)
- Removes null bytes
- Normalizes Unicode
- Trims whitespace

#### Examples

```php
// Basic sanitization
$clean = $sanitizer->sanitizeText('<script>alert("XSS")</script>Hello');
// Returns: "Hello"

// Allow safe HTML
$clean = $sanitizer->sanitizeText(
    '<p>Hello <strong>World</strong></p>',
    allowBasicHtml: true
);
// Returns: "<p>Hello <strong>World</strong></p>"

// Dangerous HTML removed even with allowBasicHtml
$clean = $sanitizer->sanitizeText(
    '<p>Hello</p><script>alert(1)</script>',
    allowBasicHtml: true
);
// Returns: "<p>Hello</p>"
```

---

### `sanitizeNumeric()`

Sanitizes numeric input with overflow protection.

```php
public function sanitizeNumeric(
    string|float|int $input,
    float $max = 999999.9999
): float
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$input` | `string\|float\|int` | Required | The numeric value to sanitize |
| `$max` | `float` | `999999.9999` | Maximum allowed value |

#### Returns

`float` - Sanitized numeric value

#### Throws

- `InvalidArgumentException` - If value exceeds maximum
- `InvalidArgumentException` - If value is negative

#### Examples

```php
// Valid numeric input
$value = $sanitizer->sanitizeNumeric('123.45');
// Returns: 123.45

// Custom maximum
$value = $sanitizer->sanitizeNumeric(500, max: 1000);
// Returns: 500.0

// Overflow protection
try {
    $value = $sanitizer->sanitizeNumeric(1000000);
} catch (InvalidArgumentException $e) {
    // Exception: "Value exceeds maximum allowed: 999999.9999"
}

// Negative value rejection
try {
    $value = $sanitizer->sanitizeNumeric(-10);
} catch (InvalidArgumentException $e) {
    // Exception: "Negative values not allowed"
}
```

---

### `sanitizeIdentifier()`

Sanitizes identifiers with path traversal prevention.

```php
public function sanitizeIdentifier(
    string $input,
    int $maxLength = 255
): string
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$input` | `string` | Required | The identifier to sanitize |
| `$maxLength` | `int` | `255` | Maximum allowed length |

#### Returns

`string` - Sanitized identifier

#### Throws

- `InvalidArgumentException` - If input exceeds max length
- `InvalidArgumentException` - If contains dangerous patterns (`..`)
- `InvalidArgumentException` - If results in empty string

#### Allowed Characters

- Letters: `a-z`, `A-Z`
- Numbers: `0-9`
- Underscore: `_`
- Hyphen: `-`
- Single dots: `.`

#### Security Features

- **Path Traversal Prevention**: Blocks `..` patterns BEFORE and AFTER character removal
- **Null Byte Protection**: Removes null bytes
- **Unicode Normalization**: Prevents homograph attacks
- **Leading/Trailing Dot Removal**: File system safety
- **Security Logging**: Logs attack attempts with IP and user context

#### Security Event Logging

When a path traversal attempt is detected, the following is logged:

```php
\Log::warning('Path traversal attempt detected in identifier', [
    'original_input' => $input,
    'sanitized_attempt' => $sanitized,
    'ip' => request()?->ip(),
    'user_id' => auth()?->id(),
]);
```

#### Examples

```php
// Valid identifiers
$id = $sanitizer->sanitizeIdentifier('provider-123');
// Returns: "provider-123"

$id = $sanitizer->sanitizeIdentifier('system.id.456');
// Returns: "system.id.456"

$id = $sanitizer->sanitizeIdentifier('aws.s3.bucket.name');
// Returns: "aws.s3.bucket.name"

// Invalid characters removed
$id = $sanitizer->sanitizeIdentifier('test@provider#123');
// Returns: "testprovider123"

// Path traversal attempts blocked
try {
    $id = $sanitizer->sanitizeIdentifier('../../../etc/passwd');
} catch (InvalidArgumentException $e) {
    // Exception: "Identifier contains invalid pattern (..)"
    // Security event logged
}

// Obfuscated path traversal blocked
try {
    $id = $sanitizer->sanitizeIdentifier('test.@.example');
} catch (InvalidArgumentException $e) {
    // Exception: "Identifier contains invalid pattern (..)"
    // After @ removal, becomes "test..example"
}

// Custom max length
$id = $sanitizer->sanitizeIdentifier('short-id', maxLength: 50);
// Returns: "short-id"

// Empty input
$id = $sanitizer->sanitizeIdentifier('');
// Returns: ""

// Whitespace only
$id = $sanitizer->sanitizeIdentifier('   ');
// Returns: ""
```

---

### `sanitizeTime()`

Validates and sanitizes time format (HH:MM).

```php
public function sanitizeTime(string $input): string
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$input` | `string` | Required | Time string to validate |

#### Returns

`string` - Validated time string

#### Throws

- `InvalidArgumentException` - If time format is invalid

#### Format

- **Pattern**: `HH:MM`
- **Hours**: `00-23`
- **Minutes**: `00-59`

#### Examples

```php
// Valid times
$time = $sanitizer->sanitizeTime('14:30');
// Returns: "14:30"

$time = $sanitizer->sanitizeTime('00:00');
// Returns: "00:00"

$time = $sanitizer->sanitizeTime('23:59');
// Returns: "23:59"

// Invalid hour
try {
    $time = $sanitizer->sanitizeTime('25:00');
} catch (InvalidArgumentException $e) {
    // Exception: "Invalid time format. Expected HH:MM"
}

// Invalid minute
try {
    $time = $sanitizer->sanitizeTime('14:60');
} catch (InvalidArgumentException $e) {
    // Exception: "Invalid time format. Expected HH:MM"
}

// Invalid format
try {
    $time = $sanitizer->sanitizeTime('14-30');
} catch (InvalidArgumentException $e) {
    // Exception: "Invalid time format. Expected HH:MM"
}
```

---

### `getCacheStats()`

Returns cache statistics for monitoring.

```php
public function getCacheStats(): array
```

#### Returns

`array` with keys:
- `size` (int) - Current cache size
- `max_size` (int) - Maximum cache size (500)
- `utilization` (float) - Cache utilization percentage

#### Example

```php
$stats = $sanitizer->getCacheStats();
// Returns: [
//     'size' => 150,
//     'max_size' => 500,
//     'utilization' => 30.0
// ]
```

---

### `clearCache()`

Clears the Unicode normalization cache.

```php
public function clearCache(): void
```

#### Returns

`void`

#### Example

```php
$sanitizer->clearCache();
// Cache is now empty
```

## Constants

### `MAX_CACHE_SIZE`

Maximum size of the Unicode normalization cache.

```php
private const MAX_CACHE_SIZE = 500;
```

### `DANGEROUS_TAGS`

HTML tags that are always removed.

```php
protected const DANGEROUS_TAGS = [
    'script', 'iframe', 'object', 'embed', 'applet',
    'meta', 'link', 'style', 'form', 'input', 'button',
];
```

### `DANGEROUS_ATTRIBUTES`

HTML attributes that are removed.

```php
protected const DANGEROUS_ATTRIBUTES = [
    'onclick', 'onload', 'onerror', 'onmouseover', 'onmouseout',
    'onkeydown', 'onkeyup', 'onfocus', 'onblur', 'onchange',
    'onsubmit', 'onreset', 'onselect', 'onabort',
];
```

## Service Registration

The service is registered as a singleton in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(InputSanitizer::class);
}
```

## Dependency Injection

```php
use App\Services\InputSanitizer;

class MyController extends Controller
{
    public function __construct(
        private InputSanitizer $sanitizer
    ) {}

    public function store(Request $request)
    {
        $remoteId = $this->sanitizer->sanitizeIdentifier(
            $request->input('remote_id')
        );
    }
}
```

## Helper Function

```php
// Get instance from container
$sanitizer = app(InputSanitizer::class);
```

## Performance Characteristics

| Operation | Time (μs) | Notes |
|-----------|-----------|-------|
| `sanitizeText()` | 10-50 | Depends on input length |
| `sanitizeNumeric()` | 1-5 | Simple float conversion |
| `sanitizeIdentifier()` | 20-100 | Includes regex and validation |
| `sanitizeTime()` | 5-10 | Simple regex match |
| Cache lookup | <1 | O(1) hash lookup |

## Related Documentation

- [Service Documentation](../services/INPUT_SANITIZER_SERVICE.md)
- [Quick Reference](../security/INPUT_SANITIZER_QUICK_REFERENCE.md)
- [Security Fix Details](../security/input-sanitizer-security-fix.md)
- [Security Patch Summary](../security/SECURITY_PATCH_2024-12-05.md)
