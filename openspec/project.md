# Project Context

## Purpose
RentCounter is a multi-tenant utility billing and property management platform. It supports role-based operational workflows for `superadmin`, `admin`, `manager`, and `tenant` users, including metering, invoicing, subscriptions, and compliance/audit reporting.

## Tech Stack
- PHP 8.4, Laravel 12
- Livewire 3
- Tailwind CSS 4
- Filament 4 (currently installed in the codebase)
- Pest 3 / PHPUnit 11
- Laravel Sanctum 4

## Project Conventions

### Code Style
- Use `declare(strict_types=1);` in PHP files.
- Follow existing Laravel conventions and project naming patterns.
- Prefer Form Requests and policy/middleware-based authorization.
- Use Tailwind utility classes for custom Blade and Livewire interfaces.

### Architecture Patterns
- Role-segmented web route groups in `routes/web.php`.
- Multi-tenant isolation using `tenant_id` and `property_id` checks.
- Middleware-driven security chain (`auth`, role middleware, subscription checks, hierarchical access).
- Layered resources/controllers/services with Eloquent-first data access.

### Testing Strategy
- Prioritize feature tests for route access, role authorization, and UI endpoints.
- Add targeted unit tests for middleware/authorization logic where useful.
- Run the smallest relevant test scope first, then full suites when doing cross-cutting refactors.

### Git Workflow
- Keep changes scoped by feature/refactor.
- Do not include unrelated modifications in the same change.
- Validate routes/tests/formatting before finalizing.

## Domain Context
- `superadmin`: platform-wide management across organizations/tenants.
- `admin`: tenant/organization administration.
- `manager`: operational management under admin scope.
- `tenant`: end-user portal with property-scoped access.
- Subscription enforcement applies mainly to admin role; superadmin/manager/tenant have different bypass rules in current middleware.

## Important Constraints
- Preserve strict tenant isolation and prevent cross-tenant data access.
- Preserve tenant-specific UI and navigation as a distinct experience.
- Keep role-based authorization enforceable both at route and controller/policy layers.
- Custom UI must use Tailwind CSS and Livewire for interactive flows.

## External Dependencies
- Laravel authentication/session stack
- Livewire runtime for reactive server-driven UI
- Filament package (to be constrained or removed from web surface via approved changes)
