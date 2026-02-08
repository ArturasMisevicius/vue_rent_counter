---
title: OOP Best Practices and Clean Code Principles
inclusion: always
priority: high
---

# OOP Best Practices and Clean Code Principles

> Based on industry best practices and decades of programming experience, these principles help avoid "spaghetti code" and create maintainable, scalable applications.

## Core Philosophy

Good code is:
- **Readable**: Easy to understand and follow
- **Reusable**: Components can be used in multiple contexts
- **Extensible**: New features can be added without breaking existing code
- **Maintainable**: Easy to debug, test, and modify

## The Four Pillars of OOP

### 1. Abstraction (Абстракция)

**Principle**: Hide complex implementation details and focus on the essence - **what** the system does, not **how** it does it.

**Key Points**:
- Leave only the essential, remove unnecessary details
- Provide simple interfaces for complex operations
- Focus on the contract, not the implementation

**Implementation Tools**:
- Abstract classes and methods
- Interfaces (define contracts)
- Packages/modules (separate layers and visibility)
- Generics (generic types)

**Example Analogy**: Driving a car - you don't need to understand how the internal combustion engine works, you just press the gas pedal to move.

### 2. Encapsulation (Инкапсуляция)

**Principle**: Hide internal data and implementation details, providing access only through controlled methods.

**Key Points**:
- Protect data from unauthorized modification
- Make code more secure and modular
- Hide complexity, provide convenient interaction methods

**Implementation Tools**:
- Classes (unite data and methods in one "capsule")
- Access modifiers (private, public, protected)
- Getters and setters (controlled access with validation)
- Constructors (set correct initial state)
- Immutability (final fields, no setters)

**Example Analogy**: Coffee machine - you don't need to know how water heats or beans grind, you just press buttons on the panel.

### 3. Inheritance (Наследование)

**Principle**: Create new classes (children) based on existing ones (parents), borrowing their properties and behavior with the ability to add or modify.

**Key Points**:
- Transfer common properties and behavior from one class to another
- Avoid code duplication
- Create hierarchies of related classes

**Implementation Tools**:
- `extends` keyword for classes
- `implements` keyword for interfaces
- Abstract classes (base framework for concrete classes)
- `super()` for calling parent constructors
- Method overriding for specialization

**Important**: In Java, a class can have exactly one parent (extends), but can implement multiple interfaces (implements A, B, C).

**Example Analogy**: All cars have engines and wheels, but electric cars have batteries instead of fuel tanks.

### 4. Polymorphism (Полиморфизм)

**Principle**: Objects of different classes can use the same interface (methods with the same name) but implement them differently.

**Key Points**:
- One interface - different implementations
- Flexibility in handling different object types
- Reduces code duplication

**Implementation Tools**:
- Method overriding (override inherited methods)
- Method overloading (same name, different parameters)
- Interfaces and inheritance (common contract)
- Covariant return types
- Upcasting and downcasting

**Example Analogy**: "Power on" button works differently on TV (starts screen), lamp (lights up), phone (activates device).

## Advanced OOP Concepts

### Decomposition (Декомпозиция)

**Definition**: Breaking down a complex system, task, or object into simpler, independent, and manageable components.

**Purpose**:
- Reduce complexity
- Each component responsible for one task (Single Responsibility Principle)
- Improve code reusability
- Simplify testing and scaling

**Example**: Planning a vacation - break it into subtasks: booking tickets, choosing hotel, planning excursions, packing.

### Composition (Композиция)

**Definition**: "Has-a" relationship where one object contains other objects as integral parts. Lifecycle of parts depends on the whole.

**Key Points**:
- Strong dependency between parent and children
- If parent is destroyed, children are destroyed too
- Parts have no meaning outside their parent

**When to Use**:
- Model strong dependencies
- Avoid code duplication
- Prefer over inheritance for building complex objects from simple ones

**Example**: Car and its engine - engine doesn't exist outside the car.

### Aggregation (Агрегация)

**Definition**: "Has-a" relationship where one object uses others as parts, but parts can exist independently.

**Key Points**:
- Weak dependency between parent and children
- Children can exist without parent
- More flexible than composition

**When to Use**:
- Model weak dependencies
- Increase flexibility and reusability
- Systems with dynamic connections

**Example**: University and students - if university closes, students continue to exist.

## Code Reuse Mechanisms Cheat Sheet

- **Is-a** → Inheritance (A dog is an animal)
- **Has-a** → Composition (A dog has muscles)
- **Is-like-a** → Interface (A dog is like a runner - because it can run)

## Advanced OOP Techniques

### Dependency Injection (DI)

**Definition**: A technique where an object receives its dependencies from external sources rather than creating them itself.

**Benefits**:
- Loose coupling between classes
- Easier testing (can inject mocks)
- Better code reusability
- Clearer dependencies

**Laravel Example**:
```php
// ✅ GOOD: Dependencies injected via constructor
final readonly class OrderService
{
    public function __construct(
        private PaymentGateway $paymentGateway,
        private InventoryService $inventoryService,
        private NotificationService $notificationService,
    ) {}
    
    public function processOrder(Order $order): void
    {
        $this->paymentGateway->charge($order->getTotal());
        $this->inventoryService->reserve($order->getItems());
        $this->notificationService->sendConfirmation($order);
    }
}

// ❌ WRONG: Creating dependencies internally
final class OrderService
{
    private PaymentGateway $paymentGateway;
    
    public function __construct()
    {
        $this->paymentGateway = new StripeGateway(); // Tight coupling
    }
}
```

### Inversion of Control (IoC)

**Definition**: A design principle where the control flow of a program is inverted - instead of the programmer controlling the flow, the framework or container controls it.

**Laravel's IoC Container**:
```php
// Binding in service provider
$this->app->bind(PaymentGateway::class, StripeGateway::class);

// Automatic resolution
public function __construct(PaymentGateway $gateway)
{
    // Laravel automatically injects StripeGateway
}
```

### Method Chaining (Fluent Interface)

**Definition**: A technique where methods return `$this` to allow chaining multiple method calls.

**Example**:
```php
final class QueryBuilder
{
    private array $conditions = [];
    private ?int $limit = null;
    
    public function where(string $column, mixed $value): self
    {
        $this->conditions[] = [$column, $value];
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function get(): array
    {
        // Execute query
        return [];
    }
}

// Usage
$results = $builder
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->limit(10)
    ->get();
```

### Immutability

**Definition**: Objects whose state cannot be modified after creation.

**Benefits**:
- Thread-safe
- Easier to reason about
- Prevents bugs from unexpected state changes
- Better for caching

**Example**:
```php
// ✅ GOOD: Immutable value object
final readonly class Money
{
    public function __construct(
        private int $amount,
        private Currency $currency,
    ) {}
    
    public function add(Money $other): self
    {
        // Returns new instance instead of modifying
        return new self(
            $this->amount + $other->amount,
            $this->currency
        );
    }
}

// ❌ WRONG: Mutable object
final class Money
{
    public function __construct(
        private int $amount,
        private Currency $currency,
    ) {}
    
    public function add(Money $other): void
    {
        // Modifies internal state
        $this->amount += $other->amount;
    }
}
```

### Law of Demeter (Principle of Least Knowledge)

**Principle**: An object should only talk to its immediate friends, not to strangers.

