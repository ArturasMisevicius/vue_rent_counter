# Structure Map

## Top-level layout

These directories carry most of the planning value:

- `app/` — application code, including Filament, Livewire, models, services, policies, jobs, and middleware
- `bootstrap/` — Laravel application bootstrapping; `bootstrap/app.php` is the active configuration entry
- `config/` — framework and app configuration, especially `config/tenanto.php`
- `database/` — migrations, factories, seeders, and default SQLite file
- `lang/` — first-party localization packs in `en`, `es`, `lt`, and `ru`
- `public/` — web root and Vite build artifacts
- `resources/` — Blade views, CSS, JS, icons
- `routes/` — web, console, and broadcast route entrypoints
- `tests/` — Pest feature/unit/performance suites and helpers
- `.planning/` — planning artifacts; codebase maps belong in `.planning/codebase/`
- `docs/` — project notes and operations/security/performance references

## `app/` directory map

### `app/Filament/`

This is the main authenticated product surface.

- `app/Filament/Resources/` — CRUD-style resources grouped by domain
- `app/Filament/Pages/` — non-resource panel pages such as dashboards, settings, reports, tenant pages
- `app/Filament/Widgets/` — dashboard/report widgets
- `app/Filament/Actions/` — reusable mutation classes invoked from pages/resources
- `app/Filament/Support/` — the biggest support namespace; holds presenters, builders, guards, queries, registries, workspace helpers
- `app/Filament/Forms/`, `app/Filament/Exports/`, `app/Filament/Concerns/` — narrower reusable UI/support pieces

Representative paths:

- invoice resource: `app/Filament/Resources/Invoices/InvoiceResource.php`
- reports page: `app/Filament/Pages/Reports.php`
- tenant page base: `app/Filament/Pages/TenantPortalPage.php`
- navigation builder: `app/Filament/Support/Shell/Navigation/NavigationBuilder.php`
- superadmin organization query object: `app/Filament/Support/Superadmin/Organizations/OrganizationListQuery.php`

#### Resource layout convention

Resource folders are strongly normalized. Typical shape:

- `app/Filament/Resources/Invoices/InvoiceResource.php`
- `app/Filament/Resources/Invoices/Pages/`
- `app/Filament/Resources/Invoices/Schemas/`
- `app/Filament/Resources/Invoices/Tables/`

The same layout appears in `app/Filament/Resources/Organizations/`, `app/Filament/Resources/Projects/`, `app/Filament/Resources/Tasks/`, and many others.

### `app/Livewire/`

This directory holds public pages, auth pages, endpoints, shell pieces, and richer page implementations.

Top-level groupings are feature- or role-based rather than technical:

- `app/Livewire/Auth/` — login, registration, password reset, invitation acceptance
- `app/Livewire/PublicSite/` — public homepage and favicon endpoint
- `app/Livewire/Shell/` — dashboard redirect, logout, impersonation stop, shell chrome components
- `app/Livewire/Tenant/` — tenant shortcut endpoints and tenant-specific components
- `app/Livewire/Profile/`, `app/Livewire/Onboarding/`, `app/Livewire/Preferences/`, `app/Livewire/Kyc/`
- `app/Livewire/Pages/` — deeper page implementations used inside Filament

Representative paths:

- `app/Livewire/Auth/LoginPage.php`
- `app/Livewire/PublicSite/HomepagePage.php`
- `app/Livewire/Tenant/TenantPortalRouteEndpoint.php`
- `app/Livewire/Pages/Reports/ReportsPage.php`

### `app/Models/`

Eloquent models are flat at the top level and broad in scope. They cover:

- platform/control plane: `Organization.php`, `Subscription.php`, `SystemConfiguration.php`, `SecurityViolation.php`
- property/utility domain: `Building.php`, `Property.php`, `Meter.php`, `MeterReading.php`, `Provider.php`, `Tariff.php`, `UtilityService.php`, `ServiceConfiguration.php`
- billing domain: `Invoice.php`, `InvoiceItem.php`, `InvoicePayment.php`, `InvoiceReminderLog.php`, `InvoiceEmailLog.php`
- tenant/access domain: `User.php`, `PropertyAssignment.php`, `OrganizationUser.php`, `UserKycProfile.php`
- projects/collaboration: `Project.php`, `Task.php`, `TaskAssignment.php`, `Comment.php`, `Attachment.php`, `Tag.php`, `TimeEntry.php`, `CostRecord.php`

