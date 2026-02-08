# Service Layer Architecture - Complete Implementation Guide

**Date**: 2025-11-25  
**Status**: ✅ PRODUCTION READY  
**Version**: 3.0.0

## Executive Summary

The Vilnius Utilities Billing Platform implements a comprehensive service layer architecture that separates business logic from controllers, provides transaction management, standardized error handling, and structured logging. This guide documents the complete architecture with production-ready examples.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    HTTP Layer (Controllers)                  │
│  - Request validation (FormRequests)                        │
│  - HTTP response formatting                                 │
│  - Thin controllers delegate to services                    │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    Service Layer                             │
│  - Business logic orchestration                             │
│  - Transaction management                                   │
│  - Error handling and logging                               │
│  - Multi-service coordination                               │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    Action Layer (Optional)                   │
│  - Single atomic operations                                 │
│  - Reusable across services                                 │
│  - Focused responsibility                                   │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    Data Layer (Models/Repositories)          │
│  - Eloquent models                                          │
│  - Database queries                                         │
│  - Data persistence                                         │
└─────────────────────────────────────────────────────────────┘
```

## Core Components

### 1. BaseService Abstract Class

**Location**: `app/Services/BaseService.php`

**Purpose**: Provides common functionality for all service classes

**Features**:
- Transaction management with automatic rollback
- Standardized error handling with logging
- Consistent response formatting (ServiceResponse)
- Structured logging with tenant/user context
- Tenant ownership validation

**Key Methods**:

```php
// Transaction management
protected function executeInTransaction(callable $callback): mixed

// Response formatting
protected function success(mixed $data = null, string $message = ''): ServiceResponse
protected function error(string $message, mixed $data = null, int $code = 0): ServiceResponse

// Logging with context
protected function log(string $level, string $message, array $context = []): void

// Exception handling
protected function handleException(Throwable $e, array $context = []): void

// Tenant validation
protected function validateTenantOwnership(object $model): bool
```

### 2. ServiceResponse DTO

**Location**: `app/Services/ServiceResponse.php`

**Purpose**: Standardized response object for all service operations

**Properties**:
```php
readonly class ServiceResponse {
    public bool $success;
    public mixed $data;
    public string $message;
    public int $code;
}
```

**Methods**:
- `isSuccess()`: Check if operation succeeded
- `isFailure()`: Check if operation failed
- `getDataOrFail()`: Get data or throw exception
- `toArray()`: Convert to array for JSON responses

## Service Layer Patterns

### Pattern 1: BillingService (Complex Orchestration)

**Use Case**: Multi-step invoice generation with tariff snapshotting

**Architecture**:


```php
class BillingService extends BaseService
{
    public function __construct(
        private readonly TariffResolver $tariffResolver,
        private readonly hot water circulationCalculator $hot water circulationCalculator,
        private readonly MeterReadingService $meterReadingService
    ) {}

    public function generateInvoice(
        Tenant $tenant, 
        Carbon $periodStart, 
        Carbon $periodEnd
    ): Invoice {
        return $this->executeInTransaction(function () use ($tenant, $periodStart, $periodEnd) {
            // 1. Log operation start
            $this->log('info', 'Starting invoice generation', [
                'tenant_id' => $tenant->id,
                'period' => "{$periodStart} to {$periodEnd}",
            ]);

            // 2. Create billing period value object
            $billingPeriod = new BillingPeriod($periodStart, $periodEnd);

            // 3. Validate property and meters
            $property = $tenant->property;
            if (!$property) {
                throw new BillingException("Tenant has no property");
            }

            // 4. Create draft invoice
            $invoice = Invoice::create([...]);

            // 5. Generate invoice items for each meter
            $invoiceItems = collect();
            foreach ($property->meters as $meter) {
                $items = $this->generateInvoiceItemsForMeter($meter, $billingPeriod, $property);
                $invoiceItems = $invoiceItems->merge($items);
            }

            // 6. Add hot water circulation items if applicable
            if ($property->building) {
                $hot water circulationItems = $this->generatehot water circulationItems($property, $billingPeriod);
                $invoiceItems = $invoiceItems->merge($hot water circulationItems);
            }

            // 7. Create invoice items and calculate total
            $totalAmount = 0.00;
            foreach ($invoiceItems as $itemData) {
                $item = InvoiceItem::create($itemData);
                $totalAmount += $item->total;
            }

            // 8. Update invoice total
            $invoice->update(['total_amount' => round($totalAmount, 2)]);

            // 9. Log completion
            $this->log('info', 'Invoice generation completed', [
                'invoice_id' => $invoice->id,
                'total_amount' => $invoice->total_amount,
            ]);

            return $invoice->fresh(['items']);
        });
    }
}
```

**Key Features**:
- Extends BaseService for transaction management
- Dependency injection of required services
- Value objects (BillingPeriod) for type safety
- Comprehensive logging at each step
- Exception handling with context
- Atomic transaction wrapping entire operation

### Pattern 2: hot water circulationCalculatorService (Authorization + Audit)

**Use Case**: Secure calculation with authorization and audit trail

**Architecture**:


```php
class hot water circulationCalculatorService extends BaseService
{
    public function __construct(
        private readonly hot water circulationCalculator $calculator
    ) {}

