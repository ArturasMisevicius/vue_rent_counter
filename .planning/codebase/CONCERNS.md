# Codebase Concerns Map

Generated for focus area `concerns` on 2026-03-31.

This document highlights active technical concerns in the repository and is meant to be used as a planning reference. It prioritizes areas where a small change can have a wide blast radius, where the current design depends on conventions rather than guarantees, or where tooling leaves important failure modes unguarded.

## Executive Summary

The main risk concentration is in the billing and reporting path. Core business rules are spread across large services, report builders, page classes, jobs, and exports, with a mix of direct database shaping, manual scoping, and queued side effects. The tenant boundary is actively tested, but it is still enforced mostly through opt-in query scopes and per-surface authorization checks instead of a single unavoidable abstraction.

The repository also has planning friction from stale or conflicting guidance files, legacy `_old/` source trees, limited CI coverage, and inconsistent code conventions. None of these are immediate production failures on their own, but together they raise the cost of upgrades, refactors, and onboarding.

## Priority Concerns At A Glance

| Priority | Concern | Key files |
| --- | --- | --- |
| High | Billing and invoice logic concentrated in large service objects | `app/Services/Billing/BillingService.php`, `app/Services/Billing/InvoiceService.php`, `app/Services/ProjectService.php` |
| High | Tenant and organization isolation depend on manual scoping and per-page authorization | `app/Models/Invoice.php`, `app/Models/Property.php`, `app/Models/Attachment.php`, `app/Models/Comment.php`, `app/Filament/Resources/Invoices/InvoiceResource.php`, `app/Filament/Resources/Projects/ProjectResource.php` |
| High | Reporting path materializes whole datasets and serializes exports into queued jobs | `app/Livewire/Pages/Reports/ReportsPage.php`, `app/Filament/Support/Admin/Reports/ConsumptionReportBuilder.php`, `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php`, `app/Services/ScheduledExportService.php`, `app/Jobs/GenerateAdminReportExportJob.php` |
| High | CI and static analysis leave major blind spots | `.github/workflows/phase-1-guardrails.yml`, `composer.json`, `package.json` |
| Medium | Hidden side effects in observers and service methods make behavior context-dependent | `app/Observers/PropertyAssignmentObserver.php`, `app/Observers/UserObserver.php`, `app/Observers/OrganizationUserObserver.php`, `app/Observers/ProjectObserver.php` |
| Medium | Tooling and instruction files disagree about framework/runtime versions | `composer.json`, `README.md`, `CLAUDE.md`, `.cursor/rules/laravel-boost.mdc` |
| Medium | Legacy `_old/` tree and duplicated abstractions increase maintenance noise | `_old/routes/console.php`, `_old/app/Filament/...`, `app/Filament/Pages/Reports.php`, `app/Livewire/Pages/Reports/ReportsPage.php` |

## 1. High-Risk Business Hotspots

### 1.1 Billing and invoice orchestration is centralized in very large classes

- `app/Services/Billing/BillingService.php` is a large orchestration class that handles previewing, generating, drafting, finalizing, payments, assignment lookup, eligibility checks, line-item building, and shared-cost distribution.
- `app/Services/Billing/InvoiceService.php` owns draft mutation, generated invoice creation, finalization, payment recording, audit logging, cache invalidation, and domain events.
- `app/Services/ProjectService.php` similarly combines creation, status changes, approvals, passthrough invoice item creation, cost recalculation, completion recalculation, notifications, and audit behavior.

Why this is a concern:

- These classes are business-critical and already mix persistence, policy assumptions, formatting, notifications, audit trails, and side-effect triggering.
- Refactoring one rule often means touching several methods inside one large file, which raises regression risk.
- Cross-domain behavior is embedded directly in services instead of being isolated into smaller, named workflows.

Planning note:

- Start by extracting the highest-risk operations behind narrower service boundaries: invoice generation, invoice finalization/payment, project approval/status transition, and project cost passthrough.

### 1.2 Side effects are not handled consistently after database commits

- `app/Services/Billing/InvoiceService.php` uses `DB::afterCommit(...)` for cache refreshes and `InvoiceFinalized` event dispatch.
- `app/Services/ProjectService.php` sends notifications inside transactions in `create()` and `approve()`.

Why this is a concern:

- The billing path protects external side effects from rollbacks, but the project path does not follow the same discipline.
- That inconsistency makes failure behavior surprising and increases the chance of sending alerts for state that never committed.

Planning note:

- Normalize the rule: database mutation first, external side effects only after commit.

## 2. Coupling And Fragile Architecture Boundaries

### 2.1 Tenant and organization isolation are mostly opt-in, not structural

- Models such as `app/Models/Invoice.php`, `app/Models/Property.php`, `app/Models/Attachment.php`, `app/Models/Comment.php`, and `app/Models/SecurityViolation.php` expose `scopeForOrganization(...)` style helpers rather than enforcing tenant or organization scoping globally.
- Resources such as `app/Filament/Resources/Invoices/InvoiceResource.php` and `app/Filament/Resources/Projects/ProjectResource.php` manually compute the current user or organization and then choose the right query path.
- Superadmin relation resources explicitly remove scopes in `app/Filament/Resources/Attachments/AttachmentResource.php` and `app/Filament/Resources/Comments/CommentResource.php`.

Why this is a concern:

- Correctness depends on every new query remembering to apply the right scope.
- The security model is understandable today, but it is easy to weaken accidentally in future pages, relation managers, widgets, jobs, or exports.
- Superadmin paths have to carefully bypass organization filtering while all other paths must preserve it, which raises the chance of accidental overexposure.

Evidence that the risk is known but still manual:

- `tests/Feature/Security/TenantPortalIsolationTest.php`
- `tests/Feature/Tenant/TenantAccessIsolationTest.php`
- `tests/Feature/Security/WorkspaceContextResolutionTest.php`
- `tests/Feature/Architecture/WorkspaceBoundaryInventoryTest.php`

Planning note:

- Reduce the number of places that decide scope independently. A shared query policy or repository layer for tenant/admin/superadmin read models would lower drift.

### 2.2 Resource authorization is repeated across many Filament classes

- `app/Filament/Resources/Invoices/InvoiceResource.php` and `app/Filament/Resources/Projects/ProjectResource.php` both implement nearly identical `currentUser()`, `allows()`, and query-selection patterns.
- Many tables and pages apply additional record-level authorization in-place, for example `app/Filament/Resources/Invoices/Tables/InvoicesTable.php`, `app/Filament/Resources/Tenants/TenantResource.php`, and `app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`.

Why this is a concern:

- The same security and visibility logic is distributed across resource classes, tables, pages, and actions.
- It is hard to prove that two resources use the same authorization rules, because the logic is not expressed in one place.

Planning note:

- Consolidate repeated resource authorization/query patterns into shared Filament concerns or dedicated read-model/query services.

### 2.3 UI routing is layered through wrappers and aliases

- `app/Filament/Pages/Reports.php` is a thin Filament wrapper around `app/Livewire/Pages/Reports/ReportsPage.php`.
- Tenant navigation uses route aliases in `routes/web.php` that point to `app/Livewire/Tenant/TenantPortalRouteEndpoint.php`, which then redirects into Filament page routes.
- Tenant Filament pages such as `app/Filament/Pages/TenantDashboard.php`, `app/Filament/Pages/TenantInvoiceHistory.php`, `app/Filament/Pages/TenantSubmitMeterReading.php`, and `app/Filament/Pages/TenantPropertyDetails.php` repeat access checks already present in `app/Filament/Pages/TenantPortalPage.php`.

Why this is a concern:

- Navigation, route access, page access, and workspace resolution live in different layers.
- Small route changes are more likely to break indirect entrypoints, aliases, or page-registration assumptions.

Planning note:

- Collapse redundant wrappers where possible and remove duplicated `canAccess()` implementations when the base class already enforces the rule.

## 3. Security And Data-Exposure Concerns

### 3.1 Public CSP reporting is deliberately open and writes to the database

- `routes/web.php` exposes `security.csp.report` publicly and disables CSRF for it.
- `app/Livewire/Security/CspViolationReportEndpoint.php` converts each accepted report into a `SecurityViolation` record.
- `app/Http/Requests/Security/CspViolationRequest.php` validates payload shape, and `app/Providers/AppServiceProvider.php` rate-limits the route to 10 per minute per IP.

Why this is a concern:

- This is a legitimate pattern, but it is still a public write endpoint.
- It can become a storage or noise amplifier if browsers, extensions, or malicious clients spam the route.
- The current reaction path in `app/Services/Security/SecurityMonitoringService.php` logs alerts, but does not escalate them beyond logs/cache-based suppression.

Planning note:

- Track retention volume and alerting quality. If CSP reporting becomes noisy in production, add stronger normalization, sampling, or upstream filtering.

