# Service Layer Architecture Guide

## Overview

This document provides comprehensive guidance on implementing a proper service layer architecture for the Universal Utility Management System. The architecture follows SOLID principles and provides clear separation of concerns between HTTP handling, business logic, and data persistence.

## Architecture Patterns

### 1. Service Layer Pattern
- **Services**: Orchestrate business operations and coordinate between multiple actions
- **Actions**: Handle single atomic operations with single responsibility
- **DTOs**: Transfer data between layers with type safety and validation
- **Interfaces**: Enable dependency injection and testing with mocks

### 2. Dependency Injection
- All services use constructor injection for dependencies
- Interfaces are bound to implementations in service providers
- Actions are registered as singletons for performance
- Mocking is enabled through interface bindings

### 3. Error Handling Strategy
- Standardized ServiceResponse objects for all operations
- Comprehensive exception handling with context preservation
- Structured logging with tenant and user context
- Performance monitoring and metrics collection

## Core Components

### Base Service Class

```php
abstract class BaseService
{
    // Transaction management with savepoints
    protected function executeInTransaction(callable $callback, ?string $savepointName = null): mixed

    // Standardized error handling with context
    protected function handleException(Throwable $e, array $context = [], bool $notify = false): void

    // Consistent response formatting
    protected function success(mixed $data = null, string $message = '', array $metadata = []): ServiceResponse
    protected function error(string $message, mixed $data = null, int $code = 0, array $metadata = []): ServiceResponse

    // Structured logging with context
    protected function log(string $level, string $message, array $context = []): void

    // Authorization helpers with audit trails
    protected function authorize(string $ability, mixed $model = null, bool $throwOnFailure = true): bool
    protected function validateTenantOwnership(object $model, bool $throwOnFailure = true): bool

    // Performance monitoring
    protected function withMetrics(string $operationName, callable $callback): mixed
    protected function recordMetric(string $operation, float $duration, array $metadata = []): void
}
```

### Service Response Object

```php
final readonly class ServiceResponse
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public string $message = '',
        public int $code = 0,
        public ?array $metadata = null
    ) {}

    public function isSuccess(): bool
    public function isFailure(): bool
    public function getDataOrFail(): mixed
    public function toArray(): array
}
```

## Service Classes

### 1. BillingService

**Responsibilities:**
- Invoice generation with consumption calculations
- Bulk billing operations with batch optimization
- Payment processing integration
- Billing period management
- Tariff application and rate calculations

**Key Methods:**
```php
public function generateInvoice(InvoiceGenerationDTO $dto): ServiceResponse
public function generateBulkInvoices(Collection $tenants, Carbon $periodStart, Carbon $periodEnd): ServiceResponse
public function finalizeInvoice(Invoice $invoice): ServiceResponse
public function calculateConsumption(Property $property, Carbon $periodStart, Carbon $periodEnd): ServiceResponse
public function getBillingHistory(Tenant $tenant, int $months = 12): ServiceResponse
```

### 2. UserManagementService

**Responsibilities:**
- User creation with role assignment
- Account activation and deactivation
- Role management with authorization
- Organization membership management
- User profile updates with validation
- Bulk user operations

**Key Methods:**
```php
public function createUser(CreateUserDTO $dto, bool $sendWelcomeEmail = true): ServiceResponse
public function updateUserProfile(User $user, array $data): ServiceResponse
public function changeUserRole(User $user, UserRole $newRole): ServiceResponse
public function activateUser(User $user): ServiceResponse
public function deactivateUser(User $user): ServiceResponse
public function getTenantUsers(int $tenantId, array $filters = []): ServiceResponse
public function bulkCreateUsers(array $usersData, bool $sendWelcomeEmails = false): ServiceResponse
```

### 3. ConsumptionCalculationService

**Responsibilities:**
- Multi-meter property consumption calculations
- Zone-based calculations
- Seasonal adjustments
- Estimation handling
- Historical pattern analysis

