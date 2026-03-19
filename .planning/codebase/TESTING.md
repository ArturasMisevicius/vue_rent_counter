# Testing Patterns

**Analysis Date:** 2026-03-19

## Test Framework

**Runner:**
- The repository uses Pest 4 on top of PHPUnit 12, declared in `composer.json`.
- Core PHPUnit configuration lives in `phpunit.xml`.
- Global Pest bootstrap lives in `tests/Pest.php`.
- The shared base test case lives in `tests/TestCase.php`.
- The default test environment uses in-memory SQLite plus array/sync drivers from `phpunit.xml`, including `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, and `MAIL_MAILER=array`.

**Assertion Library:**
- Pest `expect()` is the primary assertion style in both feature and unit tests, for example `tests/Feature/Admin/PropertiesResourceTest.php` and `tests/Unit/Services/BillingServiceTest.php`.
- Laravel HTTP assertions are used for request/response tests, including `assertSuccessful()`, `assertRedirect()`, `assertForbidden()`, `assertTooManyRequests()`, and `assertSeeText()`, as seen in `tests/Feature/Auth/LoginFlowTest.php` and `tests/Feature/Security/SecurityHeadersTest.php`.
- Livewire component tests use `Livewire::test()` plus Livewire-specific assertions such as `assertSeeHtml()` and `assertDontSeeText()`, as in `tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php`.

**Run Commands:**
```bash
composer test                                                     # Clears config, then runs `php artisan test`
php artisan test --compact                                        # Preferred human-facing command from `AGENTS.md`
php artisan test --compact tests/Feature/Auth/LoginFlowTest.php   # Run a single Pest file
php artisan test --compact --filter="rate limits login"           # Run a single named test
php artisan test tests/Performance/DashboardPerformanceTest.php   # Explicit path for opt-in performance tests
# No repository coverage script is configured in `composer.json` or `phpunit.xml`
```

## Test File Organization

**Location:**
- Tests live in a separate top-level `tests/` tree, not beside application code.
- Shared bootstrap files are `tests/Pest.php` and `tests/TestCase.php`.
- Shared test data builders live under `tests/Support`, including `tests/Support/FormRequestScenarioFactory.php` and `tests/Support/TenantPortalFactory.php`.
- Default PHPUnit suites only include `tests/Unit` and `tests/Feature` via `phpunit.xml`.
- `tests/Performance` exists, but it is outside the declared PHPUnit suites in `phpunit.xml`, so treat it as opt-in by explicit file/path invocation.

**Naming:**
- Use `*Test.php` for all tests, regardless of layer, such as `tests/Feature/Auth/LoginFlowTest.php`, `tests/Feature/Admin/PropertiesResourceTest.php`, and `tests/Unit/Requests/FormRequestStructureTest.php`.
- Group feature tests by product area or surface, for example `tests/Feature/Admin`, `tests/Feature/Auth`, `tests/Feature/Livewire/Dashboard`, `tests/Feature/Security`, and `tests/Feature/Superadmin`.
- Group unit tests by subsystem, for example `tests/Unit/Services`, `tests/Unit/Requests`, `tests/Unit/Support/Admin`, and `tests/Unit/Enums`.

**Structure:**
```text
tests/
├── Pest.php
├── TestCase.php
├── Support/
│   ├── FormRequestScenarioFactory.php
│   └── TenantPortalFactory.php
├── Feature/
│   ├── Admin/
│   ├── Architecture/
│   ├── Auth/
│   ├── Filament/
│   ├── Livewire/
│   │   └── Dashboard/
│   ├── Security/
│   ├── Shell/
│   ├── Superadmin/
│   └── Tenant/
├── Unit/
│   ├── Enums/
│   ├── Requests/
│   ├── Services/
│   └── Support/
└── Performance/
    └── DashboardPerformanceTest.php
```

## Test Structure

**Suite Organization:**
```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the login page', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSeeText('Welcome back');
});

