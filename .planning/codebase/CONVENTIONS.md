# Coding Conventions

**Analysis Date:** 2026-03-19

## Naming Patterns

**Files:**
- Use `PascalCase.php` for PHP classes and keep the path aligned to the namespace, for example `app/Livewire/Auth/LoginPage.php`, `app/Http/Requests/Profile/UpdateProfileRequest.php`, and `app/Filament/Resources/Users/UserResource.php`.
- Use `kebab-case.blade.php` for Blade templates, with directories mirroring the surface area, for example `resources/views/livewire/pages/dashboard/admin-dashboard.blade.php`, `resources/views/livewire/shell/topbar.blade.php`, and `resources/views/components/shell/app-frame.blade.php`.
- Name tests as `*Test.php` and group them by behavior area under `tests/Feature/*`, `tests/Unit/*`, and `tests/Performance/*`, for example `tests/Feature/Auth/LoginFlowTest.php` and `tests/Unit/Requests/FormRequestStructureTest.php`.
- For Filament resources, split large resources into companion `Pages`, `Schemas`, `Tables`, and `RelationManagers` classes, for example `app/Filament/Resources/Users/Pages/ListUsers.php`, `app/Filament/Resources/Users/Schemas/UserForm.php`, and `app/Filament/Resources/Tenants/RelationManagers/InvoicesRelationManager.php`.

**Functions:**
- Use `camelCase` for methods and helper functions across models, requests, Livewire components, services, and tests, for example `scopeWithWorkspaceSummary()` in `app/Models/Property.php` and `registerSharedTestRoutes()` in `tests/Pest.php`.
- Use `handle()` as the main entrypoint for action classes in `app/Filament/Actions`, for example `app/Filament/Actions/Admin/Properties/CreatePropertyAction.php`.
- Name Eloquent scopes with the `scopeX` pattern and keep them chainable, for example `scopeForSuperadminControlPlane()` in `app/Models/Organization.php` and `scopeWithTenantWorkspaceSummary()` in `app/Models/User.php`.
- Expose Livewire derived state through noun-style computed methods decorated with `#[Computed]`, for example `demoAccounts()` in `app/Livewire/Auth/LoginPage.php` and `dashboard()` in `app/Livewire/Pages/Dashboard/AdminDashboard.php`.

**Variables:**
- Use descriptive `camelCase` variables and properties such as `$organizationId`, `$showSubscriptionUsage`, `$queryCount`, and `$selectedAssignmentKeys` in `app/Livewire/Pages/Dashboard/AdminDashboard.php`, `tests/Performance/DashboardPerformanceTest.php`, and `app/Services/Billing/BillingService.php`.
- Use `UPPER_SNAKE_CASE` for class constants, especially query column lists and TTLs, such as `CONTROL_PLANE_COLUMNS` in `app/Models/Organization.php` and `SUPERADMIN_STATS_TTL_SECONDS` in `app/Filament/Support/Dashboard/DashboardCacheService.php`.
- Prefer intent-revealing array payload names like `$dashboard`, `$valid`, `$skipped`, and `$coverageMatrix` in `app/Livewire/Pages/Dashboard/AdminDashboard.php`, `app/Services/Billing/BillingService.php`, and `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`.

**Types:**
- Use `PascalCase` for classes, enums, traits, and interfaces, for example `App\Enums\UserRole`, `App\Filament\Support\Dashboard\DashboardCacheService`, and `App\Contracts\BillingServiceInterface`.
- Use enum case names in `UPPER_SNAKE_CASE`, as reflected by `app/Enums/UserRole.php` and enforced by `tests/Feature/Architecture/TranslatedEnumContractTest.php`.
- Suffix interfaces with `Interface` instead of using an `I` prefix, for example `app/Contracts/BillingServiceInterface.php`.
- Document array-heavy return types with PHPDoc array shapes when native types are too broad, for example `app/Http/Requests/Profile/UpdateProfileRequest.php`, `app/Services/Billing/BillingService.php`, and `tests/Pest.php`.

## Code Style

**Formatting:**
- Base whitespace rules come from `.editorconfig`: UTF-8, LF line endings, four-space indentation, final newline, and trimmed trailing whitespace outside Markdown.
- Use semicolons consistently in PHP files such as `app/Models/User.php` and `app/Providers/AppServiceProvider.php`.
- Keep multiline arrays, constructor arguments, and fluent chains vertically formatted with trailing commas, as in `app/Services/Billing/BillingService.php`, `app/Livewire/Auth/LoginPage.php`, and `tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php`.
- Prefer single-quoted strings in PHP unless interpolation or quoting pressure makes double quotes clearer, as shown across `app/Filament/Support/Dashboard/DashboardCacheService.php` and `tests/Feature/Admin/PropertiesResourceTest.php`.
- There is no repo-local `pint.json`; formatting is driven by Laravel Pint defaults plus the project rule in `AGENTS.md` to run `vendor/bin/pint --dirty --format agent`.
- `declare(strict_types=1);` is adopted unevenly. Newer request, service, support, and selected Livewire/test files use it, such as `app/Http/Requests/Profile/UpdateProfileRequest.php`, `app/Services/Billing/BillingService.php`, `app/Livewire/Pages/Dashboard/AdminDashboard.php`, and `tests/Unit/Requests/FormRequestValidationTest.php`. Many models, policies, Filament resources, and Blade component classes still omit it, such as `app/Models/User.php`, `app/Policies/OrganizationPolicy.php`, `app/Filament/Resources/Users/UserResource.php`, and `app/View/Components/Shell/AppFrame.php`.

