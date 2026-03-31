# Testing Map

## Scope

This document maps how the repository currently tests behavior, structure, and quality. It is intended as a planning reference for adding or updating tests in the same style.

Primary reference files:

- `tests/Pest.php`
- `tests/TestCase.php`
- `phpunit.xml`
- `composer.json`
- `README.md`

## Test framework and runner conventions

- The suite is Pest-based. `composer.json` requires `pestphp/pest` and `pestphp/pest-plugin-laravel`.
- `tests/Pest.php` globally extends `Tests\TestCase` for the `Feature` suite via:
  - `pest()->extend(TestCase::class)->in('Feature');`
- `phpunit.xml` declares two test suites only:
  - `tests/Unit`
  - `tests/Feature`
- `tests/Performance/DashboardPerformanceTest.php` exists outside those two suites. Treat `tests/Performance/` as opt-in/manual coverage unless explicitly invoked.

## Test environment and bootstrap behavior

- `tests/TestCase.php` forces deterministic in-memory SQLite configuration on every application bootstrap:
  - `APP_ENV=testing`
  - `DB_CONNECTION=sqlite`
  - `DB_DATABASE=:memory:`
- It also overrides route/config cache paths to testing-specific cache files under `bootstrap/cache/`.
- `phpunit.xml` reinforces the runtime with array-backed cache/session/mail and sync queues.
- `tests/Pest.php` resets shared state in `beforeEach()` and `afterEach()`:
  - logs out any authenticated user
  - resets Carbon test time
  - flushes `ManagerPermissionService` cache
  - seeds supported locale config for tests
  - registers shared synthetic routes used by auth/error tests

## Test layout and directory conventions

- Feature tests are grouped by product surface or concern, for example:
  - `tests/Feature/Admin/`
  - `tests/Feature/Superadmin/`
  - `tests/Feature/Livewire/`
  - `tests/Feature/Security/`
  - `tests/Feature/Architecture/`
  - `tests/Feature/Public/`
- Unit tests are grouped by technical subject, for example:
  - `tests/Unit/Requests/`
  - `tests/Unit/Services/`
  - `tests/Unit/Support/`
  - `tests/Unit/Enums/`
- Naming is behavior-first and highly explicit. Examples:
  - `tests/Feature/Admin/InvoicesResourceTest.php`
  - `tests/Feature/Architecture/WorkspaceReadModelInventoryTest.php`
  - `tests/Unit/Requests/FormRequestValidationTest.php`
  - `tests/Unit/Services/SecurityMonitoringServiceTest.php`

## Base helpers and shared fixtures

- `tests/Pest.php` defines shared helper functions used across the suite:
  - `createOrgWithAdmin()`
  - `createTenantInOrg()`
  - `signInAs(UserRole $role)`
  - `registerSharedTestRoutes()`
- Those helpers are themselves tested in `tests/Feature/TestBootstrapHelpersTest.php`, which is a strong signal that helper behavior is part of the contract.
- Two reusable support builders live under `tests/Support/`:
  - `tests/Support/TenantPortalFactory.php` builds realistic tenant/property/meter/invoice fixtures.
  - `tests/Support/FormRequestScenarioFactory.php` generates cross-request validation/authorization scenarios.

## Database lifecycle and fixtures

- The dominant database trait is `RefreshDatabase`, usually declared per file with Pest’s `uses(...)` syntax. Examples:
  - `tests/Feature/Admin/InvoicesResourceTest.php`
  - `tests/Feature/Livewire/Dashboard/DashboardPageTest.php`
  - `tests/Unit/Requests/FormRequestValidationTest.php`
  - `tests/Unit/Services/SecurityMonitoringServiceTest.php`
- The suite relies heavily on model factories, usually chaining `for(...)`, named states, and inline overrides instead of raw insert arrays.
- Core factory state usage appears in many tests and is defined in files like:
  - `database/factories/UserFactory.php` (`superadmin()`, `admin()`, `manager()`, `tenant()`, `suspended()`, `withLocale()`)
  - `database/factories/SubscriptionFactory.php`
  - `database/factories/InvoiceFactory.php`
- Seeders are also first-class tested artifacts, especially for demo/reference data. Example seeders:
  - `database/seeders/OperationalDemoDatasetSeeder.php`
  - `database/seeders/LegacyReferenceFoundationSeeder.php`
  - `database/seeders/BalticReferenceLocalizationSeeder.php`

## Feature test style

- Feature tests mix HTTP assertions and Livewire/Filament component assertions depending on the surface.
- Representative HTTP-style assertions:
  - route access and visibility in `tests/Feature/Admin/InvoicesResourceTest.php`
  - redirect/auth flows in `tests/Feature/Auth/LoginFlowTest.php`
  - public route behavior in `tests/Feature/Public/GuestAuthLocaleSwitcherTest.php`
- Representative structural/contract feature tests:
  - `tests/Feature/Architecture/WorkspaceReadModelInventoryTest.php`
  - `tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php`
  - `tests/Feature/Architecture/ReportBuildersNoRawSqlTest.php`
- The suite does not only test business outcomes; it also tests architecture boundaries and inventory contracts to prevent regression-by-drift.

## Livewire and Filament testing patterns

- Livewire testing uses `Livewire::test(...)` and `Livewire::actingAs(...)` extensively.
- Representative files:
  - `tests/Feature/Livewire/Dashboard/DashboardPageTest.php`
  - `tests/Feature/Filament/SuperadminResourcesTest.php`
  - `tests/Feature/Admin/InvoicesResourceTest.php`
  - `tests/Feature/Admin/TenantsResourceTest.php`
