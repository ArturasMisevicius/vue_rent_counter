# Cross-Cutting Behavioral Rules Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enforce Tenanto’s shared product rules across admin, manager, superadmin, and tenant experiences: subscription enforcement, meter-reading validation, invoice immutability, real-time refresh behavior, table sorting/filter persistence, loading/feedback states, and language fallback.

**Architecture:** Treat these requirements as a cross-cutting behavior overlay on top of the already-split workspace plans, not as a new product surface. Put hard business rules in shared domain/support classes first, then expose them through thin Filament concerns, Livewire traits, and shell components so the same rule applies identically whether a write originates from admin CRUD, bulk import, or the tenant portal.

**Tech Stack:** Laravel 12, Filament 5, Livewire 4, Blade, Eloquent, Laravel broadcasting/private channels, cache/session state, Pest 4, Laravel Pint.

---

## Spec Reference

- Spec: `docs/superpowers/specs/2026-03-17-cross-cutting-behavioral-rules-design.md`
- Supporting baseline: `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`

## Scope Check

This spec spans several independent subsystems, but those subsystems have already been split into earlier plans. This document is the **cross-cutting rules layer** that stitches them together after their pages and models exist.

Primary prerequisites:

- `docs/superpowers/plans/2026-03-17-shared-interface-elements.md`
- `docs/superpowers/plans/2026-03-17-admin-organization-operations.md`
- `docs/superpowers/plans/2026-03-17-manager-role-parity.md`
- `docs/superpowers/plans/2026-03-17-superadmin-control-plane.md`
- `docs/superpowers/plans/2026-03-17-tenant-self-service-portal.md`

If you want smaller branches, the natural split point is after Chunk 2:

- follow-on A: domain guardrails (`subscription enforcement`, `meter validation`, `invoice immutability`)
- follow-on B: shared UI/runtime behavior (`real-time updates`, `table persistence`, `loading`, `toasts`, `locale fallback`)

## Scope Notes

- Do not duplicate validation logic by role. Admin, manager, tenant, and import flows must all run through the same meter-reading rule engine.
- Do not duplicate invoice-lock logic in pages. Finalized-invoice mutability belongs in one guard/service that every edit path uses.
- Grace-period behavior is not full lockout: create/edit buttons stay visible, but clicking them must explain the expired subscription and route toward renewal.
- Post-grace behavior is stricter: create, edit, delete, and payment-processing actions disappear or become unavailable everywhere, while view/download access continues.
- Use Filament’s native sorting/filter persistence where possible instead of custom JavaScript state.
- Use Livewire and Filament loading primitives plus a shared toast abstraction; do not introduce browser `alert()` or bespoke modal systems.
- For “immediate partial updates,” wire components to broadcast topics and keep the timed poll intervals from the spec as a fallback/data freshness layer. Do not rely on long polling alone for this requirement.
- English is the fallback locale for missing keys. Never allow a missing translation to render a blank string or raw key identifier in the UI.

## Skills To Use During Execution

- `@laravel-11-12-app-guidelines`
- `@filament`
- `@livewire-development`
- `@laravel-actions`
- `@pest-testing`

## File Map

### Create

