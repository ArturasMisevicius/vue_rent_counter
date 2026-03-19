# Architecture

**Analysis Date:** 2026-03-19

## Pattern Overview

**Overall:** Unified-panel Laravel monolith with Filament-first administration and Livewire-assisted public, auth, and tenant flows

**Key Characteristics:**
- A single Laravel application boots from `bootstrap/app.php` and serves the public site, authentication, tenant self-service, organization workspace, and superadmin control plane from one runtime.
- One Filament panel at `/app` is configured in `app/Providers/Filament/AppPanelProvider.php`; it discovers resources, pages, and widgets and builds role-aware navigation at runtime.
- Livewire is the primary interaction layer outside traditional controllers: public/auth routes mount `app/Livewire/*` components directly, and several Filament pages are thin wrappers around Livewire components defined in `resources/views/filament/pages/*`.
- Eloquent models in `app/Models/*` carry most query shaping through explicit workspace and control-plane scopes such as `forSuperadminControlPlane()`, `forOrganizationWorkspace()`, `forAdminWorkspace()`, and `forTenantWorkspace()`.
- Authorization is layered across `app/Policies/*`, Filament `canAccess()` / `canView()` methods, route middleware in `app/Http/Middleware/*`, and targeted `Gate::authorize()` calls in actions and Livewire components.
- Validation is centralized in `app/Http/Requests/*`, including request reuse inside Livewire via `app/Http/Requests/Concerns/InteractsWithValidationPayload.php`.

## Layers

**Bootstrap and Composition Layer:**
- Purpose: Construct the Laravel application, register providers, wire middleware, and expose runtime entry points.
- Location: `bootstrap/app.php`, `bootstrap/providers.php`, `app/Providers/AppServiceProvider.php`, `app/Providers/AuthServiceProvider.php`, `app/Providers/Filament/AppPanelProvider.php`
- Contains: route registration, middleware aliases, singleton/scoped bindings, observer registration, policy mapping, and Filament panel definition.
- Depends on: Laravel framework configuration plus app services such as `app/Services/SubscriptionChecker.php` and `app/Filament/Support/*`.
- Used by: every HTTP request, console invocation from `routes/console.php`, and broadcast authorization in `routes/channels.php`.

**HTTP Boundary Layer:**
- Purpose: Accept browser requests, choose the correct page/controller, and enforce security/access middleware before domain work runs.
- Location: `routes/web.php`, `routes/web/guest.php`, `routes/web/authenticated.php`, `routes/web/logout.php`, `routes/channels.php`, `app/Http/Controllers/*`, `app/Http/Middleware/*`
- Contains: public/home routes, auth routes, tenant portal redirects, narrow endpoint controllers, and middleware for blocking IPs, locale, onboarding, account access, subscription enforcement, and security headers.
- Depends on: the bootstrap/provider layer plus request objects and support services.
- Used by: traffic hitting `/`, `/login`, `/register`, `/tenant/*`, `/app`, `/csp/report`, and the broadcast channel `org.{organizationId}` in `routes/channels.php`.

**Filament Application Layer:**
- Purpose: Deliver the unified admin, manager, superadmin, and tenant panel UX.
- Location: `app/Filament/Resources/*`, `app/Filament/Pages/*`, `app/Filament/Widgets/*`, `resources/views/filament/*`
- Contains: resource bundles such as `app/Filament/Resources/Organizations/*`, `app/Filament/Resources/Properties/*`, and `app/Filament/Resources/Invoices/*`; custom pages such as `app/Filament/Pages/Dashboard.php`, `app/Filament/Pages/Settings.php`, and `app/Filament/Pages/IntegrationHealth.php`; widgets such as `app/Filament/Widgets/Admin/OrganizationStatsOverview.php` and `app/Filament/Widgets/Superadmin/PlatformStatsOverview.php`.
- Depends on: model scopes, policies, request classes, actions, and support services like `app/Filament/Support/Admin/OrganizationContext.php`.
- Used by: the unified `/app` panel configured in `app/Providers/Filament/AppPanelProvider.php`.

