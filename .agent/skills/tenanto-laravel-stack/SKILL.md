---
name: tenanto-laravel-stack
description: Use for Tenanto Laravel, Filament, Livewire, Blade, route, request, or test work when you need the current stack, validation, scoping, naming, localization, and money-handling conventions.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Laravel Stack

## Use This Skill When

- Starting a Tenanto coding session and you need the live repository baseline.
- Editing Laravel code under `app/`, `routes/`, `resources/`, `database/`, or `tests/`.
- Building or refactoring Filament resources/pages, Livewire components, Form Requests, actions, or support classes.

## Verified Stack

- Runtime PHP: `8.5.4`
- Composer PHP constraint: `^8.2`
- Laravel: `12`
- Filament: `5.3`
- Livewire: `4`
- Tailwind CSS: `4`
- Alpine.js: `3`
- Sanctum: `4`
- Pest: `4`
- PHPUnit: `12`

## Core Code Rules

- Every new or touched PHP file starts with `declare(strict_types=1);`.
- Validation lives in `app/Http/Requests/*`, never in inline controller, Filament page, or Livewire arrays.
- Livewire components must reuse Form Request rules instead of duplicating them:
  - Simple case: call `(new XxxRequest())->rules()`.
  - Context-sensitive case: instantiate the request and use `validatePayload(...)` or another request helper backed by `rules()`.
- Navigation labels, titles, and grouped UI labels use translation keys, not hard-coded English strings.
- Monetary calculations always use BCMath-backed services such as `App\Services\Billing\UniversalBillingCalculator`; do not use floats for business math.

## Query Scoping Rules

- The historical `HierarchicalScope` idea still defines the security model, but the live repo does not currently register an active `HierarchicalScope` class in `app/`.
- In current code, enforce the same behavior explicitly with model scopes and where clauses:
  - `forOrganization($organizationId)` for admin/manager organization scope
  - `forProperty($propertyId)` for property-bound tenant data
  - `where('tenant_user_id', auth()->id())` or equivalent self-owned record predicates for tenant-owned invoices/readings
- No cross-organization query is allowed unless the actor is explicitly checked as `SUPERADMIN`.

## Livewire and View Naming

- Livewire class names are `PascalCase` and map directly to mirrored Blade views.
- `app/Livewire/Tenant/SubmitReadingPage.php` renders `resources/views/livewire/tenant/submit-reading-page.blade.php`.
- `app/Livewire/Pages/Reports/ReportsPage.php` renders `resources/views/livewire/pages/reports/reports-page.blade.php`.
- `app/Livewire/Shell/GlobalSearch.php` renders `resources/views/livewire/shell/global-search.blade.php`.
- Filament page wrappers live under `app/Filament/Pages/*` and normally render `resources/views/filament/pages/*`.

## Filament and Livewire Conventions

- Keep Filament resources/pages thin; move write logic to `app/Filament/Actions/*`.
- Put shared read/query/presenter logic in `app/Filament/Support/*`.
- Prefer `#[Computed]` for Livewire data that should only resolve once per render.
- Do not query in Blade or in Livewire `render()` when a computed property or prepared payload fits.
- Keep table/resource queries explicit with `select([...])` and eager loading.

## Working Defaults

1. Check `docs/PROJECT-CONTEXT.md` if a pasted brief conflicts with the live repo.
2. Reuse existing request, action, support, and policy classes before introducing new abstractions.
3. Keep tenant boundaries explicit in every query.
4. Add or update Pest coverage for changed behavior.
5. Run focused tests first, then `vendor/bin/pint --dirty`.

## Completion Checklist

- [ ] `strict_types` added in touched PHP files
- [ ] Validation came from a Form Request
- [ ] Navigation text came from translation keys
- [ ] Query scope is organization/property/self constrained
- [ ] Money math used BCMath-backed services
- [ ] Livewire class/view naming stayed aligned
- [ ] Focused Pest verification ran
