# InvalidPropertyAssignmentException Documentation

**Location**: `app/Exceptions/InvalidPropertyAssignmentException.php`  
**Namespace**: `App\Exceptions`  
**Status**: ✅ Production Ready  
**Requirements**: 5.3, 6.1, 7.1

## Overview

The `InvalidPropertyAssignmentException` is a security-focused exception that enforces multi-tenancy boundaries by preventing tenant assignments to properties from different organizations. This exception is critical for maintaining data isolation in the hierarchical user management system.

## Purpose

This exception serves three primary purposes:

1. **Multi-Tenancy Enforcement**: Prevents cross-tenant data access violations
2. **Security Auditing**: Logs all invalid assignment attempts to the security channel
3. **User Feedback**: Provides clear error messages for both API and web requests

## Class Definition

```php
final class InvalidPropertyAssignmentException extends Exception
```

The class is marked as `final` to prevent inheritance and ensure consistent behavior across the application.

## Constructor

```php
public function __construct(
    string $message = 'Cannot assign tenant to property from different organization.',
    int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
    ?\Throwable $previous = null
)
```

### Parameters

- **$message** (string): Custom error message. Default: "Cannot assign tenant to property from different organization."
- **$code** (int): HTTP status code. Default: 422 (Unprocessable Entity)
- **$previous** (Throwable|null): Previous exception for exception chaining

### Default Behavior

- Returns HTTP 422 status code (Unprocessable Entity)
- Provides a user-friendly default message
- Supports exception chaining for debugging

## Methods

### render()

Renders the exception as an HTTP response, supporting both JSON and HTML formats.

```php
public function render(Request $request): JsonResponse|Response
```

#### JSON Response (API Requests)

When the request expects JSON (`Accept: application/json`):

```json
{
    "message": "Cannot assign tenant to property from different organization.",
    "error": "invalid_property_assignment"
}
```

**Status Code**: 422

#### HTML Response (Web Requests)

Renders the `errors.422` Blade view with:
- `message`: The exception message
- `exception`: The exception instance

**Status Code**: 422

### report()

Logs the exception to the security audit channel.

```php
public function report(): bool
```

#### Logging Behavior

- **Channel**: `security` (configured in `config/logging.php`)
- **Level**: `warning`
- **Context**:
  - `message`: The exception message
  - `trace`: Full stack trace for debugging

#### Return Value

Always returns `true` to indicate the exception was successfully reported.

## Usage Examples

### Basic Usage

```php
use App\Exceptions\InvalidPropertyAssignmentException;

// Throw with default message
throw new InvalidPropertyAssignmentException();

// Throw with custom message
throw new InvalidPropertyAssignmentException(
    'Property belongs to organization A, but tenant belongs to organization B'
);
```

### In Service Layer

```php
namespace App\Services;

use App\Exceptions\InvalidPropertyAssignmentException;
use App\Models\Property;
use App\Models\User;

class AccountManagementService
{
    public function assignTenantToProperty(User $tenant, Property $property): void
    {
        // Validate tenant_id matches
        if ($tenant->tenant_id !== $property->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                "Cannot assign tenant (tenant_id: {$tenant->tenant_id}) " .
                "to property (tenant_id: {$property->tenant_id})"
            );
        }
        
        // Proceed with assignment
        $tenant->property_id = $property->id;
        $tenant->save();
    }
}
```

### In Controller

```php
namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidPropertyAssignmentException;
use App\Services\AccountManagementService;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function assignProperty(Request $request, User $tenant)
    {
        try {
            $property = Property::findOrFail($request->property_id);
            
            app(AccountManagementService::class)
                ->assignTenantToProperty($tenant, $property);
            
            return redirect()
                ->route('admin.tenants.show', $tenant)
                ->with('success', 'Tenant assigned to property successfully.');
                
        } catch (InvalidPropertyAssignmentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
```

### In Filament Resource

```php
namespace App\Filament\Resources\UserResource\Pages;

use App\Exceptions\InvalidPropertyAssignmentException;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate property assignment if property_id is being changed
        if (isset($data['property_id']) && $data['property_id'] !== $this->record->property_id) {
            $property = Property::find($data['property_id']);
            
            if ($property && $property->tenant_id !== $this->record->tenant_id) {
                Notification::make()
                    ->danger()
                    ->title('Invalid Property Assignment')
                    ->body('Cannot assign tenant to property from different organization.')
                    ->send();
                
                throw new InvalidPropertyAssignmentException();
            }
        }
        
        return $data;
    }
}
```

## API Response Examples

### JSON API Response

**Request**:
```http
POST /api/admin/tenants/123/assign-property
Content-Type: application/json
Accept: application/json

{
    "property_id": 456
}
```

**Response** (422 Unprocessable Entity):
```json
{
    "message": "Cannot assign tenant to property from different organization.",
    "error": "invalid_property_assignment"
}
```

### Web Response

**Request**:
```http
POST /admin/tenants/123/assign-property
Content-Type: application/x-www-form-urlencoded

property_id=456
```

**Response** (422 Unprocessable Entity):
- Renders `resources/views/errors/422.blade.php`
- Displays user-friendly error message
- Includes exception details for debugging (in non-production)

## Security Considerations

### Audit Logging

All instances of this exception are logged to the security channel:

```php
// Logged automatically by report() method
Log::channel('security')->warning('Invalid property assignment attempt', [
    'message' => $this->getMessage(),
    'trace' => $this->getTraceAsString(),
]);
```

**Log Location**: `storage/logs/security.log`

### PII Protection

The exception message should NOT include:
- User email addresses
- Personal identifiable information
- Sensitive tenant data