**Linting:**
- Use Laravel Pint as the active formatter. The repository instructions in `AGENTS.md` and `README.md` expect `vendor/bin/pint --dirty` before closeout.
- No repo-local `phpstan.neon`, `phpstan.neon.dist`, or alternative `phpstan*` config was detected in the project root.
- No repo-local GitHub Actions workflow directory was detected under `.github/workflows`, so style and static-analysis enforcement is documented in project instructions rather than visible CI config.

## Import Organization

**Order:**
1. Application imports from `App\...`
2. Package and framework imports from `Illuminate\...`, `Livewire\...`, `Filament\...`, `Carbon\...`, or similar
3. Test-only function imports when needed, such as `use function PHPUnit\Framework\assertContains;` in `tests/Unit/Requests/FormRequestStructureTest.php`

**Grouping:**
- Keep all imports beneath the namespace declaration and use one import per line, as seen in `app/Livewire/Auth/LoginPage.php`, `app/Providers/AppServiceProvider.php`, and `tests/Feature/Auth/LoginFlowTest.php`.
- Separate the `App\...` block from framework/package imports with a blank line only when the file structure benefits from it. Some files stay as a single uninterrupted block, so preserve the sibling-file pattern for the area you are touching.
- Do not rely on import sorting tools that rewrite domain grouping. Many files are grouped semantically rather than strictly alphabetically, for example `app/Providers/AppServiceProvider.php` and `app/Services/Billing/BillingService.php`.

