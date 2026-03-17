# Manager Role Parity Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ensure the manager role gets the same organization workspace as admin users, except for the two explicit differences in the spec: no subscription-usage row on the dashboard and no admin-only sections on the Settings page.

**Architecture:** Treat manager support as a role-parity layer on top of the admin organization operations rollout rather than a separate domain. Reuse the same organization-scoped resources, actions, policies, and shell navigation that admins use, then add focused role gates at the dashboard widget layer and the settings page schema/view layer so the manager experience stays intentionally smaller without forking the whole workspace.

**Tech Stack:** Laravel 12, Filament 5, Livewire 4, Blade, Tailwind CSS v4, SQLite, Pest 4, Laravel Pint.

---

## Spec Reference

- Spec: `docs/superpowers/specs/2026-03-17-manager-role-parity-design.md`
- Supporting baselines: `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`, `docs/superpowers/specs/2026-03-17-shared-interface-elements-design.md`, `docs/superpowers/specs/2026-03-17-admin-organization-operations-design.md`

## Scope Check

This is not an independent subsystem. It is a focused delta on top of:

- `docs/superpowers/plans/2026-03-17-shared-interface-elements.md`
- `docs/superpowers/plans/2026-03-17-admin-organization-operations.md`

If the admin plan has not started yet, the fastest path is to fold these role-delta tasks into the admin implementation as you build it. If the admin workspace already exists, this plan can run as a short follow-up branch.

## Scope Notes

- Managers should access the same organization-scoped resources and perform the same CRUD operations as admins for buildings, properties, tenants, meters, readings, invoices, tariffs, and providers.
- Do not duplicate admin resources or create separate manager resources.
- The only product differences here are:
  - the organization dashboard must hide subscription usage bars for managers
  - the Settings page must hide Organization Settings, Notification Preferences, and Subscription sections for managers
- The Profile page behavior is identical to admin behavior.
- The manager still sees the `Account` sidebar group with `Profile` and `Settings`.

## File Map

### Create

- `tests/Feature/Manager/ManagerWorkspaceParityTest.php`
- `tests/Feature/Manager/ManagerDashboardVisibilityTest.php`
- `tests/Feature/Manager/ManagerSettingsVisibilityTest.php`

### Modify

- `app/Filament/Pages/OrganizationDashboard.php`
- `app/Filament/Widgets/Admin/SubscriptionUsageOverview.php`
- `app/Support/Admin/Dashboard/AdminDashboardStats.php` or the exact dashboard presenter used by the admin dashboard plan
- `resources/views/filament/pages/organization-dashboard.blade.php`
- `app/Filament/Pages/Settings.php`
- `resources/views/filament/pages/settings.blade.php`
- `app/Support/Shell/Navigation/NavigationBuilder.php`
- `app/Models/User.php` (only if a tiny helper like `isManagerLike()` or translated-role helper improves clarity)
- `tests/Feature/Admin/AdminDashboardTest.php`
- `tests/Feature/Admin/ProfileAndSettingsTest.php`

### Do Not Create

- separate manager-only resources
- separate manager-only dashboard page
- separate manager-only profile page
- separate manager-only settings route

## Chunk 1: Shared Workspace Parity

### Task 1: Prove managers can access the same organization workspace resources as admins

**Files:**
- Create: `tests/Feature/Manager/ManagerWorkspaceParityTest.php`
- Modify: `app/Support/Shell/Navigation/NavigationBuilder.php`
- Modify: `app/Models/User.php` (only if needed for clear helper methods)

- [ ] **Step 1: Write the failing workspace parity test**

Create `tests/Feature/Manager/ManagerWorkspaceParityTest.php` with assertions that a manager can reach the same major workspace destinations as an admin once the admin plan resources exist:

```php
it('lets managers access the same organization workspace routes as admins', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.profile'))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.settings'))
        ->assertOk();
});
```

Then add navigation assertions for the `Account` group:

- `Profile` is visible
- `Settings` is visible
- no `Platform` section is visible

- [ ] **Step 2: Run the parity test**

Run:

```bash
php artisan test --compact tests/Feature/Manager/ManagerWorkspaceParityTest.php
```

Expected: FAIL until the shared shell/admin workspace is implemented or until manager-specific gating is corrected.

- [ ] **Step 3: Align navigation and route access with the manager role**

Implementation rules:

- managers should reuse the same admin resource/page registrations
- navigation grouping should be role-aware but not branch into separate manager structures
- any helper added to `User` must stay tiny and reusable

- [ ] **Step 4: Re-run the parity test**

Run:

```bash
php artisan test --compact tests/Feature/Manager/ManagerWorkspaceParityTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the parity foundation**

Run:

```bash
git add tests/Feature/Manager/ManagerWorkspaceParityTest.php app/Support/Shell/Navigation/NavigationBuilder.php app/Models/User.php
git commit -m "feat: align manager workspace parity"
```

## Chunk 2: Dashboard Difference

### Task 2: Hide subscription usage bars from managers while keeping the rest of the dashboard identical

**Files:**
- Create: `tests/Feature/Manager/ManagerDashboardVisibilityTest.php`
- Modify: `app/Filament/Pages/OrganizationDashboard.php`
- Modify: `app/Filament/Widgets/Admin/SubscriptionUsageOverview.php`
- Modify: `app/Support/Admin/Dashboard/AdminDashboardStats.php` or the exact admin dashboard presenter/widget registry
- Modify: `resources/views/filament/pages/organization-dashboard.blade.php`
- Modify: `tests/Feature/Admin/AdminDashboardTest.php`

- [ ] **Step 1: Write the failing manager dashboard visibility test**

Create `tests/Feature/Manager/ManagerDashboardVisibilityTest.php` with assertions like:

```php
it('does not show subscription usage bars to managers', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertOk()
        ->assertDontSeeText('Properties Used')
        ->assertDontSeeText('Tenants Used')
        ->assertSeeText('Recent Invoices')
        ->assertSeeText('Upcoming Reading Deadlines');
});
```

Also extend `tests/Feature/Admin/AdminDashboardTest.php` so it explicitly proves admins do still see the usage row.

- [ ] **Step 2: Run the dashboard tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/AdminDashboardTest.php tests/Feature/Manager/ManagerDashboardVisibilityTest.php
```

Expected: FAIL because the dashboard currently does not distinguish correctly between admin and manager.

- [ ] **Step 3: Implement role-aware dashboard composition**

Implementation rules:

- do not fork the dashboard into separate admin and manager pages
- keep the main stats widgets shared
- gate the subscription usage widget/section by role only
- avoid branching in Blade if the page/widget registry can decide visibility more cleanly

- [ ] **Step 4: Re-run the dashboard tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/AdminDashboardTest.php tests/Feature/Manager/ManagerDashboardVisibilityTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the dashboard role gate**

Run:

```bash
git add tests/Feature/Manager/ManagerDashboardVisibilityTest.php tests/Feature/Admin/AdminDashboardTest.php app/Filament/Pages/OrganizationDashboard.php app/Filament/Widgets/Admin/SubscriptionUsageOverview.php app/Support/Admin/Dashboard resources/views/filament/pages/organization-dashboard.blade.php
git commit -m "feat: hide admin subscription usage widgets from managers"
```

## Chunk 3: Settings Difference

### Task 3: Hide admin-only settings sections from managers while keeping profile behavior identical

**Files:**
- Create: `tests/Feature/Manager/ManagerSettingsVisibilityTest.php`
- Modify: `app/Filament/Pages/Settings.php`
- Modify: `resources/views/filament/pages/settings.blade.php`
- Modify: `tests/Feature/Admin/ProfileAndSettingsTest.php`

- [ ] **Step 1: Write the failing manager settings visibility test**

Create `tests/Feature/Manager/ManagerSettingsVisibilityTest.php` with assertions like:

```php
it('shows only profile-related settings sections to managers', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.settings'))
        ->assertOk()
        ->assertSeeText('Personal Information')
        ->assertSeeText('Change Password')
        ->assertDontSeeText('Organization Settings')
        ->assertDontSeeText('Notification Preferences')
        ->assertDontSeeText('Subscription');
});
```

Extend `tests/Feature/Admin/ProfileAndSettingsTest.php` so admins explicitly still see the admin-only settings sections.

- [ ] **Step 2: Run the settings tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/ProfileAndSettingsTest.php tests/Feature/Manager/ManagerSettingsVisibilityTest.php
```

Expected: FAIL because the settings page currently does not hide the admin-only sections for managers.

- [ ] **Step 3: Implement role-aware settings sections**

Implementation rules:

- keep one `Settings` page class and one route
- gate sections at the schema/view composition layer, not with CSS hiding
- manager page heading must still read `Settings`
- `Profile` page behavior remains unchanged and shared

- [ ] **Step 4: Re-run the settings tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/ProfileAndSettingsTest.php tests/Feature/Manager/ManagerSettingsVisibilityTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the settings role gate**

Run:

```bash
git add tests/Feature/Manager/ManagerSettingsVisibilityTest.php tests/Feature/Admin/ProfileAndSettingsTest.php app/Filament/Pages/Settings.php resources/views/filament/pages/settings.blade.php
git commit -m "feat: limit manager settings sections"
```

## Chunk 4: Verification

### Task 4: Run the manager verification pass

**Files:**
- Review only: all files touched in Tasks 1-3

- [ ] **Step 1: Run the manager test suite**

Run:

```bash
php artisan test --compact tests/Feature/Manager
```

Expected: PASS.

- [ ] **Step 2: Run the related admin regressions**

Run:

```bash
php artisan test --compact tests/Feature/Admin/AdminDashboardTest.php tests/Feature/Admin/ProfileAndSettingsTest.php
```

Expected: PASS.

- [ ] **Step 3: Run auth and shell regressions**

Run:

```bash
php artisan test --compact tests/Feature/Auth tests/Feature/Shell
```

Expected: PASS.

- [ ] **Step 4: Format changed PHP files**

Run:

```bash
vendor/bin/pint --dirty
```

Expected: PASS.

- [ ] **Step 5: Commit the verified manager parity slice**

Run:

```bash
git add app resources tests
git commit -m "feat: deliver manager role parity"
```

## Execution Notes

- If the admin organization operations plan is still in progress, merge these checks into that branch instead of waiting for a separate follow-up branch.
- Resist creating manager-only copies of admin resources; this is a visibility/role-composition problem, not a duplicate-CRUD problem.
- Keep the role differences explicit in tests so future admin changes do not accidentally expand the manager surface beyond the spec.