**Livewire Interaction Layer:**
- Purpose: Handle UI state, user-triggered mutations, and shell behavior without a large controller surface.
- Location: `app/Livewire/*`, paired views under `resources/views/livewire/*`, auth views under `resources/views/auth/*`, onboarding views under `resources/views/onboarding/*`, and Filament page stubs under `resources/views/filament/pages/*`
- Contains: auth pages in `app/Livewire/Auth/*`, shell components in `app/Livewire/Shell/*`, public site endpoints/pages in `app/Livewire/PublicSite/*` and `app/Livewire/Preferences/UpdateGuestLocaleEndpoint.php`, dashboard components in `app/Livewire/Pages/Dashboard/*`, and tenant flows in `app/Livewire/Tenant/*`.
- Depends on: Form Requests, Filament actions, support services, Gate/policies, and Eloquent scopes.
- Used by: routes in `routes/web*.php`, Filament page wrappers such as `resources/views/filament/pages/tenant-invoice-history.blade.php`, and Blade shell layouts like `resources/views/components/shell/app-frame.blade.php`.

**Validation and Action Layer:**
- Purpose: Keep validation and mutation logic out of routes, Blade, and thin controllers/components.
- Location: `app/Http/Requests/*`, `app/Filament/Actions/*`, `app/Rules/*`
- Contains: role-specific request namespaces such as `app/Http/Requests/Admin/*`, `app/Http/Requests/Superadmin/*`, and `app/Http/Requests/Tenant/*`; action namespaces such as `app/Filament/Actions/Admin/*`, `app/Filament/Actions/Auth/*`, `app/Filament/Actions/Superadmin/*`, and `app/Filament/Actions/Tenant/*`.
- Depends on: models, enums, services, transactions, and policies/Gate.
- Used by: Livewire methods in `app/Livewire/Auth/LoginPage.php`, `app/Livewire/Onboarding/WelcomePage.php`, and `app/Livewire/Tenant/SubmitReadingPage.php`, plus narrow controllers such as `app/Http/Controllers/TenantInvoiceDownloadController.php`.

**Domain Model Layer:**
- Purpose: Represent the product domains and expose reusable scopes/relationships for the rest of the application.
- Location: `app/Models/*`, `app/Enums/*`, `app/Observers/*`, `database/migrations/*`, `database/factories/*`
- Contains: control-plane models such as `app/Models/Organization.php`, `app/Models/Subscription.php`, `app/Models/SystemSetting.php`, `app/Models/PlatformNotification.php`, and `app/Models/SystemTenant.php`; workspace/billing models such as `app/Models/Building.php`, `app/Models/Property.php`, `app/Models/Meter.php`, `app/Models/MeterReading.php`, `app/Models/Invoice.php`, `app/Models/Tariff.php`, `app/Models/Provider.php`, and `app/Models/UtilityService.php`; security/reference models such as `app/Models/SecurityViolation.php`, `app/Models/Language.php`, and `app/Models/Translation.php`; collaboration/operations models such as `app/Models/Project.php`, `app/Models/Task.php`, `app/Models/Comment.php`, `app/Models/Activity.php`, and `app/Models/TimeEntry.php`.
- Depends on: Eloquent, enums, observers, and database schema.
- Used by: resources, actions, services, policies, seeders, and tests.

**Support and Service Layer:**
- Purpose: Hold reusable orchestration, caching, navigation, search, reporting, security, and presenter/query logic that does not belong in models or UI classes.
- Location: `app/Filament/Support/*`, `app/Services/*`, `app/View/Components/*`
- Contains: organization/subscription helpers in `app/Filament/Support/Admin/*`, report builders in `app/Filament/Support/Admin/Reports/*`, shell navigation/search helpers in `app/Filament/Support/Shell/*`, tenant portal presenters/queries in `app/Filament/Support/Tenant/Portal/*`, billing/security services in `app/Services/Billing/*` and `app/Services/Security/*`, and Blade component classes such as `app/View/Components/Shell/AppFrame.php`.
- Depends on: models, cache, config, notifications, and framework services.
- Used by: providers, middleware, resources, widgets, Livewire components, and Blade layouts.

## Data Flow

**Public and Auth Request Flow:**

