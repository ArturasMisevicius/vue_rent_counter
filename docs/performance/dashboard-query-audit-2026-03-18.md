# Dashboard Query Audit — 2026-03-18

> **AI agent usage:** This is dated audit evidence. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then re-check the current code, schema, routes, logs, and tests before making claims or changes.

## Audit Method

- Requested Laravel Boost MCP tools were not available in this session.
- Used local Laravel fallbacks instead:
  - `php artisan db:table ...` for schema and index inspection
  - focused Pest performance tests for page-load query budgets
  - SQLite `EXPLAIN QUERY PLAN` against representative Eloquent-generated SQL

## Cache Verification

`[DashboardCacheService](/Users/andrejprus/Herd/tenanto/app/Filament/Support/Dashboard/DashboardCacheService.php)` now centralizes dashboard cache policy.

- Superadmin dashboard TTL: `60` seconds
- Admin and manager dashboard TTL: `30` seconds
- Tenant dashboard TTL: `120` seconds
- Cache keys are scoped with both role and user identity:
  - `dashboard:<segment>:role-<role>:user-<id>:locale-<locale>`
- Verified by tests in `[DashboardCacheServiceTest.php](/Users/andrejprus/Herd/tenanto/tests/Unit/Support/Dashboard/DashboardCacheServiceTest.php)`

## Eager Loading Verification

- `[InvoiceResource.php](/Users/andrejprus/Herd/tenanto/app/Filament/Resources/Invoices/InvoiceResource.php)` uses `Invoice::forAdminWorkspace()`, which eager loads `property`, `property.building`, and `tenant`.
- `[BuildingResource.php](/Users/andrejprus/Herd/tenanto/app/Filament/Resources/Buildings/BuildingResource.php)` uses `Building::forOrganizationWorkspace()`, which applies `withCount('properties')`; no additional relation eager load is needed for its scalar table columns.
- `[PropertyResource.php](/Users/andrejprus/Herd/tenanto/app/Filament/Resources/Properties/PropertyResource.php)` uses `Property::forOrganizationWorkspace()`, which eager loads `building`, `currentAssignment.tenant`, and meter counts.
- `[TenantResource.php](/Users/andrejprus/Herd/tenanto/app/Filament/Resources/Tenants/TenantResource.php)` uses `User::withTenantWorkspaceSummary()`, which eager loads `currentPropertyAssignment.property.building`.
- `[MeterResource.php](/Users/andrejprus/Herd/tenanto/app/Filament/Resources/Meters/MeterResource.php)` uses `Meter::forOrganizationWorkspace()`, which eager loads `property`, `property.building`, and `latestReading`.

## Livewire Verification

- `[HomeSummary.php](/Users/andrejprus/Herd/tenanto/app/Livewire/Tenant/HomeSummary.php)`, `[PropertyPage.php](/Users/andrejprus/Herd/tenanto/app/Livewire/Tenant/PropertyPage.php)`, and `[SubmitReadingPage.php](/Users/andrejprus/Herd/tenanto/app/Livewire/Tenant/SubmitReadingPage.php)` already used `#[Computed]` correctly.
- `[TenantHomePresenter.php](/Users/andrejprus/Herd/tenanto/app/Filament/Support/Tenant/Portal/TenantHomePresenter.php)` now routes tenant dashboard payloads through `DashboardCacheService`, so the tenant dashboard is both computed-per-render and cross-request cached for `120` seconds.
- `[InvoiceHistoryPage.php](/Users/andrejprus/Herd/tenanto/app/Livewire/Tenant/InvoiceHistoryPage.php)` no longer queries inside `render()`; invoices and payment guidance are now `#[Computed]`.
- `[LoginPage.php](/Users/andrejprus/Herd/tenanto/app/Livewire/Auth/LoginPage.php)` no longer queries inside `render()`; demo accounts are now `#[Computed]`.

## Query Plans

The plans below were captured from the current local SQLite schema before applying the new additive index migration `[2026_03_18_090000_add_dashboard_and_reporting_performance_indexes.php](/Users/andrejprus/Herd/tenanto/database/migrations/2026_03_18_090000_add_dashboard_and_reporting_performance_indexes.php)`.

### 1. Admin Dashboard Stats Query

Representative SQL:

```sql
select "id",
  (select count(*) from "properties" where "organizations"."id" = "properties"."organization_id") as "properties_count",
  (select count(*) from "meters" where "organizations"."id" = "meters"."organization_id") as "meters_count",
  (select count(*) from "invoices" where "organizations"."id" = "invoices"."organization_id") as "invoices_count",
  (select count(*) from "property_assignments" where "organizations"."id" = "property_assignments"."organization_id" and "unassigned_at" is null) as "active_tenants_count",
  (select count(*) from "invoices" where "organizations"."id" = "invoices"."organization_id" and "status" in ('draft', 'finalized', 'partially_paid', 'overdue')) as "pending_invoices_count",
  (select sum("invoices"."amount_paid") from "invoices" where "organizations"."id" = "invoices"."organization_id" and "paid_at" is not null and "paid_at" between '2026-03-01 00:00:00' and '2026-03-31 23:59:59') as "revenue_this_month"
from "organizations"
where "organizations"."id" = ?
```

