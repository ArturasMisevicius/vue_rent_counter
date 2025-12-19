---
inclusion: always
---
# Codex AI Prompt: Implement Clean Architecture Structure

## Project Context
Implement a PHP Laravel project following "The Clean Structure" (Чистая структура) - a universal project structure based on Clean Architecture principles and modular monolith approach.

## Core Architecture Principles

### Dependency Rules (Critical)
1. **Presentation layer** → can use Infrastructure, Application, Domain (own and external)
2. **Infrastructure layer** → can use only Application and Domain (own and external)
3. **Application layer** → can use Domain (preferably only own)
4. **Domain layer** → can use only Domain (own and external)
5. **UseCase** → cannot use another UseCase (maintain simplicity), but can aggregate Query and Command
6. **Interactor** → should not use another Interactor, can aggregate Query, Command, and UseCase

### Structure Template

```
/src
  /Core                          # Application core, framework-independent
    (same structure as modules below)
  
  /%ModuleGroupName%             # Optional grouping of related modules
    /%ModuleName%
      /Application               # Business logic implementation
        /Command                 # Data modification in external systems (e.g., DB)
        /Dto                     # Data Transfer Objects between layers
        /Factory                 # Factory classes for Dto, Response, ValueObject
        /Responder               # Special content generation (non-JSON responses)
          /Template              # Email, Excel, Word, Telegram templates
          %Name%.php             # Response generators
        /Service                 # Self-sufficient functionality
          %Name%.php             # Unique logic implementations (e.g., ABC analysis calculator)
        /Query                   # Data retrieval from external systems
        /UseCase                 # User action handlers
        
      /Domain                    # Domain model (public module API)
        /Dto                     # Objects for Infrastructure ↔ Application transfer
        /Event                   # UseCase events/notifications
        /Exception               # Application error messages
        /Entity                  # Database table structure descriptions
        /Request                 # Incoming DTOs with validation
        /Response                # Outgoing DTOs
        /Validation              # Business validation requirements
        /ValueObject             # Specialized DTOs with validation
        %Interface%.php          # Module contracts and APIs
        
      /Infrastructure            # Anti-corruption layer, Framework Agnostic
        /Adapter                 # Domain interface implementations
        /Repository              # Simplified CQS/CQRS for data access
        /%VendorLibraryName%     # Vendor library adapters
        
      /Presentation              # Framework entry/exit points
        /Config                  # Configuration files, DI, Service Container
        /Console                 # CLI commands, cron scripts, daemons
        /Http
          /Controller            # HTTP controllers
          /Middleware            # HTTP middleware
          /View                  # HTML templates
        /Listener                # Event subscribers

/tests
  /Architecture                  # Architecture tests
  /Stub                          # Test fixtures and fakes
  /Suite
    /%ModuleGroupName%
      /%ModuleName%
        ...
  TestCase.php                   # Abstract test wrapper
```

## Implementation Guidelines

### When to Use This Structure
✅ **Use when:**
- Project has complex logic (dozens of modules)
- Long-term support and feature development planned
- More than 10 controllers and models

❌ **Don't overcomplicate when:**
- Simple project (8-10 controllers/models)
- No plans for growth (landing pages, blogs, simple sites)

### Key Concepts

**Module** - Self-sufficient product part responsible for specific functionality. Contains public interfaces, entities, events defining usage boundaries.

**UseCase** - Simple implementation of user behavior variant (what user wants to do).

**Interactor** - Global level for user behavior implementation.

**DTO** - "Typed associative array" for data transfer.

**Event Types:**
- Notifications: Past events (sync/async, multiple receivers)
- Queries: Data retrieval (sync only, single receiver)
- Commands: Create/Update/Delete operations (sync only, multiple receivers)

### Module Interaction
- Use **Anti-corruption layer** pattern
- Interact through Domain interfaces of other modules
- Use domain events for loose coupling

### DTO Usage Rules
- Required for public and protected method parameters and return types
- Private methods can use associative arrays
- Use Factory classes for complex DTO creation

### Interface Requirements
- Not mandatory within single module if only one implementation exists
- Always create interface for cross-module interactions
- Follow dependency inversion principle

### Testing Requirements
- Tests are ALWAYS required, even under time pressure
- **End-to-End tests:** Cover entire system, focus on positive scenarios
- **Unit/Integration tests:** Detailed testing, focus on negative scenarios and edge cases
- Architecture tests to enforce structure rules

## Implementation Tasks

### 1. Project Initialization
- Create /src directory structure
- Set up namespace autoloading
- Configure service providers

### 2. Core Module Setup
- Create Core module with shared functionality
- Define common interfaces and exceptions
- Implement framework wrappers

### 3. Business Module Implementation
For each business module:
- Identify bounded context
- Create Domain layer (entities, interfaces, events)
- Implement Application layer (UseCases, Commands, Queries)
- Add Infrastructure adapters
- Create Presentation layer entry points

### 4. Testing Setup
- Configure Pest/PHPUnit
- Create Architecture tests to enforce layer dependencies
- Implement test suites per module

### 5. Quality Control
- Set up static analysis (PHPStan/Psalm level 8+)
- Configure Deptrac for architecture validation
- Implement CI/CD with architecture checks

## Code Examples to Generate

### Example 1: Simple Controller
```php
// Presentation/Http/Controller/PingController.php
```

### Example 2: Health Check Module
```php
// Application/UseCase/DbHealthUseCase.php
// Application/Command/CheckDbWriter.php
// Application/Query/CheckDbReader.php
// Domain/Entity/HealthCheck.php
// Presentation/Listener/HealthChecker.php
```

### Example 3: Complex Feature with Multiple Integrations
- Multiple queries and commands
- Cross-module communication via Domain events
- Factory pattern for complex DTOs
- Responder with template generation

