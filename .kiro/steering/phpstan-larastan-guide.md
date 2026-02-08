---
inclusion: always
---

# PHPStan + Larastan Static Analysis Guide

> **TL;DR:** This project uses PHPStan level 9 with Larastan for strict static analysis. All code must pass level 9 analysis before merging. Type safety is mandatory.

## Core Principles

1. **Zero Tolerance for Type Errors**: All code must pass PHPStan level 9 analysis
2. **Explicit Types Everywhere**: No implicit mixed types, no missing type declarations
3. **Laravel-Aware Analysis**: Leverage Larastan for Laravel-specific type checking
4. **Strict Standards**: `treatPhpDocTypesAsCertain = false` enforces real type safety
5. **Continuous Analysis**: Run PHPStan on every file change before committing

## Configuration Standards

### PHPStan Level 9 Requirements

```php
declare(strict_types=1);

// ✅ CORRECT: Explicit types everywhere
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

// ❌ WRONG: Missing types, implicit mixed
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

## Type Declaration Rules

### 1. Properties Must Be Typed

```php
// ✅ CORRECT
final class UserService
{
    private UserRepository $repository;
    private ?User $cachedUser = null;
    /** @var array<string, mixed> */
    private array $config;
}

// ❌ WRONG
final class UserService
{
    private $repository;
    private $cachedUser;
    private $config;
}
```

### 2. Method Parameters and Returns Must Be Typed

```php
// ✅ CORRECT
public function findByEmail(string $email): ?User
{
    return User::query()->where('email', $email)->first();
}

// ❌ WRONG
public function findByEmail($email)
{
    return User::query()->where('email', $email)->first();
}
```

### 3. Array Shapes Must Be Documented

```php
// ✅ CORRECT
/**
 * @param array{name: string, email: string, age: int} $data
 * @return array{id: int, name: string, email: string}
 */
public function createUser(array $data): array
{
    // Implementation
}

// ❌ WRONG
public function createUser(array $data): array
{
    // No array shape documentation
}
```

### 4. Collections Must Have Generic Types

```php
// ✅ CORRECT
/** @return Collection<int, User> */
public function getActiveUsers(): Collection
{
    return User::query()->where('active', true)->get();
}

// ❌ WRONG
public function getActiveUsers(): Collection
{
    return User::query()->where('active', true)->get();
}
```

## Laravel-Specific Type Safety

### Eloquent Models

```php
// ✅ CORRECT: Typed properties and casts
final class User extends Model
{
    /** @var array<string, string> */
    protected $fillable = ['name', 'email', 'password'];
    
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }
    
    /** @return BelongsTo<Company, User> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    /** @return HasMany<Post> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

### Query Builder Type Safety

```php
// ✅ CORRECT: Type-safe queries
/** @return Collection<int, User> */
public function getAdminUsers(): Collection
{
    return User::query()
        ->where('is_admin', true)
        ->orderBy('name')
        ->get();
}

// Use specific return types
public function findUserById(int $id): ?User
{
    return User::query()->find($id);
}

public function getUserCount(): int
{
    return User::query()->count();
}
```

### Form Requests

```php
// ✅ CORRECT: Typed validation rules
final class CreateUserRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
    
    /**
     * @return array{name: string, email: string, password: string}
     */
    public function validated($key = null, $default = null): array
    {
        return parent::validated();
    }
}
```

### Controllers

```php
// ✅ CORRECT: Typed controller methods
final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}
    
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        
        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }
    
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        
        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }
}
```

## Common PHPStan Issues and Solutions

### Issue 1: Dynamic Properties

```php
// ❌ WRONG: Dynamic property access
$user->customProperty = 'value';

// ✅ CORRECT: Use array or dedicated property
final class User extends Model
{
    /** @var array<string, mixed> */
    private array $metadata = [];
    
    public function setMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }
}
```

### Issue 2: Missing Null Checks

```php
// ❌ WRONG: No null check
public function getUserName(int $id): string
{
    $user = User::find($id);
    return $user->name; // PHPStan error: $user might be null
}

// ✅ CORRECT: Proper null handling
public function getUserName(int $id): ?string
{
    $user = User::find($id);
    return $user?->name;
}
```

### Issue 3: Array Access Without Checks

```php
// ❌ WRONG: Unsafe array access
public function getConfig(string $key): string
{
    return config('app')[$key]; // Might not exist
}