### 3.2 Filesystem export helpers use permissive directory creation and local-path attachments

- `app/Filament/Support/Superadmin/Translations/TranslationCatalogService.php` creates directories with `0777` and writes translation files directly.
- `app/Filament/Support/Superadmin/Exports/OrganizationDataExportBuilder.php` and `app/Filament/Support/Superadmin/Exports/NullOrganizationDataExportBuilder.php` create export directories with `0777` and build ZIP files on local disk.
- `app/Notifications/Superadmin/OrganizationDataExportReadyNotification.php` attaches the local export path directly to mail.

Why this is a concern:

- World-writable directory creation is broader than necessary.
- Export and translation flows are tightly coupled to local filesystem semantics.
- Sensitive export artifacts can linger in storage unless explicit cleanup is performed; `tests/Feature/Superadmin/OrganizationExportsTest.php` manually unlinks generated exports during tests, which is a signal that lifecycle management is manual.

Planning note:

- Standardize export file permissions, retention, and cleanup policies before these flows grow further.

### 3.3 KYC and invoice downloads rely on endpoint-specific authorization, not shared download infrastructure

- `app/Livewire/Kyc/ShowKycAttachmentEndpoint.php` performs inline access checks.
- `app/Livewire/Tenant/DownloadInvoiceEndpoint.php` delegates to `app/Filament/Actions/Tenant/Invoices/DownloadInvoiceAction.php`, which uses `Gate::authorize('download', $invoice)`.
- `app/Livewire/Tenant/InvoiceHistory.php` loads an invoice first and authorizes the download afterward.

Why this is a concern:

- The current behavior is correct and tested, but download authorization is handled per endpoint/action rather than through one centralized file-delivery policy abstraction.
- Future download surfaces can drift if they skip one of the existing patterns.

Relevant coverage:

- `tests/Feature/Security/TenantPortalIsolationTest.php`
- `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`
- `tests/Feature/Admin/KycProfilesResourceTest.php`
- `tests/Feature/Livewire/ControllerRouteMigrationTest.php`

## 4. Performance And Scale Hotspots

### 4.1 Reports are built in memory and then re-packaged for pagination and export

- `app/Livewire/Pages/Reports/ReportsPage.php` computes `report()` as arrays, then `rows()` paginates an in-memory collection with `forPage(...)`.
- `app/Filament/Support/Admin/Reports/ConsumptionReportBuilder.php` and `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php` fetch full grouped result sets via `->get()` and then reshape them into arrays.
- `app/Services/ScheduledExportService.php` dispatches `app/Jobs/GenerateAdminReportExportJob.php` with full `$summary`, `$columns`, and `$rows` arrays serialized into the job payload.

Why this is a concern:

- Pagination happens after the full result set is already loaded.
- Large organizations or wider reporting ranges can turn the report page and export queue into memory-heavy operations.
- Queue payload size will grow with report size because the export job receives the already-built rows instead of a compact query descriptor.

Planning note:

- Convert report generation toward streamed or query-backed pagination and pass filter state to export jobs instead of materialized rows.

### 4.2 Bulk invoice generation eagerly loads deep relation graphs and loops per assignment

- `app/Services/Billing/BillingService.php` loads assignments with nested `tenant`, `property`, `serviceConfigurations`, `utilityService`, `tariff`, `meters`, and meter `readings`, then iterates through them to build line items.
- The same class repeats similar eager-loading logic in both `invoiceCandidates(...)` and `invoiceAssignment(...)`.

Why this is a concern:

- This is efficient for small batches, but batch size and billing-period length directly increase memory pressure.
- The line-item calculation path mixes data access and pricing logic, which makes it harder to optimize query shape separately from billing rules.

Relevant tests:

- `tests/Unit/Services/BillingServiceTest.php`
- `tests/Feature/Billing/BillingModuleTest.php`
- `tests/Feature/Billing/BillingPreviewFinalizationParityTest.php`

Planning note:

- Split “fetch billing candidates” from “compute billable totals,” then profile the candidate loader independently.

### 4.3 Some dashboard widgets still run repeated uncached aggregate queries

- `app/Filament/Widgets/Superadmin/PlatformStatsOverview.php` runs separate counts and sums every poll cycle.
- `app/Filament/Widgets/Superadmin/PropertiesAndManagersStatsOverview.php` performs several independent counts every 60 seconds.
- `app/Filament/Widgets/Reports/MeterComplianceStatusChart.php` rebuilds report rows and then counts categories from the in-memory collection.

