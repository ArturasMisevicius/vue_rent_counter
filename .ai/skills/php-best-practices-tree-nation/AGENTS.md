# PHP Best Practices Skillset

Implements TreeNation's DDD architecture for PHP backend components.

## How to use this skill

1. Identify which layers are involved (presentation, application, domain, infrastructure).
2. Load only the relevant rule files from `rules/`.
3. Apply rules in this order:
   1. correctness and architecture compliance
   2. type safety and strict typing
   3. testability
   4. code style (CS Fixer / PHPStan)
4. Verify with PHP CS Fixer and PHPStan before finishing.

## Repository-Specific Rules

- Follow project-local `CLAUDE.md` and repository conventions first.
- New PHP files go inside `src/` except controllers (`app/Http/Controllers/`).
- Use `declare(strict_types=1)` in every new file.
- Controllers extend `ApiController` and use `__invoke` for single-action controllers.
- Services are injected via constructor; never instantiated with `new` inside controllers.
- Always register new repository bindings in `app/Providers/TreeNationProvider.php`.
- Do not add code comments unless the user asks.

## Rule Categories

### 1) Controllers
- `rules/thin-controllers.md`
- `rules/invokable-controllers.md`
- `rules/request-validation-class.md`

### 2) DTOs
- `rules/when-to-use-dtos.md`
- `rules/immutable-dtos.md`

### 3) Services
- `rules/service-business-logic.md`
- `rules/service-response-pattern.md`

### 4) Repositories
- `rules/repository-interface.md`
- `rules/eloquent-repository-implementation.md`
- `rules/criteria-pattern.md`

### 5) Dependency Injection
- `rules/register-bindings.md`

### 6) Testing
- `rules/unit-test-services.md`