1. `bootstrap/app.php` creates the app and applies web middleware such as `App\Http\Middleware\BlockBlockedIpAddresses`, `App\Http\Middleware\SetGuestLocale`, and `App\Http\Middleware\SecurityHeaders`.
2. `routes/web.php` and `routes/web/guest.php` route the request to a Livewire page or a thin controller.
3. Livewire components such as `app/Livewire/PublicSite/HomepagePage.php` and `app/Livewire/Auth/LoginPage.php` gather or validate input with request classes like `app/Http/Requests/Auth/LoginRequest.php`.
4. Actions/support services such as `app/Filament/Support/Auth/LoginRedirector.php`, `app/Filament/Support/Auth/AuthenticatedSessionHistory.php`, or `app/Filament/Actions/Auth/CompleteOnboardingAction.php` perform side effects and persistence.
5. Blade views such as `resources/views/welcome.blade.php`, `resources/views/auth/login.blade.php`, and `resources/views/onboarding/welcome.blade.php` render the response.

**Unified Filament Panel Flow:**

1. `app/Providers/Filament/AppPanelProvider.php` serves `/app`, discovers resources/pages/widgets, and applies panel auth middleware such as `App\Http\Middleware\AuthenticateAdminPanel`, `App\Http\Middleware\EnsureAccountIsAccessible`, `App\Http\Middleware\EnsureOnboardingIsComplete`, and `App\Http\Middleware\CheckSubscriptionStatus`.
2. `AppPanelProvider::buildNavigation()` chooses navigation groups and items based on role checks like `isSuperadmin()`, `isAdminOrManager()`, and `isTenant()`.
3. Resource classes such as `app/Filament/Resources/Organizations/OrganizationResource.php`, `app/Filament/Resources/Properties/PropertyResource.php`, and `app/Filament/Resources/Invoices/InvoiceResource.php` scope queries through model methods like `forSuperadminControlPlane()`, `forOrganizationWorkspace()`, `forAdminWorkspace()`, and `forTenantWorkspace()`.
4. Resource sub-classes under `Pages/`, `Schemas/`, `Tables/`, and `RelationManagers/` shape CRUD screens and related data views.
5. Custom pages such as `app/Filament/Pages/Dashboard.php`, `app/Filament/Pages/Profile.php`, and `app/Filament/Pages/Settings.php` either render directly or delegate to Livewire page components via Blade wrappers in `resources/views/filament/pages/*`.

**Tenant Self-Service Flow:**

1. Authenticated routes in `routes/web/authenticated.php` accept human-friendly `/tenant/*` URLs and direct them through `app/Http/Controllers/TenantPortalRouteController.php`.
2. `TenantPortalRouteController` maps those destinations to Filament page routes such as `filament.admin.pages.tenant-dashboard` and `filament.admin.pages.tenant-submit-meter-reading`.
3. Filament page views like `resources/views/filament/pages/tenant-invoice-history.blade.php` and `resources/views/filament/pages/tenant-submit-meter-reading.blade.php` mount Livewire components in `app/Livewire/Tenant/*`.
4. Livewire components validate payloads with requests like `app/Http/Requests/Tenant/InvoiceHistoryFilterRequest.php` and `app/Http/Requests/Tenant/StoreMeterReadingRequest.php`, then delegate mutations to actions such as `app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php`.
5. Query helpers and presenters such as `app/Filament/Support/Tenant/Portal/TenantInvoiceIndexQuery.php` and `app/Filament/Support/Tenant/Portal/PaymentInstructionsResolver.php` keep tenant-specific query and presentation logic out of Blade files.

**State Management:**
- Persistent state lives in the relational schema defined by `database/migrations/*` and modeled in `app/Models/*`.
- Per-request access context is derived from the authenticated user and helpers such as `app/Filament/Support/Admin/OrganizationContext.php`.
- Livewire properties hold transient UI state for search, filters, forms, and overlays in components like `app/Livewire/Shell/GlobalSearch.php` and `app/Livewire/Tenant/SubmitReadingPage.php`.
- Dashboard and subscription summaries are cached by services such as `app/Filament/Support/Dashboard/DashboardCacheService.php` and `app/Services/SubscriptionChecker.php`.