- Common Filament assertions in the suite include:
  - `assertTableColumnExists(...)`
  - `assertTableFilterExists(...)`
  - `assertCanSeeTableRecords(...)`
  - `assertCanNotSeeTableRecords(...)`
  - `filterTable(...)`
  - page/component redirects after actions
- Example: `tests/Feature/Admin/InvoicesResourceTest.php` exercises both plain HTTP resource pages and Livewire table/page behavior for `ListInvoices`, `CreateInvoice`, and `ViewInvoice`.

## Authorization and role testing patterns

- Role-aware access testing is a recurring theme.
- Auth helpers are used in three main ways:
  - `$this->actingAs($user)` for HTTP tests
  - `test()->actingAs($user)` inside Pest closures
  - `Livewire::actingAs($user)` for component tests
- Role matrix examples:
  - `tests/Feature/Manager/ManagerWorkspaceParityTest.php`
  - `tests/Feature/Roles/ManagerAccessTest.php`
  - `tests/Feature/Security/TenantIsolationTest.php`
  - `tests/Feature/Superadmin/RelationCrudResourcesTest.php`

## Factories, support builders, and fixture composition

- Tests prefer composition over giant seed dumps:
  - create organization
  - create admin/tenant with role-specific factory state
  - create related property/building/assignment records with `for(...)`
  - override only the behavior-relevant attributes
- `tests/Pest.php` helper composition and `tests/Support/TenantPortalFactory.php` are the clearest examples of this style.
- `TenantPortalFactory` is especially useful for tenant UX tests because it can toggle assigned property, meters, readings, unpaid invoices, paid invoices, and billing instructions without per-test boilerplate.

## Request and validation testing patterns

- Validation coverage is unusually systematic.
- `tests/Unit/Requests/FormRequestValidationTest.php` iterates over scenarios supplied by `tests/Support/FormRequestScenarioFactory.php` and validates four behaviors for many request classes:
  - valid payload acceptance
  - required field rejection
  - malformed payload rejection
  - authorization matrix correctness
- This means request changes should usually update `FormRequestScenarioFactory` instead of adding a one-off ad hoc test only.

## Mocking and facade isolation

- Mocking is present mostly in unit tests and integration-probe tests rather than broad feature tests.
- Current mocking style relies heavily on facade expectations like `shouldReceive(...)`.
- Representative files:
  - `tests/Unit/Services/SecurityMonitoringServiceTest.php` uses `Cache::shouldReceive(...)` and `Log::shouldReceive(...)`
  - `tests/Unit/Support/FaviconUrlResolverTest.php` uses router and URL generator mocks
  - `tests/Unit/Support/Dashboard/DashboardCacheServiceTest.php` mocks cache facade behavior
  - `tests/Feature/Superadmin/IntegrationProbeRuntimeTest.php` mocks `DB`, `Queue`, and `Mail`
- `Event::fake(...)` is used where event dispatch is incidental to the tested behavior, e.g. `tests/Unit/Services/SecurityMonitoringServiceTest.php`.

## Dataset and data-provider usage

- Pest datasets are used regularly to avoid repetitive assertions.
- Examples:
  - `tests/Feature/Public/GuestAuthLocaleSwitcherTest.php`
  - `tests/Feature/Livewire/ControllerRouteMigrationTest.php`
  - `tests/Feature/Security/TenantPropertyBoundaryContractTest.php`
  - `tests/Unit/Enums/ExpandedEnumBehaviorTest.php`
  - `tests/Unit/Requests/FormRequestValidationTest.php`
- The suite mixes inline `->with([...])` datasets and named `dataset(...)` definitions depending on reuse needs.

## Assertion style

- The suite favors expressive framework assertions over numeric status checks where possible.
- Common assertions found across the codebase:
  - `assertSuccessful()`
  - `assertForbidden()`
  - `assertRedirect(...)`
  - `assertNotFound()`
  - `assertSeeText(...)`
  - `assertSeeLivewire(...)`
  - `assertDatabaseHas(...)`
  - `assertDatabaseMissing(...)`
- Example files with strong assertion style:
  - `tests/Feature/Admin/InvoicesResourceTest.php`
  - `tests/Feature/Livewire/ControllerRouteMigrationTest.php`
  - `tests/Feature/Superadmin/AuditLogsResourceTest.php`

## Practical verification commands

- General suite:
  - `php artisan test --compact`
  - `php artisan test --stop-on-failure`
  - `composer test`
- Focused file runs:
  - `php artisan test --compact tests/Feature/Admin/InvoicesResourceTest.php`
  - `php artisan test --compact tests/Unit/Requests/FormRequestValidationTest.php`
- Focused filter runs:
  - `php artisan test --compact --filter=dashboard`
  - `php artisan test --compact --filter="renders the tenant dashboard"`
- Formatting/quality companion commands from project config and docs:
  - `vendor/bin/pint --dirty --format agent`
  - `composer run guard:phase1`

## Planning guidance for new tests

- Prefer Pest feature tests for user-visible behavior, access control, and Livewire/Filament flows.
- Prefer unit tests when mocking facades or isolating support/service behavior.
- Reuse `createOrgWithAdmin()`, `createTenantInOrg()`, `signInAs()`, `TenantPortalFactory`, and factory states before inventing new fixture setup.
- When changing `FormRequest` logic, update `tests/Support/FormRequestScenarioFactory.php` and the request validation unit tests rather than only adding a single feature assertion.
- When changing read-model boundaries or moving query logic, look for existing architecture inventory tests under `tests/Feature/Architecture/` and extend those contracts.
- If work touches `tests/Performance/`, run those files explicitly because that directory is not listed in `phpunit.xml` test suites.
