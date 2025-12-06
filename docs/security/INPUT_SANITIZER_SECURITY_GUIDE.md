# InputSanitizer Security Guide

## Overview

The InputSanitizer service provides defense-in-depth input validation and sanitization. However, it is **NOT a complete security solution** and must be used alongside other Laravel security features.

## Security Layers Required

### 1. CSRF Protection (MANDATORY)

**All forms must include CSRF tokens:**

```blade
<form method="POST" action="/tariffs">
    @csrf
    <input type="text" name="remote_id" value="{{ old('remote_id') }}">
    <button type="submit">Submit</button>
</form>
```

**API endpoints must verify CSRF tokens:**

```php
// routes/web.php
Route::post('/tariffs', [TariffController::class, 'store'])
    ->middleware(['auth', 'verified', 'csrf']); // ✅ CSRF middleware
```

### 2. Authorization (MANDATORY)

**Always check permissions before sanitization:**

```php
public function store(Request $request)
{
    // ✅ Check authorization FIRST
    $this->authorize('create', Tariff::class);
    
    // Then sanitize and validate
    $remoteId = $this->sanitizer->sanitizeIdentifier(
        $request->input('remote_id')
    );
    
    // Create resource...
}
```

### 3. Rate Limiting (RECOMMENDED)

**Apply rate limiting to prevent abuse:**

```php
// routes/web.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/tariffs', [TariffController::class, 'store']);
});
```

**Or use custom throttle middleware:**

```php
Route::post('/tariffs', [TariffController::class, 'store'])
    ->middleware(['auth', ThrottleSanitization::class]);
```

### 4. Input Validation (MANDATORY)

**Use FormRequests for validation:**

```php
class StoreTariffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Tariff::class);
    }
    
    public function rules(): array
    {
        return [
            'remote_id' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:999999.9999'],
        ];
    }
    
    protected function prepareForValidation(): void
    {
        $sanitizer = app(InputSanitizerInterface::class);
        
        $this->merge([
            'remote_id' => $sanitizer->sanitizeIdentifier(
                $this->input('remote_id', '')
            ),
        ]);
    }
}
```

## Common Vulnerabilities

### ❌ WRONG: Sanitization Without Authorization

```php
public function store(Request $request)
{
    // ❌ NO authorization check!
    $remoteId = $this->sanitizer->sanitizeIdentifier(
        $request->input('remote_id')
    );
    
    Tariff::create(['remote_id' => $remoteId]); // ❌ Unauthorized creation!
}
```

### ✅ CORRECT: Authorization + Sanitization + Validation

```php
public function store(StoreTariffRequest $request)
{
    // ✅ Authorization checked in FormRequest
    // ✅ Validation rules applied
    // ✅ Sanitization in prepareForValidation()
    
    $tariff = Tariff::create($request->validated());
    
    return redirect()->route('tariffs.show', $tariff);
}
```

### ❌ WRONG: Trusting Sanitized Input

```php
// ❌ Sanitization is NOT validation!
$id = $this->sanitizer->sanitizeIdentifier($request->input('id'));

// ❌ Still need to validate it exists and user has access
$tariff = Tariff::findOrFail($id); // ❌ No tenant check!
```

### ✅ CORRECT: Sanitize + Validate + Authorize

```php
$id = $this->sanitizer->sanitizeIdentifier($request->input('id'));

// ✅ Use scoped query to enforce tenant isolation
$tariff = Tariff::where('id', $id)
    ->where('tenant_id', auth()->user()->tenant_id)
    ->firstOrFail();

// ✅ Check specific permission
$this->authorize('view', $tariff);
```

## Multi-Tenant Security

### Tenant Isolation (CRITICAL)

**Always scope queries by tenant:**