## Key Abstractions

**Unified Panel Provider:**
- Purpose: Centralize panel identity, middleware, resource/page discovery, and role-based navigation.
- Examples: `app/Providers/Filament/AppPanelProvider.php`, `app/Livewire/Shell/Sidebar.php`, `app/Livewire/Shell/Topbar.php`
- Pattern: One Filament panel with dynamic navigation instead of separate admin and tenant panels.

**Resource Bundle:**
- Purpose: Package a CRUD surface as one resource root plus page/schema/table/relation-manager classes.
- Examples: `app/Filament/Resources/Organizations/OrganizationResource.php`, `app/Filament/Resources/Organizations/Pages/ListOrganizations.php`, `app/Filament/Resources/Organizations/Schemas/OrganizationForm.php`, `app/Filament/Resources/Organizations/RelationManagers/UsersRelationManager.php`
- Pattern: Filament resource bundle with a singular `*Resource.php` root and nested UI configuration classes.

**Livewire Page-Action Component:**
- Purpose: Let a single class own a page’s state and orchestrate validation, authorization, and action calls.
- Examples: `app/Livewire/Auth/LoginPage.php`, `app/Livewire/Onboarding/WelcomePage.php`, `app/Livewire/Tenant/SubmitReadingPage.php`, `app/Livewire/Shell/GlobalSearch.php`
- Pattern: Livewire component methods use typed request objects and injected actions/services instead of fat controllers.

**Form Request Payload Adapter:**
- Purpose: Reuse Laravel Form Request rules for both HTTP requests and Livewire payload validation.
- Examples: `app/Http/Requests/Concerns/InteractsWithValidationPayload.php`, `app/Http/Requests/Tenant/StoreMeterReadingRequest.php`, `app/Http/Requests/Admin/Properties/StorePropertyRequest.php`
- Pattern: Request classes expose `validatePayload()` and authorization-aware validation without requiring an actual HTTP controller form submission.

**Workspace and Control-Plane Scopes:**
- Purpose: Give each surface an explicit query shape with selected columns, eager loads, and ordering.
- Examples: `app/Models/Organization.php`, `app/Models/User.php`, `app/Models/Property.php`, `app/Models/Meter.php`, `app/Models/Invoice.php`
- Pattern: Eloquent scopes define reusable read models such as control-plane, organization workspace, and tenant workspace queries.

**Support Registries and Context Services:**
- Purpose: Provide reusable orchestration objects for navigation, search, reporting, dashboards, and tenant/org context.
- Examples: `app/Filament/Support/Admin/OrganizationContext.php`, `app/Filament/Support/Shell/Search/GlobalSearchRegistry.php`, `app/Filament/Support/Dashboard/DashboardCacheService.php`, `app/Filament/Support/Admin/Reports/ReportExportService.php`
- Pattern: Thin UI classes delegate to support services instead of embedding orchestration logic directly.

## Entry Points

**Laravel Bootstrap:**
- Location: `bootstrap/app.php`
- Triggers: Every HTTP request.
- Responsibilities: Register routes, alias middleware, prepend/append web middleware, and create the application instance.

**Unified Filament Panel:**
- Location: `app/Providers/Filament/AppPanelProvider.php`
- Triggers: Requests to `/app` and Filament-generated routes.
- Responsibilities: Define panel path, shell components, middleware stack, navigation, and discovery for resources/pages/widgets.

**Web Route Surface:**
- Location: `routes/web.php`, `routes/web/guest.php`, `routes/web/authenticated.php`, `routes/web/logout.php`
- Triggers: Browser requests for public, auth, profile, tenant, locale, and CSP routes.
- Responsibilities: Group routes by guest/authenticated context and keep route files as simple dispatch points.

**Thin Controllers:**
- Location: `app/Http/Controllers/TenantPortalRouteController.php`, `app/Http/Controllers/TenantInvoiceDownloadController.php`, `app/Http/Controllers/NotificationTrackingController.php`, `app/Http/Controllers/CspViolationReportController.php`
- Triggers: Redirect/download/webhook-style endpoints that do not need a full page component.
- Responsibilities: Route model binding, small authorization/dispatch checks, and delegation to actions/services.

