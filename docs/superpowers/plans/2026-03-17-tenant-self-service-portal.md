# Tenant Self-Service Portal Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the tenant-facing self-service portal so tenants can navigate with a fixed bottom bar, see their current balance and meter activity, submit readings, browse invoices, view property details, and manage their profile in a mobile-first experience.

**Architecture:** Keep the tenant experience outside Filament and build it as Blade pages inside the shared authenticated shell, with small Livewire islands only where the product needs immediate feedback or timed refreshes. Reuse the organization-scoped models, policies, validation services, and invoice/meter actions from the admin rollout instead of forking tenant-only business logic; tenant code should mainly be route composition, tenant-scoped queries/presenters, and a thin reading-submission/profile layer.

**Tech Stack:** Laravel 12, Blade, Livewire 4, Tailwind CSS v4, Eloquent, Laravel notifications, Pest 4, Laravel Pint.

---

## Scope Check

This is one coherent portal subsystem, but it depends on earlier work:

- `docs/superpowers/plans/2026-03-17-foundation-auth-onboarding.md`
- `docs/superpowers/plans/2026-03-17-shared-interface-elements.md`
- `docs/superpowers/plans/2026-03-17-admin-organization-operations.md`

If the admin organization-operations plan has not created `Property`, `Meter`, `MeterReading`, `Invoice`, `OrganizationSetting`, and their policies yet, do not start Chunks 2-5. Either execute that prerequisite first or fold the missing model/policy work into the same branch before returning to this plan.

## Spec Notes And Assumptions

- Tenant users never see the left sidebar. The bottom navigation is always present and contains exactly four items: `Home`, `Readings`, `Invoices`, and `Profile`.
- `My Property` is a secondary tenant route, not a fifth bottom-nav item.
- The spec line saying `View All Readings` should navigate to the invoices page conflicts with the rest of the portal because the invoice page is card-based and does not include reading history. Implement that link to the `Readings` route unless product explicitly overrides it.
- `Pay Now` does not process money online. The tenant-facing message should be built from organization billing/contact settings that already exist in the admin/settings domain. Do not add a payment gateway in this slice.
- Every tenant query must be scoped to the signed-in tenant’s assigned property, meters, readings, and invoices only. No cross-property fallback is allowed.
- Reuse shared reading validation logic from the admin domain. Do not create a second copy of “reading must not decrease / date cannot be in the future / anomaly flag” rules for tenants.

## Skills To Use During Execution

- `@laravel-11-12-app-guidelines`
- `@livewire-development`
- `@tailwindcss-development`
- `@laravel-actions`
- `@pest-testing`

## File Map

### Create

