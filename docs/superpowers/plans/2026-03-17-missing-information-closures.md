# Missing Information Closure Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the remaining product-spec gaps around auth/session lifecycle, tenant data continuity, invitation/password flows, breadcrumbs, and empty states so the existing Tenanto plans behave consistently in edge cases.

**Architecture:** Treat these items as a clarification overlay on top of the foundation-auth, shared-shell, admin, tenant, and superadmin plans rather than as a new module. Put lifecycle and retention rules in shared actions/support classes first, then expose them through thin middleware, Filament actions/pages, and tenant portal query objects so the same rule is enforced no matter where the user enters the flow.

**Tech Stack:** Laravel 12, Filament 5, Blade, Livewire 4, Eloquent, session/database auth, Pest 4, Laravel notifications, Laravel Pint.

---

## Spec Reference

- Spec: `docs/superpowers/specs/2026-03-17-missing-information-closures-design.md`
- Supporting baselines: `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`, `docs/superpowers/specs/2026-03-17-shared-interface-elements-design.md`, `docs/superpowers/specs/2026-03-17-admin-organization-operations-design.md`, `docs/superpowers/specs/2026-03-17-superadmin-control-plane-design.md`, `docs/superpowers/specs/2026-03-17-tenant-self-service-portal-design.md`, `docs/superpowers/specs/2026-03-17-cross-cutting-behavioral-rules-design.md`

## Scope Check

This spec spans multiple existing subsystems, but they are all closure work on earlier plans rather than fresh product areas.

Primary prerequisites:

- `docs/superpowers/plans/2026-03-17-foundation-auth-onboarding.md`
- `docs/superpowers/plans/2026-03-17-shared-interface-elements.md`
- `docs/superpowers/plans/2026-03-17-admin-organization-operations.md`
- `docs/superpowers/plans/2026-03-17-superadmin-control-plane.md`
- `docs/superpowers/plans/2026-03-17-tenant-self-service-portal.md`
- `docs/superpowers/plans/2026-03-17-cross-cutting-behavioral-rules.md`

If you want smaller branches, split after Chunk 2:

- follow-on A: auth/session lifecycle + invitation/password closure
- follow-on B: tenant data continuity + shell breadcrumbs/empty states

## Scope Notes

- The password-reset token lifetime already matches the spec at `60` minutes in `config/auth.php`; lock that behavior with tests instead of inventing a second reset mechanism.
- Invitation expiry at `7` days already exists in the current auth action/model layer; this plan adds resend behavior and admin-facing affordances, not a brand-new invitation system.
- Organization suspension already blocks current requests; this plan extends that into explicit login messaging and immediate database-session invalidation for all users in the organization.
- When a tenant is unassigned, historical invoices remain visible and downloadable. New invoices must not be generated for billing periods starting after the unassignment timestamp. If later product work requires proration for partially overlapping periods, that should be a separate change.
- Tenant meter visibility must always derive from the tenant’s currently assigned property. Historical invoice access after unassignment does **not** imply historical meter visibility.
- Breadcrumbs apply to all non-dashboard pages only. Do not force breadcrumbs onto admin, superadmin, or tenant dashboard pages.
- Empty states should use Filament’s empty-state APIs where possible, backed by shared copy/illustration primitives, rather than custom one-off list-page templates.
- Session-expired copy should appear only when a previously authenticated session has lapsed. A normal guest visiting a protected page should still see the login page without the “session expired” banner.

## Skills To Use During Execution

- `@laravel-11-12-app-guidelines`
- `@filament`
- `@laravel-actions`
- `@livewire-development`
- `@pest-testing`

## File Map

### Create

- `app/Actions/Auth/TerminateOrganizationSessionsAction.php` — deletes database sessions for all users in a suspended organization.
- `app/Actions/Auth/ResendOrganizationInvitationAction.php` — issues a fresh invitation token/expiry and resends the email for inactive tenant/manager invites.
- `app/Http/Middleware/Authenticate.php` — app-level auth middleware that flashes the session-expired message for standard web routes.
- `app/Http/Middleware/AuthenticateAdminPanel.php` — Filament-specific auth middleware with the same session-expired behavior.
- `app/Support/Admin/Invoices/InvoiceEligibilityWindow.php` — central rule for whether invoice generation is allowed after tenant/property unassignment.
- `app/Support/Shell/Breadcrumbs/BreadcrumbItemData.php` — immutable breadcrumb item payload.
- `app/View/Components/Shell/Breadcrumbs.php` — shared Blade breadcrumb renderer for tenant/custom pages.
- `resources/views/components/shell/breadcrumbs.blade.php`
- `resources/views/components/ui/empty-state.blade.php`
- `tests/Feature/Auth/AccountSuspensionLifecycleTest.php`
- `tests/Feature/Auth/SessionTimeoutTest.php`
- `tests/Feature/Admin/InvitationResendTest.php`
- `tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php`
- `tests/Feature/Tenant/TenantMeterVisibilityTest.php`
- `tests/Feature/Shell/BreadcrumbsTest.php`
- `tests/Feature/Admin/EmptyOrganizationStateTest.php`

