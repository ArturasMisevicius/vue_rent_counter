# Performance Audit — 2026-03-18

This audit was prepared from the live repository with a local SQLite fallback because the requested Laravel Boost MCP profiling tools were not available in the session.

## Verified cache behavior

- `DashboardCacheService::SUPERADMIN_STATS_TTL_SECONDS` = `60`
- `DashboardCacheService::ADMIN_STATS_TTL_SECONDS` = `30`
- `DashboardCacheService::TENANT_STATS_TTL_SECONDS` = `120`
- Dashboard cache keys are scoped by role, user id, locale, and organization version.

## Verified eager-loading coverage

- `InvoiceResource` uses `Invoice::forAdminWorkspace()` / `forTenantWorkspace()` which eager loads:
  - `property`
  - `property.building`
  - `tenant` for admin-like users
- `BuildingResource` uses `Building::forOrganizationWorkspace()` with `withCount()` for `properties` and `meters`.
- `PropertyResource` uses `Property::forOrganizationWorkspace()` with:
  - `building`
  - `currentAssignment`
  - `currentAssignment.tenant`
  - `assignments`
  - `assignments.tenant`
  - `meters_count`
- `TenantResource` uses `User::withTenantWorkspaceSummary()` with:
  - `currentPropertyAssignment`
  - `currentPropertyAssignment.property`
  - `currentPropertyAssignment.property.building`
- `MeterResource` uses `Meter::forOrganizationWorkspace()` with:
  - `property`
  - `property.building`
  - `latestReading`

## Verified Livewire computed usage

- `AdminDashboard`, `SuperadminDashboard`, and `TenantDashboard` use `#[Computed]` for their main dashboard payloads.
- No dashboard-adjacent Livewire component was found to be issuing database queries directly inside `render()` without a computed property boundary.

## Local EXPLAIN QUERY PLAN summary

### Admin dashboard stats query

- Primary lookup: `organizations` by primary key.
- Subqueries use indexes on:
  - `properties.organization_id`
  - `meters.organization_id`
  - `property_assignments.organization_id + unassigned_at`
  - `invoices.organization_id + status`
- After the index pass, the revenue subquery uses `invoices.organization_id + paid_at + id`.

### Superadmin organization list query

- Main scan uses the `organizations.name` index for ordering.
- Correlated count subqueries use:
  - `users.organization_id + role`
  - `properties.organization_id + building_id`
  - `subscriptions.organization_id`

### Invoice list query with filters

- Main lookup uses `invoices.organization_id + billing_period_start + id`.
- Building filter resolves through an indexed primary-key lookup on `properties`.

### Consumption report query

- Assignment query uses `property_assignments.organization_id + tenant_user_id`.
- Readings query uses `meter_readings.organization_id + reading_date + id`.
- The expensive part is row volume rather than query count because the current builder hydrates assignments, properties, meters, and readings before grouping in PHP.

### Outstanding balances report query

- After the index pass, SQLite uses `invoices.organization_id + billing_period_end + id`.
- The temporary B-tree for the default `billing_period_end, id` ordering no longer appears in the local plan.

## Resulting optimizations

- `RevenueReportBuilder` now loads the filtered invoice range once and groups months in PHP instead of issuing one invoice query per month.
- Added invoice reporting indexes for:
  - `organization_id + billing_period_end + id`
  - `organization_id + paid_at + id`