- `app/Http/Controllers/Tenant/Readings/CreateController.php`
- `app/Http/Controllers/Tenant/Invoices/IndexController.php`
- `app/Http/Controllers/Tenant/Invoices/DownloadController.php`
- `app/Http/Controllers/Tenant/Property/ShowController.php`
- `app/Http/Controllers/Tenant/Profile/EditController.php`
- `app/Http/Controllers/Tenant/Profile/UpdateController.php`
- `app/Http/Controllers/Tenant/Profile/UpdatePasswordController.php`
- `app/Http/Requests/Tenant/UpdateTenantProfileRequest.php`
- `app/Http/Requests/Tenant/UpdateTenantPasswordRequest.php`
- `app/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- `app/Actions/Tenant/Profile/UpdateTenantProfileAction.php`
- `app/Actions/Tenant/Profile/UpdateTenantPasswordAction.php`
- `app/Support/Tenant/Portal/TenantHomePresenter.php`
- `app/Support/Tenant/Portal/TenantPropertyPresenter.php`
- `app/Support/Tenant/Portal/TenantInvoiceIndexQuery.php`
- `app/Support/Tenant/Portal/PaymentInstructionsResolver.php`
- `app/Livewire/Tenant/HomeSummary.php`
- `app/Livewire/Tenant/SubmitReadingPage.php`
- `resources/views/livewire/tenant/home-summary.blade.php`
- `resources/views/livewire/tenant/submit-reading-page.blade.php`
- `resources/views/tenant/readings/create.blade.php`
- `resources/views/tenant/invoices/index.blade.php`
- `resources/views/tenant/property/show.blade.php`
- `resources/views/tenant/profile/edit.blade.php`
- `lang/en/tenant.php`
- `lang/lt/tenant.php`
- `lang/ru/tenant.php`
- `lang/es/tenant.php`
- `tests/Support/TenantPortalFactory.php`
- `tests/Feature/Tenant/TenantPortalNavigationTest.php`
- `tests/Feature/Tenant/TenantHomePageTest.php`
- `tests/Feature/Tenant/TenantSubmitReadingTest.php`
- `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`
- `tests/Feature/Tenant/TenantPropertyPageTest.php`
- `tests/Feature/Tenant/TenantProfilePageTest.php`
- `tests/Feature/Tenant/TenantAccessIsolationTest.php`

### Modify

- `routes/web.php`
- `config/tenanto.php`
- `app/Http/Controllers/Tenant/HomeController.php`
- `app/Support/Shell/Navigation/NavigationBuilder.php`
- `app/Livewire/Shell/TenantBottomNavigation.php`
- `resources/views/tenant/home.blade.php`
- `app/Actions/Preferences/UpdateUserLocaleAction.php`
- `app/Models/User.php`
- `app/Models/Property.php` once created by the admin plan
- `app/Models/Meter.php` once created by the admin plan
- `app/Models/MeterReading.php` once created by the admin plan
- `app/Models/Invoice.php` once created by the admin plan
- `app/Models/OrganizationSetting.php` once created by the admin plan
- `app/Policies/PropertyPolicy.php` once created by the admin plan
- `app/Policies/MeterPolicy.php` once created by the admin plan
- `app/Policies/InvoicePolicy.php` once created by the admin plan
- `app/Support/Admin/ReadingValidation/ValidateReadingValue.php` or the exact shared reading-validation service created by the admin plan
- `app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php` if the tenant submit action should delegate into the existing admin/domain write path instead of duplicating it
- `tests/Feature/Auth/AccessIsolationTest.php`

### Do Not Create

- a tenant Filament panel
- a tenant sidebar
- separate tenant-only copies of invoice or meter domain models
- online payment processing
- a fifth bottom-navigation item for `My Property`

## Chunk 1: Tenant Portal Shell And Routing

### Task 1: Add tenant portal routes and fixed bottom navigation

**Files:**
- Create: `tests/Feature/Tenant/TenantPortalNavigationTest.php`
- Create: `app/Http/Controllers/Tenant/Readings/CreateController.php`
- Create: `app/Http/Controllers/Tenant/Invoices/IndexController.php`
- Create: `app/Http/Controllers/Tenant/Property/ShowController.php`
- Create: `app/Http/Controllers/Tenant/Profile/EditController.php`
- Create: `resources/views/tenant/readings/create.blade.php`
- Create: `resources/views/tenant/invoices/index.blade.php`
- Create: `resources/views/tenant/property/show.blade.php`
- Create: `resources/views/tenant/profile/edit.blade.php`
- Create: `lang/en/tenant.php`
- Create: `lang/lt/tenant.php`
- Create: `lang/ru/tenant.php`
- Create: `lang/es/tenant.php`
- Modify: `routes/web.php`
- Modify: `config/tenanto.php`
- Modify: `app/Support/Shell/Navigation/NavigationBuilder.php`
- Modify: `app/Livewire/Shell/TenantBottomNavigation.php`
- Modify: `resources/views/tenant/home.blade.php`

- [ ] **Step 1: Write the failing tenant navigation test**

Create `tests/Feature/Tenant/TenantPortalNavigationTest.php` with assertions like:

```php
it('shows the four tenant bottom navigation items and hides admin navigation', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertOk()
        ->assertSeeText('Home')
        ->assertSeeText('Readings')
        ->assertSeeText('Invoices')
        ->assertSeeText('Profile')
        ->assertDontSeeText('Buildings')
        ->assertDontSeeText('Organizations');
});
```

Add a second test asserting the route names exist and return `200` for:

- `tenant.home`
- `tenant.readings.create`
- `tenant.invoices.index`
- `tenant.profile.edit`

- [ ] **Step 2: Run the navigation test**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantPortalNavigationTest.php
```

Expected: FAIL because only `tenant.home` exists and the bottom nav does not yet match the spec.