- `app/Enums/SubscriptionAccessMode.php` — explicit write-access state for an organization: active, limit-blocked, grace read-only, post-grace read-only.
- `app/Support/Admin/SubscriptionEnforcement/OrganizationSubscriptionAccess.php` — computes current subscription enforcement state, limit usage, grace-period status, and upgrade targets.
- `app/Support/Admin/SubscriptionEnforcement/SubscriptionEnforcementMessage.php` — builds localized modal/toast copy for limit reached and expired-subscription states.
- `app/Filament/Concerns/InteractsWithSubscriptionEnforcement.php` — shared Filament glue for visible-but-intercepted create/edit actions and fully hidden post-grace actions.
- `app/Support/Admin/Invoices/FinalizedInvoiceGuard.php` — single source of truth for what remains mutable after invoice finalization.
- `app/Events/Ui/MeterReadingChanged.php` — broadcast event for reading-driven refreshes.
- `app/Events/Ui/InvoiceChanged.php` — broadcast event for invoice-driven refreshes.
- `app/Providers/BroadcastServiceProvider.php` — registers private broadcast channels for organization, tenant, and invoice refresh topics.
- `routes/channels.php` — channel authorization for org-scoped and user-scoped refresh listeners.
- `app/Livewire/Concerns/ListensForRealtimeRefreshes.php` — reusable Livewire listener trait for partial refresh components.
- `app/Filament/Concerns/HasStandardTableBehavior.php` — centralizes default sort, sortable column expectations, and filter/sort session persistence.
- `app/Support/Ui/Toast/ToastPayload.php` — normalized toast data for Blade and Filament.
- `app/Support/Ui/Toast/ToastFactory.php` — maps success/error/warning messages to durations, indicators, and persistence behavior.
- `app/Livewire/Shell/ToastStack.php` — top-right toast renderer for non-Filament pages.
- `resources/views/livewire/shell/toast-stack.blade.php`
- `resources/views/components/ui/skeleton/card.blade.php`
- `resources/views/components/ui/skeleton/list.blade.php`
- `resources/views/components/ui/skeleton/table.blade.php`
- `lang/en/behavior.php`
- `lang/lt/behavior.php`
- `lang/ru/behavior.php`
- `tests/Unit/Support/Admin/OrganizationSubscriptionAccessTest.php`
- `tests/Unit/Support/Admin/FinalizedInvoiceGuardTest.php`
- `tests/Feature/Admin/SubscriptionEnforcementTest.php`
- `tests/Feature/Admin/MeterReadingValidationRulesTest.php`
- `tests/Feature/Admin/InvoiceImmutabilityTest.php`
- `tests/Feature/Shell/RealtimeRefreshRulesTest.php`
- `tests/Feature/Admin/TableBehaviorTest.php`
- `tests/Feature/Shell/LoadingAndFeedbackTest.php`
- `tests/Feature/Shell/LanguageSwitchingBehaviorTest.php`

### Modify

