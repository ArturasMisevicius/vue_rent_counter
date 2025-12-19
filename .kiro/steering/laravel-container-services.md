---
inclusion: always
---

# Laravel Container & Service Pattern

## Core Principles
- Leverage Laravel's service container for automatic dependency injection; avoid manual `app()` or `resolve()` calls in business logic.
- Register services in `AppServiceProvider::register()` using `bind()` for transient instances or `singleton()` for shared instances.
- Use constructor injection with readonly properties (PHP 8.4+) for all dependencies; the container resolves them automatically.
- Prefer interface-based contracts over concrete classes for flexibility and testability.
- Services should be stateless and focused on single responsibility; use composition over inheritance.
- Always type-hint dependencies in constructors for automatic resolution and better IDE support.

## Service Architecture

### Service Registration
- Register services in `app/Providers/AppServiceProvider.php` `register()` method.
- Use `singleton()` for stateful services (caches, connections, registries).
- Use `bind()` for stateless services that should be fresh per resolution.
- Bind interfaces to implementations for swappable dependencies.

```php
// Singleton - shared instance
$this->app->singleton(GitHubService::class, function ($app) {
    return new GitHubService(config('services.github.token'));
});

// Interface binding
$this->app->bind(
    PaymentProcessorInterface::class,
    StripePaymentProcessor::class
);

// Repository pattern
$this->app->bind(
    CompanyRepositoryInterface::class,
    EloquentCompanyRepository::class
);
```

### Constructor Injection
- Always use constructor injection with readonly properties (PHP 8.4+).
- Type-hint dependencies in constructor; container resolves automatically.
- Avoid service locator pattern (`app()`, `resolve()`) in business logic.

```php
// ✅ GOOD
class ContactMergeService
{
    public function __construct(
        private readonly ContactDuplicateDetectionService $duplicateDetection,
        private readonly AuditLogService $auditLog
    ) {}
}

// ❌ BAD
class ContactMergeService
{
    public function merge($primary, $duplicate)
    {
        $auditLog = app(AuditLogService::class); // Don't do this
    }
}
```

## Service Organization

### Directory Structure
- Organize services by domain in `app/Services/{Domain}/`.
- Keep related services together (e.g., `Contact/`, `Opportunities/`, `AI/`).
- Use descriptive names ending with `Service`.

### Naming Conventions
- Action services: `ContactMergeService`, `LeadConversionService`
- Query services: `OpportunityMetricsService`, `CustomerProfileService`
- Integration services: `GitHubService`, `CalendarSyncService`
- Repository services: `EloquentCompanyRepository`, `CachedUserRepository`

### Single Responsibility
- Each service should have one clear purpose.
- Split large services into focused, composable services.
- Use service composition over inheritance.

```php
// ✅ GOOD - Focused services
class ContactMergeService { /* handles merging */ }
class ContactDuplicateDetectionService { /* handles detection */ }
class ContactExportService { /* handles exports */ }

// ❌ BAD - God service
class ContactService {
    public function merge() {}
    public function detectDuplicates() {}
    public function export() {}
    public function import() {}
    // Too many responsibilities
}
```

## Filament v4.3+ Integration

### In Resource Actions
- Inject services via constructor or resolve from container in action callbacks.
- Use services for complex business logic; keep actions focused on UI concerns.
- Handle service exceptions and show user-friendly notifications.

```php
protected function getHeaderActions(): array
{
    return [
        Action::make('viewMetrics')
            ->label(__('app.actions.view_metrics'))
            ->modalContent(function () {
                $metricsService = app(OpportunityMetricsService::class);
                $metrics = $metricsService->getTeamMetrics(
                    Filament::getTenant()->id
                );
                
                return view('filament.modals.metrics', compact('metrics'));
            }),
    ];
}
```

### In Table Actions
- Use services for bulk operations, data transformations, and external integrations.
- Wrap service calls in transactions when modifying multiple records.
- Provide clear feedback via notifications.

```php
Action::make('merge')
    ->action(function (People $record, array $data) {
        $mergeService = app(ContactMergeService::class);
        $duplicate = People::find($data['duplicate_id']);
        
        try {
            $mergeService->merge($record, $duplicate, []);
            
            Notification::make()
                ->title(__('app.notifications.contacts_merged'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('app.notifications.merge_failed'))
                ->danger()
                ->send();
        }
    })
    ->requiresConfirmation()
```

### In Form Actions
- Use services for data validation, external API calls, and complex calculations.
- Keep form actions lightweight; delegate to services.