- [ ] **Step 3: Add tenant route skeletons and finalize the bottom-nav contract**

Implementation rules:

- keep tenant routes under the authenticated locale-aware middleware group
- make `Readings` point at the reading-submission route
- make `Invoices` point at the card-list invoice route
- keep `My Property` as a non-bottom-nav route
- ensure the tenant bottom nav renders on every tenant page and never on admin/manager/superadmin pages

- [ ] **Step 4: Re-run the navigation test**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantPortalNavigationTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the tenant portal shell**

Run:

```bash
git add tests/Feature/Tenant/TenantPortalNavigationTest.php app/Http/Controllers/Tenant routes/web.php config/tenanto.php app/Support/Shell/Navigation/NavigationBuilder.php app/Livewire/Shell/TenantBottomNavigation.php resources/views/tenant lang
git commit -m "feat: add tenant portal routing and navigation"
```

## Chunk 2: Home Dashboard And Property View

### Task 2: Build the tenant home page with outstanding balance, monthly usage, and recent readings

**Files:**
- Create: `tests/Feature/Tenant/TenantHomePageTest.php`
- Create: `tests/Support/TenantPortalFactory.php`
- Create: `app/Support/Tenant/Portal/TenantHomePresenter.php`
- Create: `app/Support/Tenant/Portal/PaymentInstructionsResolver.php`
- Create: `app/Livewire/Tenant/HomeSummary.php`
- Create: `resources/views/livewire/tenant/home-summary.blade.php`
- Modify: `app/Http/Controllers/Tenant/HomeController.php`
- Modify: `resources/views/tenant/home.blade.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Property.php` once created
- Modify: `app/Models/Meter.php` once created
- Modify: `app/Models/MeterReading.php` once created
- Modify: `app/Models/Invoice.php` once created
- Modify: `app/Models/OrganizationSetting.php` once created

- [ ] **Step 1: Write the failing tenant home tests**

Create `tests/Feature/Tenant/TenantHomePageTest.php` with coverage for:

```php
it('shows the tenant greeting, outstanding balance, and recent readings', function () {
    $tenant = TenantPortalFactory::new()
        ->withUserName('Taylor Tenant')
        ->withUnpaidInvoices()
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.home'))
        ->assertOk()
        ->assertSeeText('Taylor')
        ->assertSeeText('Outstanding Balance')
        ->assertSeeText('This Month')
        ->assertSeeText('Recent Readings')
        ->assertSeeText('Submit New Reading');
});
```

Also add separate tests for:

- “all paid up” copy when no unpaid invoices exist
- combined total copy `Across N invoices`
- “No reading this month” copy when a meter has no current-month reading
- `My Property` link visibility on the home screen

- [ ] **Step 2: Run the home tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantHomePageTest.php
```

Expected: FAIL because the current home page is only a placeholder marketing-style panel.

- [ ] **Step 3: Implement tenant-scoped home presenters and polling widget**

Implementation rules:

- use one presenter/service to gather the home payload with eager loading
- embed the data section as a Livewire component so `wire:poll.120s` can refresh without a full page flash
- keep payment-instruction text generation in `PaymentInstructionsResolver`, not in Blade
- scope every query through the signed-in tenant’s assigned property and invoices

- [ ] **Step 4: Re-run the home tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantHomePageTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the tenant home experience**

Run:

```bash
git add tests/Support/TenantPortalFactory.php tests/Feature/Tenant/TenantHomePageTest.php app/Support/Tenant/Portal/TenantHomePresenter.php app/Support/Tenant/Portal/PaymentInstructionsResolver.php app/Livewire/Tenant/HomeSummary.php resources/views/livewire/tenant/home-summary.blade.php app/Http/Controllers/Tenant/HomeController.php resources/views/tenant/home.blade.php app/Models/User.php app/Models/Property.php app/Models/Meter.php app/Models/MeterReading.php app/Models/Invoice.php app/Models/OrganizationSetting.php
git commit -m "feat: build tenant home dashboard"
```

### Task 3: Build the read-only My Property page

**Files:**
- Create: `tests/Feature/Tenant/TenantPropertyPageTest.php`
- Create: `app/Support/Tenant/Portal/TenantPropertyPresenter.php`
- Modify: `app/Http/Controllers/Tenant/Property/ShowController.php`
- Modify: `resources/views/tenant/property/show.blade.php`
- Modify: `app/Models/Property.php` once created
- Modify: `app/Models/Meter.php` once created
- Modify: `app/Models/MeterReading.php` once created
- Modify: `app/Policies/PropertyPolicy.php` once created
- Modify: `app/Policies/MeterPolicy.php` once created

- [ ] **Step 1: Write the failing property page tests**

Create `tests/Feature/Tenant/TenantPropertyPageTest.php` with assertions like:

```php
it('shows the tenant property details and assigned meters without edit actions', function () {
    $tenant = TenantPortalFactory::new()->withAssignedProperty()->withMeters()->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.property.show'))
        ->assertOk()
        ->assertSeeText('My Property')
        ->assertSeeText($tenant->property->address)
        ->assertSeeText('Your Meters')
        ->assertDontSeeText('Edit')
        ->assertDontSeeText('Delete');
});
```

Add a second test for the empty-reading state copy `Last reading: None recorded yet`.

- [ ] **Step 2: Run the property tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantPropertyPageTest.php
```