**Rule**: Only call methods on:
- The object itself
- Objects passed as parameters
- Objects created locally
- Direct component objects

**Example**:
```php
// ❌ WRONG: Violates Law of Demeter
class OrderProcessor
{
    public function process(Order $order): void
    {
        $street = $order->getCustomer()->getAddress()->getStreet();
        // Too many chained calls
    }
}

// ✅ GOOD: Follows Law of Demeter
class OrderProcessor
{
    public function process(Order $order): void
    {
        $street = $order->getShippingStreet();
        // Order provides what we need
    }
}

final class Order
{
    public function getShippingStreet(): string
    {
        return $this->customer->getAddress()->getStreet();
    }
}
```

### Tell, Don't Ask

**Principle**: Tell objects what to do, don't ask them for data and then make decisions.

**Example**:
```php
// ❌ WRONG: Asking for data
class OrderProcessor
{
    public function process(Order $order): void
    {
        if ($order->getStatus() === 'pending' && $order->getTotal() > 0) {
            $order->setStatus('processing');
            // More logic
        }
    }
}

// ✅ GOOD: Telling what to do
class OrderProcessor
{
    public function process(Order $order): void
    {
        $order->startProcessing();
    }
}

final class Order
{
    public function startProcessing(): void
    {
        if ($this->status === 'pending' && $this->total > 0) {
            $this->status = 'processing';
            // More logic
        }
    }
}
```

## Laravel-Specific OOP Guidelines

### Class Design

```php
declare(strict_types=1);

// ✅ GOOD: Final class with promoted readonly properties
final readonly class CreateUserAction
{
    public function __construct(
        private UserRepository $repository,
        private HashService $hasher,
    ) {}
    
    public function execute(CreateUserData $data): User
    {
        $hashedPassword = $this->hasher->make($data->password);
        
        return $this->repository->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $hashedPassword,
        ]);
    }
}

// ❌ WRONG: Missing types, not final, mutable
class CreateUserAction
{
    public function __construct($repository, $hasher) {}
    
    public function execute($data)
    {
        return $this->repository->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $this->hasher->make($data->password),
        ]);
    }
}
```

### Abstraction in Laravel

```php
// ✅ GOOD: Interface defines contract
interface PaymentGateway
{
    public function charge(Money $amount, PaymentMethod $method): PaymentResult;
    public function refund(string $transactionId, Money $amount): RefundResult;
}

// Concrete implementations
final readonly class StripeGateway implements PaymentGateway
{
    public function charge(Money $amount, PaymentMethod $method): PaymentResult
    {
        // Stripe-specific implementation
    }
    
    public function refund(string $transactionId, Money $amount): RefundResult
    {
        // Stripe-specific implementation
    }
}

final readonly class PayPalGateway implements PaymentGateway
{
    public function charge(Money $amount, PaymentMethod $method): PaymentResult
    {
        // PayPal-specific implementation
    }
    
    public function refund(string $transactionId, Money $amount): RefundResult
    {
        // PayPal-specific implementation
    }
}
```

### Encapsulation in Laravel

```php
// ✅ GOOD: Proper encapsulation with validation
final class Money
{
    private function __construct(
        private readonly int $amount,
        private readonly Currency $currency,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }
    
    public static function fromCents(int $cents, Currency $currency): self
    {
        return new self($cents, $currency);
    }
    
    public function getAmount(): int
    {
        return $this->amount;
    }
    
    public function getCurrency(): Currency
    {
        return $this->currency;
    }
    
    public function add(Money $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException('Cannot add money with different currencies');
        }
        
        return new self($this->amount + $other->amount, $this->currency);
    }
}

// ❌ WRONG: Public properties, no validation
class Money
{
    public int $amount;
    public string $currency;
}
```

### Composition over Inheritance

```php
// ✅ GOOD: Use composition
final readonly class OrderProcessor
{
    public function __construct(
        private PaymentGateway $paymentGateway,
        private InventoryService $inventoryService,
        private NotificationService $notificationService,
    ) {}
    
    public function process(Order $order): ProcessResult
    {
        $paymentResult = $this->paymentGateway->charge(
            $order->getTotal(),
            $order->getPaymentMethod()
        );
        
        if ($paymentResult->isSuccessful()) {
            $this->inventoryService->reserve($order->getItems());
            $this->notificationService->sendOrderConfirmation($order);
        }
        
        return ProcessResult::fromPaymentResult($paymentResult);
    }
}

// ❌ WRONG: Deep inheritance hierarchy
class BaseProcessor {}
class PaymentProcessor extends BaseProcessor {}
class OrderProcessor extends PaymentProcessor {}
```

## Best Practices Summary

1. **Always use strict types**: `declare(strict_types=1);`
2. **Make classes final by default**: Only allow inheritance when explicitly designed for it
3. **Use readonly properties**: Immutability prevents bugs
4. **Prefer composition over inheritance**: More flexible and maintainable
5. **Use interfaces for contracts**: Define what, not how
6. **Keep classes small and focused**: Single Responsibility Principle
7. **Use value objects**: Encapsulate primitive types with validation
8. **Avoid public properties**: Always use getters/setters with validation
9. **Use type hints everywhere**: Parameters, return types, properties
10. **Follow SOLID principles**: Will be covered in detail in future articles

## Anti-Patterns to Avoid

### God Object
```php
// ❌ WRONG: One class does everything
class UserManager
{
    public function createUser() {}
    public function deleteUser() {}
    public function sendEmail() {}
    public function processPayment() {}
    public function generateReport() {}
    public function validateInput() {}
}

// ✅ GOOD: Separate responsibilities
final readonly class CreateUserAction {}
final readonly class DeleteUserAction {}
final readonly class EmailService {}
final readonly class PaymentService {}
final readonly class ReportGenerator {}
final readonly class InputValidator {}
```

### Anemic Domain Model
```php
// ❌ WRONG: Data class with no behavior
class Order
{
    public int $id;
    public float $total;
    public string $status;
}

class OrderService
{
    public function calculateTotal(Order $order): float {}
    public function validateOrder(Order $order): bool {}
}

// ✅ GOOD: Rich domain model
final class Order
{
    private function __construct(
        private readonly OrderId $id,
        private readonly OrderItems $items,
        private OrderStatus $status,
    ) {}
    
    public function calculateTotal(): Money
    {
        return $this->items->calculateTotal();
    }
    
    public function validate(): ValidationResult
    {
        // Validation logic here
    }
    
    public function markAsPaid(): void
    {
        $this->status = OrderStatus::Paid;
    }
}
```

## Testing OOP Code

```php
// ✅ GOOD: Test behavior, not implementation
final class CreateUserActionTest extends TestCase
{
    public function test_creates_user_with_hashed_password(): void
    {
        $repository = Mockery::mock(UserRepository::class);
        $hasher = Mockery::mock(HashService::class);
        
        $hasher->shouldReceive('make')
            ->once()
            ->with('password123')
            ->andReturn('hashed_password');
            
        $repository->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'hashed_password',
            ])
            ->andReturn(new User());
            
        $action = new CreateUserAction($repository, $hasher);
        
        $result = $action->execute(new CreateUserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
        ));
        
        $this->assertInstanceOf(User::class, $result);
    }
}
```

