# Exception Documentation Index

This directory contains comprehensive documentation for custom exceptions in the Vilnius Utilities Billing Platform.

## Exception Categories

### Multi-Tenancy Exceptions

Exceptions that enforce multi-tenancy boundaries and data isolation:

- **[InvalidPropertyAssignmentException](INVALID_PROPERTY_ASSIGNMENT_EXCEPTION.md)** - Prevents tenant assignment to properties from different organizations
- **CannotDeleteWithDependenciesException** - Prevents deletion of entities with dependencies

### Subscription Exceptions

Exceptions related to subscription management and enforcement:

- **SubscriptionExpiredException** - Thrown when subscription has expired
- **SubscriptionLimitExceededException** - Thrown when subscription limits are exceeded

### Billing Exceptions

Exceptions related to billing operations:

- **BillingException** - General billing operation failures
- **InvoiceException** - Invoice-specific errors
- **InvoiceAlreadyFinalizedException** - Prevents modification of finalized invoices
- **TariffNotFoundException** - Thrown when required tariff is not found

### Meter Reading Exceptions

Exceptions related to meter reading operations:

- **InvalidMeterReadingException** - Invalid meter reading data
- **MissingMeterReadingException** - Required meter reading not found

## Exception Hierarchy

```
Exception (PHP)
└── App\Exceptions
    ├── InvalidPropertyAssignmentException (final)
    ├── CannotDeleteWithDependenciesException (final)
    ├── SubscriptionExpiredException (final)
    ├── SubscriptionLimitExceededException (final)
    ├── BillingException
    ├── InvoiceException
    ├── InvoiceAlreadyFinalizedException (final)
    ├── TariffNotFoundException (final)
    ├── InvalidMeterReadingException (final)
    └── MissingMeterReadingException (final)
```

## Common Patterns

### Security Logging

All security-relevant exceptions log to the `security` channel:

```php
Log::channel('security')->warning('Security event', [
    'message' => $this->getMessage(),
    'trace' => $this->getTraceAsString(),
]);
```

### Dual Response Format

Exceptions support both JSON (API) and HTML (web) responses:

```php
public function render(Request $request): JsonResponse|Response
{
    if ($request->expectsJson()) {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'error_code',
        ], $this->getCode());
    }

    return response()->view('errors.422', [
        'message' => $this->getMessage(),
        'exception' => $this,
    ], $this->getCode());
}
```

### Final Classes

Most custom exceptions are marked as `final` to prevent inheritance and ensure consistent behavior:

```php
final class InvalidPropertyAssignmentException extends Exception
{
    // ...
}
```

## HTTP Status Codes

| Exception | Status Code | Reason |
|-----------|-------------|--------|
| InvalidPropertyAssignmentException | 422 | Unprocessable Entity |
| CannotDeleteWithDependenciesException | 422 | Unprocessable Entity |
| SubscriptionExpiredException | 403 | Forbidden |
| SubscriptionLimitExceededException | 403 | Forbidden |
| BillingException | 500 | Internal Server Error |
| InvoiceException | 422 | Unprocessable Entity |
| InvoiceAlreadyFinalizedException | 422 | Unprocessable Entity |
| TariffNotFoundException | 404 | Not Found |
| InvalidMeterReadingException | 422 | Unprocessable Entity |
| MissingMeterReadingException | 404 | Not Found |

## Testing

All custom exceptions have comprehensive unit tests in `tests/Unit/Exceptions/`:

```bash
# Run all exception tests
php artisan test --filter=Exceptions

# Run specific exception test
php artisan test --filter=InvalidPropertyAssignmentExceptionTest
```

## Monitoring

### Security Logs

Monitor security-relevant exceptions:

```bash
# View security log
tail -f storage/logs/security.log

# Count security events
grep "Invalid property assignment attempt" storage/logs/security.log | wc -l
```

### Application Logs

Monitor general exception patterns:

```bash
# View application log
tail -f storage/logs/laravel.log

# Search for specific exception
grep "InvalidPropertyAssignmentException" storage/logs/laravel.log
```

## Best Practices

### 1. Use Specific Exceptions

Throw the most specific exception for the error condition:

```php
// Good: Specific exception
throw new InvalidPropertyAssignmentException();

// Bad: Generic exception
throw new \Exception('Invalid property assignment');
```

### 2. Provide Context

Include relevant context in exception messages:

```php
// Good: Contextual message
throw new InvalidPropertyAssignmentException(
    "Property {$property->id} belongs to tenant {$property->tenant_id}"
);

// Bad: Generic message
throw new InvalidPropertyAssignmentException();
```

### 3. Handle Gracefully

Always catch and handle custom exceptions in controllers:

```php
try {
    $service->performOperation();
} catch (InvalidPropertyAssignmentException $e) {
    return back()->with('error', $e->getMessage());
}
```

### 4. Log Appropriately

Use appropriate log levels:
- `warning`: Security violations, business rule violations
- `error`: Unexpected errors, system failures
- `critical`: System-wide failures

## Configuration

### Logging Channels

Configure exception logging in `config/logging.php`:

```php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90,
        'tap' => [App\Logging\RedactSensitiveData::class],
    ],
],
```

### Error Views

Create custom error views in `resources/views/errors/`:
- `422.blade.php` - Unprocessable Entity
- `403.blade.php` - Forbidden
- `404.blade.php` - Not Found
- `500.blade.php` - Internal Server Error

## Related Documentation

- **Architecture**: [docs/architecture/SERVICE_LAYER_ARCHITECTURE.md](../architecture/SERVICE_LAYER_ARCHITECTURE.md)
- **Security**: [docs/security/SECURITY_IMPLEMENTATION_COMPLETE.md](../security/SECURITY_IMPLEMENTATION_COMPLETE.md)
- **Testing**: [docs/testing/README.md](../testing/README.md)
- **Middleware**: [docs/middleware/HIERARCHICAL_MIDDLEWARE_ARCHITECTURE.md](../middleware/HIERARCHICAL_MIDDLEWARE_ARCHITECTURE.md)

## Contributing

When adding new exceptions:

1. Create the exception class in `app/Exceptions/`
2. Mark as `final` if no inheritance is needed
3. Implement `render()` for dual response format
4. Implement `report()` for appropriate logging
5. Create comprehensive unit tests
6. Document in this directory
7. Update this README

---

**Last Updated**: 2024-11-26  
**Maintained By**: Development Team
