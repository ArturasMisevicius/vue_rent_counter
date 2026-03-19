# Codebase Structure

**Analysis Date:** 2026-03-19

## Directory Layout

```text
tenanto/
├── app/                    # Application code: Filament, Livewire, HTTP, models, policies, services
│   ├── Filament/           # Unified panel resources, pages, widgets, actions, support, concerns
│   ├── Livewire/           # Auth, public, shell, dashboard, and tenant interactive components
│   ├── Http/               # Controllers, middleware, and form requests
│   ├── Models/             # Eloquent entities and query scopes
│   ├── Policies/           # Authorization rules
│   ├── Providers/          # App, auth, and Filament panel providers
│   ├── Services/           # Billing, security, subscription, impersonation services
│   └── View/Components/    # Blade component classes
├── bootstrap/              # Laravel bootstrap and provider list
├── config/                 # Laravel and product configuration
├── database/               # Migrations, factories, seeders, local SQLite database
├── docs/                   # Project context and verified session bootstrap docs
├── lang/                   # Translation files by locale and domain
├── openspec/               # Spec/change workflow documents
├── resources/              # Blade views plus frontend assets
│   └── views/              # Layouts, Blade components, Livewire views, Filament page stubs
├── routes/                 # Web, broadcast, console, and test routes
├── tests/                  # Feature, unit, performance, and support test code
├── .planning/              # Generated planning artifacts, including codebase maps
├── .mcp.json               # Repo-local MCP server declarations
├── artisan                 # Laravel CLI entry point
├── composer.json           # PHP dependencies and scripts
├── package.json            # Frontend tooling dependencies and scripts
└── phpunit.xml             # PHPUnit/Pest configuration
```

## Directory Purposes

**`app/Filament/`:**
- Purpose: Home of the unified panel surface and the shared foundations the panel uses.
- Contains: resource bundles, custom pages, widgets, action classes, support services, and panel-level concerns.
- Key files: `app/Providers/Filament/AppPanelProvider.php`, `app/Filament/Pages/Dashboard.php`, `app/Filament/Resources/Organizations/OrganizationResource.php`, `app/Filament/Support/Admin/OrganizationContext.php`
- Subdirectories: `Resources/` for CRUD surfaces, `Pages/` for custom pages, `Widgets/` for dashboard widgets, `Actions/` for role-scoped mutations, `Support/` for panel-side services, and `Concerns/` for reusable authorization/subscription helpers.

**`app/Livewire/`:**
- Purpose: Interactive page and shell layer for public, auth, dashboard, and tenant workflows.
- Contains: standalone page components, shell components, endpoint-like action components, and tenant self-service flows.
- Key files: `app/Livewire/Auth/LoginPage.php`, `app/Livewire/PublicSite/HomepagePage.php`, `app/Livewire/Shell/GlobalSearch.php`, `app/Livewire/Tenant/SubmitReadingPage.php`
- Subdirectories: `Auth/`, `Onboarding/`, `Pages/Dashboard/`, `Pages/Reports/`, `Preferences/`, `Profile/`, `PublicSite/`, `Shell/`, and `Tenant/`.

**`app/Http/`:**
- Purpose: HTTP boundary code that remains outside the Livewire/Filament surface.
- Contains: thin controllers, middleware, and all request validation classes.
- Key files: `app/Http/Controllers/TenantPortalRouteController.php`, `app/Http/Middleware/CheckSubscriptionStatus.php`, `app/Http/Requests/Concerns/InteractsWithValidationPayload.php`
- Subdirectories: `Controllers/`, `Middleware/`, and `Requests/` with nested role/feature folders such as `Requests/Admin/*`, `Requests/Superadmin/*`, and `Requests/Tenant/*`.

**`app/Models/`:**
- Purpose: Eloquent domain model layer.
- Contains: tenancy/control-plane entities, workspace/billing entities, security/reference entities, and collaboration/operations entities.
- Key files: `app/Models/Organization.php`, `app/Models/User.php`, `app/Models/Property.php`, `app/Models/Meter.php`, `app/Models/Invoice.php`, `app/Models/Subscription.php`
- Subdirectories: Flat model namespace; organization comes from naming and relationships rather than subfolders.