**Path Aliases:**
- PHP code relies on Composer PSR-4 namespaces from `composer.json`: `App\`, `Database\Factories\`, `Database\Seeders\`, and `Tests\`.
- Blade templates use component aliases and view names instead of filesystem aliases, for example `<x-shell.app-frame>` from `resources/views/components/shell/app-frame.blade.php` and `view('auth.login')` from `app/Livewire/Auth/LoginPage.php`.

## Error Handling

**Patterns:**
- Keep validation inside Form Requests under `app/Http/Requests`, then call those requests from actions and Livewire endpoints, for example `app/Filament/Actions/Admin/Properties/CreatePropertyAction.php` and `app/Livewire/Auth/LoginPage.php`.
- Use framework exceptions and authorization guards at boundaries instead of custom result wrappers. Common patterns are `ValidationException::withMessages()` in `app/Livewire/Auth/LoginPage.php` and `abort_unless()` in `app/Livewire/Pages/Dashboard/AdminDashboard.php`.
- Keep policies thin and boolean-returning, with one method per ability, as in `app/Policies/OrganizationPolicy.php`.
- Prefer action/support-layer validation or mutation helpers over putting rules directly in controllers or Blade templates, which matches the enforced directory rules in `tests/Feature/Architecture/FilamentFoundationPlacementTest.php`.

**Error Types:**
- Throw `ValidationException` for user-correctable input failures, for example in `app/Services/Billing/BillingService.php`.
- Return HTTP 403/404 through authorization and model scoping boundaries instead of manual response objects, which is the behavior asserted in `tests/Feature/Filament/SuperadminResourcesTest.php` and `tests/Feature/Admin/PropertiesResourceTest.php`.
- Keep security and audit concerns in dedicated services, events, and observers rather than inline logging, for example `app/Services/Security/SecurityMonitor.php`, `app/Events/SecurityViolationDetected.php`, and `app/Observers/UserObserver.php`.

## Logging

**Framework:**
- Ad hoc `logger()` or `Log::...` usage is not a dominant convention in the inspected files.
- Cross-cutting audit and security traces flow through dedicated abstractions such as `app/Filament/Support/Audit/AuditLogger.php`, `app/Services/Security/SecurityMonitor.php`, and observer classes in `app/Observers`.

**Patterns:**
- Put audit and tracking behavior behind named support services or observers instead of embedding log statements inside models or Blade views.
- Trigger events for cross-cutting notifications and monitoring, for example `app/Events/InvoiceFinalized.php` and `app/Events/SecurityViolationDetected.php`.
- Keep UI code free of debugging output. No `console.log` or dump-style debugging was present in the representative Blade views under `resources/views`.

## Comments

**When to Comment:**
- Prefer minimal inline comments. The inspected codebase leans on clear naming and extracted helpers instead of explanatory inline prose.
- Use docblocks when native PHP types are not expressive enough, especially for array shapes, generic collections, or trait usage, as in `app/Models/User.php`, `app/Http/Requests/Profile/UpdateProfileRequest.php`, and `tests/Pest.php`.
- Keep comments focused on contract details rather than restating obvious code, which matches files like `app/Filament/Support/Dashboard/DashboardCacheService.php` and `app/Policies/OrganizationPolicy.php`.

**JSDoc/TSDoc:**
- Not applicable in this repository focus area.
- For PHPDoc, use `@return`, `@param`, and generic collection annotations where needed, as in `app/Services/Billing/BillingService.php`, `tests/Unit/Requests/FormRequestStructureTest.php`, and `tests/Support/FormRequestScenarioFactory.php`.

**TODO Comments:**
- No TODO-comment convention was evident in the representative files inspected for this mapping pass.

## Function Design

**Size:**
- Keep boundary methods short and push repeated work into helpers or support classes. Examples include `render()` and `mount()` in `app/Livewire/Pages/Dashboard/AdminDashboard.php` and `handle()` in `app/Filament/Actions/Admin/Properties/CreatePropertyAction.php`.
- Let large orchestration methods live in service classes only when they own the full workflow, as in `app/Services/Billing/BillingService.php`.

**Parameters:**
- Prefer typed scalar/object parameters and constructor injection, for example `app/Filament/Actions/Admin/Properties/CreatePropertyAction.php` and `app/Livewire/Auth/LoginPage.php`.
- Use associative arrays for validated payloads only at mutation boundaries, then document the expected shape in PHPDoc, as in `app/Services/Billing/BillingService.php`.
- In Blade components, define top-level props with `@props([...])`, as in `resources/views/components/layouts/app.blade.php` and `resources/views/components/shell/app-frame.blade.php`.

**Return Values:**
- Declare explicit return types wherever feasible. This is common in requests, services, Livewire components, and support classes such as `app/Http/Requests/Profile/UpdateProfileRequest.php`, `app/Livewire/Auth/LoginPage.php`, and `app/Filament/Support/Dashboard/DashboardCacheService.php`.
- For query helpers and scopes, return the `Builder` so chains stay composable, as in `app/Models/Organization.php`, `app/Models/Property.php`, and `app/Models/User.php`.
- For Livewire view components, return `View` from `render()` and keep view data explicit, as in `app/Livewire/Auth/LoginPage.php` and `app/View/Components/Shell/AppFrame.php`.

## Module Design

**Exports:**
- There are no barrel files or index-style re-export modules in the PHP application tree. Classes are referenced directly by full namespace, as seen across `app/Providers/AppServiceProvider.php` and the tests under `tests/Feature`.
- Keep one primary class per file and map the namespace directly to the filesystem path.

**Barrel Files:**
- Not used in the PHP application tree.
- Reuse direct class imports and direct view/component names instead of adding aggregation layers.

## Layer-Specific Conventions

**Requests and Validation:**
- Put request validation in `app/Http/Requests` and mix in `App\Http\Requests\Concerns\InteractsWithValidationPayload`, as shown in `app/Http/Requests/Profile/UpdateProfileRequest.php` and enforced by `tests/Unit/Requests/FormRequestStructureTest.php`.
- Define the full request contract on each form request: `authorize()`, `rules()`, `messages()`, `attributes()`, and `prepareForValidation()`.

**Models and Queries:**
- Keep reusable filtering and eager-loading logic on models via local scopes, for example `app/Models/Organization.php`, `app/Models/Property.php`, and `app/Models/User.php`.
- Prefer explicit select lists and eager-loaded summary scopes for workspace/control-plane queries, such as `scopeForSuperadminControlPlane()` in `app/Models/Organization.php` and `scopeWithWorkspaceSummary()` in `app/Models/Property.php`.

**Filament and Support Code:**
- Keep business mutations in `app/Filament/Actions`, shared orchestration in `app/Filament/Support`, and resource definitions in `app/Filament/Resources`. This layout is both instructed in `AGENTS.md` and enforced in `tests/Feature/Architecture/FilamentFoundationPlacementTest.php`.
- Filament resources often remain class-based and may omit `declare(strict_types=1);`, so match the local style of the resource subtree you are editing.

**Livewire and Blade:**
- Keep Livewire `render()` methods thin and pass precomputed view data to Blade, as in `app/Livewire/Auth/LoginPage.php` and `app/Livewire/Pages/Dashboard/AdminDashboard.php`.
- Keep Blade files presentational. Use `@props`, `@forelse`, Livewire navigation directives, and small `@php(...)` assignments, as in `resources/views/components/layouts/app.blade.php`, `resources/views/auth/login.blade.php`, and `resources/views/livewire/shell/topbar.blade.php`.
- Use `wire:poll` and `wire:navigate` directly in views when the UI surface is intentionally real-time or SPA-like, as in `resources/views/livewire/pages/dashboard/admin-dashboard.blade.php` and `resources/views/livewire/shell/topbar.blade.php`.

---

*Convention analysis: 2026-03-19*