    public function calculate(hot water circulationCalculationDTO $dto): ServiceResponse
    {
        try {
            // 1. Authorization check
            if (!Gate::allows('calculate-hot water circulation', $dto->building)) {
                return $this->error('Unauthorized to calculate hot water circulation');
            }

            // 2. Rate limiting
            RateLimiter::attempt(
                "hot water circulation-calc:{$dto->userId}",
                10, // 10 per minute
                fn() => true
            );

            // 3. Perform calculation
            $result = $this->calculator->calculate($dto->building, $dto->month);

            // 4. Create audit record
            hot water circulationCalculationAudit::create([
                'building_id' => $dto->building->id,
                'user_id' => $dto->userId,
                'calculation_month' => $dto->month,
                'result' => $result,
                'execution_time_ms' => $executionTime,
            ]);

            // 5. Log success
            $this->log('info', 'hot water circulation calculated', [
                'building_id' => $dto->building->id,
                'result' => $result,
            ]);

            return $this->success($result, 'Calculation completed');

        } catch (\Exception $e) {
            $this->handleException($e, ['dto' => $dto->toArray()]);
            return $this->error($e->getMessage());
        }
    }
}
```

**Key Features**:
- Authorization enforcement at service level
- Rate limiting to prevent abuse
- Audit trail for all calculations
- ServiceResponse for consistent returns
- Exception handling with context

## Value Objects (DTOs)

### Purpose
- Immutable data containers
- Type safety between layers
- Validation encapsulation
- Self-documenting code

### Example: BillingPeriod

**Location**: `app/ValueObjects/BillingPeriod.php`

```php
final readonly class BillingPeriod
{
    public function __construct(
        public Carbon $start,
        public Carbon $end
    ) {
        if ($this->start->isAfter($this->end)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }
    }

    public function days(): int
    {
        return $this->start->diffInDays($this->end);
    }

    public function contains(Carbon $date): bool
    {
        return $date->between($this->start, $this->end);
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
        ];
    }
}
```

### Example: ConsumptionData

**Location**: `app/ValueObjects/ConsumptionData.php`

```php
final readonly class ConsumptionData
{
    public function __construct(
        public MeterReading $startReading,
        public MeterReading $endReading,
        public ?string $zone = null
    ) {}

    public function amount(): float
    {
        return max(0, $this->endReading->value - $this->startReading->value);
    }

    public function period(): BillingPeriod
    {
        return new BillingPeriod(
            $this->startReading->reading_date,
            $this->endReading->reading_date
        );
    }

    public function toArray(): array
    {
        return [
            'start_reading_id' => $this->startReading->id,
            'end_reading_id' => $this->endReading->id,
            'amount' => $this->amount(),
            'zone' => $this->zone,
        ];
    }
}
```

## Controller Integration

### Thin Controllers Pattern

Controllers should:
- Validate input (FormRequests)
- Delegate to services
- Format HTTP responses
- Handle HTTP concerns only

### Example: InvoiceController

```php
class InvoiceController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService
    ) {}

    public function store(GenerateInvoiceRequest $request)
    {
        try {
            $invoice = $this->billingService->generateInvoice(
                tenant: $request->tenant(),
                periodStart: $request->date('period_start'),
                periodEnd: $request->date('period_end')
            );

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', __('billing.invoice_generated'));

        } catch (BillingException $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function finalize(Invoice $invoice)
    {
        try {
            $this->billingService->finalizeInvoice($invoice);

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', __('billing.invoice_finalized'));

        } catch (InvoiceAlreadyFinalizedException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
```

**Key Points**:
- Constructor injection of services
- FormRequest validation before service call
- Try-catch for specific exceptions
- Localized messages
- Redirect with flash messages

## Service Container Binding

### AppServiceProvider Registration

**Location**: `app/Providers/AppServiceProvider.php`

```php
public function register(): void
{
    // Singleton for stateless services
    $this->app->singleton(TariffResolver::class);
    $this->app->singleton(hot water circulationCalculator::class);

    // Instance binding for stateful services
    $this->app->bind(BillingService::class);
    $this->app->bind(MeterReadingService::class);

    // Interface binding for testability
    $this->app->bind(
        BillingServiceInterface::class,
        BillingService::class
    );
}
```

**Binding Strategies**:
- **Singleton**: Stateless services, calculators, resolvers
- **Bind**: Stateful services that need fresh instances
- **Interface**: For dependency inversion and testing

## Testing Strategy

### Unit Tests (Mock Dependencies)

**Purpose**: Test service logic in isolation

```php
test('billing service generates invoice with correct calculations', function () {
    // Arrange
    $mockTariffResolver = Mockery::mock(TariffResolver::class);
    $mockhot water circulation = Mockery::mock(hot water circulationCalculator::class);
    $mockMeterReading = Mockery::mock(MeterReadingService::class);

    $service = new BillingService(
        $mockTariffResolver,
        $mockhot water circulation,
        $mockMeterReading
    );

    $tenant = Tenant::factory()->create();
    $periodStart = Carbon::parse('2024-01-01');
    $periodEnd = Carbon::parse('2024-01-31');

    // Act
    $invoice = $service->generateInvoice($tenant, $periodStart, $periodEnd);

    // Assert
    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect($invoice->status)->toBe(InvoiceStatus::DRAFT);
    expect($invoice->total_amount)->toBeGreaterThan(0);
});
```

### Feature Tests (Real Services)

**Purpose**: Test service integration with database

```php
test('billing service creates invoice with meter readings', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
    $meter = Meter::factory()->create(['property_id' => $property->id]);
    
    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'reading_date' => '2024-01-01',
        'value' => 1000,
    ]);
    
    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'reading_date' => '2024-01-31',
        'value' => 1500,
    ]);

    $service = app(BillingService::class);

    // Act
    $invoice = $service->generateInvoice(
        $tenant,
        Carbon::parse('2024-01-01'),
        Carbon::parse('2024-01-31')
    );

    // Assert
    expect($invoice->items)->toHaveCount(1);
    expect($invoice->items->first()->quantity)->toBe(500.0);
});
```

## Error Handling Patterns

### Exception Hierarchy

```php
// Base exception
class BillingException extends \Exception {}

