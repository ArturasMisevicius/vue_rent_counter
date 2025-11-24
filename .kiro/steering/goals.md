# Product Goals

## North Star

Enable Vilnius‑style residential utilities management with a multi‑tenant Laravel backbone that lets superadmins, property owners, managers, and tenants work from a single source of truth, while meter readings, tariffs, gyvatukas calculations, and invoices are captured accurately and displayed through predictable Blade + Filament surfaces.

## Objectives (12-week horizon)

- Complete the Filament admin surface (`Property`, `Building`, `Meter`, `MeterReading`, `Invoice`, `Tariff`, `Provider`, `User`, `Subscription` resources) with tenant‑aware forms, bulk actions, and checkout guarding via `BelongsToTenant` and policies.
- Harden utility billing pipelines: finalize `BillingService`, `TariffResolver`, `GyvatukasCalculator`, and `MeterReadingObserver` so that tariffs and gyvatukas norms are snapshotted in invoices and recalculated safely for draft invoices.
- Expand tenant manager UX (Blade layouts + Alpine/Tailwind) so tenants see their property, meter, and invoice history with downloadable PDFs and actionable status badges.
- Cement multi‑tenant invariants: run Pest + property tests, enforce `TenantContext`, and document `test:setup`/seeders for reproducible data states across tenants.
- Monitor operations: capture subscription health in the superadmin dashboard, validate WAL + Spatie backup runs, and keep audit trails for meter/invoice changes and tenant reassignments.
- Keep documentation, specs, and `.kiro/specs/*` aligned with implementation progress, especially for `vilnius-utilities-billing`, `filament-admin-panel`, and `hierarchical-user-management`.

## Success Metrics

- Invoices snapshot tariffs and gyvatukas logic 100% of the time; no finalized invoice is recomputed when a tariff changes, and `BillingService` items match audited meter readings.
- Meter readings submitted through managers or tenants validate monotonically and produce audit records on every edit; <2% rollbacks due to bad input.
- Tenant isolation: every authenticated tenant, manager, and admin request returns only their tenant_id rows (as verified by multi‑tenancy property tests and `TenantScope` smoke tests).
- Subscription continuity: superadmin dashboard flags all accounts expiring within 14 days, and `SubscriptionService` enforces seat limits for tenant creation without downtime.
- Ops resilience: WAL mode plus `spatie/laravel-backup` completes nightly backups with retention, and production deployments run `php artisan migrate --force` plus cache optimizations without regressions.

## Guardrails / Anti-Goals

- No headless SPA rewrites; Blade/Filament powered by CDN Tailwind + Alpine remains the single-page experience.
- Avoid exposing cross-tenant data; always respect `BelongsToTenant`, `TenantContext`, and authorization policies before returning records.
- Don’t add payment gateways, billing reconciliation, or marketplace features—focus remains on meter-driven invoicing and tenant workflows.
- Keep migrations additive and reversible; destructive schema changes must ship with explicit backfill/rollback stories.
- No demo-mode leak of sensitive data; seeders and notifications keep credentials static and sanitized.


- always use MCP servers, use mcp services