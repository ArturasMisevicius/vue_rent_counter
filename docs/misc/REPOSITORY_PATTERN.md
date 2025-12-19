# Repository Pattern Implementation Guide

## Overview

This document provides a comprehensive guide to the Repository Pattern implementation in this Laravel application. The Repository Pattern provides an abstraction layer between your business logic and data access logic, offering benefits like testability, maintainability, and flexibility.

## Table of Contents

1. [When to Use Repositories](#when-to-use-repositories)
2. [Architecture Overview](#architecture-overview)
3. [Implementation Details](#implementation-details)
4. [Usage Examples](#usage-examples)
5. [Testing Strategy](#testing-strategy)
6. [Performance Considerations](#performance-considerations)
7. [Migration Strategy](#migration-strategy)
8. [Best Practices](#best-practices)
9. [Integration with Existing Systems](#integration-with-existing-systems)

## When to Use Repositories

### ✅ Repository Pattern Adds Value When:

- **Complex Business Logic**: Your application has complex domain logic that benefits from abstraction
- **Multiple Data Sources**: You need to switch between different data sources (database, API, cache)
- **Testing Requirements**: You need to mock data access for unit testing
- **Team Collaboration**: Large teams benefit from consistent data access patterns
- **Future Flexibility**: You anticipate changing data storage solutions
- **Query Reusability**: Common queries are used across multiple services/controllers

### ❌ Repository Pattern May Be Overkill When:

- **Simple CRUD Operations**: Basic create, read, update, delete operations without complex logic
- **Small Applications**: Applications with minimal data access requirements
- **Rapid Prototyping**: When speed of development is more important than architecture
- **Single Developer**: Small projects where consistency patterns aren't critical
- **Direct Eloquent Usage**: When Eloquent's built-in features are sufficient

## Architecture Overview

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Controllers   │───▶│   Repositories   │───▶│     Models      │
│                 │    │                  │    │                 │
│ - UserController│    │ - UserRepository │    │ - User Model    │
│ - API Endpoints │    │ - PropertyRepo   │    │ - Property      │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │    Criteria      │
                       │                  │
                       │ - ActiveUsers    │
                       │ - DateRange      │
                       │ - SearchTerm     │
                       └──────────────────┘
```

### Core Components

1. **Repository Interface** (`RepositoryInterface`): Defines the contract for all repositories
2. **Base Repository** (`BaseRepository`): Abstract implementation with common functionality
3. **Entity Repositories**: Specific implementations for each model (User, Property, Invoice)
4. **Criteria Pattern**: Reusable query specifications for complex filtering
5. **Service Provider**: Handles dependency injection bindings

## Implementation Details

### Base Repository Interface

```php
interface RepositoryInterface
{
    public function find(mixed $id): ?Model;
    public function findOrFail(mixed $id): Model;
    public function create(array $data): Model;
    public function update(mixed $id, array $data): Model;
    public function delete(mixed $id): bool;
    public function all(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    // ... additional methods
}
```

### Entity-Specific Repositories

Each entity has its own repository interface and implementation:

- `UserRepositoryInterface` / `UserRepository`
- `PropertyRepositoryInterface` / `PropertyRepository`
- `InvoiceRepositoryInterface` / `InvoiceRepository`

### Criteria Pattern

Reusable query specifications for complex filtering:

```php
// Usage example
$users = $userRepository
    ->where(new ActiveUsers())
    ->where(new DateRange('created_at', $startDate, $endDate))
    ->where(new SearchTerm('john', ['name', 'email']))
    ->get();
```

## Usage Examples

### Basic CRUD Operations

```php
class UserController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function store(StoreUserRequest $request)
    {
        $user = $this->userRepository->create($request->validated());
        return response()->json($user, 201);
    }

    public function show(int $id)
    {
        $user = $this->userRepository
            ->with(['property', 'subscription'])
            ->findOrFail($id);
        
        return response()->json($user);
    }
}
```

### Advanced Queries with Criteria

```php
// Find active admin users created in the last month
$users = $userRepository
    ->where(new ActiveUsers())
    ->where(new UsersByRole(UserRole::ADMIN))
    ->where(DateRange::createdBetween(now()->subMonth(), now()))
    ->orderBy('created_at', 'desc')
    ->paginate(20);
```

### Transaction Handling

```php
$result = $userRepository->transaction(function () use ($userData) {
    $user = $this->userRepository->create($userData);
    
    // Additional operations within transaction
    $this->subscriptionRepository->create([
        'user_id' => $user->id,
        'plan' => 'basic'
    ]);
    
    return $user;
});
```

### Bulk Operations

```php
// Bulk create users
$users = $userRepository->bulkCreate([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
]);

// Bulk update
$userRepository->bulkUpdate([1, 2, 3], ['is_active' => false]);

// Bulk delete
$userRepository->bulkDelete([1, 2, 3]);
```

## Testing Strategy

### Unit Testing Repositories

```php
class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository(new User());
    }

    /** @test */
    public function it_can_create_user(): void
    {
        $user = $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'role' => UserRole::ADMIN,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
    }
}
```

### Mocking Repositories for Service Tests

```php
class UserServiceTest extends TestCase
{
    /** @test */
    public function it_creates_user_with_subscription(): void
    {
        $mockRepository = Mockery::mock(UserRepositoryInterface::class);
        $mockRepository->shouldReceive('create')
            ->once()
            ->with(['name' => 'John'])
            ->andReturn(new User(['id' => 1, 'name' => 'John']));

        $service = new UserService($mockRepository);
        $result = $service->createUserWithSubscription(['name' => 'John']);

        $this->assertInstanceOf(User::class, $result);
    }
}
```

### In-Memory Repository for Testing

```php
class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];
    private int $nextId = 1;

    public function create(array $data): User
    {
        $user = new User($data);
        $user->id = $this->nextId++;
        $this->users[$user->id] = $user;
        return $user;
    }

    public function find(mixed $id): ?User
    {
        return $this->users[$id] ?? null;
    }
    
    // ... implement other methods
}
```

## Performance Considerations

### Query Optimization

1. **Eager Loading**: Use `with()` method to prevent N+1 queries
2. **Selective Loading**: Only load required relationships
3. **Chunking**: Use `chunk()` for processing large datasets
4. **Indexing**: Ensure database indexes support repository queries

### Caching Strategy

```php
// Implement caching decorator
class CacheableUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private CacheManager $cache
    ) {}

    public function find(mixed $id): ?User
    {
        return $this->cache->remember("user.{$id}", 3600, function () use ($id) {
            return $this->repository->find($id);
        });
    }
}
```

### Memory Management

- Use `chunk()` for large datasets
- Implement pagination for list operations
- Clear query builder state with `fresh()` method
- Monitor memory usage in bulk operations

## Migration Strategy

### Phase 1: Gradual Introduction

1. **Start with New Features**: Implement repositories for new functionality
2. **High-Value Areas**: Focus on complex business logic areas first
3. **Testing Critical Paths**: Begin with areas that need better test coverage

### Phase 2: Existing Code Migration

1. **Service Layer First**: Migrate services before controllers
2. **Controller Updates**: Update controllers to use repositories
3. **Remove Direct Model Usage**: Gradually eliminate direct Eloquent calls

### Phase 3: Optimization

1. **Performance Tuning**: Optimize queries and add caching
2. **Criteria Extraction**: Extract common queries into criteria classes
3. **Documentation**: Update team documentation and guidelines

### Migration Example

```php
// Before: Direct Eloquent usage
class UserService
{
    public function getActiveUsers()
    {
        return User::where('is_active', true)->get();
    }
}

// After: Repository usage
class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function getActiveUsers()
    {
        return $this->userRepository->findActiveUsers();
    }
}
```

## Best Practices

### Repository Design

1. **Single Responsibility**: Each repository handles one entity
2. **Interface Segregation**: Keep interfaces focused and minimal
3. **Dependency Injection**: Always inject repositories via constructor
4. **Error Handling**: Implement consistent error handling patterns
5. **Logging**: Add operation logging for debugging and monitoring

### Query Building

1. **Fluent Interface**: Support method chaining for complex queries
2. **Criteria Pattern**: Use criteria for reusable query logic
3. **Fresh Queries**: Reset query state between operations
4. **Relationship Loading**: Provide methods for eager loading

### Testing

1. **Mock Interfaces**: Mock repository interfaces, not implementations
2. **Integration Tests**: Test repository implementations with real database
3. **Performance Tests**: Test query performance with realistic data volumes
4. **Error Scenarios**: Test error handling and edge cases

## Integration with Existing Systems

### Multi-Tenancy Support

The repository pattern integrates seamlessly with the existing `TenantScope`:

```php
// TenantScope is automatically applied
$properties = $propertyRepository->all(); // Only current tenant's properties

// Bypass tenant scope when needed (superadmin)
$allProperties = $propertyRepository
    ->getModel()
    ->withoutTenantScope()
    ->get();
```

### Service Layer Integration

Repositories work alongside existing services:

```php
class BillingService extends BaseService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private PropertyRepositoryInterface $propertyRepository,
        private TariffResolver $tariffResolver
    ) {}

    public function generateInvoice(int $propertyId): Invoice
    {
        $property = $this->propertyRepository->findOrFail($propertyId);
        $tariff = $this->tariffResolver->resolve($property);
        
        return $this->invoiceRepository->create([
            'property_id' => $propertyId,
            'amount' => $tariff->calculateAmount(),
            'status' => InvoiceStatus::DRAFT,
        ]);
    }
}
```

### Form Request Integration

Repositories work with existing Form Requests:

```php
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'role' => ['required', Rule::in(UserRole::cases())],
        ];
    }
}