it('redirects users to the unified app entrypoint for their role context', function (Closure $userFactory, string $expectedRoute) {
    $user = $userFactory();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route($expectedRoute));
})->with([
    'superadmin' => [
        fn () => User::factory()->superadmin()->create(),
        'filament.admin.pages.dashboard',
    ],
]);
```

**Patterns:**
- Feature files usually start with `uses(RefreshDatabase::class);`, as in `tests/Feature/Auth/LoginFlowTest.php`, `tests/Feature/Admin/PropertiesResourceTest.php`, and `tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php`.
- `tests/Pest.php` extends `Tests\TestCase` only for the `Feature` directory via `pest()->extend(TestCase::class)->in('Feature');`. Unit and performance tests explicitly declare `uses(TestCase::class)` when they need the application container.
- Global `beforeEach()` and `afterEach()` hooks in `tests/Pest.php` log users out, reset Carbon test time, restore locale config, and register shared helper routes.
- File-local helper functions are common and usually sit at the bottom of the test file, for example `registerLoginDestinationFixtures()` in `tests/Feature/Auth/LoginFlowTest.php` and `seedAdminDashboardComponentData()` in `tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php`.
- `tests/TestCase.php` overrides `createApplication()` to force a dedicated test routes cache path at `bootstrap/cache/routes-testing.php`.
- Architecture and inventory tests are written as normal Pest files rather than using Pest’s `arch()` DSL. See `tests/Feature/Architecture/FilamentFoundationPlacementTest.php` and `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`.

## Mocking

**Framework:**
- Use Laravel facade fakes for framework side effects, especially `Event::fake()` and `Notification::fake()`.
- Use Livewire’s testing harness for Livewire surfaces instead of mocking the component internals.
- Use Carbon time control and DB query logging for behavior/performance assertions where appropriate.

**Patterns:**
```php
Event::fake([
    SecurityViolationDetected::class,
]);

$response = $this->call(
    'POST',
    route('security.csp.report'),
    [],
    [],
    [],
    ['CONTENT_TYPE' => 'application/csp-report'],
    json_encode([...], JSON_THROW_ON_ERROR),
);

$response->assertAccepted();

Event::assertDispatched(SecurityViolationDetected::class);
```

```php
Livewire::actingAs($admin)
    ->test(AdminDashboard::class)
    ->assertSeeText('Total Properties')
    ->assertSeeHtml('wire:poll.30s');
```

**What to Mock:**
- Framework side effects such as notifications and events, as seen in `tests/Feature/Security/SecurityHeadersTest.php`, `tests/Feature/Auth/PasswordResetTest.php`, and `tests/Feature/Livewire/Dashboard/DashboardRealtimeEventsTest.php`.
- Time when expiry or timeout behavior matters, using `Carbon::setTestNow()` globally in `tests/Pest.php` and selectively in `tests/Feature/Auth/PasswordResetTest.php`.
- Query collection when asserting performance budgets, using `DB::enableQueryLog()` in `tests/Performance/DashboardPerformanceTest.php`.

**What NOT to Mock:**
- Eloquent models and relationships for normal feature coverage. Tests generally create real records with factories and run against the in-memory database.
- Action classes and support services that are the subject of the test. For example, `tests/Feature/Admin/PropertiesResourceTest.php` executes real action classes like `CreatePropertyAction` and `AssignTenantToPropertyAction`.
- Form Request behavior. Request tests build real request instances and validate real payloads via `tests/Support/FormRequestScenarioFactory.php`.

## Fixtures and Factories

**Test Data:**
```php
$organization = Organization::factory()->create();
$admin = User::factory()->admin()->create([
    'organization_id' => $organization->id,
]);

$property = Property::factory()
    ->for($organization)
    ->for($building)
    ->create([
        'name' => 'A-12',
    ]);
```

```php
$fixture = TenantPortalFactory::new()
    ->withAssignedProperty()
    ->withMeters(2)
    ->withReadings()
    ->withUnpaidInvoices(2)
    ->create();