- `bootstrap/app.php`
- `bootstrap/providers.php`
- `app/Providers/AppServiceProvider.php`
- `config/tenanto.php`
- `config/app.php`
- `app/Http/Middleware/SetAuthenticatedUserLocale.php`
- `app/Actions/Preferences/UpdateUserLocaleAction.php`
- `app/Models/Subscription.php`
- `app/Support/Admin/SubscriptionLimitGuard.php`
- `app/Support/Admin/ReadingValidation/ReadingValidationResult.php`
- `app/Support/Admin/ReadingValidation/ValidateReadingValue.php`
- `app/Actions/Admin/Properties/CreatePropertyAction.php`
- `app/Actions/Admin/Tenants/CreateTenantAction.php`
- `app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php`
- `app/Actions/Admin/MeterReadings/UpdateMeterReadingAction.php`
- `app/Actions/Admin/MeterReadings/ImportMeterReadingsAction.php`
- `app/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- `app/Actions/Admin/Invoices/SaveInvoiceDraftAction.php`
- `app/Actions/Admin/Invoices/FinalizeInvoiceAction.php`
- `app/Actions/Admin/Invoices/RecordInvoicePaymentAction.php`
- `app/Models/MeterReading.php` once created by the admin plan
- `app/Models/Invoice.php` once created by the admin plan
- `app/Filament/Pages/OrganizationDashboard.php`
- `app/Filament/Pages/PlatformDashboard.php`
- `app/Filament/Pages/IntegrationHealth.php`
- `app/Filament/Pages/Settings.php`
- `app/Filament/Widgets/Admin/OrganizationStatsOverview.php`
- `app/Filament/Widgets/Admin/RecentInvoicesWidget.php`
- `app/Filament/Widgets/Admin/UpcomingReadingDeadlinesWidget.php`
- `app/Filament/Widgets/Admin/SubscriptionUsageOverview.php`
- `app/Filament/Widgets/Superadmin/PlatformStatsOverview.php`
- `app/Filament/Widgets/Superadmin/RevenueByPlanChart.php`
- `app/Filament/Widgets/Superadmin/ExpiringSubscriptionsWidget.php`
- `app/Filament/Widgets/Superadmin/RecentSecurityViolationsWidget.php`
- `app/Filament/Resources/Properties/PropertyResource.php`
- `app/Filament/Resources/Properties/Schemas/PropertyTable.php`
- `app/Filament/Resources/Properties/Pages/ListProperties.php`
- `app/Filament/Resources/Tenants/TenantResource.php`
- `app/Filament/Resources/Tenants/Schemas/TenantTable.php`
- `app/Filament/Resources/Tenants/Pages/ListTenants.php`
- `app/Filament/Resources/MeterReadings/MeterReadingResource.php`
- `app/Filament/Resources/MeterReadings/Schemas/MeterReadingTable.php`
- `app/Filament/Resources/Invoices/InvoiceResource.php`
- `app/Filament/Resources/Invoices/Schemas/InvoiceTable.php`
- `app/Filament/Resources/Invoices/Pages/EditInvoice.php`
- `app/Livewire/Shell/LanguageSwitcher.php`
- `app/Livewire/Shell/NotificationCenter.php`
- `app/Livewire/Tenant/HomeSummary.php`
- `app/Livewire/Tenant/SubmitReadingPage.php`
- `resources/views/livewire/shell/language-switcher.blade.php`
- `resources/views/livewire/shell/notification-center.blade.php`
- `resources/views/filament/pages/organization-dashboard.blade.php`
- `resources/views/filament/pages/platform-dashboard.blade.php`
- `resources/views/filament/pages/integration-health.blade.php`
- `resources/views/tenant/home.blade.php`
- `resources/views/tenant/readings/create.blade.php`
- `resources/views/tenant/invoices/index.blade.php`
- `resources/views/tenant/profile/edit.blade.php`
- `tests/Feature/Auth/LocalePersistenceTest.php`

### Do Not Create

- duplicated tenant-only or superadmin-only copies of reading validation
- invoice-lock rules embedded directly in Resource page methods
- custom JavaScript table persistence outside Filament
- browser `alert()` feedback
- a second locale persistence mechanism separate from the shared user `locale` field

## Chunk 1: Subscription Enforcement

### Task 1: Define subscription access states and limit/grace enforcement in one place

**Files:**
- Create: `app/Enums/SubscriptionAccessMode.php`
- Create: `app/Support/Admin/SubscriptionEnforcement/OrganizationSubscriptionAccess.php`
- Create: `app/Support/Admin/SubscriptionEnforcement/SubscriptionEnforcementMessage.php`
- Create: `tests/Unit/Support/Admin/OrganizationSubscriptionAccessTest.php`
- Modify: `app/Models/Subscription.php`
- Modify: `app/Support/Admin/SubscriptionLimitGuard.php`
- Modify: `config/tenanto.php`
- Modify: `lang/en/behavior.php`
- Modify: `lang/lt/behavior.php`
- Modify: `lang/ru/behavior.php`

- [ ] **Step 1: Write the failing unit tests for subscription access modes**

Create `tests/Unit/Support/Admin/OrganizationSubscriptionAccessTest.php` with coverage like:

```php
it('marks an expired subscription inside the grace period as read only', function () {
    $subscription = Subscription::factory()->create([
        'status' => \App\Enums\SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(3),
    ]);

    $state = app(OrganizationSubscriptionAccess::class)->forOrganization($subscription->organization);

    expect($state->mode)->toBe(SubscriptionAccessMode::GRACE_READ_ONLY);
});
```

Add separate failing tests for:

- active subscription below limits
- property limit reached
- tenant limit reached
- expired subscription after the 7-day grace period

- [ ] **Step 2: Run the subscription access tests**

Run:

```bash
php artisan test --compact tests/Unit/Support/Admin/OrganizationSubscriptionAccessTest.php
```

Expected: FAIL because there is no dedicated access-state service yet.

- [ ] **Step 3: Implement the subscription access model**

Implementation rules:

- keep grace-period duration configurable in `config/tenanto.php`
- expose the result as a typed object/DTO-like service output, not as loose arrays
- let `SubscriptionLimitGuard` delegate to `OrganizationSubscriptionAccess` instead of carrying duplicate rules
- keep message generation separate from the computation so the same state can drive both UI and tests

- [ ] **Step 4: Re-run the subscription access tests**

Run:

```bash
php artisan test --compact tests/Unit/Support/Admin/OrganizationSubscriptionAccessTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the subscription access foundation**

Run:

```bash
git add app/Enums/SubscriptionAccessMode.php app/Support/Admin/SubscriptionEnforcement app/Models/Subscription.php app/Support/Admin/SubscriptionLimitGuard.php config/tenanto.php lang tests/Unit/Support/Admin/OrganizationSubscriptionAccessTest.php
git commit -m "feat: add subscription access enforcement model"
```

### Task 2: Apply subscription enforcement to property/tenant creation and read-only states

