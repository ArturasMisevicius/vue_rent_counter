# System Architecture Overview

## Architecture Principles

Tenanto follows Clean Architecture principles with a focus on maintainability, testability, and scalability.

### Core Layers

1. **Domain Layer** - Business logic and entities
2. **Application Layer** - Use cases and application services
3. **Infrastructure Layer** - External concerns (database, APIs, etc.)
4. **Presentation Layer** - User interface (Filament, API controllers)

### Key Patterns

- **Action-Based Architecture** - Each business operation is encapsulated in a single Action class
- **Repository Pattern** - Abstract data access behind interfaces
- **Service Layer** - Complex business operations
- **Event-Driven Architecture** - Decouple components using domain events

## Technology Stack

### Backend
- **Laravel 12** - PHP framework
- **PHP 8.4** - Latest PHP with strict types
- **Filament v5** - Admin panel framework
- **PostgreSQL** - Primary database
- **Redis** - Caching and sessions

### Frontend
- **Livewire v3** - Reactive components
- **Alpine.js** - Minimal JavaScript framework
- **Tailwind CSS v4** - Utility-first CSS
- **Vite** - Build tool

### Quality Tools
- **PHPStan Level 9** - Static analysis
- **Rector v2** - Automated refactoring
- **Pest** - Testing framework
- **Laravel Pint** - Code formatting

## Multi-Tenancy

Tenanto uses hierarchical tenant scoping:
- Organization isolation is enforced with `tenant_id` plus `HierarchicalScope`.
- Tenant users get additional `property_id` filtering.
- Superadmin flows bypass tenant filtering by design for platform-level oversight.

## Canonical Role Dashboard Resolution

- Role-based dashboard routing is centralized in `App\Services\RoleDashboardResolver`.
- `/dashboard` always resolves to one canonical route:
  - `superadmin.dashboard`
  - `admin.dashboard`
  - `manager.dashboard`
  - `tenant.dashboard`
- Login redirection (`AuthenticationService`) and route-level resolution now share the same source of truth.

## Utility Billing Calculation Core

The canonical billing flow runs through `ServiceConfiguration` -> `BillingService` -> `UniversalBillingCalculator`.

- `rate_schedule.zone_rates` remains the fastest path for time-of-use (TOU) billing.
- `rate_schedule.time_windows` is supported for business-grade TOU design:
  - `zone`, `start`, `end`, `rate`
  - optional `day_types` (`weekday`/`weekend`)
  - optional `months` (`1..12`)
- Legacy `rate_schedule.time_slots` remains supported for backward compatibility.
- TOU schedule resolution rejects overlapping or ambiguous windows to keep billing deterministic.
- Optional localized calculation profile lives under `rate_schedule.localization`:
  - `locale`
  - `minimum_charge`
  - `tax_rate`
  - `money_precision`
  - `rounding_mode` (`half_up`, `half_down`, `bankers`, `up`, `down`)
  - `fixed_charges[]` and `surcharges[]`

Localization is applied as a post-calculation adjustment layer so existing pricing model behavior remains backward-compatible when no localization profile is configured.

### Billing Safety Guarantees

- `BillingService::generateInvoice()` is idempotent for draft invoices by tenant + billing period.
- Existing draft invoices are reused to prevent duplicate draft generation during repeated requests.
- Invoice generation attempts are audited in `invoice_generation_audits` with actor, period, totals, and reuse metadata.

## Security

- Role-based access control (RBAC) with Filament Shield
- Multi-factor authentication (MFA) support
- CSRF protection on all forms
- Input validation and sanitization
- Secure file uploads with filename generation

## Performance

- Database query optimization with eager loading
- Redis caching for expensive operations
- Background job processing with queues
- CDN for static assets
- Database indexing strategy

## Scalability

- Horizontal scaling with load balancers
- Database read replicas
- Queue workers for background processing
- Microservice-ready architecture
- API-first design

## Monitoring

- Application performance monitoring
- Error tracking and logging
- Database query monitoring
- Queue job monitoring
- User activity tracking

## Related Documentation

- [Development Standards](../development/standards.md)
- [Database Design](../database/design.md)
- [Security Guidelines](../security/overview.md)
- [Performance Optimization](../performance/overview.md)