Models frequently include:

- scoped read models such as `forWorkspaceIndex`, `forTenantWorkspace`, `forSuperadminControlPlane`
- carefully selected column lists for summary/index queries
- relationship-heavy projections used directly by support classes

### `app/Services/`

Service code is grouped by business concern.

- `app/Services/Billing/` — invoice generation, calculations, tariff resolution, PDF building
- `app/Services/Localization/` — translation scanning helpers
- `app/Services/Operations/` — operational readiness and release checks
- `app/Services/Security/` — security-specific helpers such as CSP construction

Representative paths:

- `app/Services/Billing/BillingService.php`
- `app/Services/Billing/InvoicePdfService.php`
- `app/Services/ScheduledExportService.php`
- `app/Services/SubscriptionChecker.php`

### `app/Http/`

This is a slim HTTP layer.

- `app/Http/Middleware/` — main request gates and security behavior
- `app/Http/Requests/` — most validation logic lives here, organized by area
- `app/Http/Controllers/` — intentionally minimal compared with Livewire/Filament

Representative paths:

- `app/Http/Middleware/CheckSubscriptionStatus.php`
- `app/Http/Middleware/EnsureUserIsTenant.php`
- `app/Http/Requests/Admin/Invoices/CreateInvoiceDraftRequest.php`

### Other important `app/` namespaces

- `app/Providers/` — service bindings, observers, rate limiters, policy registration, Filament panel provider
- `app/Policies/` — authorization policies mapped in `app/Providers/AuthServiceProvider.php`
- `app/Jobs/` — queued side effects and async work
- `app/Notifications/` — mail/database notifications grouped by concern
- `app/Observers/` — model lifecycle hooks
- `app/Enums/` — typed domain constants such as `UserRole`, `InvoiceStatus`, `ProjectStatus`
- `app/Contracts/` — very small interface layer, currently including `app/Contracts/BillingServiceInterface.php`
- `app/Exceptions/` — custom exception types, notably around projects and manager permissions

## `resources/` directory map

### Views

- `resources/views/auth/` — auth pages like `login.blade.php`, `register.blade.php`
- `resources/views/filament/pages/` — Blade views backing Filament pages such as `reports.blade.php`, `tenant-dashboard.blade.php`, `settings.blade.php`
- `resources/views/filament/resources/` — resource-specific custom view fragments
- `resources/views/livewire/` — Livewire-specific view groups for shell, pages, framework demos, and tenant components
- `resources/views/components/` — shared Blade components for shell/layout/framework/superadmin pieces
- `resources/views/profile/` and `resources/views/onboarding/` — smaller standalone page views
- `resources/views/pdf/` — PDF-oriented templates

### Frontend assets

- CSS entry: `resources/css/app.css`
- JS entry: `resources/js/app.js`
- Axios bootstrap: `resources/js/bootstrap.js`

The frontend is intentionally thin. Most UI behavior is server-driven via Filament/Livewire, with `resources/js/app.js` mainly adding progressive enhancement for auth forms and shell interactions.

## `routes/` directory map

- `routes/web.php` — public/auth/tenant shortcut routes and lightweight endpoints
- `routes/console.php` — custom artisan commands plus scheduler definitions
- `routes/channels.php` — broadcast channel authorization
- `routes/web/` — currently empty, suggesting the app keeps web routing centralized in one file

## `config/` directory map

Most framework defaults are standard Laravel files, but these are especially relevant:

- `config/tenanto.php` — role navigation, locales, shell behavior, tenant/admin defaults
- `config/filament.php` — Filament-specific configuration
- `config/livewire.php` — Livewire settings
- `config/services.php` — external integration config entrypoint

`config/tenanto.php` is architecturally important because it drives role-based navigation structure consumed by `app/Filament/Support/Shell/Navigation/NavigationBuilder.php`.