## SOLID Principles

### Single Responsibility Principle (SRP)

**Principle**: A class should have only one reason to change - it should have only one job or responsibility.

**Key Points**:
- Each class should focus on a single task
- Changes to one responsibility shouldn't affect others
- Easier to understand, test, and maintain

```php
// ❌ WRONG: Multiple responsibilities
class User
{
    public function save(): void { /* database logic */ }
    public function sendEmail(): void { /* email logic */ }
    public function generateReport(): void { /* reporting logic */ }
}

// ✅ GOOD: Single responsibility per class
final readonly class UserRepository
{
    public function save(User $user): void { /* database logic */ }
}

final readonly class UserNotifier
{
    public function sendEmail(User $user, string $message): void { /* email logic */ }
}

final readonly class UserReportGenerator
{
    public function generate(User $user): Report { /* reporting logic */ }
}
```

### Open/Closed Principle (OCP)

**Principle**: Software entities should be open for extension but closed for modification.

**Key Points**:
- Add new functionality without changing existing code
- Use abstraction and polymorphism
- Reduces risk of breaking existing functionality

```php
// ❌ WRONG: Must modify class to add new payment methods
class PaymentProcessor
{
    public function process(string $type, float $amount): void
    {
        if ($type === 'credit_card') {
            // Credit card logic
        } elseif ($type === 'paypal') {
            // PayPal logic
        }
        // Adding new payment method requires modifying this class
    }
}

// ✅ GOOD: Open for extension, closed for modification
interface PaymentMethod
{
    public function process(Money $amount): PaymentResult;
}

final readonly class CreditCardPayment implements PaymentMethod
{
    public function process(Money $amount): PaymentResult
    {
        // Credit card logic
    }
}

final readonly class PayPalPayment implements PaymentMethod
{
    public function process(Money $amount): PaymentResult
    {
        // PayPal logic
    }
}

final readonly class PaymentProcessor
{
    public function process(PaymentMethod $method, Money $amount): PaymentResult
    {
        return $method->process($amount);
    }
}
```

### Liskov Substitution Principle (LSP)

**Principle**: Objects of a superclass should be replaceable with objects of its subclasses without breaking the application.

**Key Points**:
- Subclasses must honor the contract of the parent class
- Don't strengthen preconditions or weaken postconditions
- Maintain behavioral compatibility

```php
// ❌ WRONG: Violates LSP - Square changes behavior of Rectangle
class Rectangle
{
    protected int $width;
    protected int $height;
    
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }
    
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }
    
    public function getArea(): int
    {
        return $this->width * $this->height;
    }
}

class Square extends Rectangle
{
    public function setWidth(int $width): void
    {
        $this->width = $width;
        $this->height = $width; // Violates LSP
    }
    
    public function setHeight(int $height): void
    {
        $this->width = $height; // Violates LSP
        $this->height = $height;
    }
}

// ✅ GOOD: Use composition instead
interface Shape
{
    public function getArea(): int;
}

final readonly class Rectangle implements Shape
{
    public function __construct(
        private int $width,
        private int $height,
    ) {}
    
    public function getArea(): int
    {
        return $this->width * $this->height;
    }
}

final readonly class Square implements Shape
{
    public function __construct(
        private int $side,
    ) {}
    
    public function getArea(): int
    {
        return $this->side * $this->side;
    }
}
```

### Interface Segregation Principle (ISP)

**Principle**: Clients should not be forced to depend on interfaces they don't use.

**Key Points**:
- Create specific, focused interfaces
- Avoid "fat" interfaces with many methods
- Classes implement only what they need

```php
// ❌ WRONG: Fat interface forces unnecessary implementations
interface Worker
{
    public function work(): void;
    public function eat(): void;
    public function sleep(): void;
}

class Robot implements Worker
{
    public function work(): void { /* work logic */ }
    public function eat(): void { /* robots don't eat! */ }
    public function sleep(): void { /* robots don't sleep! */ }
}

// ✅ GOOD: Segregated interfaces
interface Workable
{
    public function work(): void;
}

interface Eatable
{
    public function eat(): void;
}

interface Sleepable
{
    public function sleep(): void;
}

final readonly class Human implements Workable, Eatable, Sleepable
{
    public function work(): void { /* work logic */ }
    public function eat(): void { /* eat logic */ }
    public function sleep(): void { /* sleep logic */ }
}

final readonly class Robot implements Workable
{
    public function work(): void { /* work logic */ }
}
```

### Dependency Inversion Principle (DIP)

**Principle**: High-level modules should not depend on low-level modules. Both should depend on abstractions.

**Key Points**:
- Depend on interfaces, not concrete classes
- Invert the dependency direction
- Increases flexibility and testability

```php
// ❌ WRONG: High-level class depends on low-level class
final class MySQLDatabase
{
    public function save(array $data): void { /* MySQL logic */ }
}

final class UserService
{
    private MySQLDatabase $database;
    
    public function __construct()
    {
        $this->database = new MySQLDatabase(); // Tight coupling
    }
    
    public function saveUser(array $userData): void
    {
        $this->database->save($userData);
    }
}

// ✅ GOOD: Both depend on abstraction
interface DatabaseInterface
{
    public function save(array $data): void;
}

final readonly class MySQLDatabase implements DatabaseInterface
{
    public function save(array $data): void { /* MySQL logic */ }
}

final readonly class PostgreSQLDatabase implements DatabaseInterface
{
    public function save(array $data): void { /* PostgreSQL logic */ }
}

final readonly class UserService
{
    public function __construct(
        private DatabaseInterface $database,
    ) {}
    
    public function saveUser(array $userData): void
    {
        $this->database->save($userData);
    }
}
```

## Additional Code Quality Principles

### DRY (Don't Repeat Yourself)

**Principle**: Every piece of knowledge should have a single, unambiguous representation in the system.

**Key Points**:
- Avoid code duplication
- Extract common logic into reusable components
- Changes should be made in one place only

```php
// ❌ WRONG: Duplicated validation logic
class UserController
{
    public function create(Request $request): Response
    {
        if (strlen($request->email) < 5 || !str_contains($request->email, '@')) {
            throw new ValidationException('Invalid email');
        }
        // Create user
    }
    
    public function update(Request $request): Response
    {
        if (strlen($request->email) < 5 || !str_contains($request->email, '@')) {
            throw new ValidationException('Invalid email');
        }
        // Update user
    }
}

// ✅ GOOD: Extracted validation logic
final readonly class EmailValidator
{
    public function validate(string $email): void
    {
        if (strlen($email) < 5 || !str_contains($email, '@')) {
            throw new ValidationException('Invalid email');
        }
    }
}

final readonly class UserController
{
    public function __construct(
        private EmailValidator $emailValidator,
    ) {}
    
    public function create(Request $request): Response
    {
        $this->emailValidator->validate($request->email);
        // Create user
    }
    
    public function update(Request $request): Response
    {
        $this->emailValidator->validate($request->email);
        // Update user
    }
}
```

### KISS (Keep It Simple, Stupid)

**Principle**: Simplicity should be a key goal in design, and unnecessary complexity should be avoided.

**Key Points**:
- Write simple, straightforward code
- Avoid over-engineering
- Prefer clarity over cleverness