**Files:**
- Create: `app/Filament/Concerns/InteractsWithSubscriptionEnforcement.php`
- Create: `tests/Feature/Admin/SubscriptionEnforcementTest.php`
- Modify: `app/Actions/Admin/Properties/CreatePropertyAction.php`
- Modify: `app/Actions/Admin/Tenants/CreateTenantAction.php`
- Modify: `app/Filament/Resources/Properties/Pages/ListProperties.php`
- Modify: `app/Filament/Resources/Tenants/Pages/ListTenants.php`
- Modify: `app/Filament/Pages/Settings.php`
- Modify: `app/Filament/Resources/Properties/PropertyResource.php`
- Modify: `app/Filament/Resources/Tenants/TenantResource.php`

- [ ] **Step 1: Write the failing admin subscription enforcement feature tests**

Create `tests/Feature/Admin/SubscriptionEnforcementTest.php` with cases like:

```php
it('shows an upgrade dialog instead of the property create form when the limit is reached', function () {
    $admin = User::factory()->admin()->create();
    $organization = Organization::factory()->create([
        'owner_user_id' => $admin->id,
    ]);

    Subscription::factory()->for($organization)->create([
        'plan' => \App\Enums\SubscriptionPlan::BASIC,
        'status' => \App\Enums\SubscriptionStatus::ACTIVE,
    ]);

    Property::factory()->count(10)->for($organization)->create();

    $this->actingAs($admin);

    livewire(\App\Filament\Resources\Properties\Pages\ListProperties::class)
        ->callAction('create')
        ->assertSee('You have reached the property limit for your current plan.');
});
```

Add separate failing tests for:

- tenant-limit dialog on the tenants list page
- grace-period expired subscription still showing create/edit actions but intercepting them with the renewal message
- post-grace subscription hiding write/payment actions while leaving view/download intact
- upgrade action targeting the settings subscription section

- [ ] **Step 2: Run the admin subscription enforcement tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/SubscriptionEnforcementTest.php
```

Expected: FAIL because list/create/edit actions are not yet subscription-aware.

- [ ] **Step 3: Wire the enforcement service into Filament actions and write paths**

Implementation rules:

- keep create/edit actions visible during grace-period read-only mode
- intercept grace-period actions with a confirmation/info modal instead of opening forms
- fully hide or disable destructive/write actions after grace period
- enforce the same rules in the actions/services so direct method calls cannot bypass the UI

- [ ] **Step 4: Re-run the admin subscription enforcement tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/SubscriptionEnforcementTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the subscription-aware UI wiring**

Run:

```bash
git add app/Filament/Concerns/InteractsWithSubscriptionEnforcement.php app/Actions/Admin/Properties/CreatePropertyAction.php app/Actions/Admin/Tenants/CreateTenantAction.php app/Filament/Resources/Properties/Pages/ListProperties.php app/Filament/Resources/Tenants/Pages/ListTenants.php app/Filament/Pages/Settings.php app/Filament/Resources/Properties/PropertyResource.php app/Filament/Resources/Tenants/TenantResource.php tests/Feature/Admin/SubscriptionEnforcementTest.php
git commit -m "feat: enforce subscription limits and read-only states"
```

## Chunk 2: Data Integrity Guardrails

### Task 3: Apply one meter-reading validation engine to admin, tenant, and import flows

**Files:**
- Create: `tests/Feature/Admin/MeterReadingValidationRulesTest.php`
- Modify: `app/Support/Admin/ReadingValidation/ReadingValidationResult.php`
- Modify: `app/Support/Admin/ReadingValidation/ValidateReadingValue.php`
- Modify: `app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php`
- Modify: `app/Actions/Admin/MeterReadings/UpdateMeterReadingAction.php`
- Modify: `app/Actions/Admin/MeterReadings/ImportMeterReadingsAction.php`
- Modify: `app/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- Modify: `app/Models/MeterReading.php` once created

- [ ] **Step 1: Write the failing reading validation tests**

Create `tests/Feature/Admin/MeterReadingValidationRulesTest.php` with scenarios for:

```php
it('blocks a reading lower than the previous reading', function () {
    $meter = Meter::factory()->hasReadings(1, ['value' => 1234, 'reading_date' => now()->subMonth()])->create();

    $result = app(ValidateReadingValue::class)->validate($meter, 1200, now());

    expect($result->isBlocking())->toBeTrue()
        ->and($result->message)->toContain('higher than the previous reading');
});
```

Add separate failing tests for:

- future-date rejection
- anomaly detection when consumption is greater than 3x the average monthly usage
- gap note when more than 60 days passed since the previous reading
- tenant submission and bulk import both receiving the same validation outcome