Use generic messages or sanitized identifiers (IDs only).

### Monitoring

Monitor security logs for patterns of invalid assignment attempts:

```bash
# Check for repeated attempts
grep "Invalid property assignment attempt" storage/logs/security.log | wc -l

# View recent attempts
tail -f storage/logs/security.log | grep "Invalid property assignment"
```

## Testing

### Unit Tests

**Location**: `tests/Unit/Exceptions/InvalidPropertyAssignmentExceptionTest.php`

Key test scenarios:
- Default message and status code
- Custom message and code
- Exception chaining
- JSON response format
- HTML response format
- Security logging

### Feature Tests

Test the exception in context:

```php
test('prevents cross-tenant property assignment', function () {
    $admin = User::factory()->create(['tenant_id' => 'tenant-1']);
    $tenant = User::factory()->create(['tenant_id' => 'tenant-1']);
    $property = Property::factory()->create(['tenant_id' => 'tenant-2']);
    
    $this->actingAs($admin)
        ->post(route('admin.tenants.assign-property', $tenant), [
            'property_id' => $property->id,
        ])
        ->assertSessionHas('error')
        ->assertRedirect();
});
```

## Related Components

### Services
- `AccountManagementService`: Uses this exception for tenant assignment validation
- `SubscriptionService`: May throw this exception during tenant creation

### Middleware
- `EnsureHierarchicalAccess`: Validates tenant_id relationships (complementary validation)

### Policies
- `PropertyPolicy`: Enforces property access rules
- `UserPolicy`: Enforces user management rules

### Models
- `User`: Has `tenant_id` and `property_id` relationships
- `Property`: Has `tenant_id` relationship

## Configuration

### Logging Configuration

Ensure the security channel is configured in `config/logging.php`:

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

### Error View

Create or customize `resources/views/errors/422.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="error-container">
    <h1>Unprocessable Entity</h1>
    <p>{{ $message ?? 'The request could not be processed.' }}</p>
    
    @if(config('app.debug') && isset($exception))
        <pre>{{ $exception->getTraceAsString() }}</pre>
    @endif
</div>
@endsection
```

## Best Practices

### 1. Validate Early

Validate tenant_id relationships before attempting database operations:

```php
// Good: Validate before assignment
if ($tenant->tenant_id !== $property->tenant_id) {
    throw new InvalidPropertyAssignmentException();
}
$tenant->property_id = $property->id;
$tenant->save();

// Bad: Attempt assignment then handle failure
try {
    $tenant->property_id = $property->id;
    $tenant->save();
} catch (\Exception $e) {
    // Too late - may have side effects
}
```

### 2. Use Descriptive Messages

Provide context in custom messages:

```php
// Good: Descriptive message
throw new InvalidPropertyAssignmentException(
    "Property {$property->id} belongs to organization {$property->tenant_id}, " .
    "but tenant {$tenant->id} belongs to organization {$tenant->tenant_id}"
);

// Bad: Generic message
throw new InvalidPropertyAssignmentException();
```

### 3. Handle in Controllers

Always catch and handle this exception in controllers:

```php
try {
    $service->assignTenantToProperty($tenant, $property);
} catch (InvalidPropertyAssignmentException $e) {
    return back()->with('error', $e->getMessage());
}
```

### 4. Monitor Security Logs

Set up alerts for repeated invalid assignment attempts:

```bash
# Example: Alert if more than 10 attempts in 1 hour
if [ $(grep "Invalid property assignment attempt" storage/logs/security.log | \
       grep "$(date +%Y-%m-%d\ %H)" | wc -l) -gt 10 ]; then
    # Send alert
    echo "High number of invalid property assignment attempts detected"
fi
```

## Troubleshooting

### Issue: Exception Not Logged

**Symptoms**: Exception thrown but not appearing in security logs

**Solutions**:
1. Verify security channel configuration in `config/logging.php`
2. Check file permissions on `storage/logs/security.log`
3. Ensure `report()` method is being called (not caught and suppressed)

### Issue: Wrong HTTP Status Code

**Symptoms**: Receiving 500 instead of 422

**Solutions**:
1. Verify exception is being caught by Laravel's exception handler
2. Check for custom exception handling in `app/Exceptions/Handler.php`
3. Ensure `render()` method is returning proper response

### Issue: Missing Error View

**Symptoms**: Generic error page instead of custom 422 view

**Solutions**:
1. Create `resources/views/errors/422.blade.php`
2. Verify view path in `render()` method
3. Check view cache: `php artisan view:clear`

## Changelog

### Version 1.1.0 (Current)
- Added comprehensive constructor with default message and code
- Implemented `render()` method for JSON and HTML responses
- Implemented `report()` method for security logging
- Added full PHPDoc documentation
- Marked class as `final` for security

### Version 1.0.0 (Initial)
- Basic exception class structure
- No custom behavior

## References

- **Spec**: `.kiro/specs/3-hierarchical-user-management/tasks.md` (Task 7.2)
- **Requirements**: 5.3, 6.1, 7.1
- **Tests**: `tests/Unit/Exceptions/InvalidPropertyAssignmentExceptionTest.php`
- **Related Exceptions**: 
  - `CannotDeleteWithDependenciesException`
  - `SubscriptionExpiredException`
  - `SubscriptionLimitExceededException`

## Support

For questions or issues:
1. Review this documentation
2. Check unit tests for usage examples
3. Review security logs for debugging
4. Consult the hierarchical user management spec

---

**Last Updated**: 2024-11-26  
**Maintained By**: Development Team  
**Review Frequency**: Quarterly