```php
// ❌ WRONG: Over-complicated
class NumberProcessor
{
    public function process(int $number): int
    {
        return array_reduce(
            range(1, $number),
            fn($carry, $item) => $carry + $item,
            0
        );
    }
}

// ✅ GOOD: Simple and clear
final readonly class NumberProcessor
{
    public function process(int $number): int
    {
        return ($number * ($number + 1)) / 2;
    }
}
```

### YAGNI (You Aren't Gonna Need It)

**Principle**: Don't add functionality until it's necessary.

**Key Points**:
- Implement only what's needed now
- Avoid speculative features
- Reduces complexity and maintenance burden

```php
// ❌ WRONG: Implementing features that might be needed
class User
{
    private string $name;
    private string $email;
    private ?string $phone;
    private ?string $address;
    private ?string $city;
    private ?string $country;
    private ?string $zipCode;
    private ?string $facebookId;
    private ?string $twitterId;
    private ?string $linkedinId;
    // ... many more fields that aren't used yet
}

// ✅ GOOD: Only what's needed now
final class User
{
    public function __construct(
        private readonly string $name,
        private readonly string $email,
    ) {}
}
```

## Common Design Patterns

### Creational Patterns

#### Factory Pattern

**Purpose**: Create objects without specifying the exact class to create.

```php
interface PaymentGateway
{
    public function charge(Money $amount): PaymentResult;
}

final readonly class StripeGateway implements PaymentGateway
{
    public function charge(Money $amount): PaymentResult { /* ... */ }
}

final readonly class PayPalGateway implements PaymentGateway
{
    public function charge(Money $amount): PaymentResult { /* ... */ }
}

final readonly class PaymentGatewayFactory
{
    public function create(string $type): PaymentGateway
    {
        return match ($type) {
            'stripe' => new StripeGateway(),
            'paypal' => new PayPalGateway(),
            default => throw new InvalidArgumentException("Unknown gateway: {$type}"),
        };
    }
}
```

#### Builder Pattern

**Purpose**: Construct complex objects step by step.

```php
final class QueryBuilder
{
    private string $table = '';
    private array $columns = ['*'];
    private array $where = [];
    private ?int $limit = null;
    
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }
    
    public function select(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }
    
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->where[] = [$column, $operator, $value];
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function build(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . $this->table;
        
        if (!empty($this->where)) {
            $conditions = array_map(
                fn($w) => "{$w[0]} {$w[1]} ?",
                $this->where
            );
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        
        return $sql;
    }
}

// Usage
$query = (new QueryBuilder())
    ->table('users')
    ->select(['id', 'name', 'email'])
    ->where('status', '=', 'active')
    ->limit(10)
    ->build();
```

#### Singleton Pattern

**Purpose**: Ensure a class has only one instance.

**Note**: Use sparingly in Laravel - prefer dependency injection.

```php
final class DatabaseConnection
{
    private static ?self $instance = null;
    private PDO $connection;
    
    private function __construct()
    {
        $this->connection = new PDO(/* connection details */);
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    
    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new Exception('Cannot unserialize singleton');
    }
}
```

### Structural Patterns

#### Adapter Pattern

**Purpose**: Allow incompatible interfaces to work together.

```php
// External library with incompatible interface
class ExternalPaymentService
{
    public function makePayment(float $amount, string $currency): bool
    {
        // External implementation
        return true;
    }
}

// Our application's interface
interface PaymentGateway
{
    public function charge(Money $amount): PaymentResult;
}

// Adapter to make external service compatible
final readonly class ExternalPaymentAdapter implements PaymentGateway
{
    public function __construct(
        private ExternalPaymentService $externalService,
    ) {}
    
    public function charge(Money $amount): PaymentResult
    {
        $success = $this->externalService->makePayment(
            $amount->getAmount() / 100,
            $amount->getCurrency()->value
        );
        
        return new PaymentResult($success);
    }
}
```

#### Decorator Pattern

**Purpose**: Add new functionality to objects dynamically.

```php
interface Coffee
{
    public function getCost(): float;
    public function getDescription(): string;
}

final readonly class SimpleCoffee implements Coffee
{
    public function getCost(): float
    {
        return 2.0;
    }
    
    public function getDescription(): string
    {
        return 'Simple coffee';
    }
}

abstract readonly class CoffeeDecorator implements Coffee
{
    public function __construct(
        protected Coffee $coffee,
    ) {}
}

final readonly class MilkDecorator extends CoffeeDecorator
{
    public function getCost(): float
    {
        return $this->coffee->getCost() + 0.5;
    }
    
    public function getDescription(): string
    {
        return $this->coffee->getDescription() . ', milk';
    }
}

final readonly class SugarDecorator extends CoffeeDecorator
{
    public function getCost(): float
    {
        return $this->coffee->getCost() + 0.2;
    }
    
    public function getDescription(): string
    {
        return $this->coffee->getDescription() . ', sugar';
    }
}

// Usage
$coffee = new SimpleCoffee();
$coffee = new MilkDecorator($coffee);
$coffee = new SugarDecorator($coffee);

echo $coffee->getDescription(); // "Simple coffee, milk, sugar"
echo $coffee->getCost(); // 2.7
```

### Behavioral Patterns

#### Strategy Pattern

**Purpose**: Define a family of algorithms and make them interchangeable.

```php
interface SortingStrategy
{
    public function sort(array $data): array;
}

final readonly class BubbleSortStrategy implements SortingStrategy
{
    public function sort(array $data): array
    {
        // Bubble sort implementation
        return $data;
    }
}

final readonly class QuickSortStrategy implements SortingStrategy
{
    public function sort(array $data): array
    {
        // Quick sort implementation
        return $data;
    }
}

final readonly class Sorter
{
    public function __construct(
        private SortingStrategy $strategy,
    ) {}
    
    public function sort(array $data): array
    {
        return $this->strategy->sort($data);
    }
}

// Usage
$sorter = new Sorter(new QuickSortStrategy());
$sorted = $sorter->sort([3, 1, 4, 1, 5, 9, 2, 6]);
```

#### Observer Pattern

**Purpose**: Define a one-to-many dependency between objects.

```php
interface Observer
{
    public function update(string $event, mixed $data): void;
}

interface Subject
{
    public function attach(Observer $observer): void;
    public function detach(Observer $observer): void;
    public function notify(string $event, mixed $data): void;
}

final class User implements Subject
{
    private array $observers = [];
    private string $name;
    
    public function attach(Observer $observer): void
    {
        $this->observers[] = $observer;
    }
    
    public function detach(Observer $observer): void
    {
        $this->observers = array_filter(
            $this->observers,
            fn($obs) => $obs !== $observer
        );
    }
    
    public function notify(string $event, mixed $data): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($event, $data);
        }
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->notify('name_changed', $name);
    }
}

final readonly class EmailNotifier implements Observer
{
    public function update(string $event, mixed $data): void
    {
        if ($event === 'name_changed') {
            // Send email notification
            echo "Email sent: Name changed to {$data}\n";
        }
    }
}

final readonly class Logger implements Observer
{
    public function update(string $event, mixed $data): void
    {
        echo "Log: {$event} - {$data}\n";
    }
}

// Usage
$user = new User();
$user->attach(new EmailNotifier());
$user->attach(new Logger());
$user->setName('John Doe');
```

