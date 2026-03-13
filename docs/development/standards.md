# Development Standards

## PHP Standards

### Strict Typing
```php
<?php

declare(strict_types=1);
```

### Class Design
```php
// ✅ GOOD: Final, readonly class
final readonly class CreateUserAction
{
    public function __construct(
        private UserRepository $repository,
        private HashService $hasher,
    ) {}
    
    public function execute(CreateUserData $data): User
    {
        // Implementation
    }
}
```

### Type Declarations
- All properties must be typed
- All method parameters must be typed
- All method returns must be typed
- Use readonly properties when possible
- Use final classes by default

## Architecture Patterns

### Action Classes
```php
// Business logic in Action classes
final readonly class CreateInvoiceAction
{
    public function execute(CreateInvoiceData $data): Invoice
    {
        // Single responsibility
    }
}
```

### Repository Pattern
```php
interface UserRepository
{
    public function find(int $id): ?User;
    public function create(array $data): User;
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
}
```

## Laravel Conventions

### Controllers
- Keep controllers thin (< 50 lines)
- Delegate to Action classes
- Use Form Requests for validation
- Use Policies for authorization

```php
final class UserController
{
    public function store(CreateUserRequest $request, CreateUserAction $action): JsonResponse
    {
        $user = $action->execute(CreateUserData::fromRequest($request));
        return response()->json(new UserResource($user), 201);
    }
}
```

### Models
- Use casts for data transformation
- Define relationships with proper return types
- Use scopes for reusable queries
- Implement proper mutators/accessors

### Migrations
- Use descriptive names
- Include rollback logic
- Add proper indexes
- Use foreign key constraints

## Filament Standards

### Resources
- Extract forms to Schema classes
- Extract tables to Table classes
- Use proper authorization
- Implement proper relationships

### Translations
- No hardcoded strings
- Use translation keys for all UI text
- Keep translations organized by domain

## Testing Standards

### Test Structure
```php
it('creates user with valid data', function () {
    // Arrange
    $data = CreateUserData::fake();
    
    // Act
    $user = $this->action->execute($data);
    
    // Assert
    expect($user)->toBeInstanceOf(User::class);
});
```

### Coverage Requirements
- 100% test coverage mandatory
- Feature tests for user workflows
- Unit tests for business logic
- Integration tests for external services

## Code Quality Tools

### PHPStan Configuration
- Level 9 (strictest)
- treatPhpDocTypesAsCertain = false
- Larastan for Laravel-specific rules

### Rector Configuration
- Laravel code quality sets
- Automatic refactoring on CI
- Remove debug statements

### Pint Configuration
- PSR-12 standard
- Custom rules for line length (100 chars)
- Automatic formatting

## Git Workflow

### Commit Messages
```
feat: add user registration
fix: resolve authentication bug
docs: update API documentation
test: add user creation tests
refactor: extract user service
```

### Branch Naming
```
feature/user-registration
bugfix/authentication-issue
hotfix/security-vulnerability
```

## Documentation Standards

### Code Documentation
```php
/**
 * Create a new user account.
 *
 * @param CreateUserData $data The user data
 * @return User The created user
 * @throws ValidationException When data is invalid
 */
public function execute(CreateUserData $data): User
```

### README Files
- Clear setup instructions
- Usage examples
- API documentation
- Troubleshooting guide

## Performance Standards

### Database
- Use eager loading to prevent N+1 queries
- Add proper indexes
- Use database transactions
- Optimize query performance

### Caching
- Cache expensive operations
- Use appropriate TTL values
- Clear cache when data changes
- Use tagged caching

### Frontend
- Minimize JavaScript bundle size
- Optimize images and assets
- Use lazy loading
- Implement proper caching headers

## Security Standards

### Input Validation
- Validate all user input
- Use Form Requests
- Sanitize data before storage
- Implement proper CSRF protection

### Authentication
- Use strong password requirements
- Implement MFA when possible
- Use secure session configuration
- Implement proper logout

### Authorization
- Use Laravel Policies
- Implement role-based access control
- Check permissions at all levels
- Prevent privilege escalation

## Related Documentation

- [Architecture Overview](../architecture/overview.md)
- [Testing Guidelines](../testing/overview.md)
- [Security Guidelines](../security/overview.md)
- [Performance Optimization](../performance/overview.md)