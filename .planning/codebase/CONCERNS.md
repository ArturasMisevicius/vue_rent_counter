# Codebase Concerns

**Analysis Date:** 2026-03-19

## Tech Debt

**Repository snapshot documents drift from the live tree:**
- Issue: `AGENTS.md` and `docs/PROJECT-CONTEXT.md` describe `app/Providers/Filament/AdminPanelProvider.php`, 27 Livewire components, and 84 test files, while the repository currently exposes `app/Providers/Filament/AppPanelProvider.php`, 30 PHP files under `app/Livewire/`, and 110 PHP test files under `tests/`.
- Files: `AGENTS.md`, `docs/PROJECT-CONTEXT.md`, `app/Providers/Filament/AppPanelProvider.php`, `app/Livewire/`, `tests/`
- Impact: future planning, automation, and repo navigation can target the wrong panel provider and underestimate active surface area.
- Fix approach: regenerate the workspace snapshot from the live repository whenever provider names, Livewire counts, or test inventory changes.
- Warning: verify current provider and feature counts against `app/Providers/Filament/`, `app/Livewire/`, and `tests/` before building on documented assumptions.

**Tenant and organization boundaries are enforced query-by-query instead of through one central guardrail:**
- Issue: tenant scoping is spread across model scopes, Filament resource `getEloquentQuery()` overrides, policy checks, and ad hoc authorization in actions/pages instead of one mandatory tenant boundary layer.
- Files: `app/Models/User.php`, `app/Models/Invoice.php`, `app/Filament/Resources/Invoices/InvoiceResource.php`, `app/Filament/Resources/Properties/PropertyResource.php`, `app/Filament/Resources/Tenants/TenantResource.php`, `app/Livewire/Tenant/InvoiceHistory.php`, `app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- Impact: future features can accidentally bypass isolation by adding an unscoped query, a `findOrFail()`, or a new relation path that never applies the workspace scope.
- Fix approach: centralize workspace resolution in reusable query builders or mandatory scope helpers, and require tenant-aware policy coverage for all new tenant-facing reads and writes.
- Warning: every new Filament resource, page action, relation manager, and Livewire component touching tenant data should start from an existing scoped builder rather than `parent::getEloquentQuery()` or a naked model query.

**Billing behavior is concentrated in large orchestration services:**
- Issue: billing generation, invoice preview, finalization, shared-service distribution, and pricing logic are packed into a few oversized service classes.
- Files: `app/Services/Billing/BillingService.php`, `app/Services/Billing/InvoiceService.php`, `tests/Unit/Services/BillingServiceTest.php`, `tests/Feature/Billing/BillingModuleTest.php`
- Impact: small billing changes have a wide blast radius, review cost is high, and regressions become hard to localize because the same class mixes fetching, distribution math, invoice assembly, and persistence.
- Fix approach: split fetching, distribution, preview assembly, and finalization into smaller collaborators with focused tests per billing step.
- Warning: avoid adding more branches to `app/Services/Billing/BillingService.php`; extract new billing variants into dedicated calculators before touching invoice finalization.

**Panel navigation has multiple sources of truth:**
- Issue: panel navigation is built imperatively in `app/Providers/Filament/AppPanelProvider.php` while role-based shell navigation also exists in `config/tenanto.php`, and a custom `app/Filament/Support/Shell/Navigation/NavigationBuilder.php` remains in the tree.
- Files: `app/Providers/Filament/AppPanelProvider.php`, `config/tenanto.php`, `app/Filament/Support/Shell/Navigation/NavigationBuilder.php`
- Impact: navigation changes can drift between config and runtime wiring, leaving dead configuration or inconsistent menus across roles.
- Fix approach: pick one authoritative navigation source, delete unused builder code, and keep role/group labels derived from the same structure.
- Warning: when adding a new admin or tenant destination, update one canonical navigation definition only and verify the other source is either removed or explicitly delegated.

## Known Bugs

**Outstanding balances age invoices from billing period end instead of due date:**
- Symptoms: invoices can appear overdue before their actual due date because overdue days are calculated from `billing_period_end` whenever that value exists.
- Files: `app/Filament/Support/Admin/Reports/OutstandingBalancesReportBuilder.php`, `tests/Feature/Admin/ReportsPageTest.php`
- Trigger: any unpaid invoice with `billing_period_end` set and a later `due_date` will be counted as overdue too early in the outstanding balances report.
- Workaround: none in the current admin report flow.
- Fix approach: compute overdue status from `due_date` first, fall back only when `due_date` is absent, and add a regression test in `tests/Feature/Admin/ReportsPageTest.php`.

**Integration health can show false healthy states:**
- Symptoms: the superadmin integration health page can report database, queue, or mail as healthy when only config values or the `migrations` table exist.
- Files: `app/Filament/Pages/IntegrationHealth.php`, `app/Filament/Support/Superadmin/Integration/Probes/DatabaseProbe.php`, `app/Filament/Support/Superadmin/Integration/Probes/QueueProbe.php`, `app/Filament/Support/Superadmin/Integration/Probes/MailProbe.php`, `tests/Feature/Superadmin/IntegrationHealthPageTest.php`
- Trigger: broken SMTP credentials, a stopped worker, or degraded database access can still pass the current probe logic.
- Workaround: operators must validate real connectivity outside the application.
- Fix approach: replace config-only probes with lightweight connectivity checks, queue dispatch/consume verification, and clearer degraded-state messaging.

## Security Considerations

**Anonymous CSP violation intake is writable, CSRF-exempt, and not throttled:**
- Risk: `POST /csp/report` accepts unauthenticated input, bypasses CSRF protection, and persists data, which creates a log-pollution and storage-amplification surface.
- Files: `routes/web.php`, `app/Http/Controllers/CspViolationReportController.php`, `app/Http/Requests/Security/CspViolationRequest.php`, `app/Services/Security/SecurityMonitor.php`
- Current mitigation: the request is validated before persistence.
- Recommendations: add rate limiting, payload size constraints, and explicit retention/cleanup rules for `SecurityViolation` records; consider isolating report-only traffic on a dedicated middleware profile.
- Warning: do not attach more expensive side effects to the CSP report endpoint until throttling and retention exist.

**The CSP still allows inline script and style execution despite nonce support:**
- Risk: the header builder includes `'unsafe-inline'` in `script-src`, `style-src`, and `style-src-attr`, which weakens the protection gained from nonce-based rendering.
- Files: `app/Services/Security/CspHeaderBuilder.php`
- Current mitigation: nonces are also emitted in the policy.
- Recommendations: remove `'unsafe-inline'` where possible, inventory inline Blade and Filament rendering requirements, and move remaining inline fragments behind explicit nonces or hashed allowances.
- Warning: avoid introducing new inline scripts or inline style attributes while the policy still contains fallback inline allowances.

**Elevated access has two sources of truth:**
- Risk: `User::isSuperadmin()` grants platform access from either the `role` enum or the legacy `is_super_admin` boolean, which allows privilege drift if one flag changes and the other does not.
- Files: `app/Models/User.php`, `database/migrations/2026_03_17_121850_add_system_tenant_support_to_users_and_organizations.php`, `tests/Feature/Admin/LegacyPlatformFoundationTest.php`
- Current mitigation: the model centralizes the check in one helper.
- Recommendations: collapse superadmin authority onto one field, add migration-time reconciliation for existing records, and add authorization regression tests for mismatched role/boolean combinations.
- Warning: any future role-management UI or seeder must update both fields today or it can silently create inconsistent platform access.

## Performance Bottlenecks

**Shared-service billing performs repeated peer scans and in-memory distribution work:**
- Problem: shared-service invoice generation loads candidate assignments, then re-queries peer assignments and measurement context during distribution rather than precomputing reusable aggregates once.
- Files: `app/Services/Billing/BillingService.php`, `app/Services/Billing/InvoiceService.php`, `tests/Unit/Services/BillingServiceTest.php`
- Cause: `previewBulkInvoices()`, `finalize()`, and `sharedDistributionContext()` perform collection-heavy work after broad eager loads, which grows with organization size and shared-service density.
- Improvement path: pre-aggregate peer measurements per property/service, cache reusable distribution inputs for a billing window, and move heavy distribution steps behind dedicated calculators.
- Warning: large organizations with many shared services are the highest-risk path; benchmark `app/Services/Billing/BillingService.php` before adding new distribution modes.

**Report builders load full datasets and paginate in PHP memory:**
- Problem: report rows are materialized as full collections, grouped and transformed in PHP, and only then paginated for display.
- Files: `app/Livewire/Pages/Reports/ReportsPage.php`, `app/Filament/Support/Admin/Reports/ConsumptionReportBuilder.php`, `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php`, `app/Filament/Support/Admin/Reports/OutstandingBalancesReportBuilder.php`, `app/Filament/Support/Admin/Reports/MeterComplianceReportBuilder.php`
- Cause: each builder uses broad `->get()` calls followed by collection `groupBy()`, `map()`, and `sum()` operations; `ReportsPage::rows()` slices the already-built array with `forPage()`.
- Improvement path: push aggregation into Eloquent query layers where possible, precompute heavy metrics, and paginate query results before hydration when row counts can grow.
- Warning: avoid adding more columns, exports, or cross-organization filters to the current report builders without first moving large aggregations out of Livewire memory.

**Notification delivery runs inside interactive request flows:**
- Problem: invitation and invoice reminder emails are sent inline during Filament actions instead of being queued.
- Files: `app/Filament/Actions/Auth/CreateOrganizationInvitationAction.php`, `app/Filament/Actions/Admin/Invoices/SendInvoiceReminderAction.php`, `phpunit.xml`
- Cause: mail notifications are dispatched directly with `Notification::route(...)->notify(...)`, and the test environment uses `QUEUE_CONNECTION=sync`.
- Improvement path: queue mail delivery, add retry/failure handling, and test with a production-like queue driver in targeted integration tests.
- Warning: do not add more notification fan-out to Filament actions until background delivery exists, or admin UX will slow down as recipient counts grow.

## Fragile Areas

**`AppPanelProvider` is a single high-blast-radius bootstrap file:**
- Files: `app/Providers/Filament/AppPanelProvider.php`
- Why fragile: one file owns middleware, branding, auth routing, navigation groups, tenant-facing menu items, and panel composition, so changes in unrelated areas collide in the same class.
- Safe modification: extract navigation, middleware groups, and role-specific panel behavior into smaller support classes before layering in more panel features.
- Test coverage: `tests/Feature/Auth/LoginFlowTest.php` and panel-adjacent feature tests cover behavior indirectly, but there is no narrow regression suite for provider composition itself.

**Manual workspace scoping in resources and pages is easy to break during routine CRUD work:**
- Files: `app/Filament/Resources/Buildings/BuildingResource.php`, `app/Filament/Resources/Invoices/InvoiceResource.php`, `app/Filament/Resources/MeterReadings/MeterReadingResource.php`, `app/Filament/Resources/Properties/Pages/ViewProperty.php`, `app/Filament/Resources/Tenants/Pages/ViewTenant.php`, `app/Livewire/Tenant/InvoiceHistory.php`
- Why fragile: access depends on developers remembering to reapply workspace scopes when overriding queries or reloading records inside page classes.
- Safe modification: start from existing workspace scopes such as `forOrganizationWorkspace()`, `forTenantWorkspace()`, and `withTenantWorkspaceSummary()` instead of reconstructing filtering logic inside pages.
- Test coverage: many tenant/admin feature tests exercise happy paths, but there is no single isolation-focused suite that fails when one resource omits its workspace scope.

**The collaboration domain is schema-rich but operationally thin:**
- Files: `app/Models/Project.php`, `app/Models/Task.php`, `app/Models/EnhancedTask.php`, `app/Models/Comment.php`, `app/Models/Attachment.php`, `app/Models/Activity.php`, `app/Models/DashboardCustomization.php`, `app/Models/TaskAssignment.php`, `app/Models/TimeEntry.php`, `database/migrations/2026_03_17_122200_create_projects_table.php`, `database/migrations/2026_03_17_122900_create_time_entries_table.php`, `tests/Feature/Admin/LegacyCollaborationFoundationTest.php`, `tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
- Why fragile: the repository carries a broad collaboration schema and models, but the visible application surface is mostly foundation checks, factories, and seeded data validation rather than mature user flows.
- Safe modification: treat these models as partially integrated infrastructure until real CRUD, authorization, and reporting surfaces are added.
- Test coverage: existing tests prove tables, factories, and relations exist, but they do not protect end-user workflows because those workflows are largely absent.

