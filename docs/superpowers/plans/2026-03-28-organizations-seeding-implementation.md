# Organizations Seeding Implementation Plan

> **AI agent usage:** This is an execution plan, not proof of current implementation. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify every referenced file, command, route, schema, and test before acting.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enrich the Organizations seed and factory layer so Tenanto ships with one richly populated showcase organization per subscription plan plus a fully aligned login-demo organization.

**Architecture:** Keep the existing schema and expand the seed graph through factories, `organization_settings`, subscriptions, users, buildings, properties, meters, providers, invoices, and activity data. Use stable blueprint-driven seeding so reruns stay idempotent and the superadmin Organizations panel is meaningfully populated after `db:seed`.

**Tech Stack:** Laravel 13, Eloquent factories, database seeders, Pest feature tests, Filament 5 superadmin resources.

---

## File Structure

### Existing files to extend

- `database/factories/OrganizationFactory.php`
- `database/factories/SubscriptionFactory.php`
- `database/factories/OrganizationSettingFactory.php`
- `database/factories/UserFactory.php`
- `database/factories/BuildingFactory.php`
- `database/factories/PropertyFactory.php`
- `database/factories/MeterFactory.php`
- `database/factories/ProviderFactory.php`
- `database/seeders/OperationalDemoDatasetSeeder.php`
- `database/seeders/LoginDemoUsersSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
- `tests/Feature/Auth/LoginDemoAccountsTest.php`

### New files to create

- `database/seeders/Support/OrganizationShowcaseCatalog.php`
- `tests/Feature/Admin/OrganizationShowcaseFactoryTest.php`

### Responsibility boundaries

- Keep plan-to-volume and plan-to-status definitions in `OrganizationShowcaseCatalog`, not scattered inside multiple seeders.
- Keep factories focused on expressive states and correct defaults.
- Keep `OperationalDemoDatasetSeeder` responsible for building the plan-shaped organization graph.
- Keep `LoginDemoUsersSeeder` responsible for the curated login accounts while reusing the same richer organization contracts.

## Task 1: Add Plan-Aware Organization And Subscription Factory States

**Files:**
- Modify: `database/factories/OrganizationFactory.php`
- Modify: `database/factories/SubscriptionFactory.php`
- Create: `tests/Feature/Admin/OrganizationShowcaseFactoryTest.php`

- [ ] **Step 1: Write the failing factory tests**

```php
it('builds stable showcase organizations for each subscription plan', function () {
    expect(Organization::factory()->starterShowcase()->make()->slug)->toContain('starter');
});

it('hydrates subscription snapshot limits from the selected plan', function () {
    $subscription = Subscription::factory()->enterprise()->make();

    expect($subscription->property_limit_snapshot)->toBe(SubscriptionPlan::ENTERPRISE->limits()['properties']);
});
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Feature/Admin/OrganizationShowcaseFactoryTest.php`
Expected: FAIL because the showcase states and plan-aware subscription helpers do not exist yet.

- [ ] **Step 3: Add the factory states**

```php
public function starterShowcase(): static
{
    return $this->state([
        'name' => 'Starter Showcase Organization',
        'slug' => 'showcase-starter',
        'status' => OrganizationStatus::TRIAL,
    ]);
}
```

```php
public function forPlan(SubscriptionPlan $plan): static
{
    $limits = $plan->limits();

    return $this->state([
        'plan' => $plan,
        'property_limit_snapshot' => $limits['properties'],
        'tenant_limit_snapshot' => $limits['tenants'],
        'meter_limit_snapshot' => $limits['meters'],
        'invoice_limit_snapshot' => $limits['invoices'],
    ]);
}
```

- [ ] **Step 4: Verify the factory behavior**

Run: `php artisan test tests/Feature/Admin/OrganizationShowcaseFactoryTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/factories/OrganizationFactory.php \
  database/factories/SubscriptionFactory.php \
  tests/Feature/Admin/OrganizationShowcaseFactoryTest.php
git commit -m "feat: add organization showcase factory states"
```

## Task 2: Centralize Showcase Blueprints And Plan Volume Rules

**Files:**
- Create: `database/seeders/Support/OrganizationShowcaseCatalog.php`
- Modify: `database/seeders/OperationalDemoDatasetSeeder.php`

- [ ] **Step 1: Write the failing seeder expectations for multi-plan coverage**

Add assertions to `tests/Feature/Admin/OperationalDemoDatasetSeederTest.php` that seeded orgs cover all subscription plans and no longer default to a single plan.

```php
expect(
    Subscription::query()
        ->whereIn('organization_id', $demoOrganizationIds)
        ->pluck('plan')
        ->unique()
        ->sort()
        ->values()
        ->all()
)->toEqual([
    SubscriptionPlan::STARTER->value,
    SubscriptionPlan::BASIC->value,
    SubscriptionPlan::PROFESSIONAL->value,
    SubscriptionPlan::ENTERPRISE->value,
    SubscriptionPlan::CUSTOM->value,
]);
```

- [ ] **Step 2: Run the failing test**

Run: `php artisan test tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
Expected: FAIL because the current seeder still creates only `professional` subscriptions for the demo org set.