#### Command Pattern

**Purpose**: Encapsulate a request as an object.

```php
interface Command
{
    public function execute(): void;
}

final readonly class CreateUserCommand implements Command
{
    public function __construct(
        private UserRepository $repository,
        private string $name,
        private string $email,
    ) {}
    
    public function execute(): void
    {
        $this->repository->create([
            'name' => $this->name,
            'email' => $this->email,
        ]);
    }
}

final readonly class SendEmailCommand implements Command
{
    public function __construct(
        private EmailService $emailService,
        private string $to,
        private string $subject,
        private string $body,
    ) {}
    
    public function execute(): void
    {
        $this->emailService->send($this->to, $this->subject, $this->body);
    }
}

final class CommandInvoker
{
    private array $commands = [];
    
    public function addCommand(Command $command): void
    {
        $this->commands[] = $command;
    }
    
    public function executeAll(): void
    {
        foreach ($this->commands as $command) {
            $command->execute();
        }
        $this->commands = [];
    }
}

// Usage
$invoker = new CommandInvoker();
$invoker->addCommand(new CreateUserCommand($repository, 'John', 'john@example.com'));
$invoker->addCommand(new SendEmailCommand($emailService, 'john@example.com', 'Welcome', 'Welcome to our platform!'));
$invoker->executeAll();
```

## Laravel-Specific Pattern Implementations

### Repository Pattern

```php
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function all(): Collection;
    public function create(array $data): User;
    public function update(int $id, array $data): User;
    public function delete(int $id): bool;
}

final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }
    
    public function all(): Collection
    {
        return User::all();
    }
    
    public function create(array $data): User
    {
        return User::create($data);
    }
    
    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }
    
    public function delete(int $id): bool
    {
        return User::destroy($id) > 0;
    }
}
```

### Service Layer Pattern

```php
final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EmailService $emailService,
        private HashService $hashService,
    ) {}
    
    public function registerUser(array $data): User
    {
        $data['password'] = $this->hashService->make($data['password']);
        
        $user = $this->userRepository->create($data);
        
        $this->emailService->sendWelcomeEmail($user);
        
        return $user;
    }
    
    public function updateProfile(int $userId, array $data): User
    {
        return $this->userRepository->update($userId, $data);
    }
}
```

### Action Pattern (Laravel-specific)

```php
final readonly class CreateUserAction
{
    public function __construct(
        private UserRepository $repository,
        private HashService $hasher,
        private EventDispatcher $events,
    ) {}
    
    public function execute(CreateUserData $data): User
    {
        $user = $this->repository->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $this->hasher->make($data->password),
        ]);
        
        $this->events->dispatch(new UserCreated($user));
        
        return $user;
    }
}
```

## Best Practices Checklist

### Code Quality
- [ ] All classes use `declare(strict_types=1);`
- [ ] Classes are final by default
- [ ] Properties are readonly when possible
- [ ] All methods have explicit return types
- [ ] All parameters have type hints
- [ ] No public properties (use getters/setters)
- [ ] Single Responsibility Principle followed
- [ ] Composition preferred over inheritance

### SOLID Compliance
- [ ] Each class has one reason to change (SRP)
- [ ] Classes are open for extension, closed for modification (OCP)
- [ ] Subclasses can replace parent classes (LSP)
- [ ] Interfaces are specific and focused (ISP)
- [ ] Dependencies are on abstractions, not concretions (DIP)

### Code Simplicity
- [ ] No code duplication (DRY)
- [ ] Simple, straightforward implementations (KISS)
- [ ] Only necessary features implemented (YAGNI)
- [ ] Clear, descriptive naming
- [ ] Minimal complexity

### Testing
- [ ] Unit tests for all business logic
- [ ] Integration tests for workflows
- [ ] Mocks used for external dependencies
- [ ] Test behavior, not implementation
- [ ] High test coverage

## Practical Application Guidelines

### When to Use Each Principle

**Abstraction**:
- Use when you need to hide complex implementation details
- When creating APIs or libraries that others will use
- When you want to provide a simple interface to complex systems

**Encapsulation**:
- Always use for protecting data integrity
- When you need to control how data is accessed or modified
- When implementing validation logic

**Inheritance**:
- Use sparingly - prefer composition
- Only when there's a true "is-a" relationship
- When you need to share common behavior across related classes

**Polymorphism**:
- When you need different implementations of the same interface
- When building plugin systems or extensible architectures
- When you want to write code that works with multiple types

### Common Mistakes to Avoid

1. **Over-abstraction**: Don't create abstractions until you need them (YAGNI)
2. **Deep inheritance hierarchies**: Prefer composition over inheritance
3. **God objects**: Keep classes focused on a single responsibility
4. **Premature optimization**: Write clear code first, optimize later
5. **Ignoring SOLID**: These principles exist for a reason - follow them
6. **Public properties**: Always use getters/setters for controlled access
7. **Missing type declarations**: Always declare types explicitly
8. **Mutable value objects**: Value objects should be immutable
9. **Anemic domain models**: Put behavior where it belongs
10. **Not testing**: Write tests for all business logic

### Code Review Checklist

When reviewing code, check for:

- [ ] Proper use of type declarations
- [ ] Final classes by default
- [ ] Readonly properties where appropriate
- [ ] Single Responsibility Principle adherence
- [ ] Proper abstraction levels
- [ ] No code duplication
- [ ] Clear, descriptive naming
- [ ] Proper error handling
- [ ] Adequate test coverage
- [ ] Documentation for complex logic

### Refactoring Strategies

**From Procedural to OOP**:
1. Identify related functions and data
2. Group them into classes
3. Add proper encapsulation
4. Extract interfaces for contracts
5. Apply SOLID principles
6. Add tests

**From Inheritance to Composition**:
1. Identify the "has-a" relationships
2. Extract dependencies as constructor parameters
3. Remove inheritance
4. Use interfaces for contracts
5. Test the refactored code

**From God Object to Focused Classes**:
1. Identify distinct responsibilities
2. Extract each responsibility into its own class
3. Use dependency injection to connect them
4. Apply Single Responsibility Principle
5. Verify with tests

## Advanced OOP Concepts (Part 3)

### Cohesion and Coupling

**Cohesion** measures how closely related the responsibilities of a single module are.

**High Cohesion** (Good):
```php
// ✅ GOOD: High cohesion - all methods relate to user authentication
final readonly class UserAuthenticator
{
    public function authenticate(string $email, string $password): bool
    {
        // Authentication logic
    }
    
    public function validateCredentials(string $email, string $password): bool
    {
        // Validation logic
    }
    
    public function hashPassword(string $password): string
    {
        // Password hashing
    }
}
```

**Low Cohesion** (Bad):
```php
// ❌ WRONG: Low cohesion - unrelated responsibilities
final class UserManager
{
    public function authenticate(string $email, string $password): bool { }
    public function sendEmail(string $to, string $subject): void { }
    public function generateReport(): array { }
    public function processPayment(float $amount): bool { }
}
```

**Coupling** measures the degree of interdependence between modules.

**Loose Coupling** (Good):
```php
// ✅ GOOD: Loose coupling through interfaces
interface PaymentGateway
{
    public function charge(Money $amount): PaymentResult;
}

final readonly class OrderProcessor
{
    public function __construct(
        private PaymentGateway $gateway,
    ) {}
    
    public function process(Order $order): void
    {
        $this->gateway->charge($order->getTotal());
    }
}
```