- [ ] **Step 2: Run the reading validation tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/MeterReadingValidationRulesTest.php
```

Expected: FAIL because the existing validator/result contract does not yet cover all required states and notes.

- [ ] **Step 3: Expand the validator and route every write path through it**

Implementation rules:

- keep the validator pure and reusable
- let the result object carry blocking state, anomaly state, gap state, computed consumption, and note text
- ensure admin create/update, tenant submit, and bulk import all call the same validator before persisting
- anomaly/gap results should still save the reading when allowed, but with review status and notes

- [ ] **Step 4: Re-run the reading validation tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/MeterReadingValidationRulesTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the shared reading validation rules**

Run:

```bash
git add app/Support/Admin/ReadingValidation/ReadingValidationResult.php app/Support/Admin/ReadingValidation/ValidateReadingValue.php app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php app/Actions/Admin/MeterReadings/UpdateMeterReadingAction.php app/Actions/Admin/MeterReadings/ImportMeterReadingsAction.php app/Actions/Tenant/Readings/SubmitTenantReadingAction.php app/Models/MeterReading.php tests/Feature/Admin/MeterReadingValidationRulesTest.php
git commit -m "feat: unify meter reading validation rules"
```

### Task 4: Lock finalized invoices everywhere except payment/status updates

**Files:**
- Create: `app/Support/Admin/Invoices/FinalizedInvoiceGuard.php`
- Create: `tests/Unit/Support/Admin/FinalizedInvoiceGuardTest.php`
- Create: `tests/Feature/Admin/InvoiceImmutabilityTest.php`
- Modify: `app/Actions/Admin/Invoices/SaveInvoiceDraftAction.php`
- Modify: `app/Actions/Admin/Invoices/FinalizeInvoiceAction.php`
- Modify: `app/Actions/Admin/Invoices/RecordInvoicePaymentAction.php`
- Modify: `app/Filament/Resources/Invoices/Pages/EditInvoice.php`
- Modify: `app/Models/Invoice.php` once created

- [ ] **Step 1: Write the failing finalized-invoice guard tests**

Create `tests/Unit/Support/Admin/FinalizedInvoiceGuardTest.php` with expectations like:

```php
it('allows payment fields to change on finalized invoices but locks line items and totals', function () {
    $guard = app(FinalizedInvoiceGuard::class);

    expect($guard->canMutateField('paid_at'))->toBeTrue()
        ->and($guard->canMutateField('total_amount'))->toBeFalse();
});
```

Create `tests/Feature/Admin/InvoiceImmutabilityTest.php` with a user-facing case where editing a finalized invoice returns the “locked” explanation and leaves line items unchanged.

- [ ] **Step 2: Run the invoice immutability tests**

Run:

```bash
php artisan test --compact tests/Unit/Support/Admin/FinalizedInvoiceGuardTest.php tests/Feature/Admin/InvoiceImmutabilityTest.php
```

Expected: FAIL because finalized invoices are not yet centrally guarded.

- [ ] **Step 3: Implement the finalized-invoice guard and enforce it in all edit paths**

Implementation rules:

- put allowed mutable fields in one place
- make finalized status transition one-way for mutability purposes
- use the guard in actions first, then mirror the constraint in the Filament edit experience so the UI and backend agree
- keep payment processing working for finalized invoices

- [ ] **Step 4: Re-run the invoice immutability tests**

Run:

```bash
php artisan test --compact tests/Unit/Support/Admin/FinalizedInvoiceGuardTest.php tests/Feature/Admin/InvoiceImmutabilityTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the invoice immutability rules**

Run:

```bash
git add app/Support/Admin/Invoices/FinalizedInvoiceGuard.php app/Actions/Admin/Invoices/SaveInvoiceDraftAction.php app/Actions/Admin/Invoices/FinalizeInvoiceAction.php app/Actions/Admin/Invoices/RecordInvoicePaymentAction.php app/Filament/Resources/Invoices/Pages/EditInvoice.php app/Models/Invoice.php tests/Unit/Support/Admin/FinalizedInvoiceGuardTest.php tests/Feature/Admin/InvoiceImmutabilityTest.php
git commit -m "feat: enforce finalized invoice immutability"
```

## Chunk 3: Refresh Contracts And Runtime Updates

### Task 5: Add timed polling and event-driven partial refreshes for dashboards, invoices, and readings