- [ ] **Step 3: Create the showcase catalog**

```php
final class OrganizationShowcaseCatalog
{
    public static function blueprints(): array
    {
        return [
            [
                'slug' => 'showcase-starter',
                'plan' => SubscriptionPlan::STARTER,
                'status' => OrganizationStatus::TRIAL,
                'volumes' => ['buildings' => 1, 'properties' => 3, 'tenants' => 3],
            ],
            // ...
        ];
    }
}
```

- [ ] **Step 4: Replace hard-coded organization repetition in `OperationalDemoDatasetSeeder`**

Refactor the seeder to iterate through `OrganizationShowcaseCatalog::blueprints()` and derive:

- organization name/slug/status
- subscription plan and snapshots
- building/property/user/meter/invoice counts
- per-org seed identifiers

- [ ] **Step 5: Verify the multi-plan dataset**

Run: `php artisan test tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
Expected: PASS with plan coverage assertions green.

- [ ] **Step 6: Commit**

```bash
git add database/seeders/Support/OrganizationShowcaseCatalog.php \
  database/seeders/OperationalDemoDatasetSeeder.php \
  tests/Feature/Admin/OperationalDemoDatasetSeederTest.php
git commit -m "feat: seed showcase organizations across every plan"
```

## Task 3: Scale Buildings, Users, And Related Graph By Plan Tier

**Files:**
- Modify: `database/factories/OrganizationSettingFactory.php`
- Modify: `database/factories/UserFactory.php`
- Modify: `database/factories/BuildingFactory.php`
- Modify: `database/factories/PropertyFactory.php`
- Modify: `database/factories/MeterFactory.php`
- Modify: `database/factories/ProviderFactory.php`
- Modify: `database/seeders/OperationalDemoDatasetSeeder.php`
- Modify: `tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`

- [ ] **Step 1: Write the failing density assertions**

Extend `OperationalDemoDatasetSeederTest` with expectations that larger plans produce larger graphs than smaller plans.

```php
expect($enterpriseOrganization->buildings()->count())->toBeGreaterThan($starterOrganization->buildings()->count());
expect($customOrganization->properties()->count())->toBeGreaterThan($basicOrganization->properties()->count());
```

- [ ] **Step 2: Run the failing test**

Run: `php artisan test tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
Expected: FAIL because current seeding uses uniform organization density.

- [ ] **Step 3: Add lightweight helper states to related factories**

Examples:

```php
public function withLocale(string $locale): static
{
    return $this->state(['locale' => $locale]);
}
```

```php
public function balticAddress(string $countryCode): static
{
    // stable city/postal defaults for the chosen country
}
```

Only add states that reduce repeated seed arrays.

- [ ] **Step 4: Update the seeder to use plan-tier volumes**

Use the catalog volume map to scale:

- managers
- tenant-role users
- buildings
- properties
- meters
- invoices
- providers / service configurations

Also ensure every seeded organization has:

- `organization_settings`
- owner/admin context
- non-empty activity-support data

- [ ] **Step 5: Verify the plan-shaped density**

Run: `php artisan test tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
Expected: PASS with density and consistency assertions green.

- [ ] **Step 6: Commit**

```bash
git add database/factories/OrganizationSettingFactory.php \
  database/factories/UserFactory.php \
  database/factories/BuildingFactory.php \
  database/factories/PropertyFactory.php \
  database/factories/MeterFactory.php \
  database/factories/ProviderFactory.php \
  database/seeders/OperationalDemoDatasetSeeder.php \
  tests/Feature/Admin/OperationalDemoDatasetSeederTest.php
git commit -m "feat: scale organization demo data by subscription plan"
```

## Task 4: Align The Curated Login Demo Organization With The Richer Organization Contract

**Files:**
- Modify: `database/seeders/LoginDemoUsersSeeder.php`
- Modify: `tests/Feature/Auth/LoginDemoAccountsTest.php`

- [ ] **Step 1: Write the failing login-demo assertions**

Extend `LoginDemoAccountsTest` so the seeded login-demo organization must also have:

- a current subscription
- organization settings
- building/property/meter/invoice richness

```php
expect($loginDemoOrganization->currentSubscription)->not->toBeNull()
    ->and($loginDemoOrganization->settings)->not->toBeNull();