### Modify

- `bootstrap/app.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Http/Middleware/EnsureAccountIsAccessible.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `resources/views/auth/login.blade.php`
- `app/Actions/Auth/CreateOrganizationInvitationAction.php`
- `app/Actions/Auth/AcceptOrganizationInvitationAction.php`
- `app/Http/Controllers/Auth/AcceptInvitationController.php`
- `app/Notifications/Auth/OrganizationInvitationNotification.php`
- `tests/Feature/Auth/LoginFlowTest.php`
- `tests/Feature/Auth/PasswordResetTest.php`
- `tests/Feature/Auth/InvitationAcceptanceTest.php`
- `tests/Feature/Auth/AccessIsolationTest.php`
- `app/Actions/Superadmin/Organizations/SuspendOrganizationAction.php`
- `app/Actions/Superadmin/Organizations/ReinstateOrganizationAction.php` if the current implementation needs to clear or preserve suspension side effects explicitly
- `app/Filament/Resources/Tenants/Pages/ViewTenant.php`
- `app/Filament/Resources/Tenants/TenantResource.php`
- `app/Actions/Admin/Properties/UnassignTenantFromPropertyAction.php`
- `app/Actions/Admin/Invoices/GenerateInvoiceLineItemsAction.php`
- `app/Actions/Admin/Invoices/GenerateBulkInvoicesAction.php`
- `app/Support/Tenant/Portal/TenantHomePresenter.php`
- `app/Support/Tenant/Portal/TenantPropertyPresenter.php`
- `app/Support/Tenant/Portal/TenantInvoiceIndexQuery.php`
- `app/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- `app/Policies/MeterPolicy.php`
- `app/Policies/InvoicePolicy.php`
- `app/View/Components/Shell/AppFrame.php`
- `resources/views/components/shell/app-frame.blade.php`
- `resources/views/tenant/invoices/index.blade.php`
- `resources/views/tenant/property/show.blade.php`
- `app/Filament/Resources/Buildings/Pages/ListBuildings.php`
- `app/Filament/Resources/Properties/Pages/ListProperties.php`
- `app/Filament/Resources/Tenants/Pages/ListTenants.php`
- `app/Filament/Resources/Meters/Pages/ListMeters.php`
- `app/Filament/Resources/Buildings/Pages/ViewBuilding.php`
- `app/Filament/Resources/Properties/Pages/ViewProperty.php`
- `app/Filament/Resources/Meters/Pages/ViewMeter.php`
- `app/Filament/Resources/Invoices/Pages/ViewInvoice.php`

### Do Not Create

- a second invitation model or password-reset flow
- tenant access to historical meters after unassignment
- a custom JS breadcrumb system for Filament tables/pages
- bespoke empty-state markup for every list page if a shared component or Filament API covers it

## Chunk 1: Auth, Suspension, And Session Lifecycle

### Task 1: Codify suspension behavior, session-expiry UX, and password-reset access rules

**Files:**
- Create: `app/Actions/Auth/TerminateOrganizationSessionsAction.php`
- Create: `app/Http/Middleware/Authenticate.php`
- Create: `app/Http/Middleware/AuthenticateAdminPanel.php`
- Create: `tests/Feature/Auth/AccountSuspensionLifecycleTest.php`
- Create: `tests/Feature/Auth/SessionTimeoutTest.php`
- Modify: `bootstrap/app.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Http/Middleware/EnsureAccountIsAccessible.php`
- Modify: `app/Http/Controllers/Auth/LoginController.php`
- Modify: `resources/views/auth/login.blade.php`
- Modify: `app/Actions/Superadmin/Organizations/SuspendOrganizationAction.php`
- Modify: `tests/Feature/Auth/LoginFlowTest.php`
- Modify: `tests/Feature/Auth/PasswordResetTest.php`
- Modify: `tests/Feature/Auth/AccessIsolationTest.php`