**Files:**
- Create: `app/Events/Ui/MeterReadingChanged.php`
- Create: `app/Events/Ui/InvoiceChanged.php`
- Create: `app/Providers/BroadcastServiceProvider.php`
- Create: `routes/channels.php`
- Create: `app/Livewire/Concerns/ListensForRealtimeRefreshes.php`
- Create: `tests/Feature/Shell/RealtimeRefreshRulesTest.php`
- Modify: `bootstrap/app.php`
- Modify: `bootstrap/providers.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `config/tenanto.php`
- Modify: `app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php`
- Modify: `app/Actions/Admin/MeterReadings/ImportMeterReadingsAction.php`
- Modify: `app/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- Modify: `app/Actions/Admin/Invoices/FinalizeInvoiceAction.php`
- Modify: `app/Actions/Admin/Invoices/RecordInvoicePaymentAction.php`
- Modify: `app/Filament/Pages/OrganizationDashboard.php`
- Modify: `app/Filament/Pages/PlatformDashboard.php`
- Modify: `app/Filament/Pages/IntegrationHealth.php`
- Modify: `app/Livewire/Tenant/HomeSummary.php`
- Modify: `app/Filament/Widgets/Admin/OrganizationStatsOverview.php`
- Modify: `app/Filament/Widgets/Admin/RecentInvoicesWidget.php`
- Modify: `app/Filament/Widgets/Admin/UpcomingReadingDeadlinesWidget.php`
- Modify: `app/Filament/Widgets/Superadmin/PlatformStatsOverview.php`

- [ ] **Step 1: Write the failing refresh-contract tests**

Create `tests/Feature/Shell/RealtimeRefreshRulesTest.php` with assertions for:

```php
it('dispatches a meter-reading refresh event after a reading is created', function () {
    Event::fake([MeterReadingChanged::class]);

    $meter = Meter::factory()->hasReadings(1, [
        'value' => 1000,
        'reading_date' => now()->subMonth(),
    ])->create();

    app(CreateMeterReadingAction::class)->handle($meter, [
        'reading_date' => now()->toDateString(),
        'value' => 1100,
    ]);

    Event::assertDispatched(MeterReadingChanged::class);
});
```

Add separate failing tests that verify:

- admin dashboard components use `30s` polling
- superadmin dashboard components use `60s` polling
- tenant home uses `120s` polling
- invoice finalize/payment actions dispatch `InvoiceChanged`
- integration health page uses `30s` polling

- [ ] **Step 2: Run the refresh tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/RealtimeRefreshRulesTest.php
```

Expected: FAIL because no broadcast provider, refresh events, or polling contract exists yet.

- [ ] **Step 3: Implement broadcast topics, listener trait, and polling config**

Implementation rules:

- keep event payloads small: org ID, tenant user ID if relevant, record IDs, and refresh topic
- authorize channels by organization/user ownership
- use the listener trait to keep Livewire refresh plumbing out of page-specific components
- timed polling remains in place even after broadcasts are added

- [ ] **Step 4: Re-run the refresh tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/RealtimeRefreshRulesTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the real-time refresh layer**

Run:

```bash
git add app/Events/Ui app/Providers/BroadcastServiceProvider.php routes/channels.php app/Livewire/Concerns/ListensForRealtimeRefreshes.php bootstrap/app.php bootstrap/providers.php app/Providers/AppServiceProvider.php config/tenanto.php app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php app/Actions/Admin/MeterReadings/ImportMeterReadingsAction.php app/Actions/Tenant/Readings/SubmitTenantReadingAction.php app/Actions/Admin/Invoices/FinalizeInvoiceAction.php app/Actions/Admin/Invoices/RecordInvoicePaymentAction.php app/Filament/Pages/OrganizationDashboard.php app/Filament/Pages/PlatformDashboard.php app/Filament/Pages/IntegrationHealth.php app/Livewire/Tenant/HomeSummary.php app/Filament/Widgets/Admin app/Filament/Widgets/Superadmin tests/Feature/Shell/RealtimeRefreshRulesTest.php
git commit -m "feat: add realtime refresh contracts"
```

## Chunk 4: Shared UI Behavior

### Task 6: Standardize table sorting and session-persisted filters across Filament lists

**Files:**
- Create: `app/Filament/Concerns/HasStandardTableBehavior.php`
- Create: `tests/Feature/Admin/TableBehaviorTest.php`
- Modify: `app/Filament/Resources/Properties/Schemas/PropertyTable.php`
- Modify: `app/Filament/Resources/Tenants/Schemas/TenantTable.php`
- Modify: `app/Filament/Resources/MeterReadings/Schemas/MeterReadingTable.php`
- Modify: `app/Filament/Resources/Invoices/Schemas/InvoiceTable.php`
- Modify: `app/Filament/Resources/Properties/Pages/ListProperties.php`
- Modify: `app/Filament/Resources/Tenants/Pages/ListTenants.php`

- [ ] **Step 1: Write the failing table-behavior tests**

Create `tests/Feature/Admin/TableBehaviorTest.php` with a case like:

```php
it('cycles a table column through ascending, descending, and default sort order', function () {
    livewire(\App\Filament\Resources\Invoices\Pages\ListInvoices::class)
        ->sortTable('created_at')
        ->assertSet('tableSortColumn', 'created_at')
        ->assertSet('tableSortDirection', 'asc')
        ->sortTable('created_at')
        ->assertSet('tableSortDirection', 'desc')
        ->sortTable('created_at')
        ->assertSet('tableSortColumn', null);
});
```

Add a second failing test proving a filter survives navigation during the same browser session until “Clear All Filters” is used.

- [ ] **Step 2: Run the table behavior tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/TableBehaviorTest.php
```

