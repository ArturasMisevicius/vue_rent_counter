---
title: "How to Stop Writing Spaghetti Code: Key OOP Ideas"
category: "Architecture & Design"
priority: "high"
inclusion: "always"
tags: ["oop", "clean-code", "spaghetti-code", "best-practices", "architecture"]
source: "https://habr.com/ru/articles/964836/"
---

# How to Stop Writing Spaghetti Code: Key OOP Ideas

> **Source:** [Habr Article #964836](https://habr.com/ru/articles/964836/) - "Как перестать писать спагетти-код: ключевые идеи ООП"
>
> **TL;DR:** Practical guide to applying OOP principles to write clean, maintainable code and avoid the "spaghetti code" anti-pattern. Focus on the four pillars of OOP: Abstraction, Encapsulation, Inheritance, and Polymorphism.

## 🎯 Core Problem: What is Spaghetti Code?

**Spaghetti code** is code that:
- Lacks clear structure and organization
- Has tangled dependencies and relationships
- Is difficult to read, understand, and maintain
- Makes changes risky and error-prone
- Lacks separation of concerns

**The Solution**: Apply OOP principles systematically to create well-structured, maintainable code.

## 📐 The Four Pillars of OOP

### 1. Abstraction (Абстракция)

**Core Idea**: Hide complex implementation details and focus on **what** the system does, not **how** it does it.

**Key Principles**:
- Leave only the essential, remove unnecessary details
- Provide simple interfaces for complex operations
- Focus on the contract, not the implementation
- Separate "what" from "how"

**Implementation in Laravel**:
```php
// ✅ GOOD: Abstract interface hides complexity
interface PaymentProcessor
{
    public function process(PaymentRequest $request): PaymentResult;
}

// Concrete implementation details are hidden
final readonly class StripePaymentProcessor implements PaymentProcessor
{
    public function process(PaymentRequest $request): PaymentResult
    {
        // Complex Stripe API logic hidden from consumers
        // Consumers only care about process() method
    }
}

// ❌ BAD: Exposing implementation details
class PaymentHandler
{
    public function callStripeApi($amount, $card, $secretKey, $endpoint) {
        // Too many details exposed
    }
}
```

**Benefits**:
- Easier to understand and use
- Implementation can change without affecting consumers
- Reduces cognitive load
- Enables testing with mocks

### 2. Encapsulation (Инкапсуляция)

**Core Idea**: Hide internal data and implementation details, providing access only through controlled methods.

**Key Principles**:
- Protect data from unauthorized modification
- Make code more secure and modular
- Hide complexity, provide convenient interaction methods
- Control access through well-defined interfaces

**Implementation in Laravel**:
```php
// ✅ GOOD: Proper encapsulation
final class Order
{
    private function __construct(
        private readonly OrderId $id,
        private OrderStatus $status,
        private readonly Money $total,
        private readonly DateTimeImmutable $createdAt,
    ) {
        // Validation and initialization logic
    }
    
    public static function create(OrderId $id, Money $total): self
    {
        return new self(
            id: $id,
            status: OrderStatus::PENDING,
            total: $total,
            createdAt: new DateTimeImmutable(),
        );
    }
    
    public function markAsPaid(): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            throw new InvalidOrderStateException('Only pending orders can be paid');
        }
        
        $this->status = OrderStatus::PAID;
    }
    
    public function getStatus(): OrderStatus
    {
        return $this->status;
    }
}

// ❌ BAD: No encapsulation
class Order
{
    public string $status; // Can be changed directly
    public float $total;   // No validation
}
```

**Benefits**:
- Data integrity and validation
- Easier to maintain and debug
- Clear contracts for interaction
- Prevents invalid state transitions

### 3. Inheritance (Наследование)

**Core Idea**: Create new classes based on existing ones, borrowing their properties and behavior with the ability to add or modify.

**Key Principles**:
- Transfer common properties and behavior
- Avoid code duplication
- Create hierarchies of related classes
- **Prefer composition over inheritance** when possible

**Important Notes**:
- Inheritance creates tight coupling
- Use inheritance for "is-a" relationships
- Use composition for "has-a" relationships
- Keep inheritance hierarchies shallow (max 2-3 levels)

**Implementation in Laravel**:
```php
// ✅ GOOD: Interface-based (preferred over inheritance)
interface NotificationChannel
{
    public function send(Notification $notification): void;
}

final readonly class EmailChannel implements NotificationChannel
{
    public function send(Notification $notification): void
    {
        // Email-specific implementation
    }
}

final readonly class SmsChannel implements NotificationChannel
{
    public function send(Notification $notification): void
    {
        // SMS-specific implementation
    }
}

// ⚠️ ACCEPTABLE: Inheritance when truly needed
abstract class BaseRepository
{
    protected function findOrFail(int $id): Model
    {
        return $this->model::findOrFail($id);
    }
}

final class UserRepository extends BaseRepository
{
    protected string $model = User::class;
    
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
```

**Benefits**:
- Code reuse without duplication
- Consistent behavior across related classes
- Easier to maintain common functionality

**Pitfalls to Avoid**:
- Deep inheritance hierarchies
- Using inheritance for code reuse alone
- Violating Liskov Substitution Principle

### 4. Polymorphism (Полиморфизм)

**Core Idea**: Objects of different classes can use the same interface but implement them differently.

**Key Principles**:
- One interface - different implementations
- Flexibility in handling different object types
- Reduces code duplication
- Enables runtime behavior selection

**Implementation in Laravel**:
```php
// ✅ GOOD: Polymorphic behavior
interface FileStorage
{
    public function store(string $path, string $content): string;
    public function retrieve(string $path): string;
    public function delete(string $path): void;
}

final readonly class S3FileStorage implements FileStorage
{
    public function store(string $path, string $content): string
    {
        // S3-specific implementation
    }
    
    public function retrieve(string $path): string
    {
        // S3-specific implementation
    }
    
    public function delete(string $path): void
    {
        // S3-specific implementation
    }
}

final readonly class LocalFileStorage implements FileStorage
{
    public function store(string $path, string $content): string
    {
        // Local filesystem implementation
    }
    
    public function retrieve(string $path): string
    {
        // Local filesystem implementation
    }
    
    public function delete(string $path): void
    {
        // Local filesystem implementation
    }
}

// Consumer code doesn't care about implementation
final class FileService
{
    public function __construct(
        private readonly FileStorage $storage,
    ) {}
    
    public function saveDocument(string $content): string
    {
        return $this->storage->store('documents/' . uniqid(), $content);
    }
}
```

**Benefits**:
- Flexible and extensible code
- Easy to swap implementations
- Reduces conditional logic
- Enables dependency injection

## 🔧 Additional OOP Concepts

### Decomposition (Декомпозиция)

**Definition**: Breaking down a complex system into simpler, independent, and manageable components.

**Principles**:
- Divide complex problems into smaller parts
- Each component has a single responsibility
- Components should be loosely coupled
- Components should be highly cohesive

**Example**:
```php
// ❌ BAD: Monolithic class doing everything
class OrderProcessor
{
    public function process($order) {
        // Validate order
        // Calculate tax
        // Apply discounts
        // Process payment
        // Send email
        // Update inventory
        // Log everything
    }
}

// ✅ GOOD: Decomposed into focused classes
final class OrderValidator
{
    public function validate(Order $order): void { }
}

final class TaxCalculator
{
    public function calculate(Order $order): Money { }
}

final class DiscountApplier
{
    public function apply(Order $order, Discount $discount): void { }
}

final class PaymentProcessor
{
    public function process(Order $order, PaymentMethod $method): PaymentResult { }
}

final class OrderProcessor
{
    public function __construct(
        private readonly OrderValidator $validator,
        private readonly TaxCalculator $taxCalculator,
        private readonly DiscountApplier $discountApplier,
        private readonly PaymentProcessor $paymentProcessor,
    ) {}
    
    public function process(Order $order, PaymentMethod $method): void
    {
        $this->validator->validate($order);
        $this->taxCalculator->calculate($order);
        $this->discountApplier->apply($order, $discount);
        $this->paymentProcessor->process($order, $method);
    }
}
```

### Composition vs Inheritance

**Rule of Thumb**: Prefer composition over inheritance.

**When to Use Inheritance**:
- True "is-a" relationship
- Need to share implementation
- Framework requirements (e.g., Eloquent models)

**When to Use Composition**:
- "has-a" relationship
- Need flexibility
- Want to avoid tight coupling
- Multiple behaviors needed

```php
// ✅ GOOD: Composition
final class Car
{
    public function __construct(
        private readonly Engine $engine,
        private readonly Transmission $transmission,
        private readonly Wheels $wheels,
    ) {}
}

// ❌ BAD: Inheritance for "has-a" relationship
class Car extends Engine
{
    // Car is not an Engine!
}
```

## 🎯 Practical Guidelines

### 1. Start with Interfaces

Define what you need, not how it's implemented:

```php
interface UserRepository
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
}
```

### 2. Keep Classes Focused

Each class should have one reason to change (Single Responsibility Principle):

```php
// ✅ GOOD: Single responsibility
final class EmailValidator
{
    public function validate(string $email): bool { }
}

final class EmailSender
{
    public function send(string $to, string $subject, string $body): void { }
}

// ❌ BAD: Multiple responsibilities
class EmailService
{
    public function validateAndSend($email, $subject, $body) { }
}
```

### 3. Use Dependency Injection

Depend on abstractions, not concretions:

```php
// ✅ GOOD: Dependency injection
final class OrderService
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly PaymentProcessor $paymentProcessor,
        private readonly NotificationService $notifications,
    ) {}
}

// ❌ BAD: Hard dependencies
class OrderService
{
    public function __construct()
    {
        $this->repository = new DatabaseOrderRepository();
        $this->paymentProcessor = new StripePaymentProcessor();
    }
}
```

### 4. Make Classes Immutable When Possible

Immutable objects are easier to reason about:

```php
// ✅ GOOD: Immutable value object
final readonly class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly Currency $currency,
    ) {}
    
    public function add(Money $other): self
    {
        return new self(
            $this->amount + $other->amount,
            $this->currency
        );
    }
}
```

## 🚫 Anti-Patterns to Avoid

### 1. God Classes

Classes that do too much:

```php
// ❌ BAD: God class
class Application
{
    public function handleRequest() { }
    public function processPayment() { }
    public function sendEmail() { }
    public function generateReport() { }
    public function updateDatabase() { }
    // ... 50 more methods
}
```

### 2. Anemic Domain Models

Classes that are just data containers:

```php
// ❌ BAD: Anemic model
class Order
{
    public string $status;
    public float $total;
    // No behavior, just data
}

// Logic is elsewhere
class OrderService
{
    public function markAsPaid(Order $order) {
        $order->status = 'paid';
    }
}
```

### 3. Feature Envy

Classes that access too much of another class's internals:

```php
// ❌ BAD: Feature envy
class OrderReport
{
    public function generate(Order $order): string
    {
        return "Order #{$order->id} for {$order->customer->name} "
             . "with total {$order->calculateTotal()} "
             . "was placed on {$order->createdAt->format('Y-m-d')}";
    }
}

// ✅ GOOD: Move logic to Order
class Order
{
    public function toReportString(): string
    {
        return "Order #{$this->id} for {$this->customer->name} "
             . "with total {$this->calculateTotal()} "
             . "was placed on {$this->createdAt->format('Y-m-d')}";
    }
}
```

## 📚 Summary

To avoid spaghetti code, remember:

1. **Abstraction**: Hide complexity, expose simplicity
2. **Encapsulation**: Protect data, control access
3. **Inheritance**: Use sparingly, prefer composition
4. **Polymorphism**: One interface, many implementations

**Key Practices**:
- Start with interfaces
- Keep classes focused
- Use dependency injection
- Prefer composition over inheritance
- Make objects immutable when possible
- Decompose complex systems

**Result**: Clean, maintainable, testable code that's easy to understand and modify.

