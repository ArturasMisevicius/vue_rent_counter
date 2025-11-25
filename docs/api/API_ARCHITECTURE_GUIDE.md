# API Architecture Guide (REST, Laravel 12)

**Hooks covered:** `laravel-api-architect`, `laravel-architecture-advisor`, `laravel-requirements-analyst`.  
**Stack:** Laravel 12, Filament v4 admin, multi-tenant (`tenant_id`), Pest tests.

## Core Conventions
- **Auth:** Use Laravel Sanctum or Passport (per project standard). All endpoints require auth unless explicitly public. Include tenant isolation via `tenant_id` (from token/user context) and policies.
- **Versioning:** Prefix routes with `/api/v1/...`. Introduce `/v2` when breaking changes occur; keep old versions until clients migrate.
- **Request validation:** FormRequest classes with explicit rules, custom messages, and `authorize()` checks. Prefer enums for status/type fields.
- **Responses:** JSON only. Use a consistent envelope:
  ```json
  { "data": { ... }, "meta": { ... }, "errors": [] }
  ```
  For errors, return `{ "errors": [ { "code": "...", "message": "...", "field": "..." } ] }`.
- **HTTP codes:** 2xx success, 4xx client errors (422 for validation), 401/403 for auth, 404 for missing, 409 for conflicts, 500 for server errors.
- **Pagination:** Use `page` + `per_page` (default 15) with `meta` → `total`, `page`, `per_page`.

## Multi-Tenancy & Authorization
- All queries must be tenant-scoped via `BelongsToTenant`/policies. Never accept `tenant_id` directly from the client; derive from the authenticated user or TenantContext.
- Policies must be applied (`can`/`authorize`) per action; resource controllers should call policy methods.
- Avoid cross-tenant joins; validate `property_id`, `meter_id`, etc., against the current tenant in services/repositories.

## Controllers & Routes
- Group under `routes/api.php` with middleware: `auth:sanctum` (or Passport), `throttle:api`, tenant context resolver if needed.
- Controllers stay thin: delegate business logic to services; data access to repositories; return `JsonResource`/resource collections with proper `meta`.
- Use route model binding with scopes where applicable (`->whereTenant(...)` or scoped bindings).

## Resources/Transformers
- Prefer `JsonResource` for consistent shapes. Hide internal IDs if not needed; include slugs/uuids when appropriate.
- Include related data via `whenLoaded` to avoid N+1; eager load in controllers/services.

## Error Handling & Logging
- Centralize exception-to-response mapping (e.g., in `app/Exceptions/Handler.php`), including validation, authorization, not found, and domain exceptions.
- Log server errors with context: `tenant_id`, `user_id`, request ID, route, payload size.

## Testing
- Pest feature tests per endpoint:
  - Auth + tenant isolation
  - Happy path + validation errors
  - Policy enforcement (403/404 masking when appropriate)
  - Pagination and filtering correctness
- Use factories with `forTenantId` helpers to keep data consistent.

## Related API Documentation

### Controller APIs
- **[Tariff Controller API](TARIFF_CONTROLLER_API.md)** - Tariff CRUD operations with versioning support
- **[Meter Reading Update Controller API](METER_READING_UPDATE_CONTROLLER_API.md)** - Meter reading corrections with audit trail
- **[Meter Reading API Controller API](METER_READING_CONTROLLER_API.md)** - JSON API for meter readings

### Service APIs
- **[Billing Service API](BILLING_SERVICE_API.md)** - Invoice generation and billing calculations
- **[Gyvatukas Calculator API](GYVATUKAS_CALCULATOR_API.md)** - Heating circulation fee calculations
- **[Invoice Finalization API](INVOICE_FINALIZATION_API.md)** - Invoice finalization workflow

### Observer APIs
- **[Meter Reading Observer API](METER_READING_OBSERVER_API.md)** - Draft invoice recalculation on reading correction

### Middleware APIs
- **[Middleware API](MIDDLEWARE_API.md)** - Authentication and authorization middleware

### Validation APIs
- **[Property Validation API](PROPERTY_VALIDATION_API.md)** - Property assignment validation

## When to Update This Doc
- Adding a new API surface or changing versioning/auth approach.
- Introducing new error codes or response envelopes.
- Adding new middleware for tenant context or throttling changes.