**Key Methods:**
```php
public function calculatePropertyConsumption(Property $property, Carbon $periodStart, Carbon $periodEnd): ServiceResponse
public function calculateMeterConsumption(Meter $meter, Carbon $periodStart, Carbon $periodEnd): ServiceResponse
public function getConsumptionHistory(Meter $meter, int $months = 12): ServiceResponse
```

## Action Classes

### When to Use Actions vs Services

**Actions (Single Responsibility):**
- Single atomic operation
- Reusable across services
- Stateless and focused
- Examples: CreateUserAction, ProcessPaymentAction, ValidateMeterReadingAction

**Services (Orchestration):**
- Complex business logic
- Multiple steps coordination
- Cross-domain operations
- Examples: BillingService, UserManagementService

### Action Examples

```php
// ProcessPaymentAction - Single responsibility: Process one payment
final class ProcessPaymentAction
{
    public function execute(PaymentProcessingDTO $dto): Payment
    {
        return DB::transaction(function () use ($dto) {
            $invoice = Invoice::findOrFail($dto->invoiceId);
            $this->validateInvoiceForPayment($invoice, $dto->amount);
            
            $payment = Payment::create([...]);
            $this->updateInvoiceStatus($invoice, $payment);
            
            return $payment;
        });
    }
}

// ValidateMeterReadingAction - Single responsibility: Validate one reading
final class ValidateMeterReadingAction
{
    public function execute(MeterReading $reading, bool $autoUpdate = true): array
    {
        $validationResult = $this->validationEngine->validateMeterReading($reading);
        
        if ($autoUpdate) {
            $this->updateReadingStatus($reading, $validationResult);
        }
        
        return $validationResult;
    }
}
```

## DTO Classes

### Purpose and Benefits
- Type safety between layers
- Input validation and sanitization
- Immutable data structures
- Factory methods for creation

### DTO Examples

```php
final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserRole $role,
        public int $tenantId,
        public ?int $propertyId = null,
        public ?int $parentUserId = null,
        public ?string $organizationName = null,
        public bool $isActive = true
    ) {}

    public static function fromRequest(Request $request): self
    public static function fromArray(array $data): self
    public function toArray(): array
}

final readonly class PaymentProcessingDTO
{
    public function __construct(
        public int $invoiceId,
        public float $amount,
        public PaymentMethod $paymentMethod,
        public string $paymentReference,
        public Carbon $paymentDate,
        public ?string $notes = null
    ) {
        $this->validate(); // Built-in validation
    }
}
```

## Controller Integration

### Thin Controllers Pattern

Controllers should only handle HTTP concerns:
- Request validation
- Service delegation
- Response formatting
- Route handling

```php
final class InvoiceController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly ProcessPaymentAction $processPaymentAction
    ) {}

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $dto = InvoiceGenerationDTO::fromRequest($request);
        $result = $this->billingService->generateInvoice($dto);

        if ($result->success) {
            return redirect()
                ->route('invoices.show', $result->data)
                ->with('success', $result->message);
        }

        return back()
            ->withInput()
            ->withErrors(['error' => $result->message]);
    }

    public function processPayment(ProcessPaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('processPayment', $invoice);

        try {
            $dto = PaymentProcessingDTO::fromRequest($request);
            $payment = $this->processPaymentAction->execute($dto);

            return back()->with('success', 'Payment processed successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Payment processing failed: ' . $e->getMessage()]);
        }
    }
}
```

## Service Provider Configuration

### Dependency Injection Setup

