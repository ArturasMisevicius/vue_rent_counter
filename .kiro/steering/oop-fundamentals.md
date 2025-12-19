---
title: "OOP Fundamentals and Best Practices"
category: "Architecture & Design"
priority: "high"
inclusion: "always"
tags: ["oop", "solid", "design-patterns", "architecture", "clean-code"]
---

# OOP Fundamentals and Best Practices

> **TL;DR:** Comprehensive guide to Object-Oriented Programming principles, SOLID principles, design patterns, and advanced OOP concepts for building maintainable, scalable Laravel applications.

## 📋 TABLE OF CONTENTS

1. [Core OOP Principles](#core-oop-principles)
2. [SOLID Principles](#solid-principles)
3. [Advanced OOP Concepts](#advanced-oop-concepts)
4. [Design Patterns](#design-patterns)
5. [Laravel-Specific OOP Guidelines](#laravel-specific-oop-guidelines)
6. [Code Quality Principles](#code-quality-principles)
7. [Anti-Patterns to Avoid](#anti-patterns-to-avoid)
8. [Testing OOP Code](#testing-oop-code)

## 🎯 CORE OOP PRINCIPLES

### The Four Pillars of OOP

#### 1. Abstraction (Абстракция)

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

**Laravel Example**:
```php
// ✅ GOOD: Abstract payment gateway interface
interface PaymentGateway
{
    public function charge(Money $amount, PaymentMethod $method): PaymentResult;
    public function refund(string $transactionId, Money $amount): RefundResult;
}

// Concrete implementations hide complexity
final readonly class StripeGateway implements PaymentGateway
{
    public function charge(Money $amount, PaymentMethod $method): PaymentResult
    {
        // Complex Stripe API integration hidden from consumers
        return $this->stripeClient->charges->create([
            'amount' => $amount->getCents(),
            'currency' => $amount->getCurrency()->value,
            'source' => $method->getToken(),
        ]);
    }
}
```

#### 2. Encapsulation (Инкапсуляция)

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

**Laravel Example**:
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
    
    public function add(Money $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException('Cannot add different currencies');
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

#### 3. Inheritance (Наследование)

**Principle**: Create new classes (children) based on existing ones (parents), borrowing their properties and behavior with the ability to add or modify.

**Key Points**:
- Transfer common properties and behavior from one class to another
- Avoid code duplication
- Create hierarchies of related classes

**Implementation Tools**:
- `extends` keyword for classes
- `implements` keyword for interfaces
- Abstract classes (base framework for concrete classes)
- Method overriding for specialization

**Important**: Prefer composition over inheritance in most cases.

**Laravel Example**:
```php
// ✅ GOOD: Interface-based approach (preferred)
interface Notifiable
{
    public function notify(Notification $notification): void;
}

final readonly class EmailNotifier implements Notifiable
{
    public function notify(Notification $notification): void
    {
        // Email-specific implementation
    }
}

final readonly class SmsNotifier implements Notifiable
{
    public function notify(Notification $notification): void
    {
        // SMS-specific implementation
    }
}

// ⚠️ ACCEPTABLE: Inheritance when truly needed
abstract class BaseController
{
    protected function respondWithSuccess(mixed $data): JsonResponse
    {
        return response()->json(['data' => $data]);
    }
}

final class UserController extends BaseController
{
    public function index(): JsonResponse
    {
        return $this->respondWithSuccess(User::all());
    }
}
```

#### 4. Polymorphism (Полиморфизм)

**Principle**: Objects of different classes can use the same interface but implement them differently.

**Key Points**:
- One interface - different implementations
- Flexibility in handling different object types
- Reduces code duplication

**Implementation Tools**:
- Method overriding (override inherited methods)
- Method overloading (same name, different parameters)
- Interfaces and inheritance (common contract)
- Covariant return types

**Example Analogy**: "Power on" button works differently on TV (starts screen), lamp (lights up), phone (activates device).

**Laravel Example**:
```php
// ✅ GOOD: Polymorphic behavior through interfaces
interface Exportable
{
    public function export(): string;
}

final readonly class PdfExporter implements Exportable
{
    public function export(): string
    {
        return 'PDF content';
    }
}

final readonly class CsvExporter implements Exportable
{
    public function export(): string
    {
        return 'CSV content';
    }
}

final readonly class ExportService
{
    public function process(Exportable $exporter): void
    {
        $content = $exporter->export(); // Polymorphic call
        Storage::put('export.txt', $content);
    }
}
```

## 🏗️ ADVANCED OOP CONCEPTS

### Decomposition (Декомпозиция)

**Definition**: Breaking down a complex system into simpler, independent, and manageable components.

**Purpose**:
- Reduce complexity
- Each component responsible for one task (Single Responsibility Principle)
- Improve code reusability
- Simplify testing and scaling

**Laravel Example**:
```php
// ✅ GOOD: Decomposed order processing
final readonly class ProcessOrderAction
{
    public function __construct(
        private ValidateOrderAction $validateOrder,
        private ChargePaymentAction $chargePayment,
        private ReserveInventoryAction $reserveInventory,
        private SendConfirmationAction $sendConfirmation,
    ) {}
    
    public function execute(Order $order): OrderResult
    {
        $this->validateOrder->execute($order);
        $payment = $this->chargePayment->execute($order);
        $this->reserveInventory->execute($order);
        $this->sendConfirmation->execute($order);
        
        return OrderResult::success($payment);
    }
}
```

### Composition (Композиция)

**Definition**: "Has-a" relationship where one object contains other objects as integral parts.

**Key Points**:
- Strong dependency between parent and children
- If parent is destroyed, children are destroyed too
- Parts have no meaning outside their parent

**Laravel Example**:
```php
// ✅ GOOD: Composition - Order owns OrderItems
final class Order
{
    private OrderItems $items; // Composition
    
    public function __construct(OrderItems $items)
    {
        $this->items = $items;
    }
    
    public function calculateTotal(): Money
    {
        return $this->items->calculateTotal();
    }
}

final class OrderItems
{
    /** @var array<OrderItem> */
    private array $items = [];
    
    public function add(OrderItem $item): void
    {
        $this->items[] = $item;
    }
    
    public function calculateTotal(): Money
    {
        return array_reduce(
            $this->items,
            fn(Money $total, OrderItem $item) => $total->add($item->getSubtotal()),
            Money::zero()
        );
    }
}
```

### Aggregation (Агрегация)

**Definition**: "Has-a" relationship where parts can exist independently.

**Laravel Example**:
```php
// ✅ GOOD: Aggregation - University uses Students
final class University
{
    /** @var Collection<Student> */
    private Collection $students;
    
    public function enrollStudent(Student $student): void
    {
        $this->students->push($student);
    }
    
    public function removeStudent(Student $student): void
    {
        $this->students = $this->students->reject(
            fn($s) => $s->getId() === $student->getId()
        );
    }
}

// Student can exist without University
final class Student
{
    public function __construct(
        private readonly StudentId $id,
        private readonly string $name,
    ) {}
}
```

## 🎯 SOLID PRINCIPLES

### Single Responsibility Principle (SRP)

**Principle**: A class should have only one reason to change.

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

**Principle**: Open for extension, closed for modification.

```php
// ❌ WRONG: Must modify to add new payment methods
class PaymentProcessor
{
    public function process(string $type, float $amount): void
    {
        if ($type === 'credit_card') {
            // Credit card logic
        } elseif ($type === 'paypal') {
            // PayPal logic
        }
    }
}

// ✅ GOOD: Open for extension
interface PaymentMethod
{
    public function process(Money $amount): PaymentResult;
}

final readonly class CreditCardPayment implements PaymentMethod
{
    public function process(Money $amount): PaymentResult
    {
        // Implementation
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

**Principle**: Subclasses must be substitutable for their base classes.

```php
// ❌ WRONG: Violates LSP
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
}

class Square extends Rectangle
{
    public function setWidth(int $width): void
    {
        $this->width = $width;
        $this->height = $width; // Violates LSP
    }
}

// ✅ GOOD: Use composition
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

**Principle**: Clients shouldn't depend on interfaces they don't use.

```php
// ❌ WRONG: Fat interface
interface Worker
{
    public function work(): void;
    public function eat(): void;
    public function sleep(): void;
}

class Robot implements Worker
{
    public function work(): void { /* work */ }
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

final readonly class Human implements Workable, Eatable
{
    public function work(): void { /* work */ }
    public function eat(): void { /* eat */ }
}

final readonly class Robot implements Workable
{
    public function work(): void { /* work */ }
}
```

### Dependency Inversion Principle (DIP)

**Principle**: Depend on abstractions, not concretions.

```php
// ❌ WRONG: Depends on concrete class
final class UserService
{
    private MySQLDatabase $database;
    
    public function __construct()
    {
        $this->database = new MySQLDatabase();
    }
}

// ✅ GOOD: Depends on abstraction
interface DatabaseInterface
{
    public function save(array $data): void;
}

final readonly class UserService
{
    public function __construct(
        private DatabaseInterface $database,
    ) {}
}
```

## 🎨 DESIGN PATTERNS

### Creational Patterns

#### Factory Pattern

```php
interface PaymentGateway
{
    public function charge(Money $amount): PaymentResult;
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

```php
final class QueryBuilder
{
    private string $table = '';
    private array $columns = ['*'];
    private array $where = [];
    
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
    
    public function build(): string
    {
        // Build SQL query
    }
}
```

### Structural Patterns

#### Adapter Pattern

```php
// External library with incompatible interface
class ExternalPaymentService
{
    public function makePayment(float $amount, string $currency): bool
    {
        return true;
    }
}

// Our interface
interface PaymentGateway
{
    public function charge(Money $amount): PaymentResult;
}

// Adapter
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
```

### Behavioral Patterns

#### Strategy Pattern

```php
interface SortingStrategy
{
    public function sort(array $data): array;
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
```

#### Observer Pattern

```php
interface Observer
{
    public function update(string $event, mixed $data): void;
}

final readonly class EmailNotifier implements Observer
{
    public function update(string $event, mixed $data): void
    {
        if ($event === 'user_registered') {
            // Send email
        }
    }
}

final class UserService
{
    private array $observers = [];
    
    public function attach(Observer $observer): void
    {
        $this->observers[] = $observer;
    }
    
    public function register(array $data): User
    {
        $user = User::create($data);
        
        foreach ($this->observers as $observer) {
            $observer->update('user_registered', $user);
        }
        
        return $user;
    }
}
```

## 🚀 LARAVEL-SPECIFIC OOP GUIDELINES

### Action Classes

```php
declare(strict_types=1);

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
```

### Value Objects

```php
final readonly class Email
{
    public function __construct(
        private string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }
}
```

### Repository Pattern

```php
interface UserRepository
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
}

final readonly class EloquentUserRepository implements UserRepository
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }
    
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
    
    public function create(array $data): User
    {
        return User::create($data);
    }
}
```

## 📊 CODE QUALITY PRINCIPLES

### DRY (Don't Repeat Yourself)

```php
// ❌ WRONG: Duplicated validation
class UserController
{
    public function create(Request $request): Response
    {
        if (strlen($request->email) < 5 || !str_contains($request->email, '@')) {
            throw new ValidationException('Invalid email');
        }
    }
    
    public function update(Request $request): Response
    {
        if (strlen($request->email) < 5 || !str_contains($request->email, '@')) {
            throw new ValidationException('Invalid email');
        }
    }
}

// ✅ GOOD: Extracted validation
final readonly class EmailValidator
{
    public function validate(string $email): void
    {
        if (strlen($email) < 5 || !str_contains($email, '@')) {
            throw new ValidationException('Invalid email');
        }
    }
}
```

### KISS (Keep It Simple, Stupid)

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

```php
// ❌ WRONG: Implementing unused features
class User
{
    private string $name;
    private string $email;
    private ?string $phone;
    private ?string $address;
    private ?string $city;
    private ?string $country;
    // ... many unused fields
}

// ✅ GOOD: Only what's needed
final class User
{
    public function __construct(
        private readonly string $name,
        private readonly string $email,
    ) {}
}
```

## ⚠️ ANTI-PATTERNS TO AVOID

### God Object

```php
// ❌ WRONG: God object
class UserManager
{
    public function createUser() {}
    public function deleteUser() {}
    public function sendEmail() {}
    public function processPayment() {}
    public function generateReport() {}
}

// ✅ GOOD: Separate responsibilities
final readonly class CreateUserAction {}
final readonly class EmailService {}
final readonly class PaymentService {}
```

### Anemic Domain Model

```php
// ❌ WRONG: Anemic model
class Order
{
    public int $id;
    public float $total;
    public string $status;
}

class OrderService
{
    public function calculateTotal(Order $order): float {}
}

// ✅ GOOD: Rich domain model
final class Order
{
    public function calculateTotal(): Money
    {
        return $this->items->calculateTotal();
    }
    
    public function markAsPaid(): void
    {
        $this->status = OrderStatus::Paid;
    }
}
```

## 🧪 TESTING OOP CODE

```php
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

## 📚 REFERENCES

- Original articles: 
  - https://habr.com/ru/articles/964836/ (OOP Fundamentals)
  - https://habr.com/ru/articles/957618/ (SOLID Principles)
  - https://habr.com/ru/articles/959198/ (SOLID & Patterns)
  - https://habr.com/ru/articles/951476/ (Advanced OOP - Part 3)
  - https://habr.com/ru/articles/954878/ (Advanced Principles)
  - https://habr.com/ru/articles/966998/ (Comprehensive OOP Guide)
- Laravel Documentation: https://laravel.com/docs
- PHP The Right Way: https://phptherightway.com/
- Clean Code: Robert C. Martin
- Domain-Driven Design: Eric Evans

---

**Remember**: Good OOP design makes code maintainable, testable, and scalable. Always prioritize readability and simplicity over cleverness