class UserController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        // Repository automatically handles validation
        $user = $this->userRepository->create($request->validated());
        return response()->json($user, 201);
    }
}
```

### Policy Integration

Repositories work with existing authorization policies:

```php
class UserController extends Controller
{
    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->userRepository->findOrFail($id);
        
        // Existing policy authorization
        $this->authorize('update', $user);
        
        $updatedUser = $this->userRepository->update($id, $request->validated());
        return response()->json($updatedUser);
    }
}
```

### Factory and Seeder Integration

Repositories work with existing factories and seeders:

```php
// In tests
$users = User::factory()->count(10)->create();
$activeUsers = $this->userRepository->findActiveUsers();

// In seeders
class UserSeeder extends Seeder
{
    public function run(UserRepositoryInterface $userRepository): void
    {
        $userRepository->bulkCreate([
            ['name' => 'Admin', 'email' => 'admin@example.com', 'role' => UserRole::ADMIN],
            ['name' => 'Manager', 'email' => 'manager@example.com', 'role' => UserRole::MANAGER],
        ]);
    }
}
```

## Trade-offs vs Direct Eloquent Usage

### Advantages of Repository Pattern

1. **Testability**: Easy to mock for unit testing
2. **Consistency**: Standardized data access patterns
3. **Flexibility**: Easy to switch data sources
4. **Reusability**: Common queries encapsulated in methods
5. **Team Collaboration**: Clear contracts for data access
6. **Business Logic**: Domain-specific methods (e.g., `findActiveUsers()`)

### Disadvantages of Repository Pattern

1. **Complexity**: Additional abstraction layer
2. **Development Time**: More code to write and maintain
3. **Learning Curve**: Team needs to understand the pattern
4. **Over-Engineering**: May be overkill for simple applications
5. **Performance**: Potential overhead from abstraction
6. **Eloquent Features**: May lose some Eloquent-specific features

### Decision Matrix

| Factor | Direct Eloquent | Repository Pattern |
|--------|----------------|-------------------|
| Development Speed | ✅ Fast | ❌ Slower |
| Testability | ❌ Harder | ✅ Easy |
| Maintainability | ❌ Harder | ✅ Better |
| Team Consistency | ❌ Variable | ✅ Consistent |
| Learning Curve | ✅ Low | ❌ Higher |
| Flexibility | ❌ Limited | ✅ High |

## Conclusion

The Repository Pattern is a powerful tool for organizing data access logic in Laravel applications. It provides significant benefits for testability, maintainability, and team collaboration, but comes with additional complexity.

**Use repositories when:**
- Your application has complex business logic
- You need comprehensive testing coverage
- You're working with a team
- You anticipate future changes to data storage

**Stick with direct Eloquent when:**
- Building simple CRUD applications
- Rapid prototyping is the priority
- Working solo on small projects
- Eloquent's features meet all your needs

The implementation provided in this codebase offers a solid foundation that can be gradually adopted and extended based on your specific needs.