Why this is a concern:

- The repository already has query-budget protection in `tests/Performance/DashboardPerformanceTest.php`, but the risk is concentrated in polled widgets that combine global aggregates with frequent refresh.
- These widgets are fine now, but they are the first places likely to regress as the data set grows.

## 5. Test Coverage And CI Blind Spots

### 5.1 CI only enforces a subset of the actual quality surface

- `.github/workflows/phase-1-guardrails.yml` is the only visible CI workflow in the repository.
- `composer.json` defines `guard:phase1`, which runs `pint --test` and a selected subset of tests.
- `composer.json` does not include `phpstan` or `larastan`, and no `phpstan.neon` or similar config is present at the repository root.
- `package.json` has only `build` and `dev`; there is no frontend lint or test script.

Why this is a concern:

- A large amount of repository behavior is covered by tests locally, but not all of it is enforced in CI.
- Static analysis is currently a process expectation, not a repository-enforced gate.
- Frontend and asset regressions are only guarded by successful build output.

Planning note:

- Add staged CI layers: static analysis, critical feature suite, then optional full suite.

### 5.2 Service-level test coverage is uneven across critical modules

- Direct unit service tests are sparse: `tests/Unit/Services/BillingServiceTest.php` and `tests/Unit/Services/SecurityMonitoringServiceTest.php` are the only visible `tests/Unit/Services/*.php` files.
- There is no direct `InvoiceService` unit test for `app/Services/Billing/InvoiceService.php`.
- `app/Services/Billing/InvoicePdfService.php` is covered indirectly through `tests/Feature/Tenant/InvoicePdfLocalizationTest.php` and `tests/Feature/Tenant/InvoiceExplainabilityContractTest.php`, not through dedicated service tests.
- Report builders are partially guarded by `tests/Feature/Billing/ReportsTest.php` and `tests/Feature/Architecture/ReportBuildersNoRawSqlTest.php`, but not by dedicated builder-level regression suites per builder.

Why this is a concern:

- The most complex state-mutation services are not tested at the same granularity as their risk level.
- Indirect coverage makes failures harder to localize when a refactor breaks behavior.

### 5.3 Some “coverage” tests assert inventory, not deep behavior

- `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php` verifies that every resource is mapped to a regression test file and that expected pages exist.
- Shared superadmin relation-resource coverage is concentrated in files such as `tests/Feature/Superadmin/RelationCrudResourcesTest.php`, `tests/Feature/Superadmin/RelationResourceListContextTest.php`, and `tests/Feature/Superadmin/FinanceRelationResourceListContextTest.php`.

Why this is a concern:

- Inventory tests are useful guardrails, but they do not prove that every resource has deep create/view/edit/delete behavior or authorization coverage.
- It is possible for a resource to remain “covered” in inventory while only receiving shallow list-context assertions.

## 6. Upgrade Friction

### 6.1 Repository instructions disagree about the actual stack

- `composer.json` requires `laravel/framework` `^13.0`.
- `README.md` describes the verified stack as Laravel `13.2.0`, Filament `5`, Livewire `4`, Tailwind `4`, Pest `4`, PHPUnit `12`.
- `CLAUDE.md` still describes Laravel `12` as foundational context.
- `.cursor/rules/laravel-boost.mdc` still references Filament `4`, Livewire `3`, Pest `3`, and PHP `8.4.16`.

Why this is a concern:

- Humans and coding agents will make different assumptions depending on which instruction file they read first.
- Upgrade and implementation guidance can drift away from the real runtime.

Planning note:

- Treat instruction-file alignment as first-class maintenance work, not documentation cleanup.

### 6.2 Report builders still depend on manual SQL expressions and driver branching

- `app/Filament/Support/Admin/Reports/ConsumptionReportBuilder.php` and `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php` use `Illuminate\Database\Query\Expression` and manual join wiring.
- `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php` branches on driver name for month formatting.
- `tests/Feature/Architecture/ReportBuildersNoRawSqlTest.php` only prevents obvious `DB::raw(...)` and `selectRaw(...)` usage; it does not eliminate manual SQL expression complexity.

Why this is a concern:

- Schema changes and database portability work will hit these builders first.
- The guardrail test gives a false sense of abstraction if the real query complexity still lives in expression strings.

### 6.3 Migration history shows rapid schema evolution and corrective follow-ups