// ✅ CORRECT: Safe array access
public function getConfig(string $key): ?string
{
    $config = config('app');
    return is_array($config) ? ($config[$key] ?? null) : null;
}
```

### Issue 4: Mixed Return Types

```php
// ❌ WRONG: Implicit mixed return
public function getData()
{
    return $this->data;
}

// ✅ CORRECT: Explicit return type
/** @return array<string, mixed> */
public function getData(): array
{
    return $this->data;
}
```

## Running PHPStan

### Command Line

```bash
# Run full analysis
composer lint:phpstan

# Or directly
vendor/bin/phpstan analyse --memory-limit=2G

# Analyze specific file
vendor/bin/phpstan analyse app/Services/UserService.php

# Generate baseline (use sparingly)
vendor/bin/phpstan analyse --generate-baseline
```

### Pre-Commit Hook

PHPStan should run automatically on file changes via the `.kiro/hooks/phpstan-analyzer.kiro.hook`.

### CI/CD Integration

```yaml
# Example GitHub Actions
- name: PHPStan Analysis
  run: composer lint:phpstan
```

## Suppressing Errors (Use Sparingly)

### When Suppression is Acceptable

1. **Third-party package issues**: When vendor code has type issues
2. **Complex generics**: When PHPStan can't infer complex generic types
3. **Framework magic**: Rare cases where Laravel magic confuses PHPStan

### How to Suppress

```php
// Inline suppression (avoid if possible)
/** @phpstan-ignore-next-line */
$result = $this->complexMethod();

// Better: Add to phpstan.neon with explanation
// In phpstan.neon:
ignoreErrors:
    -
        message: '#specific error message#'
        path: app/Services/SpecificService.php
        # Comment explaining why this is acceptable
```

## PHPStan + Testing

### Test Type Safety

```php
// ✅ CORRECT: Typed test methods
final class UserServiceTest extends TestCase
{
    private UserService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService(
            new UserRepository(),
            new HashService(),
        );
    }
    
    public function test_creates_user_successfully(): void
    {
        $data = new CreateUserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
        );
        
        $user = $this->service->create($data);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
    }
}
```

## Value Objects for Type Safety

```php
// ✅ CORRECT: Use value objects instead of arrays
final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}

// Usage
public function createUser(CreateUserData $data): User
{
    // Type-safe access to properties
    return User::create([
        'name' => $data->name,
        'email' => $data->email,
        'password' => Hash::make($data->password),
    ]);
}
```

## Enums for Type Safety

```php
// ✅ CORRECT: Use enums for fixed sets of values
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
    
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::USER => 'Regular User',
            self::GUEST => 'Guest User',
        };
    }
}

// Usage
public function assignRole(User $user, UserRole $role): void
{
    $user->role = $role->value;
    $user->save();
}
```

## Verification Checklist

Before committing code, ensure:

```
✓ PHPStan Analysis
- [ ] All files pass PHPStan level 9 analysis
- [ ] No new errors introduced
- [ ] All properties have type declarations
- [ ] All methods have parameter and return types
- [ ] Array shapes are documented
- [ ] Collections have generic types
- [ ] Null safety is handled properly
- [ ] No dynamic property access
- [ ] No implicit mixed types
- [ ] Eloquent relationships are typed

✓ Laravel-Specific
- [ ] Model relationships have generic types
- [ ] Form request validation rules are typed
- [ ] Controller methods have return types
- [ ] Service classes use dependency injection with types
- [ ] Query results have proper type hints

✓ Documentation
- [ ] Complex array shapes documented with @param/@return
- [ ] Generic types specified for collections
- [ ] PHPDoc blocks match actual types
- [ ] No @var tags without actual type declarations
```

## Resources

- **PHPStan Documentation**: https://phpstan.org/
- **Larastan Documentation**: https://github.com/larastan/larastan
- **PHPStan Rules**: https://phpstan.org/rules
- **Laravel Type Coverage**: https://github.com/larastan/larastan#features

## Enforcement

- **Pre-commit**: PHPStan runs automatically via Kiro hook
- **CI/CD**: PHPStan must pass in pipeline
- **Code Review**: Type safety is a blocking requirement
- **No Exceptions**: Level 9 compliance is mandatory for all new code

---

**Remember**: Type safety prevents bugs before they happen. Invest time in proper typing now to save debugging time later.