**`app/Policies/`:**
- Purpose: Central authorization rules for model-bound access.
- Contains: policy classes and shared policy concerns.
- Key files: `app/Policies/OrganizationPolicy.php`, `app/Policies/PropertyPolicy.php`, `app/Policies/InvoicePolicy.php`, `app/Policies/Concerns/AuthorizesSuperadminOnly.php`
- Subdirectories: Mostly flat, with `Concerns/` for shared policy behavior.

**`app/Providers/`:**
- Purpose: Service registration and runtime composition.
- Contains: application bindings, policy registration, and Filament panel registration.
- Key files: `app/Providers/AppServiceProvider.php`, `app/Providers/AuthServiceProvider.php`, `app/Providers/Filament/AppPanelProvider.php`
- Subdirectories: `Filament/` currently holds the panel provider.

**`app/Services/`:**
- Purpose: Non-UI services that sit outside the Filament support tree.
- Contains: billing, security, subscription, impersonation, meter-reading, and notification preference services.
- Key files: `app/Services/Billing/BillingService.php`, `app/Services/Billing/InvoicePdfService.php`, `app/Services/SubscriptionChecker.php`, `app/Services/Security/SecurityHeaderService.php`
- Subdirectories: `Billing/` and `Security/` are feature-grouped; other services are currently flat.

**`resources/views/`:**
- Purpose: Blade rendering layer for layouts, components, Livewire views, error pages, and Filament page shells.
- Contains: auth pages, shared and shell components, Livewire view templates, Filament page wrappers, and the public landing page.
- Key files: `resources/views/layouts/app.blade.php`, `resources/views/layouts/public.blade.php`, `resources/views/components/shell/app-frame.blade.php`, `resources/views/filament/pages/dashboard.blade.php`, `resources/views/livewire/tenant/invoice-history.blade.php`, `resources/views/welcome.blade.php`
- Subdirectories: `auth/`, `components/`, `errors/`, `filament/`, `layouts/`, `livewire/`, `onboarding/`, and `profile/`.

**`database/`:**
- Purpose: Persistence definition, seed data, and model test-data support.
- Contains: timestamped migrations, factories for nearly every model, seeders for languages/system/platform/legacy/demo data, and `database/database.sqlite`.
- Key files: `database/migrations/2026_03_17_000100_create_organizations_table.php`, `database/migrations/2026_03_17_110300_create_property_assignments_table.php`, `database/migrations/2026_03_19_090100_create_organization_user_table.php`, `database/seeders/DatabaseSeeder.php`
- Subdirectories: `migrations/`, `factories/`, and `seeders/`.

**`routes/`:**
- Purpose: Route definitions split by runtime surface.
- Contains: root web routes, nested guest/authenticated/logout web splits, console routes, broadcast channels, and test-only routes.
- Key files: `routes/web.php`, `routes/web/guest.php`, `routes/web/authenticated.php`, `routes/web/logout.php`, `routes/channels.php`, `routes/testing.php`
- Subdirectories: `web/` contains the split web route files.

**`tests/`:**
- Purpose: Executable architecture, behavior, and performance verification.
- Contains: feature tests grouped by role/surface, unit tests, performance tests, and support helpers/factories.
- Key files: `tests/Pest.php`, `tests/TestCase.php`, `tests/Feature/Architecture/FilamentFoundationPlacementTest.php`, `tests/Feature/Livewire/ControllerRouteMigrationTest.php`, `tests/Performance/DashboardPerformanceTest.php`
- Subdirectories: `Feature/`, `Unit/`, `Performance/`, `Support/`, and `tmp/`.

**`docs/`:**
- Purpose: Canonical repo context and verified operator guidance.
- Contains: snapshot and bootstrap documents that explain how to reason about the repository and current MCP state.
- Key files: `docs/PROJECT-CONTEXT.md`, `docs/SESSION-BOOTSTRAP.md`
- Subdirectories: Flat docs plus longer-lived planning/history files elsewhere in the repo.