// Specific exceptions
class MissingMeterReadingException extends BillingException {}
class InvoiceAlreadyFinalizedException extends BillingException {}
class InvalidTariffException extends BillingException {}
```

### Service-Level Error Handling

```php
public function generateInvoice(...): Invoice
{
    return $this->executeInTransaction(function () use (...) {
        try {
            // Business logic
            
        } catch (MissingMeterReadingException $e) {
            $this->log('warning', 'Missing meter reading', [
                'meter_id' => $meter->id,
                'error' => $e->getMessage(),
            ]);
            // Continue with other meters or rethrow
        } catch (\Exception $e) {
            $this->handleException($e, ['context' => 'invoice_generation']);
            throw $e; // Rollback transaction
        }
    });
}
```

## Logging Best Practices

### Structured Logging

```php
// Good: Structured with context
$this->log('info', 'Invoice generated', [
    'invoice_id' => $invoice->id,
    'tenant_id' => $tenant->id,
    'total_amount' => $invoice->total_amount,
    'items_count' => $invoice->items->count(),
]);

// Bad: Unstructured string
Log::info("Invoice {$invoice->id} generated for tenant {$tenant->id}");
```

### Log Levels

- **debug**: Detailed diagnostic information
- **info**: Significant events (invoice generated, calculation completed)
- **warning**: Exceptional occurrences that are not errors (missing readings, negative values)
- **error**: Runtime errors that should be investigated

### Automatic Context

BaseService automatically adds:
- `service`: Service class name
- `tenant_id`: Current tenant context
- `user_id`: Authenticated user ID
- `user_role`: User role

## Performance Considerations

### Transaction Boundaries

```php
// Good: Single transaction for atomic operation
public function generateInvoice(...): Invoice
{
    return $this->executeInTransaction(function () {
        // All database operations
    });
}