- [ ] **Step 1: Write the failing auth lifecycle tests**

Create `tests/Feature/Auth/AccountSuspensionLifecycleTest.php` with scenarios like:

```php
it('shows the suspended-account message on login when the organization is suspended', function () {
    $organization = Organization::factory()->create([
        'status' => \App\Enums\OrganizationStatus::SUSPENDED,
    ]);
    $admin = User::factory()->admin()->for($organization)->create();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => __('auth.account_suspended'),
        ]);
});
```

Create `tests/Feature/Auth/SessionTimeoutTest.php` with coverage for:

- expired session on a protected web route redirects to login with `Your session expired. Please log in again.`
- expired session on an admin-panel route does the same
- login after timeout returns the user to the originally intended URL

Extend `tests/Feature/Auth/PasswordResetTest.php` to prove:

- superadmin, admin, manager, and tenant users can all request reset links
- reset tokens remain valid for `60` minutes and fail afterward

- [ ] **Step 2: Run the auth lifecycle tests**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AccountSuspensionLifecycleTest.php tests/Feature/Auth/SessionTimeoutTest.php tests/Feature/Auth/PasswordResetTest.php
```

Expected: FAIL because session-expiry messaging is not implemented and organization suspension does not yet invalidate all active sessions.

- [ ] **Step 3: Implement session invalidation and timeout-aware auth middleware**

Implementation rules:

- delete all `sessions` rows for users in the suspended organization when the superadmin suspension action runs
- keep `EnsureAccountIsAccessible` as the request-time safety net for any surviving stale session
- replace the default web `auth` redirect behavior with the app middleware that flashes the timeout message only when a previously authenticated session cookie/session record existed
- use the Filament-specific middleware in `AdminPanelProvider` so admin-panel redirects match the public web behavior
- do not change the password-reset broker; only lock the existing role-agnostic access + 60-minute expiry with tests

- [ ] **Step 4: Re-run the auth lifecycle tests**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AccountSuspensionLifecycleTest.php tests/Feature/Auth/SessionTimeoutTest.php tests/Feature/Auth/PasswordResetTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the auth/session lifecycle closure**

Run:

```bash
git add app/Actions/Auth/TerminateOrganizationSessionsAction.php app/Http/Middleware/Authenticate.php app/Http/Middleware/AuthenticateAdminPanel.php bootstrap/app.php app/Providers/Filament/AdminPanelProvider.php app/Http/Middleware/EnsureAccountIsAccessible.php app/Http/Controllers/Auth/LoginController.php resources/views/auth/login.blade.php app/Actions/Superadmin/Organizations/SuspendOrganizationAction.php tests/Feature/Auth/AccountSuspensionLifecycleTest.php tests/Feature/Auth/SessionTimeoutTest.php tests/Feature/Auth/LoginFlowTest.php tests/Feature/Auth/PasswordResetTest.php tests/Feature/Auth/AccessIsolationTest.php
git commit -m "feat: close auth suspension and session lifecycle gaps"
```

## Chunk 2: Invitation Lifecycle Closure

### Task 2: Add resend-invitation support for inactive tenant and manager accounts

**Files:**
- Create: `app/Actions/Auth/ResendOrganizationInvitationAction.php`
- Create: `tests/Feature/Admin/InvitationResendTest.php`
- Modify: `app/Actions/Auth/CreateOrganizationInvitationAction.php`
- Modify: `app/Actions/Auth/AcceptOrganizationInvitationAction.php`
- Modify: `app/Http/Controllers/Auth/AcceptInvitationController.php`
- Modify: `app/Notifications/Auth/OrganizationInvitationNotification.php`
- Modify: `app/Filament/Resources/Tenants/Pages/ViewTenant.php`
- Modify: `app/Filament/Resources/Tenants/TenantResource.php`
- Modify: `tests/Feature/Auth/InvitationAcceptanceTest.php`

- [ ] **Step 1: Write the failing invitation resend tests**

Create `tests/Feature/Admin/InvitationResendTest.php` with cases like:

```php
it('resends a fresh invitation for an inactive tenant account', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->for($organization)->create();

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'role' => \App\Enums\UserRole::TENANT,
        'accepted_at' => null,
        'expires_at' => now()->subDay(),
    ]);

    app(\App\Actions\Auth\ResendOrganizationInvitationAction::class)
        ->handle($admin, $invitation);

    Notification::assertSentOnDemand(\App\Notifications\Auth\OrganizationInvitationNotification::class);
});
```

Add separate failing tests for:

- resend action is hidden when the tenant/manager account is already activated
- resend action creates a fresh expiry window and token instead of reusing an expired token
- expired invitation page still shows the clear “ask your administrator to resend” message

- [ ] **Step 2: Run the invitation resend tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/InvitationResendTest.php tests/Feature/Auth/InvitationAcceptanceTest.php
```