```php
// ❌ WRONG: No tenant check
$tariff = Tariff::where('remote_id', $sanitizedId)->first();

// ✅ CORRECT: Tenant-scoped query
$tariff = Tariff::where('remote_id', $sanitizedId)
    ->where('tenant_id', auth()->user()->tenant_id)
    ->first();

// ✅ BETTER: Use global scope (already applied in models)
$tariff = Tariff::where('remote_id', $sanitizedId)->first();
// TenantScope automatically adds tenant_id filter
```

### Cache Poisoning Prevention

**Request cache includes tenant context:**

```php
// ✅ Cache key includes tenant ID
$cacheKey = "id:{$tenantId}:{$input}:{$maxLength}";
```

**Never cache across tenants:**

```php
// ❌ WRONG: Shared cache key
Cache::remember("tariff:{$id}", 3600, fn() => Tariff::find($id));

// ✅ CORRECT: Tenant-specific cache key
Cache::remember(
    "tariff:{$tenantId}:{$id}",
    3600,
    fn() => Tariff::where('tenant_id', $tenantId)->find($id)
);
```

## Security Event Monitoring

### Viewing Security Logs

```bash
# View path traversal attempts
tail -f storage/logs/security.log | grep "Path traversal"

# Count attempts by IP hash
grep "Path traversal" storage/logs/security.log | \
  jq -r '.ip_hash' | sort | uniq -c | sort -rn

# View recent violations
tail -100 storage/logs/security.log | jq 'select(.type == "path_traversal")'
```

### Alert Thresholds

The system automatically alerts on:

- **5+ violations from same IP in 1 hour**: WARNING logged
- **10+ violations**: CRITICAL alert (implement email/Slack notification)
- **Authenticated user violations**: IMMEDIATE investigation required

### Implementing Alerts

```php
// app/Listeners/AlertSecurityTeam.php
final class AlertSecurityTeam implements ShouldQueue
{
    public function handle(SecurityViolationDetected $event): void
    {
        $violations = cache()->get("security:violations:{$event->ipAddress}", 0);
        
        if ($violations > 10) {
            // Send Slack notification
            Notification::route('slack', config('services.slack.security_webhook'))
                ->notify(new SecurityAlert($event));
            
            // Email security team
            Mail::to(config('mail.security_team'))
                ->send(new SecurityViolationMail($event));
        }
    }
}
```

## Testing Security

### Unit Tests

```php
it('blocks path traversal attempts', function () {
    $sanitizer = app(InputSanitizerInterface::class);
    
    expect(fn() => $sanitizer->sanitizeIdentifier('../etc/passwd'))
        ->toThrow(InvalidArgumentException::class);
});

it('logs security violations', function () {
    Event::fake();
    
    try {
        $sanitizer->sanitizeIdentifier('test..example');
    } catch (InvalidArgumentException $e) {
        // Expected
    }
    
    Event::assertDispatched(SecurityViolationDetected::class);
});
```

### Integration Tests

```php
it('enforces rate limiting on sanitization', function () {
    $user = User::factory()->create();
    
    // Make 1001 requests (limit is 1000/hour)
    for ($i = 0; $i < 1001; $i++) {
        $response = $this->actingAs($user)
            ->post('/tariffs', ['remote_id' => "test-{$i}"]);
    }
    
    // Last request should be rate limited
    expect($response->status())->toBe(429);
});
```

## Compliance Checklist

- [ ] CSRF tokens on all forms
- [ ] Authorization checks before sanitization
- [ ] Rate limiting on public endpoints
- [ ] FormRequest validation
- [ ] Tenant isolation in queries
- [ ] Security event monitoring configured
- [ ] Alert thresholds defined
- [ ] PII redaction in logs verified
- [ ] Security tests passing
- [ ] Documentation reviewed by security team

## References

- [OWASP Input Validation](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html)
- [Laravel Security Best Practices](https://laravel.com/docs/12.x/security)
- [GDPR Compliance Guide](https://gdpr.eu/)
- [Path Traversal Prevention](https://owasp.org/www-community/attacks/Path_Traversal)