```php
TextInput::make('email')
    ->suffixAction(
        Action::make('verify')
            ->icon('heroicon-o-check-badge')
            ->action(function ($state, $set) {
                $verificationService = app(EmailVerificationService::class);
                $result = $verificationService->verify($state);
                
                $set('email_verified', $result->isValid);
            })
    )
```

## Testing Services

### Unit Tests
- Mock dependencies using Mockery or Pest's `mock()` helper.
- Test service logic in isolation from framework concerns.
- Use factories for model creation; avoid database when possible.

```php
it('merges contacts and transfers relationships', function () {
    $duplicateDetection = Mockery::mock(ContactDuplicateDetectionService::class);
    $auditLog = Mockery::mock(AuditLogService::class);
    
    $auditLog->shouldReceive('logMerge')->once();
    
    $service = new ContactMergeService($duplicateDetection, $auditLog);
    
    $primary = People::factory()->create();
    $duplicate = People::factory()->create();
    
    $result = $service->merge($primary, $duplicate, []);
    
    expect($result->id)->toBe($primary->id);
});
```

### Feature Tests
- Test services with real dependencies and database.
- Use `RefreshDatabase` trait for clean state.
- Assert side effects (database changes, events, notifications).

```php
it('calculates team metrics correctly', function () {
    $team = Team::factory()->create();
    
    Opportunity::factory()->create([
        'team_id' => $team->id,
        'value' => 10000,
        'status' => 'won',
    ]);
    
    $service = app(OpportunityMetricsService::class);
    $metrics = $service->getTeamMetrics($team->id);
    
    expect($metrics['total_value'])->toBe(10000.0);
    expect($metrics['win_rate'])->toBe(100.0);
});
```

### Integration Tests
- Test services with external dependencies using `Http::fake()`.
- Verify retry logic, error handling, and timeouts.
- Use `Http::preventStrayRequests()` to catch unexpected calls.

```php
it('syncs calendar events from external API', function () {
    Http::fake([
        'calendar.example.com/*' => Http::response([
            'events' => [/* ... */],
        ], 200),
    ]);
    
    $user = User::factory()->create();
    $service = app(CalendarSyncService::class);
    
    $result = $service->syncUserCalendar($user);
    
    expect($result['success'])->toBeTrue();
    Http::assertSentCount(1);
});
```

## Best Practices

### DO:
- ✅ Use constructor injection for all dependencies
- ✅ Register services in AppServiceProvider
- ✅ Use interfaces for swappable implementations
- ✅ Keep services focused on single responsibility
- ✅ Use readonly properties for immutability
- ✅ Handle errors gracefully with try-catch
- ✅ Log errors with context (user, action, data)
- ✅ Return typed results (DTOs, arrays, models)
- ✅ Use transactions for multi-step operations
- ✅ Cache expensive operations with appropriate TTL

### DON'T:
- ❌ Use service locator pattern in business logic
- ❌ Create god services with too many responsibilities
- ❌ Forget to register services in container
- ❌ Use concrete classes when interfaces would be better
- ❌ Ignore error handling and logging
- ❌ Skip testing service logic
- ❌ Mix presentation logic with business logic
- ❌ Use mutable state in services
- ❌ Hardcode configuration values
- ❌ Forget to clear caches when data changes

## Common Patterns

### Service with Configuration
```php
class EmailService
{
    public function __construct(
        private readonly string $fromAddress,
        private readonly string $fromName,
        private readonly bool $queueEmails = true
    ) {}
    
    public static function fromConfig(): self
    {
        return new self(
            config('mail.from.address'),
            config('mail.from.name'),
            config('mail.queue', true)
        );
    }
}

// Register
$this->app->singleton(EmailService::class, fn () => EmailService::fromConfig());
```

### Service with Events
```php
class OrderService
{
    public function __construct(
        private readonly EventDispatcher $events
    ) {}
    
    public function createOrder(array $data): Order
    {
        $order = Order::create($data);
        $this->events->dispatch(new OrderCreated($order));
        return $order;
    }
}
```

### Service with Caching
```php
class ProductCatalogService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600
    ) {}
    
    public function getProducts(int $categoryId): Collection
    {
        return $this->cache->remember(
            "products.category.{$categoryId}",
            $this->ttl,
            fn () => Product::where('category_id', $categoryId)->get()
        );
    }
}
```