Expected: FAIL because the page is only a route skeleton at this point.

- [ ] **Step 3: Implement the property presenter and route authorization**

Implementation rules:

- eager load property, building, meters, and latest reading in one tenant-scoped query path
- do not expose meter-edit or property-edit controls
- make “None recorded yet” link to `tenant.readings.create`
- authorize the route so a tenant can never bind another property record

- [ ] **Step 4: Re-run the property tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantPropertyPageTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the property view**

Run:

```bash
git add tests/Feature/Tenant/TenantPropertyPageTest.php app/Support/Tenant/Portal/TenantPropertyPresenter.php app/Http/Controllers/Tenant/Property/ShowController.php resources/views/tenant/property/show.blade.php app/Models/Property.php app/Models/Meter.php app/Models/MeterReading.php app/Policies/PropertyPolicy.php app/Policies/MeterPolicy.php
git commit -m "feat: add tenant property page"
```

## Chunk 3: Reading Submission Flow

### Task 4: Build the tenant reading-submission page with live validation and confirmation state

**Files:**
- Create: `tests/Feature/Tenant/TenantSubmitReadingTest.php`
- Create: `app/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- Create: `app/Livewire/Tenant/SubmitReadingPage.php`
- Create: `resources/views/livewire/tenant/submit-reading-page.blade.php`
- Modify: `resources/views/tenant/readings/create.blade.php`
- Modify: `app/Models/Meter.php` once created
- Modify: `app/Models/MeterReading.php` once created
- Modify: `app/Support/Admin/ReadingValidation/ValidateReadingValue.php` or the exact shared validation class once created
- Modify: `app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php` if delegation keeps a single write path

- [ ] **Step 1: Write the failing reading-submission tests**

Create `tests/Feature/Tenant/TenantSubmitReadingTest.php`. Use Livewire testing for the form interactions:

```php
it('lets a tenant submit a reading only for meters assigned to their property', function () {
    $tenant = TenantPortalFactory::new()->withSingleMeter()->create();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('readingValue', '1450')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Reading Submitted!');
});
```

Add separate failing tests for:

- previous reading box visibility after meter selection
- “lower than previous reading” validation message
- future-date rejection
- single-meter accounts showing the meter as preselected and locked
- confirmation state showing submitted value, unit, and meter identifier

- [ ] **Step 2: Run the reading tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantSubmitReadingTest.php
```

Expected: FAIL because no reading form component or tenant submit action exists.

- [ ] **Step 3: Implement the tenant submit action and Livewire page**

Implementation rules:

- keep the component thin and push persistence into `SubmitTenantReadingAction`
- delegate validation/anomaly rules into the shared domain validator/write path instead of re-implementing them
- expose the computed consumption preview live as the user types
- show a full success state after submit instead of redirecting immediately

- [ ] **Step 4: Re-run the reading tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantSubmitReadingTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the reading-submission flow**

Run:

```bash
git add tests/Feature/Tenant/TenantSubmitReadingTest.php app/Actions/Tenant/Readings/SubmitTenantReadingAction.php app/Livewire/Tenant/SubmitReadingPage.php resources/views/livewire/tenant/submit-reading-page.blade.php resources/views/tenant/readings/create.blade.php app/Models/Meter.php app/Models/MeterReading.php app/Support/Admin/ReadingValidation/ValidateReadingValue.php app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php
git commit -m "feat: add tenant reading submission flow"
```

## Chunk 4: Invoice History

### Task 5: Build the tenant invoice history page and protected PDF download flow

**Files:**
- Create: `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`
- Create: `app/Support/Tenant/Portal/TenantInvoiceIndexQuery.php`
- Create: `app/Http/Controllers/Tenant/Invoices/DownloadController.php`
- Modify: `app/Http/Controllers/Tenant/Invoices/IndexController.php`
- Modify: `resources/views/tenant/invoices/index.blade.php`
- Modify: `app/Models/Invoice.php` once created
- Modify: `app/Policies/InvoicePolicy.php` once created

- [ ] **Step 1: Write the failing invoice history tests**

Create `tests/Feature/Tenant/TenantInvoiceHistoryTest.php` with assertions for:

```php
it('shows tenant invoices as cards with quick status filters', function () {
    $tenant = TenantPortalFactory::new()->withInvoices()->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.invoices.index'))
        ->assertOk()
        ->assertSeeText('My Invoices')
        ->assertSeeText('All')
        ->assertSeeText('Unpaid')
        ->assertSeeText('Paid');
});
```

Add separate failing tests for:

- unpaid filter showing only unpaid invoices
- all-paid-up empty state copy
- overdue invoice copy or marker
- `Download PDF` opening a tenant-authorized response for the tenant’s own invoice only

- [ ] **Step 2: Run the invoice tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantInvoiceHistoryTest.php
```

Expected: FAIL because the invoice page is only a shell route and no tenant-authorized download flow exists.

- [ ] **Step 3: Implement the invoice query object, filters, and secure download**

Implementation rules:

- keep the list query in `TenantInvoiceIndexQuery`, not in the controller or Blade
- use query-string filters so pagination and “back” behavior stay stable
- authorize downloads through the invoice policy and tenant scoping
- reuse the existing invoice document path/generator from the admin domain instead of generating a second PDF format

- [ ] **Step 4: Re-run the invoice tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantInvoiceHistoryTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the invoice history flow**

Run:

```bash
git add tests/Feature/Tenant/TenantInvoiceHistoryTest.php app/Support/Tenant/Portal/TenantInvoiceIndexQuery.php app/Http/Controllers/Tenant/Invoices/IndexController.php app/Http/Controllers/Tenant/Invoices/DownloadController.php resources/views/tenant/invoices/index.blade.php app/Models/Invoice.php app/Policies/InvoicePolicy.php
git commit -m "feat: add tenant invoice history"
```

## Chunk 5: Profile, Isolation, And Final Verification

### Task 6: Build the tenant profile page with locale switching and password updates

**Files:**
- Create: `tests/Feature/Tenant/TenantProfilePageTest.php`
- Create: `app/Http/Requests/Tenant/UpdateTenantProfileRequest.php`
- Create: `app/Http/Requests/Tenant/UpdateTenantPasswordRequest.php`
- Create: `app/Http/Controllers/Tenant/Profile/UpdateController.php`
- Create: `app/Http/Controllers/Tenant/Profile/UpdatePasswordController.php`
- Create: `app/Actions/Tenant/Profile/UpdateTenantProfileAction.php`
- Create: `app/Actions/Tenant/Profile/UpdateTenantPasswordAction.php`
- Modify: `app/Http/Controllers/Tenant/Profile/EditController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/tenant/profile/edit.blade.php`
- Modify: `app/Actions/Preferences/UpdateUserLocaleAction.php`
- Modify: `app/Models/User.php`

- [ ] **Step 1: Write the failing tenant profile tests**

Create `tests/Feature/Tenant/TenantProfilePageTest.php` with assertions like:

```php
it('lets tenants update profile details and preferred language', function () {
    $tenant = User::factory()->tenant()->create(['locale' => 'en']);

    $this->actingAs($tenant)
        ->patch(route('tenant.profile.update'), [
            'name' => 'Taylor Tenant',
            'email' => 'tenant@example.test',
            'phone' => '+37060000000',
            'locale' => 'lt',
        ])
        ->assertRedirect(route('tenant.profile.edit'));

    expect($tenant->fresh()->locale)->toBe('lt');
});
```

Add separate failing tests for:

- password confirmation mismatch error copy
- current-password requirement when changing password
- immediate locale switching behavior after a successful language change

- [ ] **Step 2: Run the profile tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantProfilePageTest.php
```