```

**Location:**
- Use Eloquent model factories from `database/factories` for almost all record setup, with named factory states such as `admin()`, `manager()`, `tenant()`, `superadmin()`, `active()`, and `flat()`.
- Keep reusable higher-level fixture builders under `tests/Support`, notably `tests/Support/FormRequestScenarioFactory.php` for request contract matrices and `tests/Support/TenantPortalFactory.php` for tenant-portal flows.
- Keep small scenario seeders local to the test file when they are specific to one surface, for example `seedAdminDashboardComponentData()` in `tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php`.

## Coverage

**Requirements:**
- No numeric coverage threshold is configured in `phpunit.xml` or `composer.json`.
- Coverage is reinforced through inventory and architecture tests that fail when expected surfaces lose regression coverage, such as `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php` and `tests/Unit/Requests/FormRequestStructureTest.php`.
- The session bootstrap in `docs/SESSION-BOOTSTRAP.md` records a known-good baseline of `php artisan test --stop-on-failure`, but that is operational guidance rather than an enforced coverage percentage.

**Configuration:**
- `phpunit.xml` includes `app/` as the source directory for PHPUnit source mapping.
- No dedicated coverage script, HTML coverage output path, or CI-enforced minimum was detected in the repository.
- No repo-local CI workflow files were found under `.github/workflows`.

**View Coverage:**
```bash
# No repository shortcut is configured
# Use PHPUnit/Laravel coverage flags manually only if your local PHP build has coverage support enabled
```

## Test Types

**Unit Tests:**
- Unit tests cover isolated domain and support logic such as billing calculations, request contracts, dashboard cache behavior, and enum contracts in `tests/Unit/Services/BillingServiceTest.php`, `tests/Unit/Requests/FormRequestValidationTest.php`, `tests/Unit/Support/Dashboard/DashboardCacheServiceTest.php`, and `tests/Unit/Enums/ExpandedEnumBehaviorTest.php`.
- Unit files often use `uses(TestCase::class)` and add `RefreshDatabase::class` only when the unit under test touches the database.
- Datasets are used for repetitive enum, validation, and calculation cases, as shown in `tests/Unit/Enums/ExpandedEnumBehaviorTest.php` and `tests/Unit/Services/BillingServiceTest.php`.

**Integration Tests:**
- Most “feature” tests are integration-heavy: they boot the application, hit real routes, exercise Livewire/Filament pages, and use actual Eloquent factories and policies.
- Representative files include `tests/Feature/Auth/LoginFlowTest.php`, `tests/Feature/Admin/PropertiesResourceTest.php`, `tests/Feature/Security/SecurityHeadersTest.php`, and `tests/Feature/Filament/UnifiedPanelTest.php`.
- Integration tests assert both access control and rendered content, often by checking route behavior plus specific translated or domain strings.

**E2E Tests:**
- Not detected.
- There is no `tests/Browser` directory, no Dusk test tree, no Playwright config, and no snapshot directories in the repository root.

**Performance Tests:**
- Performance assertions live in `tests/Performance/DashboardPerformanceTest.php`.
- These tests warm the page, enable query logging, and assert select-query budgets for dashboard routes.
- Because `tests/Performance` is outside the default `phpunit.xml` suites, run these tests explicitly when relevant.

## Common Patterns

**Livewire Component Testing:**
```php
Livewire::actingAs($admin)
    ->test(AdminDashboard::class)
    ->assertSeeText('Total Properties')
    ->assertDontSeeText('INV-OUTSIDE-001')
    ->assertSeeHtml('wire:poll.30s');
```

**Validation and Error Testing:**
```php
$this->from(route('login'))
    ->post(route('login.store'), [
        'email' => 'asta@example.com',
        'password' => 'wrong-password',
    ])
    ->assertRedirect(route('login'))
    ->assertSessionHasErrors([
        'email' => __('auth.invalid_credentials'),
    ]);
```

```php
expect(fn () => app(DeletePropertyAction::class)->handle($propertyAtLimit))
    ->toThrow(ValidationException::class);
```

**Performance Regression Testing:**
```php
$this->actingAs($admin)
    ->get(route('filament.admin.pages.dashboard'))
    ->assertSuccessful();

DB::flushQueryLog();
DB::enableQueryLog();

$this->actingAs($admin)
    ->get(route('filament.admin.pages.dashboard'))
    ->assertSuccessful();

expect(collect(DB::getQueryLog())->count())->toBeLessThan(10);
```

**Snapshot Testing:**
- Not used in the inspected repository.

---

*Testing analysis: 2026-03-19*
