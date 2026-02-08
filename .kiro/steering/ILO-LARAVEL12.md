---
inclusion: always
---

# ILO CODE – Laravel 12 Rules
- No model properties in database migrations → use casts + accessors with attributes objects
- All business logic in Action classes (app/Actions/CreateUserAction.php)
- No controllers longer than 50 lines
- FormRequest + Policy + Action triad mandatory
- Never use Eloquent directly in controllers or Livewire components
- All queries go through dedicated Query classes or repositories
- Use Laravel 12 new `once()` + `remember()` cache patterns
- Use defer() for heavy loading in Livewire
- Use Eloquent ORM and Repository patterns for data access.
- Secure APIs with Laravel Passport and ensure proper CSRF protection.
- Leverage Laravel’s caching mechanisms for optimal performance.
- Use Laravel’s testing tools (PHPUnit, Dusk) for unit and feature testing.
- Apply API versioning for maintaining backward compatibility.
- Ensure database integrity with proper indexing, transactions, and migrations.
- Use Laravel's localization features for multi-language support.
- Optimize front-end development with TailwindCSS and PrimeVue integration.

  Key Principles
  - Write concise, technical responses with accurate examples in PHP 
  - Use object-oriented programming with a focus on SOLID principles.
  - Favor iteration and modularization over duplication.
  - Use descriptive and meaningful names for variables, methods, and files.
  - Adhere to Laravel's directory structure conventions (e.g., app/Http/Controllers).
  - Prioritize dependency injection and service containers.
  - Leverage PHP 8.2+ features (e.g., readonly properties, match expressions).
  - Apply strict typing: declare(strict_types=1).
  - Follow PSR-12 coding standards for PHP.
  - Use Laravel's built-in features and helpers (e.g., `Str::` and `Arr::`).
  - File structure: Stick to Laravel's MVC architecture and directory organization.
  - Implement error handling and logging:
    - Use Laravel's exception handling and logging tools.
    - Create custom exceptions when necessary.
    - Apply try-catch blocks for predictable errors.
  - Use Laravel's request validation and middleware effectively.
  - Implement Eloquent ORM for database modeling and queries.
  - Use migrations and seeders to manage database schema changes and test data.

  Best Practices
  - Use Eloquent ORM and Repository patterns for data access.
  - Secure APIs with Laravel Passport and ensure proper CSRF protection.
  - Leverage Laravel’s caching mechanisms for optimal performance.
  - Use Laravel’s testing tools (PHPUnit, Dusk) for unit and feature testing.
  - Ensure database integrity with proper indexing, transactions, and migrations.
  - Use Laravel's localization features for multi-language support.
  - Optimize front-end development with TailwindCSS and PrimeVue integration.

  Key Conventions
  1. Follow Laravel's MVC architecture.
  2. Use routing for clean URL and endpoint definitions.
  3. Implement request validation with Form Requests.
  4. Build reusable Vue components and modular state management.
  5. Use Laravel's Blade engine or API resources for efficient views.
  6. Manage database relationships using Eloquent's features.
  7. Ensure code decoupling with Laravel's events and listeners.
  8. Implement job queues and background tasks for better scalability.
  9. Use Laravel's built-in scheduling for recurring processes.
  10. Employ Laravel Mix or Vite for asset optimization and bundling.
  