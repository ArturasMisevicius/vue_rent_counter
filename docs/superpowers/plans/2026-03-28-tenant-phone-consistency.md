# Tenant Phone Consistency Implementation Plan

> **AI agent usage:** This is an execution plan, not proof of current implementation. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify every referenced file, command, route, schema, and test before acting.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make tenant phone data editable and visible consistently across tenant self-profile, admin tenant edit flows, and tenant portal identity views.

**Architecture:** Preserve the current request/action write paths and fix the cross-surface contract by adding focused tests first, then widening the existing tenant portal presenter payloads and views to include `tenant_phone`. Keep query behavior stable by only extending existing `select([...])` clauses.

**Tech Stack:** Laravel 12, Filament, Livewire, Blade, Eloquent, Pest

---

### Task 1: Lock The Self-Profile Phone Contract

**Files:**
- Modify: `tests/Feature/Tenant/TenantProfilePageTest.php`
- Verify: `app/Filament/Pages/Concerns/InteractsWithAccountProfileForms.php`
- Verify: `app/Http/Requests/Profile/UpdateProfileRequest.php`
- Verify: `app/Filament/Actions/Admin/Settings/UpdateProfileAction.php`

- [ ] **Step 1: Write the failing tests**

Add focused assertions that the tenant profile page shows the current phone and that `saveProfile` persists updated phone values.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Tenant/TenantProfilePageTest.php`

Expected: failing assertions around missing or unpersisted phone behavior.

- [ ] **Step 3: Write minimal implementation**

Update only the code path needed for the failing assertions. If the existing shared profile action already supports phone, keep the change limited to the missing test-backed behavior.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Tenant/TenantProfilePageTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Tenant/TenantProfilePageTest.php
git commit -m "test: lock tenant profile phone behavior"
```

### Task 2: Lock The Admin Tenant Update Contract

**Files:**
- Modify: `tests/Feature/Admin/TenantsResourceTest.php`
- Verify: `app/Filament/Actions/Admin/Tenants/UpdateTenantAction.php`
- Verify: `app/Http/Requests/Admin/Tenants/UpdateTenantRequest.php`
- Verify: `app/Filament/Resources/Tenants/Schemas/TenantForm.php`

- [ ] **Step 1: Write the failing tests**

Add a focused test that updates a tenant through `UpdateTenantAction` and asserts the new phone value is persisted.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/TenantsResourceTest.php --filter=phone`

Expected: FAIL because the admin update contract is not fully asserted yet or exposes a drift.

- [ ] **Step 3: Write minimal implementation**

If the action already persists phone correctly, keep the implementation change at zero and let the new test document the contract. If a gap appears, patch only the admin update path.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Admin/TenantsResourceTest.php --filter=phone`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Admin/TenantsResourceTest.php
git commit -m "test: cover admin tenant phone updates"
```

### Task 3: Extend Tenant Portal Read Models And Views

**Files:**
- Modify: `app/Filament/Support/Tenant/Portal/TenantHomePresenter.php`
- Modify: `app/Filament/Support/Tenant/Portal/TenantPropertyPresenter.php`
- Modify: `resources/views/livewire/pages/dashboard/tenant-dashboard.blade.php`
- Modify: `resources/views/livewire/tenant/property-details.blade.php`
- Modify: `tests/Feature/Tenant/TenantHomePageTest.php`
- Modify: `tests/Feature/Tenant/TenantPropertyPresenterTest.php`

- [ ] **Step 1: Write the failing tests**

Add presenter/page assertions for `tenant_phone` so the tenant portal contract is explicit.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Tenant/TenantHomePageTest.php tests/Feature/Tenant/TenantPropertyPresenterTest.php`

Expected: FAIL because tenant phone is not yet exposed in presenter output and rendered tenant identity cards.

- [ ] **Step 3: Write minimal implementation**

Add `phone` to the existing tenant `select([...])` clauses, add `tenant_phone` to the returned arrays, and render it in the existing tenant identity cards.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Tenant/TenantHomePageTest.php tests/Feature/Tenant/TenantPropertyPresenterTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Support/Tenant/Portal/TenantHomePresenter.php app/Filament/Support/Tenant/Portal/TenantPropertyPresenter.php resources/views/livewire/pages/dashboard/tenant-dashboard.blade.php resources/views/livewire/tenant/property-details.blade.php tests/Feature/Tenant/TenantHomePageTest.php tests/Feature/Tenant/TenantPropertyPresenterTest.php
git commit -m "feat: surface tenant phone consistently in portal"
```

### Task 4: Final Verification

**Files:**
- Verify: `tests/Feature/Tenant/TenantProfilePageTest.php`
- Verify: `tests/Feature/Admin/TenantsResourceTest.php`
- Verify: `tests/Feature/Tenant/TenantHomePageTest.php`
- Verify: `tests/Feature/Tenant/TenantPropertyPresenterTest.php`

- [ ] **Step 1: Run the targeted verification suite**

Run: `php artisan test --compact tests/Feature/Tenant/TenantProfilePageTest.php tests/Feature/Admin/TenantsResourceTest.php tests/Feature/Tenant/TenantHomePageTest.php tests/Feature/Tenant/TenantPropertyPresenterTest.php`

Expected: PASS

- [ ] **Step 2: Run formatting**

Run: `vendor/bin/pint --dirty`

Expected: exit 0

- [ ] **Step 3: Review diff**

Run: `git diff -- app/Filament/Support/Tenant/Portal/TenantHomePresenter.php app/Filament/Support/Tenant/Portal/TenantPropertyPresenter.php app/Filament/Pages/Concerns/InteractsWithAccountProfileForms.php resources/views/livewire/pages/dashboard/tenant-dashboard.blade.php resources/views/livewire/tenant/property-details.blade.php tests/Feature/Tenant/TenantProfilePageTest.php tests/Feature/Admin/TenantsResourceTest.php tests/Feature/Tenant/TenantHomePageTest.php tests/Feature/Tenant/TenantPropertyPresenterTest.php`

Expected: only the intended tenant phone consistency changes