Expected: FAIL because no tenant profile update routes or actions exist yet.

- [ ] **Step 3: Implement tenant profile editing and password change**

Implementation rules:

- use Form Requests for the two POST/PATCH flows
- keep locale persistence delegated to `UpdateUserLocaleAction`
- make the page mobile-friendly but server-rendered; do not move tenant profile into Filament
- preserve inline validation messages underneath each field

- [ ] **Step 4: Re-run the profile tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantProfilePageTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the tenant profile flow**

Run:

```bash
git add tests/Feature/Tenant/TenantProfilePageTest.php app/Http/Requests/Tenant/UpdateTenantProfileRequest.php app/Http/Requests/Tenant/UpdateTenantPasswordRequest.php app/Http/Controllers/Tenant/Profile app/Actions/Tenant/Profile resources/views/tenant/profile/edit.blade.php routes/web.php app/Actions/Preferences/UpdateUserLocaleAction.php app/Models/User.php
git commit -m "feat: add tenant profile management"
```

### Task 7: Lock down tenant isolation and run the portal verification suite

**Files:**
- Create: `tests/Feature/Tenant/TenantAccessIsolationTest.php`
- Modify: `tests/Feature/Auth/AccessIsolationTest.php`
- Modify: `app/Policies/PropertyPolicy.php` once created
- Modify: `app/Policies/MeterPolicy.php` once created
- Modify: `app/Policies/InvoicePolicy.php` once created
- Modify: `app/Models/Property.php` once created
- Modify: `app/Models/Meter.php` once created
- Modify: `app/Models/Invoice.php` once created

- [ ] **Step 1: Write the failing tenant isolation tests**

Create `tests/Feature/Tenant/TenantAccessIsolationTest.php` with scenarios proving one tenant cannot reach another tenant’s:

- property page
- invoice download route
- meter-reading submit target

Example:

```php
it('blocks a tenant from downloading another tenants invoice', function () {
    [$first, $second] = TenantPortalFactory::new()->pairWithInvoices()->create();

    $this->actingAs($first->user)
        ->get(route('tenant.invoices.download', $second->invoice))
        ->assertForbidden();
});
```

Also extend `tests/Feature/Auth/AccessIsolationTest.php` with one high-level portal regression proving tenant portal routes stay organization- and assignment-scoped.

- [ ] **Step 2: Run the isolation tests**

Run:

```bash
php artisan test --compact tests/Feature/Tenant/TenantAccessIsolationTest.php tests/Feature/Auth/AccessIsolationTest.php
```

Expected: FAIL until tenant authorization and scoping rules are complete.

- [ ] **Step 3: Finalize policies/scopes and run the full tenant verification pass**

Run:

```bash
php artisan test --compact tests/Feature/Tenant
php artisan test --compact tests/Feature/Auth/AccessIsolationTest.php
vendor/bin/pint --dirty
```

Expected:

- all tenant feature tests PASS
- auth isolation regression PASS
- Pint returns clean or auto-fixes only touched files

- [ ] **Step 4: Commit the completed tenant portal**

Run:

```bash
git add tests/Feature/Tenant tests/Feature/Auth/AccessIsolationTest.php app/Policies/PropertyPolicy.php app/Policies/MeterPolicy.php app/Policies/InvoicePolicy.php app/Models/Property.php app/Models/Meter.php app/Models/Invoice.php
git commit -m "feat: deliver tenant self-service portal"
```

## Execution Notes

- Execute this in a dedicated worktree so tenant UI work does not get tangled with the much larger admin domain rollout.
- If the admin plan is still creating the underlying models, keep the tenant branch small by rebasing on that work rather than duplicating migrations or actions here.
- Prefer tenant-scoped model methods or query objects over controller-local `where` chains.
- Reuse one invoice PDF path and one meter-reading write path across admin and tenant roles.