Expected: FAIL because no resend action or tenant-view affordance exists yet.

- [ ] **Step 3: Implement resend behavior on top of the existing invitation system**

Implementation rules:

- do not mutate accepted invitations
- create a fresh pending invitation token/expiry for resend scenarios
- if the admin plan models pending activation on the user, key the button off that state; if it models pending activation by outstanding invitation only, key it off the active invitation instead
- keep the invitation acceptance page and message contract unchanged apart from using the freshest valid invitation

- [ ] **Step 4: Re-run the invitation resend tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/InvitationResendTest.php tests/Feature/Auth/InvitationAcceptanceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the invitation lifecycle closure**

Run:

```bash
git add app/Actions/Auth/ResendOrganizationInvitationAction.php app/Actions/Auth/CreateOrganizationInvitationAction.php app/Actions/Auth/AcceptOrganizationInvitationAction.php app/Http/Controllers/Auth/AcceptInvitationController.php app/Notifications/Auth/OrganizationInvitationNotification.php app/Filament/Resources/Tenants/Pages/ViewTenant.php app/Filament/Resources/Tenants/TenantResource.php tests/Feature/Admin/InvitationResendTest.php tests/Feature/Auth/InvitationAcceptanceTest.php
git commit -m "feat: add invitation resend support"
```

## Chunk 3: Tenant Continuity And Access Scoping

### Task 3: Preserve historical invoices after tenant unassignment and stop post-unassignment invoice generation

**Files:**
- Create: `app/Support/Admin/Invoices/InvoiceEligibilityWindow.php`
- Create: `tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php`
- Modify: `app/Actions/Admin/Properties/UnassignTenantFromPropertyAction.php`
- Modify: `app/Actions/Admin/Invoices/GenerateInvoiceLineItemsAction.php`
- Modify: `app/Actions/Admin/Invoices/GenerateBulkInvoicesAction.php`
- Modify: `app/Support/Tenant/Portal/TenantInvoiceIndexQuery.php`
- Modify: `app/Policies/InvoicePolicy.php`
- Modify: `resources/views/tenant/invoices/index.blade.php`

- [ ] **Step 1: Write the failing invoice-retention tests**

Create `tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php` with scenarios like:

```php
it('keeps historical invoices visible after a tenant is unassigned', function () {
    $organization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->for($organization)->create();
    $property = Property::factory()->for($organization)->create();
    $invoice = Invoice::factory()->for($tenant, 'tenant')->create();

    app(\App\Actions\Admin\Properties\UnassignTenantFromPropertyAction::class)
        ->handle($property);

    expect($invoice->fresh()->tenant_id)->toBe($tenant->id);
});
```

Add separate failing tests for:

- tenant can still download historical invoices after unassignment
- invoice generation is blocked or skipped for billing periods whose start date is after the unassignment timestamp
- admin still sees the historical invoice in the tenant record and invoice lists

