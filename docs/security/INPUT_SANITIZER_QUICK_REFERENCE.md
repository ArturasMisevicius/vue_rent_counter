# InputSanitizer Quick Reference

## TL;DR

The `InputSanitizer` service prevents XSS, SQL injection, and path traversal attacks. Always use it for user input, especially external system IDs.

## Common Usage

```php
use App\Services\InputSanitizer;

$sanitizer = app(InputSanitizer::class);

// Text input (XSS prevention)
$clean = $sanitizer->sanitizeText($userInput);

// External system IDs (path traversal prevention)
$remoteId = $sanitizer->sanitizeIdentifier($request->input('remote_id'));

// Numeric values (overflow protection)
$amount = $sanitizer->sanitizeNumeric($request->input('amount'));

// Time values (format validation)
$time = $sanitizer->sanitizeTime($request->input('start_time'));
```

## When to Use

### ✅ Always Use For:
- External system IDs (tariff providers, meter IDs)
- User-provided identifiers
- File names or paths
- Display text with HTML
- Numeric inputs for billing
- Time inputs for schedules

### ❌ Don't Use For:
- Already validated Eloquent model IDs
- Enum values (use validation rules)
- Boolean values
- Internal system identifiers

## Security Fix (2024-12-05)

**Critical**: Path traversal vulnerability fixed in `sanitizeIdentifier()`.

### What Changed
- Removed dot collapse logic that was masking attacks
- Added post-sanitization check for `..` patterns
- Added security event logging

### Attack Vectors Blocked
```php
// These now throw InvalidArgumentException:
"test.@.example"      // Obfuscated double dots
".@./.@./etc/passwd"  // Path traversal
"test.#.#.example"    // Multiple invalid chars
```

### Migration Required?
**No** - The fix is backward compatible. Valid identifiers continue to work.

## Integration Examples

### Controller
```php
class TariffController extends Controller
{
    public function __construct(
        private InputSanitizer $sanitizer
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'remote_id' => 'required|string|max:255',
        ]);
        
        $remoteId = $this->sanitizer->sanitizeIdentifier(
            $validated['remote_id']
        );
        
        Tariff::create(['remote_id' => $remoteId]);
    }
}
```

### Form Request
```php
class StoreTariffRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $this->merge([
            'remote_id' => app(InputSanitizer::class)
                ->sanitizeIdentifier($this->input('remote_id', '')),
        ]);
    }
}
```

### Filament Resource
```php
use Filament\Forms\Components\TextInput;

TextInput::make('remote_id')
    ->label('Remote System ID')
    ->dehydrateStateUsing(fn ($state) => 
        app(InputSanitizer::class)->sanitizeIdentifier($state)
    )
```

## Error Handling

```php
try {
    $id = $sanitizer->sanitizeIdentifier($input);
} catch (InvalidArgumentException $e) {
    // Log the error
    Log::warning('Invalid identifier provided', [
        'input' => $input,
        'error' => $e->getMessage(),
    ]);
    
    // Return user-friendly error
    return back()->withErrors([
        'remote_id' => 'Invalid identifier format. Please use only letters, numbers, hyphens, underscores, and single dots.',
    ]);
}
```

## Monitoring

### Check Logs for Attacks
```bash
# View path traversal attempts
grep "Path traversal attempt" storage/logs/laravel.log

# Count attempts by IP
grep "Path traversal attempt" storage/logs/laravel.log | \
  grep -oP 'ip":\s*"\K[^"]+' | sort | uniq -c | sort -rn
```

### Cache Statistics
```php
$stats = $sanitizer->getCacheStats();
// ['size' => 150, 'max_size' => 500, 'utilization' => 30.0]

// Clear cache if needed (testing only)
$sanitizer->clearCache();
```

## Testing

```php
use App\Services\InputSanitizer;
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    /** @test */
    public function it_sanitizes_external_ids(): void
    {
        $sanitizer = new InputSanitizer();
        
        // Valid identifier
        $result = $sanitizer->sanitizeIdentifier('provider-123');
        $this->assertEquals('provider-123', $result);
        
        // Path traversal blocked
        $this->expectException(\InvalidArgumentException::class);
        $sanitizer->sanitizeIdentifier('../../../etc/passwd');
    }
}
```

## Performance

- **Text sanitization**: 10-50μs
- **Identifier sanitization**: 20-100μs
- **Numeric sanitization**: 1-5μs
- **Cache hit rate**: 80-90%

## Related Documentation

- [Full Service Documentation](../services/INPUT_SANITIZER_SERVICE.md)
- [Security Fix Details](input-sanitizer-security-fix.md)
- [Security Patch Summary](SECURITY_PATCH_2024-12-05.md)

## Support

- **Security Issues**: security@example.com
- **General Questions**: dev-team@example.com
