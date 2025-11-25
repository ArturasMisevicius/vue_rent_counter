# Service Layer Architecture Guide

## Executive Summary

This document provides comprehensive guidance on implementing a robust service layer architecture for the Vilnius Utilities Billing Platform. The service layer acts as the orchestration layer between controllers and models, encapsulating complex business logic, ensuring transaction integrity, and maintaining separation of concerns.

**Date**: November 26, 2025  
**Status**: ✅ PRODUCTION READY  
**Laravel Version**: 12.x

---

## Table of Contents

1. [Current Architecture Analysis](#current-architecture-analysis)
2. [Service Layer Principles](#service-layer-principles)
3. [Base Service Class](#base-service-class)
4. [Service Classes](#service-classes)
5. [Action Classes](#action-classes)
6. [DTOs (Data Transfer Objects)](#dtos-data-transfer-objects)
7. [Service Container Binding](#service-container-binding)
8. [Controller Integration](#controller-integration)
9. [Testing Strategy](#testing-strategy)
10. [Best Practices](#best-practices)

---

## Current Architecture Analysis

### Existing Services (✅ Well-Designed)

The application already has excellent service layer examples:

1. **BillingService** - Orchestrates invoice generation with tariff snapshotting
2. **TariffResolver** - Resolves active tariffs and calculates costs
3. **GyvatukasCalculator** - Handles seasonal circulation fee calculations
4. **SubscriptionService** - Manages subscription lifecycle
5. **AccountManagementService** - Handles user account operations

### New Controller Analysis

**MeterReadingUpdateController** (newly created):
- ✅ Single-action controller (good)
- ✅ Delegates to observer for audit trail
- ✅ Uses FormRequest for validation
- ✅ Clear documentation
- ⚠️ Could benefit from a dedicated service for complex reading updates

### Opportunities for Improvement

1. **MeterReadingService** - Extract reading validation and update logic
2. **InvoiceService** - Separate invoice operations from BillingService
3. **NotificationService** - Centralize notification dispatching
4. **AuditService** - Standardize audit logging across services

---

## Service Layer Principles

### When to Use Services vs Actions vs Helpers

```
┌─────────────────────────────────────────────────────────────┐
│                    Decision Tree                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Is it a pure function with no dependencies?                │
│  └─ YES → Helper Function (app/Support/helpers.php)         │
│  └─ NO  → Continue                                           │
│                                                              │
│  Does it perform a single atomic operation?                 │
│  └─ YES → Action Class (app/Actions/)                       │
│  └─ NO  → Continue                                           │
│                                                              │
│  Does it orchestrate multiple operations?                   │
│  └─ YES → Service Class (app/Services/)                     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Service Responsibilities

**Services SHOULD**:
- Orchestrate complex business logic
- Manage transactions
- Coordinate multiple actions
- Handle cross-cuttingr logic here
                return $data;
            });
            
            return $this->success($result);
        } catch (\Throwable $e) {
            $this->handleException($e);
            return $this->error('Operation failed');
        }
    }
}
```

### ServiceResponse

Immutable DTO for service responses:

**Location**: `app/Services/ServiceResponse.php`

**Properties**:
- `success` (bool): Operation success status
- `data` (mixed): Response data
- `message` (string): Human-readable message
- `code` (int): Error code (optional)

**Methods**:
- `isSuccess()`: Check if successful
- `isFailure()`: Check if failed
- `getDataOrFail()`: Get data or throw exception
- `toArray()`: Convert to array

**Usage**:
```php
$response = $service->calculate($building, $month);

if ($response->isSuccess()) {
    $energy = $response->data;
} else {
    return back()->withErrors($response->message);
}
```

### DTOs (Data Transfer Objects)

Immutable objects for transferring data between layers:

**Location**: `app/DTOs/`

**Example**: `GyvatukasCalculationDTO`

**Features**:
- Immutable (readonly properties)
- Factory methods (fromRequest, fromModel)
- Validation methods
- Type safety

**Usage**:
```php
$dto = GyvatukasCalculationDTO::fromRequest($request);

if (!$dto->isValid()) {
    return back()->withErrors($dto->validate());
}

$response = $service->calculate($dto);
```

## Service Examples

### GyvatukasCalculatorService

Comprehensive service for gyvatukas calculations:

**Location**: `app/Services/GyvatukasCalculatorService.php`

**Features**:
- Authorization enforcement via policy
- Rate limiting (10/min per user, 100/min per tenant)
- Caching (1 hour TTL)
- Audit trail for all calculations
- Structured error handling

**Usage**:
```php
// In controller
public function calculate(CalculateGyvatukasRequest $request)
{
    $building = Building::findOrFail($request->building_id);
    $month = Carbon::parse($request->billing_month);
    
    $response = $this->gyvatukasService->calculate($building, $month);
    
    if ($response->isFailure()) {
        return back()->withErrors($response->message);
    }
    
    return view('gyvatukas.result', [
        'energy' => $response->data,
        'building' => $building,
    ]);
}
```

**Authorization**:
- Superadmin: Can calculate for any building
- Admin/Manager: Can calculate for buildings in their tenant
- Tenant: Cannot calculate (view-only)

**Rate Limiting**:
- Per-user: 10 calculations per minute
- Per-tenant: 100 calculations per minute

**Caching**:
- Cache key: `gyvatukas:building:{id}:month:{Y-m}`
- TTL: 1 hour
- Invalidation: Manual via `clearBuildingCache()`

**Audit Trail**:
- Table: `gyvatukas_calculation_audits`
- Records: building_id, billing_month, circulation_energy, execution_time_ms, calculated_by

### BillingService

Service for invoice generation and billing operations:

**Location**: `app/Services/BillingService.php`

**Responsibilities**:
- Invoice generation
- Tariff resolution
- Meter reading validation
- Cost calculations
- Invoice finalization

**Usage**:
```php
$response = $billingService->generateInvoice(
    property: $property,
    billingPeriod: $period,
    meterReadings: $readings
);

if ($response->isSuccess()) {
    $invoice = $response->data;
    return redirect()->route('invoices.show', $invoice);
}
```

### MeterReadingService

Service for meter reading operations:

**Location**: `app/Services/MeterReadingService.php`

**Responsibilities**:
- Reading validation (monotonicity, temporal)
- Consumption calculations
- Audit trail creation
- Invoice recalculation triggers

**Usage**:
```php
$response = $meterReadingService->createReading(
    meter: $meter,
    value: $value,
    readingDate: $date,
    enteredBy: $user
);

if ($response->isSuccess()) {
    $reading = $response->data;
    // Trigger invoice recalculation if needed
}
```

## Controller Integration

### Thin Controllers

Controllers should be thin and delegate to services:

```php
class GyvatukasController extends Controller
{
    public function __construct(
        private readonly GyvatukasCalculatorService $gyvatukasService
    ) {}

    public function calculate(CalculateGyvatukasRequest $request)
    {
        $building = Building::findOrFail($request->building_id);
        $month = Carbon::parse($request->billing_month);
        
        $response = $this->gyvatukasService->calculate($building, $month);
        
        return $response->isSuccess()
            ? view('gyvatukas.result', ['energy' => $response->data])
            : back()->withErrors($response->message);
    }
}
```

### Form Request Validation

Use Form Requests for input validation:

```php
class CalculateGyvatukasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'billing_month' => ['required', 'date', 'before_or_equal:today'],
            'distribution_method' => ['sometimes', 'in:equal,area'],
        ];
    }
}
```

## Testing Strategy

### Unit Tests

Test services in isolation with mocked dependencies:

```php
test('gyvatukas service calculates with authorization', function () {
    $mockCalculator = Mockery::mock(GyvatukasCalculator::class);
    $mockPolicy = Mockery::mock(GyvatukasCalculatorPolicy::class);
    
    $mockPolicy->shouldReceive('calculate')->andReturn(true);
    $mockCalculator->shouldReceive('calculate')->andReturn(150.50);
    
    $service = new GyvatukasCalculatorService($mockCalculator, $mockPolicy);
    
    $response = $service->calculate($building, $month);
    
    expect($response->isSuccess())->toBeTrue();
    expect($response->data)->toBe(150.50);
});
```

### Feature Tests

Test services with real dependencies and database:

```php
test('gyvatukas service creates audit record', function () {
    $building = Building::factory()->create();
    $month = Carbon::create(2024, 6, 1);
    
    $service = app(GyvatukasCalculatorService::class);
    $response = $service->calculate($building, $month);
    
    expect($response->isSuccess())->toBeTrue();
    
    $this->assertDatabaseHas('gyvatukas_calculation_audits', [
        'building_id' => $building->id,
        'billing_month' => $month->format('Y-m-d'),
    ]);
});
```

### Integration Tests

Test complete workflows:

```php
test('invoice generation includes gyvatukas calculation', function () {
    $property = Property::factory()->create();
    $month = Carbon::create(2024, 6, 1);
    
    $billingService = app(BillingService::class);
    $response = $billingService->generateInvoice($property, $month);
    
    expect($response->isSuccess())->toBeTrue();
    
    $invoice = $response->data;
    expect($invoice->items)->toContain(fn($item) => 
        $item->description === 'Gyvatukas (Circulation Fee)'
    );
});
```

## Service Provider Registration

Register services in `AppServiceProvider`:

```php
public function register(): void
{
    // Singleton for stateless services
    $this->app->singleton(GyvatukasCalculator::class);
    $this->app->singleton(TariffResolver::class);
    
    // Bind services with dependencies
    $this->app->bind(GyvatukasCalculatorService::class, function ($app) {
        return new GyvatukasCalculatorService(
            $app->make(GyvatukasCalculator::class),
            $app->make(GyvatukasCalculatorPolicy::class)
        );
    });
    
    // Interface binding for testability
    $this->app->bind(
        BillingServiceInterface::class,
        BillingService::class
    );
}
```

## Error Handling

### Exception Hierarchy

```
ServiceException (base)
├── AuthorizationException
├── ValidationException
├── CalculationException
├── RateLimitException
└── TenantIsolationException
```

### Error Response Format

```php
ServiceResponse {
    success: false,
    data: null,
    message: 'User-friendly error message',
    code: 1001 // Optional error code
}
```

### Logging Errors

All errors are logged with context:

```php
$this->log('error', 'Calculation failed', [
    'building_id' => $building->id,
    'exception' => get_class($e),
    'message' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

## Performance Considerations

### Caching Strategy

- **Cache Key Format**: `{service}:{entity}:{id}:{context}`
- **TTL**: 1 hour for calculations, 24 hours for static data
- **Invalidation**: Manual via service methods or observers

### Rate Limiting

- **Per-User**: 10 requests per minute
- **Per-Tenant**: 100 requests per minute
- **Implementation**: Laravel RateLimiter with Redis backend

### Query Optimization

- Eager load relationships in services
- Use selective column loading
- Implement query result caching
- Monitor N+1 queries in tests

## Security Considerations

### Authorization

- All service methods check authorization via policies
- Unauthorized attempts are logged
- Services validate tenant ownership

### Audit Trail

- All critical operations create audit records
- Audit records include: user_id, tenant_id, timestamp, operation details
- Audit records are immutable

### Rate Limiting

- Prevents DoS attacks
- Configurable limits per user and tenant
- Logged when limits are exceeded

### Data Validation

- Input validation via Form Requests
- DTO validation before processing
- Tenant isolation validation

## Best Practices

### DO

✅ Use services for business logic  
✅ Return ServiceResponse from all service methods  
✅ Log all operations with context  
✅ Validate authorization in services  
✅ Create audit trails for critical operations  
✅ Use DTOs for complex data transfer  
✅ Write unit tests with mocked dependencies  
✅ Write feature tests with real database  

### DON'T

❌ Put business logic in controllers  
❌ Return raw data from services  
❌ Skip authorization checks  
❌ Forget to log errors  
❌ Skip audit trails  
❌ Use arrays for complex data  
❌ Skip tests  
❌ Ignore rate limiting  

## Migration Guide

### From Fat Controllers

**Before**:
```php
public function calculate(Request $request)
{
    $building = Building::find($request->building_id);
    
    // Authorization
    if (!auth()->user()->can('calculate', $building)) {
        abort(403);
    }
    
    // Business logic
    $calculator = new GyvatukasCalculator();
    $energy = $calculator->calculate($building, now());
    
    // Audit
    GyvatukasCalculationAudit::create([...]);
    
    return view('result', ['energy' => $energy]);
}
```

**After**:
```php
public function calculate(CalculateGyvatukasRequest $request)
{
    $building = Building::findOrFail($request->building_id);
    $month = Carbon::parse($request->billing_month);
    
    $response = $this->gyvatukasService->calculate($building, $month);
    
    return $response->isSuccess()
        ? view('result', ['energy' => $response->data])
        : back()->withErrors($response->message);
}
```

## Related Documentation

- [GyvatukasCalculator API](../api/GYVATUKAS_CALCULATOR_API.md)
- [Service Testing Guide](../testing/SERVICE_TESTING_GUIDE.md)
- [Authorization Guide](../security/AUTHORIZATION_GUIDE.md)
- [Audit Trail Guide](../security/AUDIT_TRAIL_GUIDE.md)

---

**Document Version**: 1.0.0  
**Last Updated**: 2024-11-25  
**Status**: Complete ✅