Expected: FAIL because table defaults and session persistence are not yet standardized.

- [ ] **Step 3: Create the shared table-behavior concern and apply it**

Implementation rules:

- default sort is always `created_at desc` unless a specific table truly has no created timestamp
- use Filament session persistence for filters and sorting
- prefer one concern/shared pattern over reconfiguring each resource ad hoc
- every user-facing sortable column in these tables must explicitly opt into sorting

- [ ] **Step 4: Re-run the table behavior tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/TableBehaviorTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the standard table behavior**

Run:

```bash
git add app/Filament/Concerns/HasStandardTableBehavior.php app/Filament/Resources/Properties/Schemas/PropertyTable.php app/Filament/Resources/Tenants/Schemas/TenantTable.php app/Filament/Resources/MeterReadings/Schemas/MeterReadingTable.php app/Filament/Resources/Invoices/Schemas/InvoiceTable.php app/Filament/Resources/Properties/Pages/ListProperties.php app/Filament/Resources/Tenants/Pages/ListTenants.php tests/Feature/Admin/TableBehaviorTest.php
git commit -m "feat: standardize table sorting and filter persistence"
```

### Task 7: Add shared skeleton loading states and toast feedback behavior

**Files:**
- Create: `app/Support/Ui/Toast/ToastPayload.php`
- Create: `app/Support/Ui/Toast/ToastFactory.php`
- Create: `app/Livewire/Shell/ToastStack.php`
- Create: `resources/views/livewire/shell/toast-stack.blade.php`
- Create: `resources/views/components/ui/skeleton/card.blade.php`
- Create: `resources/views/components/ui/skeleton/list.blade.php`
- Create: `resources/views/components/ui/skeleton/table.blade.php`
- Create: `tests/Feature/Shell/LoadingAndFeedbackTest.php`
- Modify: `app/Livewire/Shell/NotificationCenter.php`
- Modify: `resources/views/tenant/home.blade.php`
- Modify: `resources/views/tenant/readings/create.blade.php`
- Modify: `resources/views/tenant/invoices/index.blade.php`
- Modify: `resources/views/tenant/profile/edit.blade.php`
- Modify: `app/Filament/Pages/OrganizationDashboard.php`
- Modify: `app/Filament/Pages/PlatformDashboard.php`

- [ ] **Step 1: Write the failing loading/feedback tests**

Create `tests/Feature/Shell/LoadingAndFeedbackTest.php` with checks such as:

```php
it('renders skeleton placeholders instead of blank content for tenant dashboard sections', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertOk()
        ->assertSee('data-skeleton-card');
});
```

Add separate failing tests for:

- success toast payload auto-dismiss duration of 5 seconds
- warning toast auto-dismiss duration of 8 seconds
- error toast persistence until manual dismissal
- buttons rendering loading/disabled state hooks during server actions