// Bad: Multiple transactions
public function generateInvoice(...): Invoice
{
    DB::transaction(fn() => Invoice::create(...));
    DB::transaction(fn() => InvoiceItem::create(...)); // Separate transaction!
}
```

### Eager Loading

```php
// Good: Eager load in service
$meters = $property->meters()->with('readings')->get();

// Bad: N+1 queries
foreach ($property->meters as $meter) {
    $readings = $meter->readings; // Query per meter
}
```

### Caching Strategy

```php
// Cache expensive calculations
public function calculate(...): float
{
    $cacheKey = "hot water circulation:{$building->id}:{$month}";
    
    return Cache::remember($cacheKey, 3600, function () use (...) {
        return $this->calculator->calculate(...);
    });
}
```

## Security Considerations

### Authorization at Service Level

```php
public function generateInvoice(...): Invoice
{
    // Check authorization before processing
    if (!Gate::allows('generate-invoice', $tenant)) {
        throw new UnauthorizedException();
    }

    // Validate tenant ownership
    if (!$this->validateTenantOwnership($tenant)) {
        throw new TenantMismatchException();
    }

    // Proceed with business logic
}
```

### Rate Limiting

```php
public function calculate(...): ServiceResponse
{
    $executed = RateLimiter::attempt(
        "calc:{$userId}",
        $perMinute = 10,
        function () use (...) {
            return $this->performCalculation(...);
        }
    );

    if (!$executed) {
        return $this->error('Rate limit exceeded');
    }

    return $this->success($executed);
}
```

### Audit Trails

```php
public function finalizeInvoice(Invoice $invoice): Invoice
{
    $invoice->finalize();

    // Create audit record
    InvoiceGenerationAudit::create([
        'invoice_id' => $invoice->id,
        'user_id' => auth()->id(),
        'action' => 'finalized',
        'metadata' => [
            'total_amount' => $invoice->total_amount,
            'items_count' => $invoice->items->count(),
        ],
    ]);

    return $invoice;
}
```

## Migration Guide

### From Fat Controllers to Services

**Before (Fat Controller)**:
```php
public function store(Request $request)
{
    DB::transaction(function () use ($request) {
        $invoice = Invoice::create([...]);
        
        foreach ($meters as $meter) {
            $readings = $meter->readings()->get();
            $consumption = $readings->last()->value - $readings->first()->value;
            $tariff = Tariff::where(...)->first();
            $cost = $consumption * $tariff->rate;
            
            InvoiceItem::create([...]);
        }
        
        $invoice->update(['total' => $total]);
    });
}
```

**After (Thin Controller + Service)**:
```php
// Controller
public function store(GenerateInvoiceRequest $request)
{
    $invoice = $this->billingService->generateInvoice(
        $request->tenant(),
        $request->date('period_start'),
        $request->date('period_end')
    );

    return redirect()->route('invoices.show', $invoice);
}

// Service
public function generateInvoice(...): Invoice
{
    return $this->executeInTransaction(function () use (...) {
        // All business logic here
    });
}
```

## Checklist for New Services

- [ ] Extend BaseService
- [ ] Use dependency injection in constructor
- [ ] Wrap database operations in executeInTransaction()
- [ ] Add structured logging at key points
- [ ] Use value objects for complex data
- [ ] Return ServiceResponse or domain objects
- [ ] Add authorization checks
- [ ] Create audit records for sensitive operations
- [ ] Write unit tests with mocked dependencies
- [ ] Write feature tests with real database
- [ ] Document public methods with PHPDoc
- [ ] Add to service provider if needed

## Related Documentation

- [Service Layer Summary](SERVICE_LAYER_SUMMARY.md)
- [BillingService Implementation](../implementation/BILLING_SERVICE_V2_IMPLEMENTATION.md)
- [hot water circulationCalculatorService](../implementation/hot water circulation_CALCULATOR_IMPLEMENTATION.md)
- [Testing Guide](../guides/TESTING_GUIDE.md)

---

**Document Version**: 1.0.0  
**Last Updated**: 2025-11-25  
**Status**: Complete ✅  
**Next Review**: After production deployment