```php
final class ServiceLayerServiceProvider extends ServiceProvider
{
    public array $bindings = [
        BillingServiceInterface::class => BillingService::class,
        UserManagementServiceInterface::class => UserManagementService::class,
        ConsumptionCalculationServiceInterface::class => ConsumptionCalculationService::class,
    ];

    public array $singletons = [
        ProcessPaymentAction::class => ProcessPaymentAction::class,
        ValidateMeterReadingAction::class => ValidateMeterReadingAction::class,
        CreateUserAction::class => CreateUserAction::class,
    ];

    public function register(): void
    {
        // Interface bindings for testability
        $this->app->bind(BillingServiceInterface::class, function ($app) {
            return new BillingService(
                $app->make(GenerateInvoiceAction::class),
                $app->make(UniversalBillingCalculator::class),
                $app->make(MeterReadingService::class),
                $app->make(ConsumptionCalculationService::class)
            );
        });
    }
}
```

## Testing Strategy

### Unit Tests (Services)
- Mock all dependencies
- Test business logic in isolation
- Focus on service behavior
- No database interactions

```php
final class BillingServiceTest extends TestCase
{
    private BillingService $billingService;
    private Mockery\MockInterface $generateInvoiceAction;
    private Mockery\MockInterface $billingCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generateInvoiceAction = Mockery::mock(GenerateInvoiceAction::class);
        $this->billingCalculator = Mockery::mock(UniversalBillingCalculator::class);

        $this->billingService = new BillingService(
            $this->generateInvoiceAction,
            $this->billingCalculator,
            // ... other mocked dependencies
        );
    }

    /** @test */
    public function it_generates_invoice_successfully(): void
    {
        // Arrange
        $dto = new InvoiceGenerationDTO(...);
        $invoice = Invoice::factory()->make();

        $this->generateInvoiceAction
            ->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andReturn($invoice);

        // Act
        $result = $this->billingService->generateInvoice($dto);

        // Assert
        $this->assertTrue($result->success);
        $this->assertInstanceOf(Invoice::class, $result->data);
    }
}
```

### Feature Tests (End-to-End)
- Real services and database
- Complete workflows
- User interaction scenarios
- Integration testing

```php
final class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_create_invoice_through_web_interface(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('invoices.store'), [
            'tenant_id' => $this->tenant->id,
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);
    }
}
```

## Performance Considerations

### Transaction Management
- Use savepoints for nested transactions
- Automatic rollback on exceptions
- Performance monitoring for slow operations

### Caching Strategy
- Service-level caching for expensive operations
- Cache invalidation on data changes
- Multi-layer caching (application, database, HTTP)

### Batch Operations
- Chunk processing for large datasets
- Memory management and garbage collection
- Progress tracking and error handling

### Monitoring and Metrics
- Operation duration tracking
- Memory usage monitoring
- Query count optimization
- Error rate tracking

## Security Features

### Authorization
- Policy-based authorization checks
- Tenant isolation validation
- Role-based access control
- Audit trail logging

### Input Validation
- DTO-level validation
- Sanitization and filtering
- Type safety enforcement
- Bounds checking

### Error Handling
- Secure error messages
- Context preservation
- Audit logging
- Critical error notifications

## Migration Strategy

### Gradual Migration
1. Start with new features using the enhanced architecture
2. Refactor existing controllers to use services
3. Extract business logic into actions
4. Add comprehensive test coverage
5. Monitor performance and adjust

### Backward Compatibility
- Maintain existing API contracts
- Gradual deprecation of old patterns
- Feature flags for new implementations
- Comprehensive testing during migration

## Best Practices

### Service Design
- Single responsibility principle
- Dependency injection for all dependencies
- Interface-based contracts
- Comprehensive error handling

### Action Design
- Atomic operations only
- Stateless implementations
- Reusable across services
- Clear input/output contracts

### DTO Design
- Immutable objects
- Built-in validation
- Factory methods for creation
- Type safety enforcement

### Testing
- High test coverage (>90%)
- Unit tests for services
- Feature tests for workflows
- Performance tests for critical paths

### Monitoring
- Structured logging
- Performance metrics
- Error tracking
- Business metrics

This architecture provides a solid foundation for scalable, maintainable, and testable Laravel applications with clear separation of concerns and comprehensive business logic handling.