Plan highlights:

- `SEARCH organizations USING INTEGER PRIMARY KEY`
- `SEARCH properties USING COVERING INDEX properties_organization_id_building_id_index`
- `SEARCH meters USING COVERING INDEX meters_organization_id_property_id_index`
- `SEARCH invoices USING COVERING INDEX invoices_organization_id_status_index`
- `SEARCH property_assignments USING INDEX property_assignments_organization_id_tenant_user_id_index`

Finding:

- The aggregation query shape is good after consolidating the dashboard metrics into one organization snapshot query.
- `property_assignments` still relied on an organization-plus-tenant index for `current()` rows, so a better `organization_id, unassigned_at` index was added in the new migration.

### 2. Superadmin Organization List Query

Representative SQL:

```sql
select "id", "name", "slug", "status", "owner_user_id", "created_at", "updated_at",
  (select count(*) from "users" where "organizations"."id" = "users"."organization_id") as "users_count",
  (select count(*) from "properties" where "organizations"."id" = "properties"."organization_id") as "properties_count",
  (select count(*) from "subscriptions" where "organizations"."id" = "subscriptions"."organization_id") as "subscriptions_count"
from "organizations"
order by "name" asc, "id" asc
limit 15
```

Plan highlights:

- `SCAN organizations USING INDEX organizations_name_index`
- `SCAN users`
- `SEARCH properties USING COVERING INDEX properties_organization_id_building_id_index`
- `SCAN subscriptions`

Finding:

- The correlated counts for `users` and `subscriptions` were doing full scans because neither table had an organization index.
- Added `users (organization_id, role)` and `subscriptions (organization_id)` indexes in the new migration.

### 3. Invoice List Query

Representative SQL:

```sql
select "id", "organization_id", "property_id", "tenant_user_id", "invoice_number",
  "billing_period_start", "billing_period_end", "status", "currency", "total_amount",
  "amount_paid", "paid_amount", "due_date", "finalized_at", "paid_at",
  "payment_reference", "items", "notes", "document_path", "created_at", "updated_at"
from "invoices"
where "organization_id" = ?
order by "billing_period_start" desc, "id" desc
limit 15
```

Plan highlights:

- `SEARCH invoices USING INDEX invoices_organization_id_status_index`
- `USE TEMP B-TREE FOR ORDER BY`

Finding:

- The list page was sorting after filtering by organization because no matching composite sort index existed.
- Added `invoices (organization_id, billing_period_start, id)`.
- Note: the current invoice table does not yet define explicit Filament filters, so this plan documents the base list query rather than a filtered variant.

### 4. Consumption Report Query

Representative SQL:

```sql
select "id", "organization_id", "property_id", "meter_id", "reading_value", "reading_date", "validation_status"
from "meter_readings"
where "organization_id" = ?
  and "reading_date" between '2026-03-01' and '2026-03-31'
  and exists (
    select * from "meters"
    where "meter_readings"."meter_id" = "meters"."id"
      and "type" = 'water'
  )
order by "reading_date" desc, "id" desc
```

Plan highlights:

- `SEARCH meter_readings USING INDEX meter_readings_organization_id_property_id_index`
- `SEARCH meters USING INTEGER PRIMARY KEY`
- `USE TEMP B-TREE FOR ORDER BY`

Finding:

- The existing `meter_readings` indexes did not cover the organization-plus-date sort path used by the report.
- Added `meter_readings (organization_id, reading_date, id)`.

### 5. Outstanding Balances Report Query

Representative SQL:

```sql
select "id", "organization_id", "property_id", "tenant_user_id", "invoice_number",
  "status", "currency", "total_amount", "amount_paid", "paid_amount", "due_date"
from "invoices"
where "organization_id" = ?
  and "due_date" between '2026-03-01' and '2026-03-31'
  and "status" not in ('paid', 'void')
order by "due_date" asc, "id" asc
```

Plan highlights:

- `SEARCH invoices USING INDEX invoices_organization_id_status_index`
- `USE TEMP B-TREE FOR ORDER BY`

Finding:

- The report wanted due-date ordering, but SQLite had to materialize a temp sort.
- Added `invoices (organization_id, due_date, id)`.
- Also corrected the query builder to `reorder()` before applying due-date sorting so it no longer carries the admin workspace's default billing sort.

## Query Delta

- Warm admin dashboard page load previously failed the regression budget at `19` queries.
- The new regression guard now enforces:
  - admin dashboard `< 10` queries
  - superadmin dashboard `< 15` queries
- Verified by `[DashboardPerformanceTest.php](/Users/andrejprus/Herd/tenanto/tests/Performance/DashboardPerformanceTest.php)`.

## Caveats

- Boost MCP `database-query`, `database-schema`, and `browser-logs` were not available in this Codex session, so this audit used local framework tooling instead.
- The query plans above were captured against the currently migrated local SQLite database before applying the new additive index migration to the local file DB.
- Focused Pest verification covers the new cache service, admin and superadmin dashboards, tenant home, login demo accounts, and reports. A full `php artisan test` run was not executed in this pass.