- [ ] **Step 2: Run the invoice-retention tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php
```

Expected: FAIL because invoice-generation eligibility is not yet tied to assignment history.

- [ ] **Step 3: Add a shared invoice-eligibility rule and wire it into unassignment/invoice generation**

Implementation rules:

- never null out invoice ownership/history when a tenant is unassigned
- use the assignment end timestamp as the cutoff for future invoice generation
- keep the eligibility rule in one support class used by single-invoice and bulk-invoice flows
- historical invoice visibility/download remains tied to the tenant identity, not the current property assignment

- [ ] **Step 4: Re-run the invoice-retention tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the tenant invoice-retention rules**

Run:

```bash
git add app/Support/Admin/Invoices/InvoiceEligibilityWindow.php app/Actions/Admin/Properties/UnassignTenantFromPropertyAction.php app/Actions/Admin/Invoices/GenerateInvoiceLineItemsAction.php app/Actions/Admin/Invoices/GenerateBulkInvoicesAction.php app/Support/Tenant/Portal/TenantInvoiceIndexQuery.php app/Policies/InvoicePolicy.php resources/views/tenant/invoices/index.blade.php tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php
git commit -m "feat: preserve historical invoices after unassignment"
```

### Task 4: Restrict tenant-visible meters to the currently assigned property only

**Files:**
- Create: `tests/Feature/Tenant/TenantMeterVisibilityTest.php`
- Modify: `app/Support/Tenant/Portal/TenantHomePresenter.php`
- Modify: `app/Support/Tenant/Portal/TenantPropertyPresenter.php`
- Modify: `app/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- Modify: `app/Policies/MeterPolicy.php`
- Modify: `app/Policies/InvoicePolicy.php`
- Modify: `resources/views/tenant/property/show.blade.php`

- [ ] **Step 1: Write the failing tenant meter-visibility tests**

Create `tests/Feature/Tenant/TenantMeterVisibilityTest.php` with cases like:

```php
it('shows only meters assigned to the tenants current property', function () {
    $fixture = TenantPortalFactory::new()->withBuildingContainingMultipleUnits()->create();

    $this->actingAs($fixture->tenantUser)
        ->get(route('tenant.property.show'))
        ->assertOk()
        ->assertSeeText($fixture->assignedMeter->serial_number)
        ->assertDontSeeText($fixture->otherUnitMeter->serial_number);
});
```

Add separate failing tests for:

- submit-reading page only offering current-property meters
- direct access to another property’s meter route/download/submission being forbidden
- historical invoice access still working even when no current-property meters are visible after unassignment

- [ ] **Step 2: Run the tenant meter-visibility tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantMeterVisibilityTest.php
```

Expected: FAIL because current portal scoping is not yet explicitly locked to current-property meters only.

- [ ] **Step 3: Centralize current-property meter scoping in tenant presenters/policies**

Implementation rules:

- distinguish clearly between `current property meters` and `historical invoices`
- never infer meter visibility from “same organization” or “same building”
- ensure the submit-reading action rejects meters outside the tenant’s current property even if an ID is posted manually

- [ ] **Step 4: Re-run the tenant meter-visibility tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantMeterVisibilityTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the tenant meter-scope closure**

Run:

```bash
git add app/Support/Tenant/Portal/TenantHomePresenter.php app/Support/Tenant/Portal/TenantPropertyPresenter.php app/Actions/Tenant/Readings/SubmitTenantReadingAction.php app/Policies/MeterPolicy.php app/Policies/InvoicePolicy.php resources/views/tenant/property/show.blade.php tests/Feature/Tenant/TenantMeterVisibilityTest.php
git commit -m "feat: enforce tenant meter visibility scope"
```

## Chunk 4: Breadcrumbs And Empty States

### Task 5: Add shared breadcrumbs to every non-dashboard page

**Files:**
- Create: `app/Support/Shell/Breadcrumbs/BreadcrumbItemData.php`
- Create: `app/View/Components/Shell/Breadcrumbs.php`
- Create: `resources/views/components/shell/breadcrumbs.blade.php`
- Create: `tests/Feature/Shell/BreadcrumbsTest.php`
- Modify: `app/View/Components/Shell/AppFrame.php`
- Modify: `resources/views/components/shell/app-frame.blade.php`
- Modify: `resources/views/tenant/property/show.blade.php`
- Modify: `resources/views/tenant/invoices/index.blade.php`
- Modify: `app/Filament/Resources/Buildings/Pages/ViewBuilding.php`
- Modify: `app/Filament/Resources/Properties/Pages/ViewProperty.php`
- Modify: `app/Filament/Resources/Tenants/Pages/ViewTenant.php`
- Modify: `app/Filament/Resources/Meters/Pages/ViewMeter.php`
- Modify: `app/Filament/Resources/Invoices/Pages/ViewInvoice.php`

- [ ] **Step 1: Write the failing breadcrumb tests**

Create `tests/Feature/Shell/BreadcrumbsTest.php` with checks like:

```php
it('renders breadcrumbs on tenant non-dashboard pages', function () {
    $tenant = TenantPortalFactory::new()->withAssignedProperty()->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.property.show'))
        ->assertOk()
        ->assertSeeText('My Property');
});
```

Add assertions for:

- dashboard pages do **not** render breadcrumbs
- building/property/admin view pages expose the expected trail structure
- the last breadcrumb item is plain text, not a link

- [ ] **Step 2: Run the breadcrumb tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/BreadcrumbsTest.php
```