**Tight Coupling** (Bad):
```php
// ❌ WRONG: Tight coupling to concrete implementation
final readonly class OrderProcessor
{
    public function __construct(
        private StripeGateway $gateway, // Coupled to specific implementation
    ) {}
}
```

### Favor Composition Over Inheritance

**Problem with Deep Inheritance**:
```php
// ❌ WRONG: Deep inheritance hierarchy
class Animal { }
class Mammal extends Animal { }
class Dog extends Mammal { }
class Labrador extends Dog { }
class ServiceLabrador extends Labrador { }
```

**Solution with Composition**:
```php
// ✅ GOOD: Composition-based design
interface Movable
{
    public function move(): void;
}

interface Trainable
{
    public function train(string $skill): void;
}

final readonly class Dog implements Movable, Trainable
{
    public function __construct(
        private MovementBehavior $movement,
        private TrainingBehavior $training,
    ) {}
    
    public function move(): void
    {
        $this->movement->execute();
    }
    
    public function train(string $skill): void
    {
        $this->training->learn($skill);
    }
}
```

### Object Calisthenics

Object Calisthenics are programming exercises that help write better OOP code:

1. **One level of indentation per method**
2. **Don't use the ELSE keyword**
3. **Wrap all primitives and strings**
4. **First class collections**
5. **One dot per line**
6. **Don't abbreviate**
7. **Keep all entities small**
8. **No classes with more than two instance variables**
9. **No getters/setters/properties**

**Example - No ELSE keyword**:
```php
// ❌ WRONG: Using else
public function getDiscount(User $user): float
{
    if ($user->isPremium()) {
        return 0.20;
    } else {
        return 0.10;
    }
}

// ✅ GOOD: Early return, no else
public function getDiscount(User $user): float
{
    if ($user->isPremium()) {
        return 0.20;
    }
    
    return 0.10;
}
```

**Example - Wrap primitives**:
```php
// ❌ WRONG: Primitive obsession
public function createUser(string $email, string $password): User
{
    // Email and password are just strings
}

// ✅ GOOD: Wrapped in value objects
public function createUser(Email $email, Password $password): User
{
    // Email and Password are value objects with validation
}

final readonly class Email
{
    public function __construct(
        private string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
}
```

### Null Object Pattern

Instead of returning null, return a special object that implements the expected interface:

```php
// ✅ GOOD: Null Object Pattern
interface User
{
    public function getName(): string;
    public function getEmail(): string;
    public function isGuest(): bool;
}

final readonly class RegisteredUser implements User
{
    public function __construct(
        private string $name,
        private string $email,
    ) {}
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function isGuest(): bool
    {
        return false;
    }
}

final readonly class GuestUser implements User
{
    public function getName(): string
    {
        return 'Guest';
    }
    
    public function getEmail(): string
    {
        return '';
    }
    
    public function isGuest(): bool
    {
        return true;
    }
}

// Usage
final readonly class UserService
{
    public function getCurrentUser(): User
    {
        $user = Auth::user();
        
        return $user ?? new GuestUser();
    }
}
```

### Repository Pattern (Advanced)

```php
// ✅ GOOD: Repository with specification pattern
interface Specification
{
    public function isSatisfiedBy(mixed $candidate): bool;
    public function toSqlClauses(): array;
}

final readonly class ActiveUserSpecification implements Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate instanceof User && $candidate->isActive();
    }
    
    public function toSqlClauses(): array
    {
        return ['active' => true];
    }
}

interface UserRepository
{
    public function find(int $id): ?User;
    public function findBySpecification(Specification $spec): Collection;
    public function save(User $user): void;
}

final readonly class EloquentUserRepository implements UserRepository
{
    public function findBySpecification(Specification $spec): Collection
    {
        $query = User::query();
        
        foreach ($spec->toSqlClauses() as $column => $value) {
            $query->where($column, $value);
        }
        
        return $query->get();
    }
}
```

### Event Sourcing Pattern

```php
// ✅ GOOD: Event sourcing for audit trail
interface DomainEvent
{
    public function occurredOn(): DateTimeImmutable;
    public function toArray(): array;
}

final readonly class UserRegistered implements DomainEvent
{
    public function __construct(
        private int $userId,
        private string $email,
        private DateTimeImmutable $occurredOn,
    ) {}
    
    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
    
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}

final class User
{
    private array $events = [];
    
    public function register(string $email, string $password): void
    {
        // Registration logic
        
        $this->recordEvent(new UserRegistered(
            $this->id,
            $email,
            new DateTimeImmutable()
        ));
    }
    
    private function recordEvent(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
    
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
}
```

### CQRS (Command Query Responsibility Segregation)

```php
// ✅ GOOD: Separate read and write models
// Command (Write)
final readonly class CreateUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {}
    
    public function handle(CreateUserCommand $command): void
    {
        $user = User::create([
            'name' => $command->name,
            'email' => $command->email,
            'password' => Hash::make($command->password),
        ]);
        
        $this->repository->save($user);
    }
}

// Query (Read)
final readonly class GetUserQuery
{
    public function __construct(
        public int $userId,
    ) {}
}

final readonly class GetUserHandler
{
    public function __construct(
        private UserReadRepository $repository,
    ) {}
    
    public function handle(GetUserQuery $query): UserDTO
    {
        return $this->repository->findById($query->userId);
    }
}
```

### Practical Anti-Patterns to Avoid

#### 1. God Object
```php
// ❌ WRONG: God object that does everything
class Application
{
    public function handleRequest() { }
    public function authenticateUser() { }
    public function processPayment() { }
    public function sendEmail() { }
    public function generateReport() { }
    public function validateInput() { }
    public function logActivity() { }
}
```

#### 2. Anemic Domain Model
```php
// ❌ WRONG: Anemic model with no behavior
class Order
{
    public int $id;
    public float $total;
    public string $status;
}

class OrderService
{
    public function calculateTotal(Order $order): float { }
    public function validateOrder(Order $order): bool { }
    public function processOrder(Order $order): void { }
}

// ✅ GOOD: Rich domain model
final class Order
{
    private function __construct(
        private readonly OrderId $id,
        private readonly OrderItems $items,
        private OrderStatus $status,
    ) {}
    
    public function calculateTotal(): Money
    {
        return $this->items->calculateTotal();
    }
    
    public function validate(): ValidationResult
    {
        // Validation logic
    }
    
    public function process(): void
    {
        if (!$this->validate()->isValid()) {
            throw new InvalidOrderException();
        }
        
        $this->status = OrderStatus::Processing;
    }
}
```

#### 3. Circular Dependencies
```php
// ❌ WRONG: Circular dependency
class UserService
{
    public function __construct(
        private OrderService $orderService,
    ) {}
}

class OrderService
{
    public function __construct(
        private UserService $userService, // Circular!
    ) {}
}

// ✅ GOOD: Break circular dependency with events
class UserService
{
    public function createUser(array $data): User
    {
        $user = User::create($data);
        
        event(new UserCreated($user));
        
        return $user;
    }
}

class OrderService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}
    
    public function handleUserCreated(UserCreated $event): void
    {
        // Handle the event
    }
}
```