## `database/` directory map

- `database/migrations/` — high-volume migration history covering platform, billing, property, tenant, security, and project domains
- `database/factories/` — extensive test factory coverage that mirrors the model surface
- `database/seeders/` — reference, localization, demo, and system seeders
- `database/database.sqlite` — default local database

Structure signals:

- migrations are domain-explicit rather than generic
- factory coverage is broad, which supports feature-heavy test suites
- seeders are split into reference/demo/support concerns rather than one monolith

## `tests/` directory map

- `tests/Feature/` — dominant suite, organized by concern (`Admin`, `Billing`, `Filament`, `Security`, `Tenant`, `Architecture`, `Projects`, `Superadmin`, etc.)
- `tests/Unit/` — lower-level unit tests
- `tests/Performance/` — performance-focused coverage
- `tests/Support/` — reusable helpers such as `FormRequestScenarioFactory.php` and `TenantPortalFactory.php`
- `tests/Pest.php` and `tests/TestCase.php` — test bootstrap

The test structure mirrors product and architectural boundaries rather than only technical type. Good planning examples:

- architecture contracts: `tests/Feature/Architecture/WorkspaceBoundaryInventoryTest.php`
- tenant surface checks: `tests/Feature/Tenant/TenantPortalNavigationTest.php`
- panel behavior checks: `tests/Feature/Filament/UnifiedPanelTest.php`

## Docs and planning folders

- `docs/operations/`, `docs/security/`, `docs/performance/` — focused operational references
- `docs/SESSION-BOOTSTRAP.md` and `docs/PROJECT-CONTEXT.md` — high-value repo orientation docs
- `.planning/` — active planning artifacts, including phase plans and research summaries
- `.planning/codebase/` — target location for codebase mapping references

The repo also contains assistant/tooling directories such as `.agent/`, `.agents/`, `.claude/`, and `.github/`, but these support automation and workflow guidance rather than runtime application behavior.

## Naming and layout conventions

### Filament naming

- resource folders use plural feature names: `app/Filament/Resources/Invoices/`, `app/Filament/Resources/Projects/`
- resource classes are singular: `InvoiceResource.php`, `ProjectResource.php`
- subfolders are predictable: `Pages/`, `Schemas/`, `Tables/`, sometimes `RelationManagers/`

### Livewire naming

- grouped by product area or role: `Auth`, `Tenant`, `PublicSite`, `Shell`, `Preferences`
- route endpoints often end with `Endpoint`, for example `app/Livewire/Tenant/DownloadInvoiceEndpoint.php`
- page classes often end with `Page`, for example `app/Livewire/Onboarding/WelcomePage.php`

### Support naming

- builders assemble derived UI/report payloads
- presenters shape data for Blade/Filament consumption
- queries encapsulate reusable list filtering or pagination
- guards enforce mutation constraints
- registries collect pluggable providers/probes
- resolvers convert runtime context into a concrete target or payload

### Tests naming

- test files read like behavioral contracts, e.g. `TenantAccessIsolationTest.php`, `MutationPipelineInventoryTest.php`, `ReportBuildersNoRawSqlTest.php`

## Planning shortcuts

- changing authenticated shell behavior usually means `app/Providers/Filament/AppPanelProvider.php`, `config/tenanto.php`, and `app/Filament/Support/Shell/**`
- changing a CRUD area usually means one `app/Filament/Resources/<Module>/` folder plus its matching model in `app/Models/`
- changing tenant UX usually means `app/Livewire/Tenant/`, `app/Filament/Pages/Tenant*.php`, and `app/Filament/Support/Tenant/Portal/**`
- changing billing usually means `app/Services/Billing/`, `app/Filament/Resources/Invoices/`, and billing-related models/migrations/tests
- changing platform/superadmin behavior usually means `app/Filament/Support/Superadmin/**` and superadmin-facing resources/pages

Overall, the repository is structurally **feature-grouped at the UI layer, model-centric at the domain layer, and support-class heavy in `app/Filament/Support/` for orchestration and read-model composition**.
