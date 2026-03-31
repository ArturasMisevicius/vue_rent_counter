# Architecture Map

## System shape

Tenanto is a single Laravel application with one shared runtime and several role-specific surfaces layered on top of it. The dominant shape is **Filament-first for authenticated workspaces** plus **Livewire-driven public and auth entrypoints**.

- HTTP bootstrap starts in `public/index.php` and delegates application configuration to `bootstrap/app.php`.
- Public, auth, tenant shortcut, and lightweight endpoint routes are declared in `routes/web.php`.
- The authenticated workspace is mounted as one Filament panel in `app/Providers/Filament/AppPanelProvider.php` at the `/app` path.
- Console automation and scheduled operations live in `routes/console.php`.
- Broadcast authorization is narrow and organization-scoped in `routes/channels.php`.

This produces one codebase with multiple role projections rather than separate apps.

## Runtime entry points

### HTTP entry

1. `public/index.php`
2. `bootstrap/app.php`
3. `routes/web.php`
4. Either a Livewire component endpoint/page such as `app/Livewire/Auth/LoginPage.php` or a Filament page/resource discovered by `app/Providers/Filament/AppPanelProvider.php`

### Panel entry

`app/Providers/Filament/AppPanelProvider.php` is the main authenticated shell composer:

- mounts the panel at `/app`
- discovers `app/Filament/Resources`, `app/Filament/Pages`, and `app/Filament/Widgets`
- injects custom shell components from `app/Livewire/Shell/Topbar.php` and `app/Livewire/Shell/Sidebar.php`
- applies auth and subscription middleware
- builds navigation through `app/Filament/Support/Shell/Navigation/NavigationBuilder.php`

### Console and scheduled entry

`routes/console.php` contains both ad hoc commands and recurring workflows:

- operations checks through `app/Services/Operations/BackupRestoreReadinessService.php` and `app/Services/Operations/ReleaseReadinessEvidenceService.php`
- project notifications and escalations using models plus notifications under `app/Notifications/Projects/`
- scheduled pruning and external sync tasks

## Main architectural layers

### 1. Delivery surfaces

#### Public and auth Livewire pages

- homepage: `app/Livewire/PublicSite/HomepagePage.php` -> `resources/views/welcome.blade.php`
- login/register/reset flows: `app/Livewire/Auth/*.php` -> `resources/views/auth/*.blade.php`
- onboarding/profile: `app/Livewire/Onboarding/WelcomePage.php`, `app/Livewire/Profile/EditProfilePage.php`

These classes often act like route handlers with small page concerns and delegate non-trivial decisions to support services or form requests.

#### Filament workspace shell

- panel provider: `app/Providers/Filament/AppPanelProvider.php`
- standalone pages: `app/Filament/Pages/*.php`
- widgets: `app/Filament/Widgets/**`
- resources: `app/Filament/Resources/**`

Filament is the dominant authenticated UI layer for superadmin, admin, manager, and tenant users.

#### Hybrid Filament + Livewire pages

Some Filament pages are thin wrappers around larger Livewire page classes. Example:

- wrapper: `app/Filament/Pages/Reports.php`
- implementation: `app/Livewire/Pages/Reports/ReportsPage.php`
- Blade view: `resources/views/filament/pages/reports.blade.php`

This is a recurring pattern when a page needs richer state handling but still belongs inside the Filament shell.

### 2. Request validation and access control

- middleware registration and aliases are centralized in `bootstrap/app.php`
- request throttling and rate limiters are configured in `app/Providers/AppServiceProvider.php`
- validation is pushed into form requests under `app/Http/Requests/**`
- authorization is mostly policy-based via `app/Providers/AuthServiceProvider.php` and `app/Policies/**`

Examples:

- tenant gate middleware: `app/Http/Middleware/EnsureUserIsTenant.php`
- subscription gating: `app/Http/Middleware/CheckSubscriptionStatus.php`
- invoice draft validation: `app/Http/Requests/Admin/Invoices/CreateInvoiceDraftRequest.php`

Controllers are intentionally minimal; `app/Http/Controllers/` is sparse compared with `app/Livewire/` and `app/Filament/`.

### 3. Application support / orchestration layer

The main orchestration layer is not a classic `app/Actions` or `app/Support` root. Instead it is split by concern, mostly under `app/Filament/Support/` and `app/Filament/Actions/`.

Common abstraction types:

- **Resolvers**: `app/Filament/Support/Workspace/WorkspaceResolver.php`, `app/Services/Billing/TariffResolver.php`
- **Presenters**: `app/Filament/Support/Tenant/Portal/TenantHomePresenter.php`, `app/Filament/Support/Admin/Invoices/InvoiceViewPresenter.php`
- **Builders**: `app/Filament/Support/Admin/Reports/ConsumptionReportBuilder.php`, `app/Filament/Support/Shell/Navigation/NavigationBuilder.php`
- **Queries**: `app/Filament/Support/Superadmin/Organizations/OrganizationListQuery.php`, `app/Filament/Support/Tenant/Portal/TenantInvoiceIndexQuery.php`
- **Guards**: `app/Filament/Support/Admin/SubscriptionLimitGuard.php`, `app/Filament/Support/Admin/Invoices/FinalizedInvoiceGuard.php`
- **Registries**: `app/Filament/Support/Shell/Search/GlobalSearchRegistry.php`, `app/Filament/Support/Superadmin/Integration/IntegrationProbeRegistry.php`
- **Mutating action classes**: `app/Filament/Actions/Admin/Invoices/SendInvoiceReminderAction.php`

This layer is where most planning-level business coordination happens.

### 4. Domain and persistence layer

Eloquent models are the main domain boundary and query surface.

- primary business entities live in `app/Models/*.php`
- enums live in `app/Enums/*.php`
- model scopes encode read models and context filtering
- relations are heavily used to express organization, property, tenant, billing, and project boundaries

Examples:

- tenant/admin invoice query surfaces in `app/Models/Invoice.php`
- workspace-aware user helpers in `app/Models/User.php`
- control-plane organization projections in `app/Models/Organization.php`
- tenancy assignment boundary in `app/Models/PropertyAssignment.php`

### 5. Async and notification layer

Mutation side effects are pushed into jobs and notifications instead of remaining in UI code.

- jobs: `app/Jobs/*.php`, `app/Jobs/Projects/*.php`, `app/Jobs/Superadmin/*.php`
- notifications: `app/Notifications/**`

Concrete flow:

1. `app/Filament/Actions/Admin/Invoices/SendInvoiceReminderAction.php`
2. dispatches `app/Jobs/SendInvoiceReminderJob.php`
3. job sends `app/Notifications/InvoiceOverdueReminderNotification.php`
4. job records audit state in `InvoiceReminderLog`

## Dominant flow patterns

### Flow A: login and workspace routing

1. `routes/web.php` routes `/login` to `app/Livewire/Auth/LoginPage.php`
2. `LoginPage::store()` validates through `app/Http/Requests/Auth/LoginRequest.php`
3. redirect target is decided by `app/Filament/Support/Auth/LoginRedirector.php`
4. `/dashboard` is resolved by `app/Livewire/Shell/DashboardRedirectEndpoint.php`
5. final destination is usually a Filament page under `/app`

### Flow B: tenant shortcut routes into the shared panel

1. tenant-friendly routes such as `/tenant/invoices` are declared in `routes/web.php`
2. all map to `app/Livewire/Tenant/TenantPortalRouteEndpoint.php`
3. endpoint checks tenant workspace through `app/Filament/Support/Workspace/WorkspaceResolver.php`
4. endpoint redirects to Filament page routes like `filament.admin.pages.tenant-invoice-history`

This keeps one panel while still exposing clean tenant URLs.

### Flow C: billing mutation pipeline

1. UI or page code triggers billing operations from Filament pages/resources
2. validation lives in `app/Http/Requests/Admin/Invoices/*.php`
3. orchestration runs through `app/Services/Billing/BillingService.php`
4. subordinate services handle specialized concerns such as `app/Services/Billing/InvoiceService.php`, `app/Services/Billing/InvoicePdfService.php`, `app/Services/Billing/UniversalBillingCalculator.php`
5. state is persisted via Eloquent models like `app/Models/Invoice.php`, `app/Models/InvoiceItem.php`, `app/Models/MeterReading.php`

### Flow D: reporting and exports

1. page wrapper `app/Filament/Pages/Reports.php`
2. stateful implementation `app/Livewire/Pages/Reports/ReportsPage.php`
3. report builders under `app/Filament/Support/Admin/Reports/*.php`
4. charts from `app/Filament/Widgets/Reports/*.php`
5. export scheduling through `app/Services/ScheduledExportService.php`

## Module boundaries

### Shell and workspace context

Core boundary files:

- `app/Providers/Filament/AppPanelProvider.php`
- `app/Filament/Support/Workspace/WorkspaceResolver.php`
- `app/Filament/Support/Workspace/WorkspaceContext.php`
- `app/Filament/Support/Shell/**`
- `config/tenanto.php`

This boundary decides who the current user is in platform, organization, or tenant scope and drives navigation, redirects, and feature visibility.

### Superadmin control plane

Lives mostly in:

- `app/Filament/Resources/Organizations/**`
- `app/Filament/Support/Superadmin/**`
- `app/Livewire/Superadmin/ExportRecentOrganizationsCsvEndpoint.php`
- `app/Filament/Pages/SystemConfiguration.php`

This area owns cross-organization visibility, subscriptions, system config, integrations, and platform-wide audit/security views.

### Organization operations

Main surfaces:

- `app/Filament/Resources/Buildings/**`
- `app/Filament/Resources/Properties/**`
- `app/Filament/Resources/Tenants/**`
- `app/Filament/Resources/Meters/**`
- `app/Filament/Resources/MeterReadings/**`
- `app/Filament/Pages/Reports.php`

This is the default admin/manager workspace for day-to-day property and utility operations.

### Billing subsystem

Main code:

- `app/Services/Billing/**`
- `app/Filament/Resources/Invoices/**`
- `app/Filament/Actions/Admin/Invoices/**`
- `app/Models/Invoice.php`, `app/Models/InvoiceItem.php`, `app/Models/InvoicePayment.php`
- `app/Notifications/Billing/**` plus `app/Notifications/InvoiceOverdueReminderNotification.php`

This subsystem is one of the deepest and most service-oriented parts of the codebase.

### Tenant self-service subsystem

Main code:

- `app/Livewire/Tenant/**`
- `app/Filament/Pages/TenantDashboard.php`
- `app/Filament/Pages/TenantInvoiceHistory.php`
- `app/Filament/Pages/TenantPropertyDetails.php`
- `app/Filament/Pages/TenantSubmitMeterReading.php`
- `app/Filament/Support/Tenant/Portal/**`

Tenant functionality is built as a projection over the same underlying data model, filtered by workspace context.

### Projects and collaboration subsystem

Main code:

- `app/Models/Project.php`, `app/Models/Task.php`, `app/Models/Comment.php`, `app/Models/Attachment.php`
- `app/Filament/Resources/Projects/**`
- `app/Filament/Resources/Tasks/**`
- `app/Jobs/Projects/RescopeProjectChildrenJob.php`
- `app/Notifications/Projects/**`

This subsystem is structurally separate from billing/property operations but still organization-scoped.

## Cross-cutting concerns

- localization: `app/Http/Middleware/SetGuestLocale.php`, `app/Http/Middleware/SetAuthenticatedUserLocale.php`, `lang/*`, `app/Services/Localization/PhpFileMissingTranslationsScanner.php`
- security: `app/Http/Middleware/SecurityHeaders.php`, `app/Livewire/Security/CspViolationReportEndpoint.php`, `app/Models/SecurityViolation.php`
- auditing/observers: observers registered in `app/Providers/AppServiceProvider.php`, model observers under `app/Observers/`
- subscription enforcement: `app/Services/SubscriptionChecker.php`, `app/Http/Middleware/CheckSubscriptionStatus.php`
- search/integration registries: `app/Filament/Support/Shell/Search/GlobalSearchRegistry.php`, `app/Filament/Support/Superadmin/Integration/IntegrationProbeRegistry.php`

## Planning reference: where to make changes

- change workspace routing or role landing behavior in `routes/web.php`, `app/Livewire/Shell/DashboardRedirectEndpoint.php`, and `app/Filament/Support/Auth/LoginRedirector.php`
- change role scoping or tenant boundaries in `app/Filament/Support/Workspace/WorkspaceResolver.php`, related middleware, and relevant model scopes
- change admin UI behavior in the relevant `app/Filament/Resources/<Module>/` or `app/Filament/Pages/` folder first
- change tenant projections in `app/Livewire/Tenant/` and `app/Filament/Support/Tenant/Portal/`
- change business calculations in `app/Services/Billing/` before touching UI files
- change superadmin list/report composition in `app/Filament/Support/Superadmin/`

The codebase is best understood as **one Laravel runtime with a shared data model, a unified Filament shell, and role-specific read/write projections implemented through workspace-aware support classes and model scopes**.