### Real-World Laravel Example: E-Commerce Order Processing

```php
// Value Objects
final readonly class Money
{
    public function __construct(
        private int $amount,
        private Currency $currency,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }
    
    public function add(Money $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }
        
        return new self($this->amount + $other->amount, $this->currency);
    }
}

// Domain Events
final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        private OrderId $orderId,
        private Money $total,
        private DateTimeImmutable $occurredOn,
    ) {}
}

// Aggregate Root
final class Order
{
    private array $events = [];
    
    private function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        private OrderItems $items,
        private OrderStatus $status,
    ) {}
    
    public static function place(
        OrderId $id,
        CustomerId $customerId,
        OrderItems $items,
    ): self {
        $order = new self($id, $customerId, $items, OrderStatus::Pending);
        
        $order->recordEvent(new OrderPlaced(
            $id,
            $items->calculateTotal(),
            new DateTimeImmutable()
        ));
        
        return $order;
    }
    
    public function confirm(): void
    {
        if (!$this->status->canTransitionTo(OrderStatus::Confirmed)) {
            throw new InvalidOrderStateException();
        }
        
        $this->status = OrderStatus::Confirmed;
        $this->recordEvent(new OrderConfirmed($this->id, new DateTimeImmutable()));
    }
    
    private function recordEvent(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
    
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
}

// Application Service
final readonly class PlaceOrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EventDispatcher $eventDispatcher,
    ) {}
    
    public function execute(PlaceOrderCommand $command): OrderId
    {
        $order = Order::place(
            OrderId::generate(),
            new CustomerId($command->customerId),
            OrderItems::fromArray($command->items),
        );
        
        $this->orderRepository->save($order);
        
        foreach ($order->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
        
        return $order->getId();
    }
}
```

## Advanced OOP Principles from Industry Practice

### Principle of Least Astonishment (POLA)

**Principle**: Code should behave in a way that least surprises the user.

**Key Points**:
- Method names should clearly indicate what they do
- Return types should match expectations
- Side effects should be minimal and documented
- Consistent naming conventions across the codebase

```php
// ❌ WRONG: Surprising behavior
final class UserService
{
    public function getUser(int $id): User
    {
        $user = User::find($id);
        $user->last_accessed = now(); // Unexpected side effect!
        $user->save();
        return $user;
    }
}

// ✅ GOOD: Predictable behavior
final readonly class UserService
{
    public function getUser(int $id): ?User
    {
        return User::find($id);
    }
    
    public function recordUserAccess(User $user): void
    {
        $user->last_accessed = now();
        $user->save();
    }
}
```

### Hollywood Principle

**Principle**: "Don't call us, we'll call you" - Let the framework call your code, not the other way around.

**Key Points**:
- Use dependency injection instead of service locators
- Leverage framework hooks and lifecycle methods
- Implement interfaces that the framework expects
- Use event-driven architecture

```php
// ✅ GOOD: Hollywood Principle in Laravel
final readonly class OrderCreatedListener
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}
    
    public function handle(OrderCreated $event): void
    {
        // Framework calls this when event is dispatched
        $this->notificationService->sendOrderConfirmation($event->order);
    }
}

// Register in EventServiceProvider
protected $listen = [
    OrderCreated::class => [
        OrderCreatedListener::class,
    ],
];
```

### Separation of Concerns (SoC)

**Principle**: Different concerns should be handled by different modules.

**Key Points**:
- Business logic separate from presentation
- Data access separate from business logic
- Infrastructure concerns separate from domain logic
- Cross-cutting concerns (logging, caching) handled separately

```php
// ✅ GOOD: Proper separation of concerns
// Domain Layer
final readonly class Order
{
    public function calculateTotal(): Money
    {
        return $this->items->sum(fn($item) => $item->getPrice());
    }
}

// Application Layer
final readonly class CreateOrderAction
{
    public function __construct(
        private OrderRepository $repository,
        private PaymentGateway $gateway,
    ) {}
    
    public function execute(CreateOrderData $data): Order
    {
        $order = Order::create($data);
        $this->gateway->charge($order->calculateTotal());
        return $this->repository->save($order);
    }
}

// Presentation Layer
final class OrderController
{
    public function store(CreateOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $order = $action->execute($request->validated());
        return response()->json(new OrderResource($order), 201);
    }
}
```

### Contract-First Design

**Principle**: Define interfaces before implementations.

**Key Points**:
- Start with interface definitions
- Multiple implementations can satisfy the same contract
- Easier to test with mocks
- Clearer API boundaries

```php
// ✅ GOOD: Contract-first approach
// 1. Define the contract
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): bool;
    public function delete(string $key): bool;
    public function has(string $key): bool;
}

// 2. Implement for different backends
final readonly class RedisCache implements CacheInterface
{
    public function get(string $key): mixed
    {
        return Redis::get($key);
    }
    
    public function set(string $key, mixed $value, int $ttl): bool
    {
        return Redis::setex($key, $ttl, $value);
    }
    
    public function delete(string $key): bool
    {
        return Redis::del($key) > 0;
    }
    
    public function has(string $key): bool
    {
        return Redis::exists($key) > 0;
    }
}

final readonly class FileCache implements CacheInterface
{
    public function get(string $key): mixed
    {
        // File-based implementation
    }
    
    public function set(string $key, mixed $value, int $ttl): bool
    {
        // File-based implementation
    }
    
    public function delete(string $key): bool
    {
        // File-based implementation
    }
    
    public function has(string $key): bool
    {
        // File-based implementation
    }
}
```

### Defensive Programming

**Principle**: Assume that errors will occur and code defensively.

**Key Points**:
- Validate all inputs
- Check preconditions
- Handle edge cases
- Fail fast with clear error messages
- Use assertions for invariants

```php
// ✅ GOOD: Defensive programming
final readonly class MoneyTransferService
{
    public function transfer(Account $from, Account $to, Money $amount): TransferResult
    {
        // Validate inputs
        if ($amount->isNegative()) {
            throw new InvalidArgumentException('Transfer amount must be positive');
        }
        
        if ($from->equals($to)) {
            throw new InvalidArgumentException('Cannot transfer to the same account');
        }
        
        // Check preconditions
        if (!$from->hasBalance($amount)) {
            return TransferResult::insufficientFunds();
        }
        
        if ($to->isClosed()) {
            return TransferResult::accountClosed();
        }
        
        // Perform transfer
        DB::transaction(function () use ($from, $to, $amount) {
            $from->debit($amount);
            $to->credit($amount);
        });
        
        return TransferResult::success();
    }
}
```

### Fail Fast Principle

**Principle**: Detect and report errors as early as possible.

**Key Points**:
- Validate at the boundaries
- Don't propagate invalid state
- Throw exceptions for programming errors
- Return result objects for business errors

```php
// ✅ GOOD: Fail fast
final readonly class Email
{
    public function __construct(
        private string $value,
    ) {
        // Fail immediately if invalid
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
        
        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Email too long');
        }
    }
    
    public function getValue(): string
    {
        // No need to validate here - guaranteed valid
        return $this->value;
    }
}

// Usage
try {
    $email = new Email('invalid-email'); // Fails immediately
} catch (InvalidArgumentException $e) {
    // Handle invalid email
}
```