## Key File Locations

**Entry Points:**
- `bootstrap/app.php`: Laravel bootstrap, route registration, middleware aliases, and web middleware stack.
- `app/Providers/Filament/AppPanelProvider.php`: Unified Filament panel definition for `/app`.
- `routes/web.php`: Root public web routing plus inclusion of split route files.
- `routes/web/guest.php`: Guest auth flows such as login, register, reset password, and invitation acceptance.
- `routes/web/authenticated.php`: Authenticated profile, onboarding, impersonation, notification tracking, and tenant portal redirects.
- `routes/web/logout.php`: Logout endpoint.
- `routes/channels.php`: Broadcast channel authorization.
- `routes/console.php`: Console route registration.

**Configuration:**
- `config/tenanto.php`: Product-specific auth, locales, subscription, shell navigation, and global search configuration.
- `config/filament.php`: Filament cache/assets/system route configuration.
- `.env.example`: Expected environment keys for local setup.
- `.mcp.json`: Repo-local MCP server declarations.
- `vite.config.js`: Frontend asset build configuration.
- `phpunit.xml`: Test runner configuration.

**Core Logic:**
- `app/Filament/Resources/`: Filament CRUD surfaces and relation managers.
- `app/Filament/Actions/`: Role-scoped action classes that perform mutations.
- `app/Filament/Support/`: Panel-side support services, presenters, registries, and report builders.
- `app/Livewire/`: Interactive page and shell behavior.
- `app/Models/`: Domain entities and reusable query scopes.
- `app/Services/`: Billing, security, impersonation, and subscription services outside the panel support tree.

**Testing:**
- `tests/Feature/`: End-to-end HTTP, Livewire, Filament, role, security, and tenant behavior tests.
- `tests/Unit/`: Focused unit tests for enums, requests, services, and support classes.
- `tests/Performance/`: Query-budget and performance assertions.
- `tests/Support/`: Shared factories/helpers such as `tests/Support/TenantPortalFactory.php`.
- `tests/Pest.php`: Test bootstrap helpers and shared setup.

**Documentation:**
- `AGENTS.md`: Repo-specific working rules and stack instructions.
- `docs/PROJECT-CONTEXT.md`: Canonical project snapshot.
- `docs/SESSION-BOOTSTRAP.md`: Verified session-start commands and MCP assumptions.
- `openspec/AGENTS.md`: Spec workflow instructions for proposal/change work.
- `.planning/codebase/`: Generated codebase map documents for orchestration workflows.

## Naming Conventions

**Files:**
- `PascalCase.php` for PHP classes across `app/`, such as `app/Livewire/Tenant/SubmitReadingPage.php` and `app/Filament/Actions/Superadmin/Organizations/CreateOrganizationAction.php`.
- Singular Filament resource root files inside plural directories, such as `app/Filament/Resources/Properties/PropertyResource.php`.
- `kebab-case.blade.php` for views, such as `resources/views/filament/pages/tenant-invoice-history.blade.php` and `resources/views/livewire/shell/global-search.blade.php`.
- `*Test.php` for test files, such as `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`.
- Timestamped snake_case migration files in `database/migrations/`.

**Directories:**
- Role/surface grouping for requests, actions, and tests, such as `app/Http/Requests/Admin/*`, `app/Filament/Actions/Tenant/*`, and `tests/Feature/Superadmin/*`.
- Filament resource bundle structure with child directories such as `Pages/`, `Schemas/`, `Tables/`, `RelationManagers/`, and `Widgets/`.
- Livewire directories organized by workflow area: `Auth/`, `PublicSite/`, `Shell/`, `Tenant/`, and `Pages/*`.
- Locale-first translation layout under `lang/{locale}/{domain}.php`.

**Special Patterns:**
- Filament page views in `resources/views/filament/pages/*` often exist only to mount a Livewire component, for example `resources/views/filament/pages/tenant-invoice-history.blade.php`.
- Route definitions are split by context under `routes/web/*.php` instead of piling all web routes into one file.
- Architecture guardrails are enforced by `tests/Feature/Architecture/FilamentFoundationPlacementTest.php`, which expects request classes in `app/Http/Requests` and action/support classes in `app/Filament/Actions` and `app/Filament/Support`.