### Service with HTTP Client
```php
class GitHubService
{
    public function __construct(
        private readonly string $token,
        private readonly int $cacheTtl = 3600
    ) {}
    
    public function getStarsCount(): int
    {
        return Cache::remember('github.stars', $this->cacheTtl, function () {
            $response = Http::github()->get('/repos/owner/repo');
            
            if ($response->failed()) {
                Log::warning('GitHub API failed', ['status' => $response->status()]);
                return 0;
            }
            
            return $response->json('stargazers_count', 0);
        });
    }
}
```

## Integration with Existing Patterns

### Works With
- **HTTP Client Macros**: Use `Http::external()` in services (see `laravel-http-client.md`)
- **Array Helpers**: Use `ArrayHelper` for data formatting (see `array-helpers.md`)
- **Date Scopes**: Services leverage model date scopes (see `laravel-date-scopes.md`)
- **Filament Actions**: Services power resource actions (see `filament-conventions.md`)
- **Queue Jobs**: Services called from queued jobs (see `testing-standards.md`)
- **Precognition**: Services validate form data (see `laravel-precognition.md`)

## Error Handling

### Exceptions
- Create custom exceptions for domain-specific errors.
- Catch and log exceptions with context.
- Return user-friendly error messages.

```php
class ContactMergeException extends \Exception
{
    public static function relationshipTransferFailed(People $contact, \Throwable $previous): self
    {
        return new self(
            "Failed to transfer relationships for contact {$contact->id}",
            0,
            $previous
        );
    }
}

// In service
try {
    $this->transferRelationships($from, $to);
} catch (\Exception $e) {
    Log::error('Relationship transfer failed', [
        'from_id' => $from->id,
        'to_id' => $to->id,
        'error' => $e->getMessage(),
    ]);
    
    throw ContactMergeException::relationshipTransferFailed($from, $e);
}
```

### Validation
- Use Form Requests for HTTP validation.
- Use `Validator::make()` for programmatic validation in services.
- Return validation errors as structured data.

```php
public function validateContactData(array $data): array
{
    $validator = Validator::make($data, [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:people,email',
    ]);
    
    if ($validator->fails()) {
        return ['valid' => false, 'errors' => $validator->errors()->toArray()];
    }
    
    return ['valid' => true, 'data' => $validator->validated()];
}
```

## Performance Considerations

### Caching
- Cache expensive queries and external API calls.
- Use appropriate TTL based on data volatility.
- Clear cache when underlying data changes.
- Use tagged caching for related data.

### Database Queries
- Eager load relationships to avoid N+1 queries.
- Use `select()` to limit columns when possible.
- Leverage database indexes for searchable/sortable fields.
- Use query scopes for reusable query logic.

### Background Processing
- Queue long-running operations (imports, exports, reports).
- Use job batching for bulk operations.
- Implement retry logic with exponential backoff.
- Monitor queue health and failed jobs.

## Quick Reference

### Service Registration Checklist
1. ✅ Create service class in `app/Services/{Domain}/`
2. ✅ Use constructor injection with readonly properties
3. ✅ Register in `AppServiceProvider::register()`
4. ✅ Choose appropriate binding type (bind/singleton/scoped)
5. ✅ Write unit tests with mocked dependencies
6. ✅ Write feature tests with real dependencies
7. ✅ Document public methods and return types
8. ✅ Handle errors gracefully with try-catch
9. ✅ Log errors with context
10. ✅ Cache expensive operations

### Common Service Types
- **Action Services**: `ContactMergeService`, `LeadConversionService` (transient)
- **Query Services**: `OpportunityMetricsService`, `ReportGeneratorService` (singleton with caching)
- **Integration Services**: `GitHubService`, `CalendarSyncService` (singleton)
- **Repository Services**: `EloquentContactRepository`, `CachedUserRepository` (singleton)
- **Utility Services**: `VCardService`, `ExportService` (transient)

### Filament Integration Points
- **Resource Actions**: Inject via method parameters in action callbacks
- **Table Actions**: Inject via method parameters in action callbacks
- **Form Actions**: Inject via method parameters in suffix/prefix actions
- **Widgets**: Inject via constructor for widget data
- **Pages**: Inject via constructor or method parameters
- **Relation Managers**: Inject via method parameters in actions

## Reference
- Documentation: `docs/laravel-service-container-integration.md` (comprehensive guide)
- Documentation: `docs/laravel-container-services.md` (original reference)
- Related: `laravel-conventions.md`, `filament-conventions.md`, `testing-standards.md`
- Laravel docs: https://laravel.com/docs/container
- Filament docs: https://filamentphp.com/docs/4.x/actions/overview