### Command-Query Separation (CQS)

**Principle**: Methods should either change state (commands) or return data (queries), but not both.

**Key Points**:
- Commands return void or status
- Queries return data without side effects
- Makes code more predictable
- Easier to reason about

```php
// ✅ GOOD: Command-Query Separation
final class ShoppingCart
{
    private array $items = [];
    
    // Command - changes state, returns void
    public function addItem(Product $product, int $quantity): void
    {
        $this->items[] = new CartItem($product, $quantity);
    }
    
    // Query - returns data, no side effects
    public function getTotal(): Money
    {
        return array_reduce(
            $this->items,
            fn($total, $item) => $total->add($item->getSubtotal()),
            Money::zero()
        );
    }
    
    // Query - returns data, no side effects
    public function getItemCount(): int
    {
        return count($this->items);
    }
}

// ❌ WRONG: Mixing command and query
final class ShoppingCart
{
    public function addItemAndGetTotal(Product $product, int $quantity): Money
    {
        $this->items[] = new CartItem($product, $quantity); // Command
        return $this->getTotal(); // Query
    }
}
```

### Explicit Dependencies Principle

**Principle**: All dependencies should be explicitly declared.

**Key Points**:
- Use constructor injection
- Avoid service locators
- Make dependencies visible
- Easier to test and understand

```php
// ✅ GOOD: Explicit dependencies
final readonly class OrderService
{
    public function __construct(
        private OrderRepository $repository,
        private PaymentGateway $gateway,
        private EmailService $emailService,
        private LoggerInterface $logger,
    ) {}
    
    public function createOrder(CreateOrderData $data): Order
    {
        // All dependencies are explicit and injected
        $this->logger->info('Creating order', ['data' => $data]);
        
        $order = Order::create($data);
        $this->gateway->charge($order->getTotal());
        $this->repository->save($order);
        $this->emailService->sendOrderConfirmation($order);
        
        return $order;
    }
}

// ❌ WRONG: Hidden dependencies
final class OrderService
{
    public function createOrder(CreateOrderData $data): Order
    {
        // Hidden dependency on global state
        $repository = app(OrderRepository::class);
        $gateway = app(PaymentGateway::class);
        
        // Dependencies not visible in constructor
        $order = Order::create($data);
        $gateway->charge($order->getTotal());
        $repository->save($order);
        
        return $order;
    }
}
```

### Stable Dependencies Principle

**Principle**: Depend on things that are more stable than you are.

**Key Points**:
- Depend on abstractions (interfaces) not concretions
- Core domain should not depend on infrastructure
- High-level modules should not depend on low-level modules
- Stable packages should not depend on volatile packages

```php
// ✅ GOOD: Stable dependencies
// Domain layer (most stable)
interface OrderRepository
{
    public function save(Order $order): void;
    public function find(OrderId $id): ?Order;
}

// Application layer (depends on stable domain)
final readonly class CreateOrderAction
{
    public function __construct(
        private OrderRepository $repository, // Depends on interface
    ) {}
    
    public function execute(CreateOrderData $data): Order
    {
        $order = Order::create($data);
        $this->repository->save($order);
        return $order;
    }
}

// Infrastructure layer (least stable, depends on stable abstractions)
final readonly class EloquentOrderRepository implements OrderRepository
{
    public function save(Order $order): void
    {
        // Eloquent-specific implementation
    }
    
    public function find(OrderId $id): ?Order
    {
        // Eloquent-specific implementation
    }
}
```

### Hexagonal Architecture (Ports and Adapters)

**Principle**: Isolate core business logic from external concerns.

**Key Points**:
- Core domain in the center
- Ports define interfaces
- Adapters implement interfaces
- External dependencies at the edges

```php
// ✅ GOOD: Hexagonal architecture
// Core Domain (center)
final readonly class Order
{
    public function place(): void
    {
        // Pure business logic
        $this->status = OrderStatus::Placed;
        $this->placedAt = now();
    }
}

// Port (interface)
interface PaymentGateway
{
    public function charge(Money $amount, PaymentMethod $method): PaymentResult;
}

// Adapter (implementation)
final readonly class StripePaymentAdapter implements PaymentGateway
{
    public function __construct(
        private StripeClient $client,
    ) {}
    
    public function charge(Money $amount, PaymentMethod $method): PaymentResult
    {
        // Stripe-specific implementation
        $result = $this->client->charges->create([
            'amount' => $amount->getCents(),
            'currency' => $amount->getCurrency()->value,
            'source' => $method->getToken(),
        ]);
        
        return PaymentResult::fromStripeCharge($result);
    }
}

// Application Service (uses port)
final readonly class PlaceOrderAction
{
    public function __construct(
        private PaymentGateway $gateway, // Depends on port, not adapter
        private OrderRepository $repository,
    ) {}
    
    public function execute(PlaceOrderCommand $command): Order
    {
        $order = Order::create($command->items);
        $this->gateway->charge($order->getTotal(), $command->paymentMethod);
        $order->place();
        $this->repository->save($order);
        
        return $order;
    }
}
```

### Screaming Architecture

**Principle**: The architecture should scream the intent of the system.

**Key Points**:
- Directory structure reflects business domains
- Framework details are secondary
- Use cases are visible
- Business rules are prominent

```php
// ✅ GOOD: Screaming architecture directory structure
app/
├── Domain/
│   ├── Order/
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── OrderStatus.php
│   │   └── OrderRepository.php
│   ├── Payment/
│   │   ├── Payment.php
│   │   ├── PaymentMethod.php
│   │   └── PaymentGateway.php
│   └── Customer/
│       ├── Customer.php
│       ├── CustomerType.php
│       └── CustomerRepository.php
├── Application/
│   ├── Order/
│   │   ├── PlaceOrderAction.php
│   │   ├── CancelOrderAction.php
│   │   └── PlaceOrderCommand.php
│   └── Payment/
│       ├── ProcessPaymentAction.php
│       └── RefundPaymentAction.php
└── Infrastructure/
    ├── Persistence/
    │   ├── EloquentOrderRepository.php
    │   └── EloquentCustomerRepository.php
    └── Payment/
        ├── StripePaymentGateway.php
        └── PayPalPaymentGateway.php
```

## References

- Original articles: 
  - https://habr.com/ru/articles/964836/ (OOP Fundamentals)
  - https://habr.com/ru/articles/957618/ (SOLID Principles Deep Dive)
  - https://habr.com/ru/companies/ruvds/articles/959198/ (SOLID & Patterns)
  - https://habr.com/ru/companies/timeweb/articles/951476/ (Advanced OOP Concepts - Part 3)
  - https://habr.com/ru/articles/954878/ (Advanced OOP Principles and Patterns)
- Laravel Documentation: https://laravel.com/docs
- PHP The Right Way: https://phptherightway.com/
- SOLID Principles: https://en.wikipedia.org/wiki/SOLID
- Design Patterns: Gang of Four (GoF) Book
- Clean Code: Robert C. Martin
- Refactoring: Improving the Design of Existing Code - Martin Fowler
- Domain-Driven Design: Eric Evans
- Implementing Domain-Driven Design: Vaughn Vernon
- Clean Architecture: Robert C. Martin
- Patterns of Enterprise Application Architecture: Martin Fowler