## Where to Add New Code

**New Admin CRUD Feature:**
- Primary code: `app/Filament/Resources/{PluralFeature}/`
- Validation: `app/Http/Requests/Admin/{PluralFeature}/`
- Mutations: `app/Filament/Actions/Admin/{PluralFeature}/`
- Model/query scopes: `app/Models/{Model}.php`
- Tests: `tests/Feature/Admin/{Feature}Test.php`

**New Superadmin Control-Plane Feature:**
- Primary code: `app/Filament/Resources/{PluralFeature}/` or `app/Filament/Pages/{Feature}.php`
- Validation: `app/Http/Requests/Superadmin/{Feature}/`
- Mutations: `app/Filament/Actions/Superadmin/{Feature}/`
- Support logic: `app/Filament/Support/Superadmin/{Feature}/`
- Tests: `tests/Feature/Superadmin/{Feature}Test.php`

**New Tenant Self-Service Page or Interaction:**
- Panel page shell: `app/Filament/Pages/{TenantPage}.php`
- Livewire implementation: `app/Livewire/Tenant/{Component}.php`
- Filament page view wrapper: `resources/views/filament/pages/{kebab-page}.blade.php`
- Livewire view: `resources/views/livewire/tenant/{kebab-component}.blade.php`
- Validation/actions: `app/Http/Requests/Tenant/*` and `app/Filament/Actions/Tenant/*`
- Tests: `tests/Feature/Tenant/{Feature}Test.php`

**New Public or Auth Flow:**
- Route definition: `routes/web.php` for public root-level routes or `routes/web/guest.php` for guest auth routes
- Livewire page/action: `app/Livewire/PublicSite/*` or `app/Livewire/Auth/*`
- View: `resources/views/welcome.blade.php`, `resources/views/auth/*`, or a new matching Blade file
- Tests: `tests/Feature/Public/*` or `tests/Feature/Auth/*`

**New Shared Panel/UI Support Module:**
- Shared panel logic: `app/Filament/Support/{Area}/`
- Blade component class: `app/View/Components/{Area}/`
- Blade component view: `resources/views/components/{area}/`
- Shell/navigation/search helpers: keep them under `app/Filament/Support/Shell/*`

**New Cross-App Service or Framework Integration:**
- Implementation: `app/Services/{Area}/` or `app/Services/{Service}.php`
- Contract if needed: `app/Contracts/{Contract}.php`
- Tests: `tests/Unit/Services/{Service}Test.php` or a matching feature test

**New Route or Endpoint Wrapper:**
- Web route file: `routes/web.php`, `routes/web/guest.php`, `routes/web/authenticated.php`, or `routes/web/logout.php`, matching the auth boundary
- Thin controller: `app/Http/Controllers/{Controller}.php` only if the endpoint is a redirect/download/webhook-style boundary
- Prefer Livewire action components over growing the controller layer for page-like flows

**Utilities and Guardrails:**
- Shared validation belongs in `app/Http/Requests/*` and `app/Rules/*`
- Do not create `app/Actions` or `app/Support`; the current codebase keeps these foundations in `app/Filament/Actions` and `app/Filament/Support`
- Keep model-centric scopes and relationship summaries in `app/Models/{Model}.php` when the logic directly shapes data access

## Special Directories

**`bootstrap/cache/`:**
- Purpose: Laravel and Filament cached metadata such as services, packages, events, and Blade icon/component caches.
- Source: Generated by framework/package cache commands.
- Committed: Partially; the directory is tracked and generated cache files are currently present.

**`.planning/codebase/`:**
- Purpose: Generated codebase map documents consumed by planning/execution workflows.
- Source: Written by codebase mapping commands and agents.
- Committed: Yes.

**`openspec/`:**
- Purpose: Change proposal/spec workflow files used when planning or documenting major changes.
- Source: Maintained project documentation rather than runtime code.
- Committed: Yes.

---

*Structure analysis: 2026-03-19*