Expected: FAIL because the shell and resource pages do not yet expose a shared breadcrumb contract.

- [ ] **Step 3: Build the breadcrumb component and apply it to tenant/custom pages while normalizing Filament page breadcrumbs**

Implementation rules:

- keep breadcrumb data explicit per page/resource rather than inferring from URLs
- do not render breadcrumbs on dashboard pages
- reuse Filament’s page breadcrumb APIs for admin/superadmin pages where possible instead of bypassing the framework

- [ ] **Step 4: Re-run the breadcrumb tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/BreadcrumbsTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the breadcrumb closure**

Run:

```bash
git add app/Support/Shell/Breadcrumbs/BreadcrumbItemData.php app/View/Components/Shell/Breadcrumbs.php resources/views/components/shell/breadcrumbs.blade.php app/View/Components/Shell/AppFrame.php resources/views/components/shell/app-frame.blade.php resources/views/tenant/property/show.blade.php resources/views/tenant/invoices/index.blade.php app/Filament/Resources/Buildings/Pages/ViewBuilding.php app/Filament/Resources/Properties/Pages/ViewProperty.php app/Filament/Resources/Tenants/Pages/ViewTenant.php app/Filament/Resources/Meters/Pages/ViewMeter.php app/Filament/Resources/Invoices/Pages/ViewInvoice.php tests/Feature/Shell/BreadcrumbsTest.php
git commit -m "feat: add shared breadcrumb behavior"
```

### Task 6: Add friendly empty states to the first-run admin organization lists

**Files:**
- Create: `resources/views/components/ui/empty-state.blade.php`
- Create: `tests/Feature/Admin/EmptyOrganizationStateTest.php`
- Modify: `app/Filament/Resources/Buildings/Pages/ListBuildings.php`
- Modify: `app/Filament/Resources/Properties/Pages/ListProperties.php`
- Modify: `app/Filament/Resources/Tenants/Pages/ListTenants.php`
- Modify: `app/Filament/Resources/Meters/Pages/ListMeters.php`

- [ ] **Step 1: Write the failing empty-state tests**

Create `tests/Feature/Admin/EmptyOrganizationStateTest.php` with cases like:

```php
it('shows a friendly empty state on the buildings list for a new organization', function () {
    $admin = AdminDomainFactory::new()->withoutBuildings()->create()->user;

    $this->actingAs($admin)
        ->get(\App\Filament\Resources\Buildings\BuildingResource::getUrl())
        ->assertOk()
        ->assertSeeText('You have not added any buildings yet')
        ->assertSeeText('Add Your First Building');
});
```

Add similar failing assertions for the Properties, Tenants, and Meters list pages.

- [ ] **Step 2: Run the empty-state tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/EmptyOrganizationStateTest.php
```

Expected: FAIL because list pages will otherwise show empty tables with default Filament copy.

- [ ] **Step 3: Implement shared empty-state rendering on the organization list pages**

Implementation rules:

- use Filament empty-state heading/description/action hooks where possible
- route the primary action to the correct create page
- keep the illustration/content markup shared via one component instead of duplicating four custom layouts

- [ ] **Step 4: Re-run the empty-state tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/EmptyOrganizationStateTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the empty-state closure**

Run:

```bash
git add resources/views/components/ui/empty-state.blade.php app/Filament/Resources/Buildings/Pages/ListBuildings.php app/Filament/Resources/Properties/Pages/ListProperties.php app/Filament/Resources/Tenants/Pages/ListTenants.php app/Filament/Resources/Meters/Pages/ListMeters.php tests/Feature/Admin/EmptyOrganizationStateTest.php
git commit -m "feat: add friendly empty organization states"
```

## Execution Notes

- Execute this after the prerequisite page/model plans exist, or fold the relevant chunk into those branches while building them.
- Prefer closing gaps with regression tests around existing behavior before introducing new abstractions.
- Keep invoice-retention and tenant-meter-scope rules separate in code even though they both concern post-unassignment behavior; one is historical financial access, the other is current operational visibility.
- For session-timeout UX, preserve Laravel’s intended-URL redirect behavior; add only the flash-message signal, not a parallel redirect system.
