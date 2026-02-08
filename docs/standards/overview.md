# Code Standards Overview

## Philosophy

CFlow maintains strict code quality standards to ensure maintainability, security, and performance. All code must pass automated quality checks before merging.

## Quality Tools Stack

### Static Analysis
- **PHPStan Level 9** - Strictest static analysis
- **Larastan** - Laravel-specific PHPStan rules
- **treatPhpDocTypesAsCertain = false** - Enforce real type safety

### Code Formatting
- **Laravel Pint** - PSR-12 with custom rules
- **Line length limit**: 100 characters
- **Strict PSR-12 compliance**

### Automated Refactoring
- **Rector v2** - Automated code improvements
- **CI Integration** - Runs with --dry-run false
- **Laravel-specific rules** - Code quality and modernization

### Architecture Enforcement
- **Deptrac** - Architecture boundary enforcement
- **Domain isolation** - Domains cannot use Filament directly
- **Layer separation** - Strict dependency rules

### Git Hooks
Pre-commit hooks run:
1. **Laravel Pint** - Code formatting
2. **PHPStan** - Static analysis
3. **Pest** - Test suite with coverage

## PHP Standards

### Strict Typing
```php
<?php

declare(strict_types=1);

namespace App\Actions\User;

final readonly class CreateUserAction
{
    public function __construct(
        private UserRepository $repository,
        private HashService $hasher,
    ) {}
    
    public function execute(CreateUserData $data): User
    {
        // Implementation with strict types
    }
}
```

### Class Design Rules
- **Final by default** - Prevent inheritance unless designed for it
- **Readonly properties** - Immutability by default
- **No public properties** - Use getters/setters with validation
- **Constructor promotion** - Use promoted readonly properties
- **Single responsibility** - One reason to change

### Type Declaration Requirements
- All properties must be typed
- All method parameters must be typed
- All method returns must be typed
- No mixed types unless absolutely necessary
- Use union types when appropriate

## Laravel Standards

### Architecture Patterns
- **Action classes** - All business logic in Action classes
- **Repository pattern** - Data access abstraction
- **Service layer** - Complex operations
- **Event-driven** - Decouple with domain events

### Controller Rules
- **Maximum 50 lines** per controller method
- **FormRequest + Policy + Action** triad mandatory
- **No Eloquent** directly in controllers
- **Thin controllers** - Delegate to Action classes

### Model Standards
- **No business logic** in models (use Actions)
- **Proper relationships** with return types
- **Casts for data transformation**
- **Scopes for reusable queries**

### Query Standards
- **Repository pattern** - All queries through repositories
- **Query classes** - Complex queries in dedicated classes
- **Eager loading** - Prevent N+1 queries
- **Proper indexing** - Database performance

## Testing Standards

### Coverage Requirements
- **100% test coverage** - No exceptions
- **Pest PHP only** - No PHPUnit syntax
- **RefreshDatabase + DatabaseTransactions** - Use both
- **Action class testing** - Every Action must have tests
- **Value object testing** - Every Value Object tested

### Test Structure
```php
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

## Filament Standards

### Resource Organization
- **Schema extraction** - Complex forms in separate Schema classes
- **Table extraction** - Table configurations in separate classes
- **Translation keys** - No hardcoded strings
- **JSON field handling** - Proper mixed type handling

### Authorization
- **Filament Shield** - RBAC for all resources
- **Policy checks** - Every action authorized
- **Team scoping** - Multi-tenant isolation

## Security Standards

### Input Validation
- **Form Requests** - All input validated
- **Sanitization** - Clean data before storage
- **CSRF protection** - All forms protected
- **File upload security** - Proper validation and storage

### Authentication
- **MFA support** - Multi-factor authentication
- **Strong passwords** - Enforced requirements
- **Session security** - Proper configuration
- **API authentication** - Sanctum tokens

## Performance Standards

### Database
- **Eager loading** - Prevent N+1 queries
- **Proper indexing** - Query optimization
- **Transactions** - Data consistency
- **Query monitoring** - Performance tracking

### Caching
- **Strategic caching** - Expensive operations cached
- **Cache invalidation** - Proper cache management
- **TTL configuration** - Appropriate expiration
- **Tagged caching** - Related data grouping

## Documentation Standards

### Code Documentation
```php
/**
 * Create a new user account.
 *
 * @param CreateUserData $data The user registration data
 * @return User The created user instance
 * @throws ValidationException When validation fails
 */
public function execute(CreateUserData $data): User
```

### README Requirements
- Clear setup instructions
- Usage examples
- API documentation
- Troubleshooting guide

## Git Standards

### Commit Messages
Follow conventional commits:
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

## Quality Gates

### Pre-commit Checks
- Code formatting (Pint)
- Static analysis (PHPStan)
- Test coverage (Pest)
- Architecture rules (Deptrac)

### CI/CD Pipeline
```yaml
- name: Quality Checks
  run: |
    composer lint
    composer test:coverage
    composer test:architecture
```

### Code Review Requirements
- [ ] All tests pass
- [ ] 100% test coverage maintained
- [ ] PHPStan level 9 compliance
- [ ] Architecture rules followed
- [ ] Documentation updated
- [ ] Security considerations addressed

## Enforcement

### Automated Enforcement
- **Git hooks** - Pre-commit quality checks
- **CI pipeline** - Automated testing and analysis
- **Merge requirements** - Quality gates must pass
- **Rector integration** - Automatic code improvements

### Manual Review
- **Code review** - Peer review required
- **Architecture review** - Complex changes reviewed
- **Security review** - Security-sensitive changes
- **Performance review** - Performance-critical changes

## Tools Configuration

### PHPStan Configuration
```php
// phpstan.neon
parameters:
    level: 9
    treatPhpDocTypesAsCertain: false
    paths:
        - app
        - tests
    ignoreErrors:
        - '#specific patterns only when necessary#'
```

### Pint Configuration
```php
// pint.json
{
    "preset": "psr12",
    "rules": {
        "line_length": {
            "line_length": 100
        }
    }
}
```

### Rector Configuration
```php
// rector.php
use Rector\Config\RectorConfig;
use Rector\Laravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/tests',
    ])
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
    ]);
```

## Continuous Improvement

### Regular Reviews
- **Monthly standards review** - Update standards as needed
- **Tool updates** - Keep quality tools current
- **Best practices** - Incorporate new patterns
- **Team feedback** - Gather developer input

### Metrics Tracking
- **Code coverage trends** - Monitor coverage over time
- **Quality metrics** - Track PHPStan violations
- **Performance metrics** - Monitor application performance
- **Security metrics** - Track security issues

## Related Documentation

- [Development Standards](../development/standards.md)
- [Testing Guidelines](../testing/overview.md)
- [Architecture Overview](../architecture/overview.md)
- [Security Guidelines](../security/overview.md)