**Standalone Livewire Pages:**
- Location: `app/Livewire/PublicSite/HomepagePage.php`, `app/Livewire/Auth/*`, `app/Livewire/Onboarding/WelcomePage.php`
- Triggers: Public and guest/auth flows mounted directly from route definitions.
- Responsibilities: Render page views, validate payloads, and hand off side effects to actions/support services.

**CLI and Broadcasting:**
- Location: `routes/console.php`, `routes/channels.php`
- Triggers: Artisan console execution and broadcast subscription authorization.
- Responsibilities: Register lightweight console endpoints and authorize org-scoped realtime channels.

## Error Handling

**Strategy:** Authorize and validate at the boundary, let middleware and policies short-circuit invalid access, and return explicit redirects, validation errors, abort responses, or dedicated error views.

**Patterns:**
- Livewire methods throw or catch `ValidationException` and `AuthorizationException` close to the interaction point in `app/Livewire/Auth/LoginPage.php` and `app/Livewire/Tenant/SubmitReadingPage.php`.
- Middleware enforces account/subscription/onboarding state in `app/Http/Middleware/EnsureAccountIsAccessible.php`, `app/Http/Middleware/EnsureOnboardingIsComplete.php`, and `app/Http/Middleware/CheckSubscriptionStatus.php`; blocked states redirect to login, onboarding, or `resources/views/errors/subscription-suspended.blade.php`.
- Controllers use route model binding with `abort_if()` / `abort_unless()` in `app/Http/Controllers/TenantPortalRouteController.php` and `app/Http/Controllers/NotificationTrackingController.php`.
- CSP violations are recorded and acknowledged with `202 No Content` in `app/Http/Controllers/CspViolationReportController.php`.
- User-facing fallback error screens live in `resources/views/errors/403.blade.php`, `resources/views/errors/404.blade.php`, `resources/views/errors/500.blade.php`, and `resources/views/errors/layout.blade.php`.

## Cross-Cutting Concerns

**Logging and Audit:**
- Audit and security records flow through `app/Filament/Support/Audit/AuditLogger.php`, model observers in `app/Observers/*`, and audit/security models such as `app/Models/AuditLog.php`, `app/Models/OrganizationActivityLog.php`, `app/Models/SuperAdminAuditLog.php`, and `app/Models/SecurityViolation.php`.

**Validation:**
- Validation is concentrated in `app/Http/Requests/*` and reused inside Livewire through `app/Http/Requests/Concerns/InteractsWithValidationPayload.php`.
- Subscription-aware custom rules live in `app/Rules/WithinPropertyLimit.php` and `app/Rules/WithinTenantLimit.php`.

**Authentication and Authorization:**
- Web auth is applied through route groups in `routes/web.php` plus middleware registered in `bootstrap/app.php`.
- Panel access is gated by `app/Http/Middleware/AuthenticateAdminPanel.php` and per-page/resource checks such as `app/Filament/Pages/PlatformDashboard.php` and `app/Filament/Resources/Properties/PropertyResource.php`.
- Model-level authorization is centralized in `app/Policies/*` and referenced by Filament resources, Gate checks, and route model binding flows.

**Tenant and Organization Scoping:**
- Organization context is resolved centrally in `app/Filament/Support/Admin/OrganizationContext.php`.
- Workspace read models are encoded as Eloquent scopes in `app/Models/Organization.php`, `app/Models/Property.php`, `app/Models/Meter.php`, `app/Models/Invoice.php`, and `app/Models/User.php`.

**Security Headers and CSP:**
- Security headers are prepared/applied by `app/Http/Middleware/SecurityHeaders.php` and `app/Services/Security/SecurityHeaderService.php`.
- CSP violation intake is handled by `app/Http/Controllers/CspViolationReportController.php`.

**Localization:**
- Guest and authenticated locale handling flows through `app/Http/Middleware/SetGuestLocale.php`, `app/Http/Middleware/SetAuthenticatedUserLocale.php`, `app/Filament/Actions/Preferences/*`, and translations in `lang/*/*.php`.

---

*Architecture analysis: 2026-03-19*
