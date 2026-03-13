# InputSanitizer Refactoring - Quick Reference

## 🚀 TL;DR

The `InputSanitizer` service now uses **interface-based design**, **event-driven security monitoring**, and **Laravel Cache** for better testability, monitoring, and performance.

## 📝 Usage (No Changes Required!)

```php
// Still works exactly the same
$sanitizer = app(InputSanitizer::class);
$clean = $sanitizer->sanitizeText($input);
$id = $sanitizer->sanitizeIdentifier($identifier);
```

## ✨ New Features

### 1. Interface-Based Injection (Recommended)

```php
// Before
public function __construct(InputSanitizer $sanitizer) {}

// After (better for testing)
use App\Contracts\InputSanitizerInterface;

public function __construct(InputSanitizerInterface $sanitizer) {}
```

### 2. Security Event Monitoring

```php
// Automatically dispatched on security violations
SecurityViolationDetected::dispatch(
    violationType: 'path_traversal',
    originalInput: '../etc/passwd',
    sanitizedAttempt: '..etcpasswd',
    ipAddress: '192.168.1.1',
    userId: 123
);

// Add your own listener
Event::listen(SecurityViolationDetected::class, function ($event) {
    // Send Slack notification
    // Block IP
    // Alert security team
});
```

### 3. Cross-Request Caching

```php
// Unicode normalization now cached across requests
// Uses Laravel Cache (Redis, Memcached, etc.)
// Automatic TTL management (1 hour)
```

## 🧪 Testing

```php
// Mock the interface in tests
$this->mock(InputSanitizerInterface::class, function ($mock) {
    $mock->shouldReceive('sanitizeText')
         ->once()
         ->with('input')
         ->andReturn('clean');
});

// Test security events
Event::fake();
$sanitizer->sanitizeIdentifier('../etc/passwd');
Event::assertDispatched(SecurityViolationDetected::class);
```

## 📊 Monitoring

```php
// Check cache stats
$stats = $sanitizer->getCacheStats();
// ['size' => 0, 'max_size' => 500, 'utilization' => 0.0, ...]

// View security logs
tail -f storage/logs/security.log

// Count violations by IP
grep "Security violation" storage/logs/security.log | \
  grep -oP 'ip_address":\s*"\K[^"]+' | sort | uniq -c
```

## 🔧 Configuration

### Optional: Security Log Channel

```php
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90, // Keep longer for compliance
    ],
],
```

### Optional: Cache Driver

```php
// .env
CACHE_DRIVER=redis  # or memcached, file, database
```

## 🎯 Common Patterns

### Controller Usage

```php
use App\Contracts\InputSanitizerInterface;

class TariffController extends Controller
{
    public function __construct(
        private InputSanitizerInterface $sanitizer
    ) {}

    public function store(Request $request)
    {
        $remoteId = $this->sanitizer->sanitizeIdentifier(
            $request->input('remote_id')
        );
        
        // Use sanitized value...
    }
}
```

### Form Request Usage

```php
use App\Contracts\InputSanitizerInterface;

class StoreTariffRequest extends FormRequest
{
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

### Filament Resource Usage

```php
use App\Contracts\InputSanitizerInterface;

TextInput::make('remote_id')
    ->dehydrateStateUsing(function ($state) {
        return app(InputSanitizerInterface::class)
            ->sanitizeIdentifier($state ?? '');
    })
```

## 🚨 Security Alerts

### Automatic Violation Tracking

```php
// Automatically tracks violations per IP
// Alerts after 5 violations in 1 hour
// Critical alert after 20 violations

// View violation count
cache("security:violations:192.168.1.1"); // Returns count
```

### Add Custom Alerting

```php
// app/Listeners/AlertSecurityTeam.php
class AlertSecurityTeam implements ShouldQueue
{
    public function handle(SecurityViolationDetected $event)
    {
        if ($event->violationType === 'path_traversal') {
            // Send Slack notification
            // Email security team
            // Create incident ticket
        }
    }
}

// Register in AppServiceProvider
Event::listen(
    SecurityViolationDetected::class,
    AlertSecurityTeam::class
);
```

## 📈 Performance Tips

1. **Use Redis for Cache**: Better performance than file/database
2. **Queue Security Logging**: Already implemented (ShouldQueue)
3. **Monitor Cache Hit Rate**: Check `getCacheStats()` regularly
4. **Tune Cache TTL**: Adjust `CACHE_TTL` constant if needed

## 🐛 Troubleshooting

### Issue: Events Not Firing

```php
// Check listener is registered
php artisan event:list

// Clear cache
php artisan cache:clear
php artisan config:clear
```

### Issue: Cache Not Working

```php
// Check cache driver
php artisan tinker
>>> config('cache.default')

// Test cache
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
```

### Issue: Security Logs Missing

```php
// Check log channel exists
// config/logging.php must have 'security' channel

// Check permissions
ls -la storage/logs/
```

## 📚 Documentation

- **Full Guide**: [docs/refactoring/INPUT_SANITIZER_REFACTORING.md](INPUT_SANITIZER_REFACTORING.md)
- **API Reference**: [docs/api/INPUT_SANITIZER_API.md](../api/INPUT_SANITIZER_API.md)
- **Tests**: `tests/Unit/Services/InputSanitizerRefactoredTest.php`

## ✅ Migration Checklist

- [x] Service provider updated (automatic)
- [x] Event listener registered (automatic)
- [ ] Update type hints to interface (optional, recommended)
- [ ] Configure security log channel (optional)
- [ ] Add custom alerting (optional)
- [ ] Run tests: `php artisan test --filter=InputSanitizer`

## 🎉 Benefits

✅ **Better Testability**: Mock interface instead of concrete class  
✅ **Centralized Monitoring**: All violations tracked in one place  
✅ **Better Performance**: Cross-request caching with Redis  
✅ **Extensible**: Easy to add custom alerting/blocking  
✅ **Zero Breaking Changes**: Existing code works unchanged  

---

**Questions?** See full documentation in `docs/refactoring/`