```

- [ ] **Step 2: Run the failing test**

Run: `php artisan test tests/Feature/Auth/LoginDemoAccountsTest.php`
Expected: FAIL because the current login-demo seeder does not guarantee a fully aligned subscription-backed organization profile.

- [ ] **Step 3: Update `LoginDemoUsersSeeder`**

Ensure the curated login-demo org:

- is created or updated with stable identifiers
- receives an explicit current subscription and snapshot plan
- has `organization_settings`
- preserves the fixed demo accounts and passwords
- keeps the related tenant portfolio data intact

- [ ] **Step 4: Verify the curated login demo contract**

Run: `php artisan test tests/Feature/Auth/LoginDemoAccountsTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/seeders/LoginDemoUsersSeeder.php \
  tests/Feature/Auth/LoginDemoAccountsTest.php
git commit -m "feat: align login demo organization with showcase seed contract"
```

## Task 5: Seed Superadmin-Facing Scenarios And Idempotency

**Files:**
- Modify: `database/seeders/OperationalDemoDatasetSeeder.php`
- Modify: `tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
- Modify: `tests/Feature/Superadmin/OrganizationsListPageTest.php`

- [ ] **Step 1: Write failing assertions for panel-oriented scenarios**

Add coverage proving the seeded org set includes:

- mixed plans
- mixed statuses
- overdue invoices
- activity logs
- security violations or integration gaps on at least one org

```php
expect(Organization::query()->distinct()->pluck('status'))->toContain(OrganizationStatus::TRIAL->value);
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Feature/Admin/OperationalDemoDatasetSeederTest.php tests/Feature/Superadmin/OrganizationsListPageTest.php`
Expected: FAIL because the current dataset is too uniform.

- [ ] **Step 3: Seed superadmin-facing diversity**

Update the showcase blueprints so the dataset includes:

- a trial org
- an active org
- a grace-period or overdue-billing org
- a suspended or security-flagged org
- varying usage levels

Keep the data coherent with the current Organizations module behavior.

- [ ] **Step 4: Verify idempotency**

Add or extend the seeder test so running `DatabaseSeeder` twice does not duplicate showcase organizations or curated demo accounts.

- [ ] **Step 5: Run the verification slice**

Run: `php artisan test tests/Feature/Admin/OperationalDemoDatasetSeederTest.php tests/Feature/Auth/LoginDemoAccountsTest.php tests/Feature/Superadmin/OrganizationsListPageTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/seeders/OperationalDemoDatasetSeeder.php \
  tests/Feature/Admin/OperationalDemoDatasetSeederTest.php \
  tests/Feature/Auth/LoginDemoAccountsTest.php \
  tests/Feature/Superadmin/OrganizationsListPageTest.php
git commit -m "feat: enrich seeded organizations for superadmin control plane"
```

## Task 6: Final Verification And Cleanup

**Files:**
- Modify: `database/seeders/*.php`
- Modify: `database/factories/*.php`
- Modify: `tests/Feature/Admin/*.php`
- Modify: `tests/Feature/Auth/*.php`
- Modify: `tests/Feature/Superadmin/*.php`

- [ ] **Step 1: Run the organization seed verification suite**

Run:

```bash
php artisan test \
  tests/Feature/Admin/OrganizationShowcaseFactoryTest.php \
  tests/Feature/Admin/OperationalDemoDatasetSeederTest.php \
  tests/Feature/Auth/LoginDemoAccountsTest.php \
  tests/Feature/Superadmin/OrganizationsListPageTest.php \
  tests/Feature/Superadmin/OrganizationsViewPageTest.php
```

Expected: PASS

- [ ] **Step 2: Run formatting**

Run: `vendor/bin/pint --dirty`
Expected: PASS

- [ ] **Step 3: Run final diff hygiene**

Run: `git diff --check`
Expected: no whitespace or merge-marker issues

- [ ] **Step 4: Commit**

```bash
git add database/factories database/seeders tests/Feature/Admin tests/Feature/Auth tests/Feature/Superadmin
git commit -m "chore: finalize organization showcase seeding"
```

## Execution Notes

- Follow TDD strictly: test first, fail, implement minimally, verify, then commit.
- Keep the seed graph coherent with current schema instead of inventing new organization columns.
- Prefer stable `updateOrCreate()` keys over random inserts so `DatabaseSeeder` remains rerunnable.
- Do not let plan-specific density explode local seed runtime; the showcase should be rich, not massive.
- Reuse factory states from the seeders wherever possible so test data and demo data stay aligned.