## Critical Reminders

1. **Don't overcomplicate initially** - Start with necessary folders only
2. **Low Coupling, High Cohesion** - Keep modules independent
3. **Framework Agnostic** - Business logic should not depend on Laravel
4. **Test-Driven** - Write tests to validate architecture decisions
5. **Gradual complexity** - Add layers as needed, not upfront

## Questions to Answer Before Implementation

1. What Modules/Bounded Contexts exist in the project?
2. How tightly are they connected?
3. What are the plans for future development?
4. What is the current team expertise level?
5. What is the project timeline and budget?

## Output Requirements

Generate clean, well-documented code that:
- Follows PSR-12 coding standards
- Uses typed properties and return types (PHP 8.1+)
- Includes PHPDoc blocks for public APIs
- Implements proper error handling
- Follows SOLID principles
- Passes architecture tests


  Core Principles
  - Write concise, technical responses with accurate PHP/Laravel examples.
  - Prioritize SOLID principles for object-oriented programming and clean architecture.
  - Follow PHP and Laravel best practices, ensuring consistency and readability.
  - Design for scalability and maintainability, ensuring the system can grow with ease.
  - Prefer iteration and modularization over duplication to promote code reuse.
  - Use consistent and descriptive names for variables, methods, and classes to improve readability.

  Dependencies
  - Composer for dependency management
  - PHP 8.3+
  - Laravel 11.0+

  PHP and Laravel Standards
  - Leverage PHP 8.3+ features when appropriate (e.g., typed properties, match expressions).
  - Adhere to PSR-12 coding standards for consistent code style.
  - Always use strict typing: declare(strict_types=1);
  - Utilize Laravel's built-in features and helpers to maximize efficiency.
  - Follow Laravel's directory structure and file naming conventions.
  - Implement robust error handling and logging:
    > Use Laravel's exception handling and logging features.
    > Create custom exceptions when necessary.
    > Employ try-catch blocks for expected exceptions.
  - Use Laravel's validation features for form and request data.
  - Implement middleware for request filtering and modification.
  - Utilize Laravel's Eloquent ORM for database interactions.
  - Use Laravel's query builder for complex database operations.
  - Create and maintain proper database migrations and seeders.


  Laravel Best Practices
  - Use Eloquent ORM and Query Builder over raw SQL queries when possible
  - Implement Repository and Service patterns for better code organization and reusability
  - Utilize Laravel's built-in authentication and authorization features (Sanctum, Policies)
  - Leverage Laravel's caching mechanisms (Redis, Memcached) for improved performance
  - Use job queues and Laravel Horizon for handling long-running tasks and background processing
  - Implement comprehensive testing using PHPUnit and Laravel Dusk for unit, feature, and browser tests
  - Use API resources and versioning for building robust and maintainable APIs
  - Implement proper error handling and logging using Laravel's exception handler and logging facade
  - Utilize Laravel's validation features, including Form Requests, for data integrity
  - Implement database indexing and use Laravel's query optimization features for better performance
  - Use Laravel Telescope for debugging and performance monitoring in development
  - Leverage Laravel Nova or Filament for rapid admin panel development
  - Implement proper security measures, including CSRF protection, XSS prevention, and input sanitization

  Code Architecture
    * Naming Conventions:
      - Use consistent naming conventions for folders, classes, and files.
      - Follow Laravel's conventions: singular for models, plural for controllers (e.g., User.php, UsersController.php).
      - Use PascalCase for class names, camelCase for method names, and snake_case for database columns.
    * Controller Design:
      - Controllers should be final classes to prevent inheritance.
      - Make controllers read-only (i.e., no property mutations).
      - Avoid injecting dependencies directly into controllers. Instead, use method injection or service classes.
    * Model Design:
      - Models should be final classes to ensure data integrity and prevent unexpected behavior from inheritance.
    * Services:
      - Create a Services folder within the app directory.
      - Organize services into model-specific services and other required services.
      - Service classes should be final and read-only.
      - Use services for complex business logic, keeping controllers thin.
    * Routing:
      - Maintain consistent and organized routes.
      - Create separate route files for each major model or feature area.
      - Group related routes together (e.g., all user-related routes in routes/user.php).
    * Type Declarations:
      - Always use explicit return type declarations for methods and functions.
      - Use appropriate PHP type hints for method parameters.
      - Leverage PHP 8.3+ features like union types and nullable types when necessary.
    * Data Type Consistency:
      - Be consistent and explicit with data type declarations throughout the codebase.
      - Use type hints for properties, method parameters, and return types.
      - Leverage PHP's strict typing to catch type-related errors early.
    * Error Handling:
      - Use Laravel's exception handling and logging features to handle exceptions.
      - Create custom exceptions when necessary.
      - Use try-catch blocks for expected exceptions.
      - Handle exceptions gracefully and return appropriate responses.

  Key points
  - Follow Laravel’s MVC architecture for clear separation of business logic, data, and presentation layers.
  - Implement request validation using Form Requests to ensure secure and validated data inputs.
  - Use Laravel’s built-in authentication system, including Laravel Sanctum for API token management.
  - Ensure the REST API follows Laravel standards, using API Resources for structured and consistent responses.
  - Leverage task scheduling and event listeners to automate recurring tasks and decouple logic.
  - Implement database transactions using Laravel's database facade to ensure data consistency.
  - Use Eloquent ORM for database interactions, enforcing relationships and optimizing queries.
  - Implement API versioning for maintainability and backward compatibility.
  - Optimize performance with caching mechanisms like Redis and Memcached.
  - Ensure robust error handling and logging using Laravel’s exception handler and logging features.
  