## Scaling Limits

**Current performance validation is dashboard-only and uses very small fixtures:**
- Current capacity: `tests/Performance/DashboardPerformanceTest.php` warms dashboards with tiny seeded datasets rather than organization-scale billing or reporting volumes.
- Limit: bottlenecks in `app/Services/Billing/BillingService.php` and `app/Livewire/Pages/Reports/ReportsPage.php` will not be caught before production-sized tenants exercise them.
- Scaling path: add repeatable performance fixtures for large organizations, high meter counts, and shared-service billing windows; benchmark reports and invoice generation separately from dashboard render time.

**Async infrastructure is configured but not exercised as a first-class runtime path:**
- Current capacity: `phpunit.xml` forces `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, and `SESSION_DRIVER=array`, while the application has no files under `app/Jobs/` or `app/Listeners/`.
- Limit: retry behavior, queue backpressure, cache invalidation races, and worker failure handling are not validated by the repository's primary test loop.
- Scaling path: introduce queued delivery for notification and export work, add at least one queued integration path under test, and create operational checks for queue health outside config presence.

## Dependencies at Risk

**`erag/laravel-pwa` is present with generic application metadata and public assets:**
- Risk: the repository ships a service worker and manifest with stock naming, while `config/pwa.php` still advertises generic app metadata and `livewire-app` remains disabled.
- Impact: users can receive a half-enabled PWA experience, and future contributors can assume the PWA layer is product-ready when it still reads as template configuration.
- Migration plan: either complete the PWA integration with tenant-safe caching/offline behavior and branded metadata, or remove the package and public assets until that work is intentionally scheduled.
- Files: `composer.json`, `config/pwa.php`, `public/manifest.json`, `public/sw.js`, `public/offline.html`

## Missing Critical Features

**The repository has no CI workflow files:**
- Problem: `.github/workflows/` is empty, so there is no enforced server-side check for Pest, Pint, static analysis, or migration safety.
- Blocks: test discipline, code style enforcement, and deployment confidence depend on local runs only.
- Files: `.github/workflows/`, `phpunit.xml`, `composer.json`
- Warning: assume every branch can drift in quality until CI is added; do not rely on local developer discipline as the only release gate.

**Backups and restore operations are configuration concepts, not implemented operations:**
- Problem: backup settings exist in enums and seed data, but the repository does not expose backup jobs, provider integration, restore tooling, or operational verification.
- Blocks: disaster recovery, backup retention enforcement, and tenant-safe restore drills.
- Files: `app/Enums/SystemSettingCategory.php`, `database/seeders/SystemSettingSeeder.php`
- Warning: do not treat the `backups` settings category as evidence of a working backup program.

**Operational health visibility is incomplete for real service failures:**
- Problem: the current health surface verifies config and table presence but does not prove SMTP delivery, queue processing, or worker execution.
- Blocks: reliable on-call diagnosis and trustworthy admin-side operational status.
- Files: `app/Filament/Pages/IntegrationHealth.php`, `app/Filament/Support/Superadmin/Integration/Probes/DatabaseProbe.php`, `app/Filament/Support/Superadmin/Integration/Probes/QueueProbe.php`, `app/Filament/Support/Superadmin/Integration/Probes/MailProbe.php`
- Warning: future operational features should not depend on the current health page as an authoritative signal.

## Test Coverage Gaps

**Outstanding balance aging rules are not regression-tested:**
- What's not tested: overdue classification when `due_date` and `billing_period_end` diverge.
- Files: `app/Filament/Support/Admin/Reports/OutstandingBalancesReportBuilder.php`, `tests/Feature/Admin/ReportsPageTest.php`
- Risk: report fixes or refactors can keep misclassifying overdue invoices without an explicit failing test.
- Priority: High

**Billing preview, finalize, and shared-service performance paths lack deep regression coverage:**
- What's not tested: end-to-end behavior of `previewBulkInvoices()`, `finalize()`, and the expensive shared-service distribution path under larger datasets.
- Files: `app/Services/Billing/BillingService.php`, `app/Services/Billing/InvoiceService.php`, `tests/Unit/Services/BillingServiceTest.php`, `tests/Feature/Billing/BillingModuleTest.php`, `tests/Feature/Admin/BulkInvoiceGenerationTest.php`
- Risk: core billing changes can pass unit coverage while still regressing correctness, runtime cost, or invoice composition in production-like data shapes.
- Priority: High

**Production-like queue, cache, and session behavior is not exercised in the main test harness:**
- What's not tested: queued notifications, cache invalidation, worker retries, and session persistence behavior under non-array drivers.
- Files: `phpunit.xml`, `app/Filament/Actions/Auth/CreateOrganizationInvitationAction.php`, `app/Filament/Actions/Admin/Invoices/SendInvoiceReminderAction.php`
- Risk: request-time code appears healthy in tests but fails once real queueing or cache backends are introduced.
- Priority: Medium

**The collaboration and audit domain is covered mostly by existence/foundation tests:**
- What's not tested: real user-facing workflows across projects, tasks, comments, attachments, time tracking, and organization activity review.
- Files: `app/Models/Project.php`, `app/Models/Task.php`, `app/Models/EnhancedTask.php`, `app/Models/Comment.php`, `app/Models/Attachment.php`, `app/Models/TimeEntry.php`, `app/Models/OrganizationActivityLog.php`, `tests/Feature/Admin/LegacyCollaborationFoundationTest.php`, `tests/Feature/Admin/LegacyMembershipAndAuditFoundationTest.php`, `tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
- Risk: future integration work can silently break relations, authorization, or persistence rules because the current suite mostly proves that the tables and factories exist.
- Priority: Medium

---

*Concerns audit: 2026-03-19*
