# Testing Overview

## Testing Philosophy

CFlow follows a comprehensive testing strategy with 100% test coverage requirement using Pest PHP as the primary testing framework.

## Testing Pyramid

### Unit Tests (60%)
- Test individual classes and methods in isolation
- Fast execution (< 1ms per test)
- Mock external dependencies
- Focus on business logic and edge cases

### Integration Tests (30%)
- Test component interactions
- Database interactions with RefreshDatabase
- Service integrations
- API endpoint testing

### Feature Tests (10%)
- End-to-end user workflows
- Browser testing with Pest + Livewire
- Critical user journeys
- Cross-browser compatibility

## Testing Standards

### Mandatory Requirements
- **100% test coverage** - No exceptions
- **Pest PHP only** - No PHPUnit syntax
- **RefreshDatabase + DatabaseTransactions** - Use both together
- **Action class testing** - Every Action class must have tests
- **Value object testing** - Every Value Object must have unit tests

### Test Structure
```php
<?php

use App\Actions\User\CreateUserAction;
use App\Data\User\CreateUserData;

it('creates user with valid data', function () {
    // Arrange
    $data = CreateUserData::fake();
    
    // Act
    $user = app(CreateUserAction::class)->execute($data);
    
    // Assert
    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->email)->toBe($data->email);
});
```

## Test Categories

### 1. Unit Tests
**Location**: `tests/Unit/`

**Purpose**: Test individual components in isolation

**Examples**:
- Value Objects
- Services (with mocked dependencies)
- Helpers and Utilities
- Validation Rules

```php
it('validates email format', function () {
    expect(fn() => new Email('invalid-email'))
        ->toThrow(InvalidArgumentException::class);
});
```

### 2. Feature Tests
**Location**: `tests/Feature/`

**Purpose**: Test complete user workflows

**Examples**:
- User registration flow
- Invoice creation process
- Payment processing
- File uploads

```php
it('allows user to create invoice', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/invoices', [
            'tenant_id' => $tenant->id,
            'amount' => 100.00,
        ]);
    
    $response->assertCreated();
    $this->assertDatabaseHas('invoices', [
        'tenant_id' => $tenant->id,
        'amount' => 100.00,
    ]);
});
```

### 3. Filament Tests
**Location**: `tests/Feature/Filament/`

**Purpose**: Test admin panel functionality

**Pattern**: `actingAs($user)->postJson()` for API calls

```php
use function Pest\Livewire\livewire;

it('can list users in admin panel', function () {
    $users = User::factory()->count(3)->create();
    
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});
```

### 4. Browser Tests
**Location**: `tests/Browser/`

**Purpose**: End-to-end testing with real browser

```php
it('completes user registration flow', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', 'John Doe')
            ->type('email', 'john@example.com')
            ->type('password', 'password123')
            ->press('Register')
            ->assertPathIs('/dashboard');
    });
});
```

## Testing Patterns

### Database Testing
```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(RefreshDatabase::class, DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(); // Seed basic data if needed
});
```

### Factory Usage
```php
// Create with relationships
$invoice = Invoice::factory()
    ->for(Tenant::factory())
    ->has(InvoiceItem::factory()->count(3))
    ->create();

// Create with specific state
$user = User::factory()->admin()->create();
```

### Mocking External Services
```php
it('sends notification via external service', function () {
    $mock = Mockery::mock(NotificationService::class);
    $mock->shouldReceive('send')
        ->once()
        ->with(Mockery::type('string'))
        ->andReturn(true);
    
    app()->instance(NotificationService::class, $mock);
    
    // Test code that uses the service
});
```

### Testing Exceptions
```php
it('throws exception for invalid data', function () {
    expect(fn() => new Email('invalid'))
        ->toThrow(InvalidArgumentException::class, 'Invalid email format');
});
```

### Testing Events
```php
use Illuminate\Support\Facades\Event;

it('dispatches user created event', function () {
    Event::fake();
    
    User::factory()->create();
    
    Event::assertDispatched(UserCreated::class);
});
```

## Performance Testing

### Query Count Testing
```php
it('prevents N+1 queries when loading invoices', function () {
    Invoice::factory()->count(10)->create();
    
    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });
    
    Invoice::with('tenant')->get();
    
    expect($queries)->toBeLessThan(3); // Should be 2 queries max
});
```

### Memory Usage Testing
```php
it('handles large datasets efficiently', function () {
    $startMemory = memory_get_usage();
    
    // Process large dataset
    Invoice::factory()->count(1000)->create();
    
    $endMemory = memory_get_usage();
    $memoryUsed = $endMemory - $startMemory;
    
    expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024); // 50MB limit
});
```

## Test Data Management

### Factories
```php
// InvoiceFactory.php
public function definition(): array
{
    return [
        'number' => $this->faker->unique()->numerify('INV-####'),
        'amount' => $this->faker->randomFloat(2, 10, 1000),
        'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        'status' => InvoiceStatus::DRAFT,
    ];
}

public function paid(): static
{
    return $this->state(['status' => InvoiceStatus::PAID]);
}
```

### Seeders for Testing
```php
// TestSeeder.php
public function run(): void
{
    User::factory()->admin()->create([
        'email' => 'admin@test.com',
    ]);
    
    Tenant::factory()->count(5)->create();
}
```

## Continuous Integration

### Test Commands
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/UserTest.php

# Run tests matching pattern
php artisan test --filter="user creation"

# Parallel testing
php artisan test --parallel
```

### Coverage Requirements
- **Minimum**: 100% line coverage
- **Branches**: 90% branch coverage
- **Methods**: 100% method coverage

### CI Pipeline
```yaml
# .github/workflows/tests.yml
- name: Run Tests
  run: |
    php artisan test --coverage --min=100
    php artisan test:parallel
```

## Testing Tools

### Pest Plugins
- `pestphp/pest-plugin-laravel` - Laravel integration
- `pestphp/pest-plugin-livewire` - Livewire testing
- `pestphp/pest-plugin-faker` - Faker integration
- `defstudio/pest-plugin-laravel-expectations` - Enhanced assertions

### Assertions
```php
// Laravel Expectations
expect($response)->toBeOk();
expect($response)->toBeRedirect();
expect($model)->toExist();

// Custom expectations
expect($user)->toBeAdmin();
expect($invoice)->toBePaid();
```

## Best Practices

### DO:
- ✅ Write tests before implementation (TDD)
- ✅ Use descriptive test names
- ✅ Test edge cases and error conditions
- ✅ Mock external dependencies
- ✅ Use factories for test data
- ✅ Test one thing per test
- ✅ Keep tests fast and isolated

### DON'T:
- ❌ Skip testing because of time pressure
- ❌ Test implementation details
- ❌ Use real external services in tests
- ❌ Create interdependent tests
- ❌ Ignore test failures
- ❌ Write tests without assertions

## Troubleshooting

### Common Issues

**Tests are slow**
- Check for N+1 queries
- Use `RefreshDatabase` instead of `DatabaseMigrations`
- Mock external services

**Tests are flaky**
- Remove time-dependent assertions
- Use `Carbon::setTestNow()` for time testing
- Ensure proper test isolation

**Coverage is low**
- Check for untested branches
- Add tests for exception paths
- Test private methods through public interface

## Related Documentation

- [Development Standards](../development/standards.md)
- [Filament Testing](../filament/testing.md)
- [Performance Testing](../performance/testing.md)
- [Security Testing](../security/testing.md)