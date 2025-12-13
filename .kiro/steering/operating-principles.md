# Operating Principles

## Engineering

- Lean on Filament resources, Blade components, and reusable traits (`BelongsToTenant`, `TenantScope`, `TenantContext`) so every request is scoped to a tenant before it hits the `BillingService`, `GyvatukasCalculator`, or `MeterReadingObserver`.
- Keep models final with strict typing, capture invariants via ValueObjects (`BillingPeriod`, `ConsumptionData`, `InvoiceItemData`), and revisit complex calculations with dedicated services instead of inline logic.
- Follow the Laravel pattern: validation lives in FormRequests, authorization in Policies, and data access via scopes/services (e.g., `TenantScope`, `SubscriptionService`, `GyvatukasCalculator`).
- Prefer composable Blade components for cards, data tables, badges, modals, and forms so that Filament and tenant-facing views reuse the same building blocks.
- Guard optimistic UI hints with explicit error/fallback states—especially when recalculating invoices after meter reading edits or when `MeterReadingObserver` flushes drafts.

## Delivery & Review

- Every PR includes Pest suites (Feature, Unit, Filament folder) plus targeted property-based tests that exercise tenancy, tariff snapshotting, gyvatukas math, and authorization invariants.
- Run `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse`, `php artisan test` (or `composer test`), and `php artisan test:setup --fresh` locally when changing seeders or user flows; treat warnings as blockers.
- Document the change in docs/ (frontend, routes, reviews) and `.kiro/specs/*` (especially `filament-admin-panel`, `hierarchical-user-management`, and `vilnius-utilities-billing`) so requirements stay aligned.
- Keep change sets small and describe rollout implications (backups, tenant switches, gyvatukas recalculations) in PR descriptions.

## Accessibility & UX

- Tailwind + Alpine delivered via CDN keep markup lightweight; every interactive state (filters, meter reading forms, invoices) exposes clear labels, error states, and focus outlines.
- Blade components include ARIA attributes and descriptive helper text; Filament panels reuse those patterns to keep the admin experience consistent.
- Keep data tables searchable/sortable, preserve query parameters for filters/search, and use keyboard-friendly modals for meter entries and invoice details.
- Optimize tenant dashboards for clarity—status badges, stat cards, and invoice breakdowns highlight actionable numbers and make locale switches straightforward.

## Security

- Enforce authorization using Policies across Filament resources and controllers so only the appropriate role (superadmin, admin, manager, tenant) can mutate data; combine with `tenant_id` filtering to prevent accidental cross-tenant leaks.
- Regenerate sessions on login/logout, set CSP headers via `config/session`/`config/billing`, and keep demo/test seeds sanitized (`TestUsersSeeder`, `HierarchicalUsersSeeder`).
- Use Spatie backup + WAL mode to keep SQLite/MySQL data durable; capture tenant switch events through `TenantContext` to log guardrails for superadmin overrides.
- Audit-sensitive operations (meter readings edits, invoices finalization, tenant reassignments) in observers/notifications so every change gets a traceable record.

## Operations

- Production deploys run migrations + `php artisan optimize` plus `php artisan config:cache`; run `php artisan pail` or equivalent log tailing when debugging queue/workers.
- Respect tenant quotas enforced by `SubscriptionService`; send `SubscriptionExpiryWarningEmail` when expiry is near and gracefully enter read-only mode when a subscription lapses.
- Keep the seeding pipeline deterministic (`TestDatabaseSeeder`, specialized seeders for buildings/properties/meters/invoices) so `php artisan test:setup --fresh` is reliable for CI/CD.
- Monitor background tasks like meter-reading imports or invoice exports with queue/worker health checks; document any manual reminders in `.kiro/scripts` or docs/reviews.


- always use MCP servers, use mcp services