- The repository has a dense migration history in `database/migrations/`, including broad domain creation on `2026_03_17_*`, performance/index patches on `2026_03_18_*` and `2026_03_19_*`, contract changes on `2026_03_24_*`, and project expansion on `2026_03_28_*`.
- Examples include `database/migrations/2026_03_18_090000_add_dashboard_and_reporting_performance_indexes.php`, `database/migrations/2026_03_24_060000_update_buildings_and_properties_for_admin_contract.php`, and `database/migrations/2026_03_28_180000_expand_projects_module_tables.php`.

Why this is a concern:

- This pattern suggests active correction and expansion of the data model.
- Future upgrades will need strong migration review because reporting, billing, and workspace boundaries are tightly bound to schema details.

## 7. Maintenance Hazards And Cleanup Targets

### 7.1 The `_old/` tree is still present and contains stale operational guidance

- `_old/routes/console.php` contains schedules for backup, export cleanup, subscription monitoring, and cache warming that are not part of the active app schedule in `routes/console.php`.
- `_old/app/...` and `_old/resources/...` still contain TODOs and historical Filament code, for example `_old/app/Filament/Clusters/SuperAdmin/Resources/AuditLogResource/Pages/ListAuditLogs.php` and `_old/resources/views/vendor/filament/components/section/index.blade.php`.

Why this is a concern:

- Search results mix active and inactive implementations.
- New contributors and automated tools can mistake archived behavior for live behavior.

Planning note:

- Either remove `_old/` from the main working tree or make its archive status impossible to misread.

### 7.2 Observer side effects are hidden and not uniformly covered

- `app/Observers/PropertyAssignmentObserver.php` mutates `users.organization_id` when assignments are saved.
- `app/Observers/UserObserver.php` and `app/Observers/OrganizationUserObserver.php` reset manager permissions using `auth()->user()` as actor context.
- `app/Observers/ProjectObserver.php` synchronizes legacy columns, validates scope, blocks deletion, and dispatches `RescopeProjectChildrenJob`.

Why this is a concern:

- Persistence side effects are triggered by saving models, not by explicit domain actions.
- Runtime behavior depends partly on whether a request has an authenticated user.
- Observer coverage is uneven; `tests/Feature/Models/PropertyAssignmentObserverTest.php` exists, but there are no same-name model observer tests for `UserObserver`, `OrganizationUserObserver`, `ProjectObserver`, `OrganizationObserver`, or `SubscriptionObserver`.

### 7.3 Export and report helpers overlap in responsibility

- `app/Services/ExportService.php` and `app/Services/PdfReportService.php` both wrap `ReportPdfExporter` and both provide streaming/export concerns.
- `app/Services/ScheduledExportService.php` is a thin job dispatcher over `app/Jobs/GenerateAdminReportExportJob.php`.
- `app/Filament/Support/Dashboard/DashboardCacheService.php` is only a wrapper subclass around `app/Services/DashboardCacheService.php`.

Why this is a concern:

- Thin wrappers and overlapping services make the service graph harder to reason about than the actual logic warrants.
- This increases refactor cost because it is not always obvious which class is the true ownership point.

### 7.4 Code conventions are still mixed in active app code

- Some active files use strict typing and modern declarations, such as `app/Services/ProjectService.php`, `app/Services/Billing/InvoiceService.php`, and `app/Livewire/Pages/Reports/ReportsPage.php`.
- Other active files do not declare strict types, for example `app/Models/Invoice.php`, `app/Models/Property.php`, `app/Models/Organization.php`, `app/Providers/AppServiceProvider.php`, `app/Providers/AuthServiceProvider.php`, and several observer classes.

Why this is a concern:

- Mixed conventions make automated modernization and static analysis adoption harder.
- It becomes difficult to know which coding expectations are aspirational versus enforced.

## Suggested Planning Order

1. Stabilize the billing/reporting core: reduce responsibility in `app/Services/Billing/BillingService.php`, `app/Services/Billing/InvoiceService.php`, and `app/Livewire/Pages/Reports/ReportsPage.php`.
2. Centralize tenant/organization scoping so resources and widgets are not deciding isolation independently.
3. Upgrade CI from “subset guardrails” to “critical-path confidence,” starting with static analysis and a broader required suite.
4. Align stack guidance in `composer.json`, `README.md`, `CLAUDE.md`, and `.cursor/rules/laravel-boost.mdc`.
5. Remove or quarantine `_old/` to reduce false positives during maintenance.
6. Clean up observer side effects and move cross-entity mutations into explicit workflows where possible.