- [ ] **Step 2: Run the loading/feedback tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/LoadingAndFeedbackTest.php
```

Expected: FAIL because there is no shared toast abstraction or standardized skeleton markup yet.

- [ ] **Step 3: Build the shared toast and skeleton layer**

Implementation rules:

- keep database notifications separate from transient action-feedback toasts
- use one factory to normalize durations, colors, and dismiss behavior
- let Filament actions map into the same severity rules as Blade/Livewire pages
- use lightweight skeleton components so pages never render as empty white space during loading

- [ ] **Step 4: Re-run the loading/feedback tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/LoadingAndFeedbackTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the shared loading/feedback behavior**

Run:

```bash
git add app/Support/Ui/Toast app/Livewire/Shell/ToastStack.php resources/views/livewire/shell/toast-stack.blade.php resources/views/components/ui/skeleton resources/views/tenant/home.blade.php resources/views/tenant/readings/create.blade.php resources/views/tenant/invoices/index.blade.php resources/views/tenant/profile/edit.blade.php app/Livewire/Shell/NotificationCenter.php app/Filament/Pages/OrganizationDashboard.php app/Filament/Pages/PlatformDashboard.php tests/Feature/Shell/LoadingAndFeedbackTest.php
git commit -m "feat: add shared loading states and toast feedback"
```

## Chunk 5: Locale Behavior And Final Verification

### Task 8: Enforce immediate locale switching and English fallback for missing translations

**Files:**
- Create: `tests/Feature/Shell/LanguageSwitchingBehaviorTest.php`
- Modify: `config/app.php`
- Modify: `config/tenanto.php`
- Modify: `app/Http/Middleware/SetAuthenticatedUserLocale.php`
- Modify: `app/Actions/Preferences/UpdateUserLocaleAction.php`
- Modify: `app/Livewire/Shell/LanguageSwitcher.php`
- Modify: `resources/views/livewire/shell/language-switcher.blade.php`
- Modify: `tests/Feature/Auth/LocalePersistenceTest.php`

- [ ] **Step 1: Write the failing language behavior tests**

Create `tests/Feature/Shell/LanguageSwitchingBehaviorTest.php` with coverage like:

```php
it('falls back to the english translation when the selected locale is missing a key', function () {
    app()->setLocale('lt');

    expect(__('behavior.example_missing_key_falls_back'))->toBe('English fallback value');
});
```

Add separate failing tests for:

- the language switcher persisting the new locale immediately to the user record
- the next response in the same session using the new locale without requiring a reload
- login/logout restoring the saved preference

- [ ] **Step 2: Run the language tests**

Run:

```bash
php artisan test --compact tests/Feature/Auth/LocalePersistenceTest.php tests/Feature/Shell/LanguageSwitchingBehaviorTest.php
```

Expected: FAIL because fallback and immediate switch behavior are not fully codified/tested yet.

- [ ] **Step 3: Finalize locale persistence and fallback settings**

Implementation rules:

- keep `en` as the configured fallback locale
- update the shared locale action instead of adding a second code path
- make the switcher return/render translated labels immediately after change
- never expose raw translation keys when a localized value is absent

- [ ] **Step 4: Re-run the language tests and the cross-cutting verification suite**

Run:

```bash
php artisan test --compact tests/Unit/Support/Admin/OrganizationSubscriptionAccessTest.php tests/Unit/Support/Admin/FinalizedInvoiceGuardTest.php
php artisan test --compact tests/Feature/Admin/SubscriptionEnforcementTest.php tests/Feature/Admin/MeterReadingValidationRulesTest.php tests/Feature/Admin/InvoiceImmutabilityTest.php tests/Feature/Admin/TableBehaviorTest.php
php artisan test --compact tests/Feature/Shell/RealtimeRefreshRulesTest.php tests/Feature/Shell/LoadingAndFeedbackTest.php tests/Feature/Shell/LanguageSwitchingBehaviorTest.php tests/Feature/Auth/LocalePersistenceTest.php
vendor/bin/pint --dirty
```

Expected:

- unit and feature suites PASS
- locale persistence regression PASS
- Pint reports clean formatting or auto-fixes touched files only

- [ ] **Step 5: Commit the locale/fallback rules and final verification pass**

Run:

```bash
git add config/app.php config/tenanto.php app/Http/Middleware/SetAuthenticatedUserLocale.php app/Actions/Preferences/UpdateUserLocaleAction.php app/Livewire/Shell/LanguageSwitcher.php resources/views/livewire/shell/language-switcher.blade.php tests/Feature/Auth/LocalePersistenceTest.php tests/Feature/Shell/LanguageSwitchingBehaviorTest.php
git commit -m "feat: complete cross-cutting behavioral rules"
```

## Execution Notes

- Execute this plan only after the prerequisite page/model plans land, or fold the relevant task into those branches as you build them.
- Prefer enforcing rules in services/actions first, then mirroring them in Filament/Livewire so the UI cannot drift from the backend.
- When a rule applies to both Filament and tenant Blade pages, create one shared support class and two thin adapters instead of duplicating conditionals in views.
- Keep the scope disciplined: this plan does not add new CRUD domains or billing features; it hardens behavior around the features already planned.
