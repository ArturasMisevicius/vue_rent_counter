# Superadmin Control Plane Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the superadmin control plane so the platform owner can navigate a complete superadmin area, manage organizations/users/subscriptions, operate governance tools, and monitor security and integration health from a real Filament-backed interface.

**Architecture:** Extend the authenticated shell from the shared-interface plan instead of rebuilding navigation inside this slice. Implement the superadmin area as a platform-owned Filament suite: widgets for the dashboard, Resources for list/create/edit/view workflows, and custom Pages for system configuration, translation management, and integration health. Add platform-owned models for audit logs, notifications, languages, security violations, settings, integration checks, and subscription payments, while using thin read-model adapters for property/billing/domain data that does not yet exist in the repo so the control plane can ship without inventing unrelated CRUD modules.

**Tech Stack:** Laravel 12, Filament 5, Livewire 4, Blade, Tailwind CSS v4, SQLite, Laravel database notifications, Pest 4, Laravel Pint.

---

## Scope Check

This spec is too large for one safe “single feature” rollout. It spans multiple independent subsystems:

- platform control plane
- property/billing/reporting read models
- localization management
- security operations
- integration monitoring

This plan intentionally covers the **superadmin control-plane slice** that can be built against the current repo. It assumes or extends two earlier plan layers:

- `docs/superpowers/plans/2026-03-17-foundation-auth-onboarding.md`
- `docs/superpowers/plans/2026-03-17-shared-interface-elements.md`

The following pieces should be treated as follow-on plans after this slice lands:

- property/billing domain rollout for non-empty buildings/properties/meters/invoices data
- report module rollout
- deeper organization export packaging once billing/meter data is real

## Scope Notes

- Keep all platform-owned pages in Filament.
- Reuse the shared sidebar/topbar shell; do not duplicate shell logic in these resources.
- Use Eloquent only. No raw SQL. No query logic in Blade or Resource closures when a scope/action can own it.
- Use dedicated schema classes for Filament tables/forms/infolists.
- Put business logic in Actions/Support classes, not Resource page methods.
- Use adapter services for cross-domain counts and tables that depend on still-missing models.

## File Map

### Create

- `app/Enums/SubscriptionDuration.php` — duration options for organization creation and subscription extension.
- `app/Enums/SystemSettingCategory.php` — grouping for system configuration entries.
- `app/Enums/AuditLogAction.php` — translated audit action types.
- `app/Enums/PlatformNotificationSeverity.php`
- `app/Enums/PlatformNotificationStatus.php`
- `app/Enums/LanguageStatus.php`
- `app/Enums/SecurityViolationSeverity.php`
- `app/Enums/SecurityViolationType.php`
- `app/Enums/IntegrationHealthStatus.php`
- `app/Models/SystemSetting.php`
- `app/Models/AuditLog.php`
- `app/Models/PlatformNotification.php`
- `app/Models/PlatformNotificationDelivery.php`
- `app/Models/SubscriptionPayment.php`
- `app/Models/Language.php`
- `app/Models/SecurityViolation.php`
- `app/Models/BlockedIpAddress.php`
- `app/Models/IntegrationHealthCheck.php`
- `database/factories/SystemSettingFactory.php`
- `database/factories/AuditLogFactory.php`
- `database/factories/PlatformNotificationFactory.php`
- `database/factories/PlatformNotificationDeliveryFactory.php`
- `database/factories/SubscriptionPaymentFactory.php`
- `database/factories/LanguageFactory.php`
- `database/factories/SecurityViolationFactory.php`
- `database/factories/BlockedIpAddressFactory.php`
- `database/factories/IntegrationHealthCheckFactory.php`
- `database/migrations/2026_03_17_100100_add_limit_snapshot_fields_to_subscriptions_table.php`
- `database/migrations/2026_03_17_100200_create_subscription_payments_table.php`
- `database/migrations/2026_03_17_100300_create_system_settings_table.php`
- `database/migrations/2026_03_17_100400_create_audit_logs_table.php`
- `database/migrations/2026_03_17_100500_create_platform_notifications_table.php`
- `database/migrations/2026_03_17_100600_create_platform_notification_deliveries_table.php`
- `database/migrations/2026_03_17_100700_create_languages_table.php`
- `database/migrations/2026_03_17_100800_create_security_violations_table.php`
- `database/migrations/2026_03_17_100900_create_blocked_ip_addresses_table.php`
- `database/migrations/2026_03_17_101000_create_integration_health_checks_table.php`
- `database/seeders/LanguageSeeder.php`
- `database/seeders/SystemSettingSeeder.php`
- `database/seeders/IntegrationHealthCheckSeeder.php`
- `app/Policies/OrganizationPolicy.php`
- `app/Policies/UserPolicy.php`
- `app/Policies/SubscriptionPolicy.php`
- `app/Policies/SystemSettingPolicy.php`
- `app/Policies/PlatformNotificationPolicy.php`
- `app/Policies/LanguagePolicy.php`
- `app/Policies/SecurityViolationPolicy.php`
- `app/Policies/AuditLogPolicy.php`
- `app/Policies/IntegrationHealthCheckPolicy.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Http/Middleware/BlockBlockedIpAddresses.php`
- `app/Observers/OrganizationObserver.php`
- `app/Observers/SubscriptionObserver.php`
- `app/Observers/UserObserver.php`
- `app/Observers/SystemSettingObserver.php`
- `app/Observers/PlatformNotificationObserver.php`
- `app/Support/Audit/AuditLogger.php`
- `app/Support/Superadmin/Usage/OrganizationUsageSnapshot.php`
- `app/Support/Superadmin/Usage/OrganizationUsageReader.php`
- `app/Support/Superadmin/Usage/NullOrganizationUsageReader.php`
- `app/Support/Superadmin/Exports/OrganizationDataExportBuilder.php`
- `app/Support/Superadmin/Exports/NullOrganizationDataExportBuilder.php`
- `app/Support/Superadmin/Translations/TranslationCatalogService.php`
- `app/Support/Superadmin/Translations/TranslationRowData.php`
- `app/Support/Superadmin/Integration/Contracts/IntegrationProbe.php`
- `app/Support/Superadmin/Integration/IntegrationProbeRegistry.php`
- `app/Support/Superadmin/Integration/Probes/DatabaseProbe.php`
- `app/Support/Superadmin/Integration/Probes/QueueProbe.php`
- `app/Support/Superadmin/Integration/Probes/MailProbe.php`
- `app/Actions/Superadmin/Organizations/CreateOrganizationAction.php`
- `app/Actions/Superadmin/Organizations/UpdateOrganizationAction.php`
- `app/Actions/Superadmin/Organizations/SuspendOrganizationAction.php`
- `app/Actions/Superadmin/Organizations/ReinstateOrganizationAction.php`
- `app/Actions/Superadmin/Organizations/SendOrganizationNotificationAction.php`
- `app/Actions/Superadmin/Organizations/StartOrganizationImpersonationAction.php`
- `app/Actions/Superadmin/Organizations/ExportOrganizationDataAction.php`
- `app/Actions/Superadmin/Subscriptions/ExtendSubscriptionAction.php`
- `app/Actions/Superadmin/Subscriptions/UpgradeSubscriptionPlanAction.php`
- `app/Actions/Superadmin/Subscriptions/SuspendSubscriptionAction.php`
- `app/Actions/Superadmin/Subscriptions/CancelSubscriptionAction.php`
- `app/Actions/Superadmin/SystemConfiguration/UpdateSystemSettingAction.php`
- `app/Actions/Superadmin/Notifications/SavePlatformNotificationDraftAction.php`
- `app/Actions/Superadmin/Notifications/SendPlatformNotificationAction.php`
- `app/Actions/Superadmin/Languages/SetDefaultLanguageAction.php`
- `app/Actions/Superadmin/Languages/ToggleLanguageStatusAction.php`
- `app/Actions/Superadmin/Languages/DeleteLanguageAction.php`
- `app/Actions/Superadmin/Translations/ImportTranslationsAction.php`
- `app/Actions/Superadmin/Translations/ExportMissingTranslationsAction.php`
- `app/Actions/Superadmin/Translations/UpdateTranslationValueAction.php`
- `app/Actions/Superadmin/Security/BlockIpAddressAction.php`
- `app/Actions/Superadmin/Integration/RunIntegrationHealthChecksAction.php`
- `app/Actions/Superadmin/Integration/ResetIntegrationCircuitBreakerAction.php`
- `app/Filament/Widgets/Superadmin/PlatformStatsOverview.php`
- `app/Filament/Widgets/Superadmin/RevenueByPlanChart.php`
- `app/Filament/Widgets/Superadmin/ExpiringSubscriptionsWidget.php`
- `app/Filament/Widgets/Superadmin/RecentSecurityViolationsWidget.php`
- `app/Filament/Widgets/Superadmin/RecentlyCreatedOrganizationsWidget.php`
- `app/Filament/Resources/Organizations/OrganizationResource.php`
- `app/Filament/Resources/Organizations/Schemas/OrganizationForm.php`
- `app/Filament/Resources/Organizations/Schemas/OrganizationTable.php`
- `app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php`
- `app/Filament/Resources/Organizations/Pages/ListOrganizations.php`
- `app/Filament/Resources/Organizations/Pages/CreateOrganization.php`
- `app/Filament/Resources/Organizations/Pages/EditOrganization.php`
- `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- `app/Filament/Resources/Users/UserResource.php`
- `app/Filament/Resources/Users/Schemas/UserForm.php`
- `app/Filament/Resources/Users/Schemas/UserTable.php`
- `app/Filament/Resources/Users/Pages/ListUsers.php`
- `app/Filament/Resources/Users/Pages/CreateUser.php`
- `app/Filament/Resources/Users/Pages/EditUser.php`
- `app/Filament/Resources/Users/Pages/ViewUser.php`
- `app/Filament/Resources/Subscriptions/SubscriptionResource.php`
- `app/Filament/Resources/Subscriptions/Schemas/SubscriptionForm.php`
- `app/Filament/Resources/Subscriptions/Schemas/SubscriptionTable.php`
- `app/Filament/Resources/Subscriptions/Pages/ListSubscriptions.php`
- `app/Filament/Resources/Subscriptions/Pages/CreateSubscription.php`
- `app/Filament/Resources/Subscriptions/Pages/EditSubscription.php`
- `app/Filament/Resources/Subscriptions/Pages/ViewSubscription.php`
- `app/Filament/Resources/AuditLogs/AuditLogResource.php`
- `app/Filament/Resources/AuditLogs/Schemas/AuditLogTable.php`
- `app/Filament/Resources/AuditLogs/Pages/ListAuditLogs.php`
- `app/Filament/Resources/PlatformNotifications/PlatformNotificationResource.php`
- `app/Filament/Resources/PlatformNotifications/Schemas/PlatformNotificationForm.php`
- `app/Filament/Resources/PlatformNotifications/Schemas/PlatformNotificationTable.php`
- `app/Filament/Resources/PlatformNotifications/Pages/ListPlatformNotifications.php`
- `app/Filament/Resources/PlatformNotifications/Pages/CreatePlatformNotification.php`
- `app/Filament/Resources/PlatformNotifications/Pages/EditPlatformNotification.php`
- `app/Filament/Resources/PlatformNotifications/Pages/ViewPlatformNotification.php`
- `app/Filament/Resources/Languages/LanguageResource.php`
- `app/Filament/Resources/Languages/Schemas/LanguageForm.php`
- `app/Filament/Resources/Languages/Schemas/LanguageTable.php`
- `app/Filament/Resources/Languages/Pages/ListLanguages.php`
- `app/Filament/Resources/Languages/Pages/CreateLanguage.php`
- `app/Filament/Resources/Languages/Pages/EditLanguage.php`
- `app/Filament/Resources/SecurityViolations/SecurityViolationResource.php`
- `app/Filament/Resources/SecurityViolations/Schemas/SecurityViolationTable.php`
- `app/Filament/Resources/SecurityViolations/Pages/ListSecurityViolations.php`
- `app/Filament/Pages/SystemConfiguration.php`
- `app/Filament/Pages/TranslationManagement.php`
- `app/Filament/Pages/IntegrationHealth.php`
- `resources/views/filament/pages/platform-dashboard.blade.php`
- `resources/views/filament/pages/system-configuration.blade.php`
- `resources/views/filament/pages/translation-management.blade.php`
- `resources/views/filament/pages/integration-health.blade.php`
- `resources/views/filament/resources/organizations/pages/view-organization.blade.php`
- `app/Http/Requests/Superadmin/Organizations/StoreOrganizationRequest.php`
- `app/Http/Requests/Superadmin/Organizations/UpdateOrganizationRequest.php`
- `app/Http/Requests/Superadmin/Subscriptions/StoreSubscriptionRequest.php`
- `app/Http/Requests/Superadmin/Subscriptions/UpdateSubscriptionRequest.php`
- `app/Http/Requests/Superadmin/PlatformNotifications/StorePlatformNotificationRequest.php`
- `app/Http/Requests/Superadmin/SystemConfiguration/UpdateSystemSettingRequest.php`
- `lang/en/superadmin.php`
- `lang/lt/superadmin.php`
- `lang/ru/superadmin.php`
- `lang/es/superadmin.php`
- `tests/Feature/Superadmin/SuperadminDashboardTest.php`
- `tests/Feature/Superadmin/OrganizationsResourceTest.php`
- `tests/Feature/Superadmin/OrganizationActionsTest.php`
- `tests/Feature/Superadmin/UsersResourceTest.php`
- `tests/Feature/Superadmin/SubscriptionsResourceTest.php`
- `tests/Feature/Superadmin/SystemConfigurationPageTest.php`
- `tests/Feature/Superadmin/AuditLogsResourceTest.php`
- `tests/Feature/Superadmin/PlatformNotificationsResourceTest.php`
- `tests/Feature/Superadmin/LanguagesResourceTest.php`
- `tests/Feature/Superadmin/TranslationManagementPageTest.php`
- `tests/Feature/Superadmin/SecurityViolationsResourceTest.php`
- `tests/Feature/Superadmin/IntegrationHealthPageTest.php`

### Modify

- `bootstrap/app.php` — register blocked-IP middleware and any exception/render hooks needed by the control plane.
- `bootstrap/providers.php` — register `AuthServiceProvider`.
- `app/Providers/AppServiceProvider.php` — bind read-model adapters, translation catalog service, integration probes, and model observers.
- `app/Providers/Filament/AdminPanelProvider.php` — register superadmin resources/pages/widgets and navigation groups once the shared shell plan is in place.
- `app/Models/Organization.php` — add explicit selects, counts, and helper relationships used by superadmin resources.
- `app/Models/Subscription.php` — add limit snapshot casts and current-scope helpers.
- `app/Models/User.php` — add superadmin list/view helpers and organization-scoped ownership helpers.
- `app/Enums/SubscriptionPlan.php` — add labels and limits snapshot helpers.
- `app/Enums/SubscriptionStatus.php` — add labels where needed.
- `app/Enums/UserRole.php` — add translated labels used in tables and infolists.
- `database/seeders/DatabaseSeeder.php` — call new platform seeders.
- `config/tenanto.php` — extend shared-shell navigation with superadmin section definitions and locale source notes.
- `app/Support/Shell/Navigation/NavigationBuilder.php` — register all superadmin sidebar items and route names after pages/resources exist.
- `tests/Feature/Auth/AccessIsolationTest.php` — extend with blocked-IP / superadmin-only control-plane assertions if needed.

### Intentionally Deferred

- Full property/building/meter/invoice CRUD modules.
- Non-empty organization export datasets for invoices/meter readings if those modules still do not exist.
- Report pages beyond route registration and navigation.
- Detailed user create/edit forms if product later supplies a more specific user-management spec.

## Chunk 1: Platform Foundation and Navigation Contracts

### Task 1: Add the platform-owned schema, enums, and seeders

**Files:**
- Create: `app/Enums/SubscriptionDuration.php`
- Create: `app/Enums/SystemSettingCategory.php`
- Create: `app/Enums/AuditLogAction.php`
- Create: `app/Enums/PlatformNotificationSeverity.php`
- Create: `app/Enums/PlatformNotificationStatus.php`
- Create: `app/Enums/LanguageStatus.php`
- Create: `app/Enums/SecurityViolationSeverity.php`
- Create: `app/Enums/SecurityViolationType.php`
- Create: `app/Enums/IntegrationHealthStatus.php`
- Create: `app/Models/SystemSetting.php`
- Create: `app/Models/AuditLog.php`
- Create: `app/Models/PlatformNotification.php`
- Create: `app/Models/PlatformNotificationDelivery.php`
- Create: `app/Models/SubscriptionPayment.php`
- Create: `app/Models/Language.php`
- Create: `app/Models/SecurityViolation.php`
- Create: `app/Models/BlockedIpAddress.php`
- Create: `app/Models/IntegrationHealthCheck.php`
- Create: `database/migrations/2026_03_17_100100_add_limit_snapshot_fields_to_subscriptions_table.php`
- Create: `database/migrations/2026_03_17_100200_create_subscription_payments_table.php`
- Create: `database/migrations/2026_03_17_100300_create_system_settings_table.php`
- Create: `database/migrations/2026_03_17_100400_create_audit_logs_table.php`
- Create: `database/migrations/2026_03_17_100500_create_platform_notifications_table.php`
- Create: `database/migrations/2026_03_17_100600_create_platform_notification_deliveries_table.php`
- Create: `database/migrations/2026_03_17_100700_create_languages_table.php`
- Create: `database/migrations/2026_03_17_100800_create_security_violations_table.php`
- Create: `database/migrations/2026_03_17_100900_create_blocked_ip_addresses_table.php`
- Create: `database/migrations/2026_03_17_101000_create_integration_health_checks_table.php`
- Create: `database/seeders/LanguageSeeder.php`
- Create: `database/seeders/SystemSettingSeeder.php`
- Create: `database/seeders/IntegrationHealthCheckSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Superadmin/SuperadminDashboardTest.php`

- [ ] **Step 1: Write the first failing dashboard data test**

Create `tests/Feature/Superadmin/SuperadminDashboardTest.php` with a smoke test that seeds a superadmin, organizations, subscriptions, subscription payments, and security violations, then asserts the platform dashboard can eventually show all four metric families:

```php
it('shows the superadmin dashboard metrics', function () {
    $superadmin = User::factory()->superadmin()->create();

    Organization::factory()->count(3)->create();
    Subscription::factory()->count(2)->active()->create();
    SubscriptionPayment::factory()->create(['amount' => 9900, 'paid_at' => now()]);
    SecurityViolation::factory()->count(2)->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertOk()
        ->assertSeeText('Total Organizations')
        ->assertSeeText('Active Subscriptions')
        ->assertSeeText('Platform Revenue This Month')
        ->assertSeeText('Security Violations (7 Days)');
});
```

- [ ] **Step 2: Run the dashboard test to prove the platform schema is missing**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SuperadminDashboardTest.php
```

Expected: FAIL because the new models/tables do not exist.

- [ ] **Step 3: Add enums, models, migrations, and seeders**

Implementation rules:

- snapshot subscription limits onto the subscription row so history tables do not depend on future enum changes
- keep `SystemSetting` rows typed (`string`, `integer`, `boolean`, `json`, `email`)
- give `AuditLog`, `SecurityViolation`, and `IntegrationHealthCheck` explicit indexed timestamps
- seed four languages (`en`, `lt`, `ru`, `es`) and the default language flag
- seed the required system configuration categories from the spec

- [ ] **Step 4: Migrate and seed the platform tables**

Run:

```bash
php artisan migrate --force
php artisan db:seed --class=LanguageSeeder --class=SystemSettingSeeder --class=IntegrationHealthCheckSeeder
```

Expected: all migrations and seeders PASS.

- [ ] **Step 5: Re-run the dashboard smoke test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SuperadminDashboardTest.php
```

Expected: still FAIL on page content, but no longer fail on missing tables/models.

- [ ] **Step 6: Commit the platform schema**

Run:

```bash
git add app/Enums app/Models database/migrations database/seeders tests/Feature/Superadmin/SuperadminDashboardTest.php
git commit -m "feat: add superadmin platform data foundation"
```

### Task 2: Wire policies, middleware, observers, and shared superadmin navigation contracts

**Files:**
- Create: `app/Policies/OrganizationPolicy.php`
- Create: `app/Policies/UserPolicy.php`
- Create: `app/Policies/SubscriptionPolicy.php`
- Create: `app/Policies/SystemSettingPolicy.php`
- Create: `app/Policies/PlatformNotificationPolicy.php`
- Create: `app/Policies/LanguagePolicy.php`
- Create: `app/Policies/SecurityViolationPolicy.php`
- Create: `app/Policies/AuditLogPolicy.php`
- Create: `app/Policies/IntegrationHealthCheckPolicy.php`
- Create: `app/Providers/AuthServiceProvider.php`
- Create: `app/Http/Middleware/BlockBlockedIpAddresses.php`
- Create: `app/Observers/OrganizationObserver.php`
- Create: `app/Observers/SubscriptionObserver.php`
- Create: `app/Observers/UserObserver.php`
- Create: `app/Observers/SystemSettingObserver.php`
- Create: `app/Observers/PlatformNotificationObserver.php`
- Create: `app/Support/Audit/AuditLogger.php`
- Modify: `bootstrap/app.php`
- Modify: `bootstrap/providers.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `config/tenanto.php`
- Modify: `app/Support/Shell/Navigation/NavigationBuilder.php`
- Test: `tests/Feature/Superadmin/OrganizationsResourceTest.php`

- [ ] **Step 1: Write a failing access/navigation test**

Create `tests/Feature/Superadmin/OrganizationsResourceTest.php` with a first assertion that only superadmins can open the organizations resource and that the sidebar contains the Platform section once the resource is registered:

```php
it('only allows superadmins to reach organizations control-plane pages', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organizations.index'))
        ->assertForbidden();
});
```

- [ ] **Step 2: Run the access test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/OrganizationsResourceTest.php
```

Expected: FAIL because the resource route and policy layer do not exist.

- [ ] **Step 3: Register policy, observer, and blocked-IP plumbing**

Implementation notes:

- use `AuditLogger` inside observers and explicit Actions
- block requests early via middleware, before auth-sensitive platform pages render
- bind observers in `AppServiceProvider::boot()`
- extend the shared navigation builder with the full superadmin section list from the spec, but let route existence control which links render until each page lands

- [ ] **Step 4: Re-run the access test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/OrganizationsResourceTest.php
```

Expected: still FAIL on missing resource, but policy/middleware bootstrap errors should be gone.

- [ ] **Step 5: Commit the access foundation**

Run:

```bash
git add app/Policies app/Providers/AuthServiceProvider.php app/Http/Middleware/BlockBlockedIpAddresses.php app/Observers app/Support/Audit bootstrap/app.php bootstrap/providers.php app/Providers/AppServiceProvider.php config/tenanto.php app/Support/Shell/Navigation/NavigationBuilder.php tests/Feature/Superadmin/OrganizationsResourceTest.php
git commit -m "feat: add superadmin access and audit foundation"
```

## Chunk 2: Dashboard and Organization Management

### Task 3: Replace the placeholder platform dashboard with real widgets

**Files:**
- Create: `app/Filament/Widgets/Superadmin/PlatformStatsOverview.php`
- Create: `app/Filament/Widgets/Superadmin/RevenueByPlanChart.php`
- Create: `app/Filament/Widgets/Superadmin/ExpiringSubscriptionsWidget.php`
- Create: `app/Filament/Widgets/Superadmin/RecentSecurityViolationsWidget.php`
- Create: `app/Filament/Widgets/Superadmin/RecentlyCreatedOrganizationsWidget.php`
- Create: `app/Support/Superadmin/Usage/OrganizationUsageSnapshot.php`
- Create: `app/Support/Superadmin/Usage/OrganizationUsageReader.php`
- Create: `app/Support/Superadmin/Usage/NullOrganizationUsageReader.php`
- Modify: `app/Filament/Pages/PlatformDashboard.php`
- Modify: `resources/views/filament/pages/platform-dashboard.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Test: `tests/Feature/Superadmin/SuperadminDashboardTest.php`

- [ ] **Step 1: Expand the failing dashboard test**

Add assertions for:

- four stats cards
- revenue-by-plan chart labels
- expiring subscriptions card
- recent security violations card
- recently created organizations table

- [ ] **Step 2: Run the dashboard test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SuperadminDashboardTest.php
```

Expected: FAIL because the platform dashboard is still the placeholder shell page.

- [ ] **Step 3: Implement the widget and adapter layer**

Important rules:

- set widget polling to `60s`
- use Eloquent scopes/selects, not ad-hoc queries in Blade
- let `NullOrganizationUsageReader` return empty/zero cross-domain stats until property/billing modules exist
- keep chart data generation in the widget class, not the Blade view

- [ ] **Step 4: Re-run the dashboard test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SuperadminDashboardTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the dashboard**

Run:

```bash
git add app/Filament/Widgets/Superadmin app/Support/Superadmin/Usage app/Filament/Pages/PlatformDashboard.php resources/views/filament/pages/platform-dashboard.blade.php app/Providers/AppServiceProvider.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/Superadmin/SuperadminDashboardTest.php
git commit -m "feat: add superadmin dashboard widgets"
```

### Task 4: Implement organizations list/create/edit/view flows

**Files:**
- Create: `app/Actions/Superadmin/Organizations/CreateOrganizationAction.php`
- Create: `app/Actions/Superadmin/Organizations/UpdateOrganizationAction.php`
- Create: `app/Actions/Superadmin/Organizations/SuspendOrganizationAction.php`
- Create: `app/Actions/Superadmin/Organizations/ReinstateOrganizationAction.php`
- Create: `app/Actions/Superadmin/Organizations/SendOrganizationNotificationAction.php`
- Create: `app/Actions/Superadmin/Organizations/StartOrganizationImpersonationAction.php`
- Create: `app/Actions/Superadmin/Organizations/ExportOrganizationDataAction.php`
- Create: `app/Support/Superadmin/Exports/OrganizationDataExportBuilder.php`
- Create: `app/Support/Superadmin/Exports/NullOrganizationDataExportBuilder.php`
- Create: `app/Filament/Resources/Organizations/OrganizationResource.php`
- Create: `app/Filament/Resources/Organizations/Schemas/OrganizationForm.php`
- Create: `app/Filament/Resources/Organizations/Schemas/OrganizationTable.php`
- Create: `app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php`
- Create: `app/Filament/Resources/Organizations/Pages/ListOrganizations.php`
- Create: `app/Filament/Resources/Organizations/Pages/CreateOrganization.php`
- Create: `app/Filament/Resources/Organizations/Pages/EditOrganization.php`
- Create: `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- Create: `resources/views/filament/resources/organizations/pages/view-organization.blade.php`
- Create: `app/Http/Requests/Superadmin/Organizations/StoreOrganizationRequest.php`
- Create: `app/Http/Requests/Superadmin/Organizations/UpdateOrganizationRequest.php`
- Modify: `app/Models/Organization.php`
- Modify: `app/Models/Subscription.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Superadmin/OrganizationsResourceTest.php`
- Test: `tests/Feature/Superadmin/OrganizationActionsTest.php`

- [ ] **Step 1: Write the failing organizations tests**

Cover:

- list filters and table columns
- create organization flow with slug auto-generation, plan, and duration
- existing-owner vs invitation-owner branching
- suspend/reinstate confirmation flow
- impersonate-admin action
- send notification modal
- export-data action returning a ZIP, even if some inner sheets are empty through the null exporter

- [ ] **Step 2: Run the organizations tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/OrganizationsResourceTest.php tests/Feature/Superadmin/OrganizationActionsTest.php
```

Expected: FAIL because the resource, actions, and view page do not exist.

- [ ] **Step 3: Build the resource and actions**

Implementation notes:

- snapshot plan limits onto the subscription created/updated by organization forms
- if the owner email already exists, reject illegal ownership reassignment cases instead of silently stealing the user from another org
- keep organization view tabs implemented as a custom page view so users/subscriptions/activity/buildings panels can mix direct relations with adapter-backed data
- use the shared impersonation manager from the shell plan

- [ ] **Step 4: Re-run the organizations tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/OrganizationsResourceTest.php tests/Feature/Superadmin/OrganizationActionsTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the organizations resource**

Run:

```bash
git add app/Actions/Superadmin/Organizations app/Support/Superadmin/Exports app/Filament/Resources/Organizations app/Http/Requests/Superadmin/Organizations resources/views/filament/resources/organizations/pages/view-organization.blade.php app/Models/Organization.php app/Models/Subscription.php app/Models/User.php tests/Feature/Superadmin/OrganizationsResourceTest.php tests/Feature/Superadmin/OrganizationActionsTest.php
git commit -m "feat: add superadmin organization management"
```

## Chunk 3: Users and Subscriptions

### Task 5: Implement the system users resource

**Files:**
- Create: `app/Filament/Resources/Users/UserResource.php`
- Create: `app/Filament/Resources/Users/Schemas/UserForm.php`
- Create: `app/Filament/Resources/Users/Schemas/UserTable.php`
- Create: `app/Filament/Resources/Users/Pages/ListUsers.php`
- Create: `app/Filament/Resources/Users/Pages/CreateUser.php`
- Create: `app/Filament/Resources/Users/Pages/EditUser.php`
- Create: `app/Filament/Resources/Users/Pages/ViewUser.php`
- Modify: `app/Policies/UserPolicy.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Superadmin/UsersResourceTest.php`

- [ ] **Step 1: Write the failing users test**

Cover:

- filters for role/status/organization/last login
- table columns and row actions
- disabled delete state when dependent data exists
- impersonate action for user rows

For create/edit fields not fully specified in the product text, keep the initial form minimal and explicit: `name`, `email`, `role`, `organization_id`, `status`.

- [ ] **Step 2: Run the users test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/UsersResourceTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement the users resource**

Keep delete eligibility in a dedicated query/helper method so later invoice/building modules can extend it without rewriting table logic.

- [ ] **Step 4: Re-run the users test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/UsersResourceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the users resource**

Run:

```bash
git add app/Filament/Resources/Users app/Policies/UserPolicy.php app/Models/User.php tests/Feature/Superadmin/UsersResourceTest.php
git commit -m "feat: add superadmin users resource"
```

### Task 6: Implement the subscriptions resource

**Files:**
- Create: `app/Actions/Superadmin/Subscriptions/ExtendSubscriptionAction.php`
- Create: `app/Actions/Superadmin/Subscriptions/UpgradeSubscriptionPlanAction.php`
- Create: `app/Actions/Superadmin/Subscriptions/SuspendSubscriptionAction.php`
- Create: `app/Actions/Superadmin/Subscriptions/CancelSubscriptionAction.php`
- Create: `app/Filament/Resources/Subscriptions/SubscriptionResource.php`
- Create: `app/Filament/Resources/Subscriptions/Schemas/SubscriptionForm.php`
- Create: `app/Filament/Resources/Subscriptions/Schemas/SubscriptionTable.php`
- Create: `app/Filament/Resources/Subscriptions/Pages/ListSubscriptions.php`
- Create: `app/Filament/Resources/Subscriptions/Pages/CreateSubscription.php`
- Create: `app/Filament/Resources/Subscriptions/Pages/EditSubscription.php`
- Create: `app/Filament/Resources/Subscriptions/Pages/ViewSubscription.php`
- Create: `app/Http/Requests/Superadmin/Subscriptions/StoreSubscriptionRequest.php`
- Create: `app/Http/Requests/Superadmin/Subscriptions/UpdateSubscriptionRequest.php`
- Modify: `app/Models/Subscription.php`
- Modify: `app/Enums/SubscriptionPlan.php`
- Test: `tests/Feature/Superadmin/SubscriptionsResourceTest.php`

- [ ] **Step 1: Write the failing subscriptions test**

Cover:

- organization/plan/status/expiring-within filters
- properties-used and tenants-used columns
- extend expiry and upgrade plan modal actions
- suspend/cancel confirmation actions

Use the usage adapter for property counts until that domain exists.

- [ ] **Step 2: Run the subscriptions test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SubscriptionsResourceTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement the resource and actions**

Keep plan limits centralized on `SubscriptionPlan` or a single config-backed resolver; do not duplicate them inside Filament action closures.

- [ ] **Step 4: Re-run the subscriptions test**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SubscriptionsResourceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the subscriptions resource**

Run:

```bash
git add app/Actions/Superadmin/Subscriptions app/Filament/Resources/Subscriptions app/Http/Requests/Superadmin/Subscriptions app/Models/Subscription.php app/Enums/SubscriptionPlan.php tests/Feature/Superadmin/SubscriptionsResourceTest.php
git commit -m "feat: add superadmin subscriptions resource"
```

## Chunk 4: Governance and Localization Operations

### Task 7: Implement system configuration and audit logs

**Files:**
- Create: `app/Actions/Superadmin/SystemConfiguration/UpdateSystemSettingAction.php`
- Create: `app/Filament/Pages/SystemConfiguration.php`
- Create: `resources/views/filament/pages/system-configuration.blade.php`
- Create: `app/Http/Requests/Superadmin/SystemConfiguration/UpdateSystemSettingRequest.php`
- Create: `app/Filament/Resources/AuditLogs/AuditLogResource.php`
- Create: `app/Filament/Resources/AuditLogs/Schemas/AuditLogTable.php`
- Create: `app/Filament/Resources/AuditLogs/Pages/ListAuditLogs.php`
- Test: `tests/Feature/Superadmin/SystemConfigurationPageTest.php`
- Test: `tests/Feature/Superadmin/AuditLogsResourceTest.php`

- [ ] **Step 1: Write the failing governance tests**

Cover:

- grouped system settings with inline edit
- audit log filtering, colored action states, and before/after expansion

- [ ] **Step 2: Run the governance tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SystemConfigurationPageTest.php tests/Feature/Superadmin/AuditLogsResourceTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement system configuration and audit logs**

Keep setting mutation in the action class and audit both successful changes and list-page actions through the shared `AuditLogger`.

- [ ] **Step 4: Re-run the governance tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SystemConfigurationPageTest.php tests/Feature/Superadmin/AuditLogsResourceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit governance pages**

Run:

```bash
git add app/Actions/Superadmin/SystemConfiguration app/Filament/Pages/SystemConfiguration.php resources/views/filament/pages/system-configuration.blade.php app/Http/Requests/Superadmin/SystemConfiguration app/Filament/Resources/AuditLogs tests/Feature/Superadmin/SystemConfigurationPageTest.php tests/Feature/Superadmin/AuditLogsResourceTest.php
git commit -m "feat: add superadmin governance pages"
```

### Task 8: Implement platform notifications, languages, and translation management

**Files:**
- Create: `app/Actions/Superadmin/Notifications/SavePlatformNotificationDraftAction.php`
- Create: `app/Actions/Superadmin/Notifications/SendPlatformNotificationAction.php`
- Create: `app/Actions/Superadmin/Languages/SetDefaultLanguageAction.php`
- Create: `app/Actions/Superadmin/Languages/ToggleLanguageStatusAction.php`
- Create: `app/Actions/Superadmin/Languages/DeleteLanguageAction.php`
- Create: `app/Actions/Superadmin/Translations/ImportTranslationsAction.php`
- Create: `app/Actions/Superadmin/Translations/ExportMissingTranslationsAction.php`
- Create: `app/Actions/Superadmin/Translations/UpdateTranslationValueAction.php`
- Create: `app/Support/Superadmin/Translations/TranslationCatalogService.php`
- Create: `app/Support/Superadmin/Translations/TranslationRowData.php`
- Create: `app/Filament/Resources/PlatformNotifications/PlatformNotificationResource.php`
- Create: `app/Filament/Resources/PlatformNotifications/Schemas/PlatformNotificationForm.php`
- Create: `app/Filament/Resources/PlatformNotifications/Schemas/PlatformNotificationTable.php`
- Create: `app/Filament/Resources/PlatformNotifications/Pages/ListPlatformNotifications.php`
- Create: `app/Filament/Resources/PlatformNotifications/Pages/CreatePlatformNotification.php`
- Create: `app/Filament/Resources/PlatformNotifications/Pages/EditPlatformNotification.php`
- Create: `app/Filament/Resources/PlatformNotifications/Pages/ViewPlatformNotification.php`
- Create: `app/Filament/Resources/Languages/LanguageResource.php`
- Create: `app/Filament/Resources/Languages/Schemas/LanguageForm.php`
- Create: `app/Filament/Resources/Languages/Schemas/LanguageTable.php`
- Create: `app/Filament/Resources/Languages/Pages/ListLanguages.php`
- Create: `app/Filament/Resources/Languages/Pages/CreateLanguage.php`
- Create: `app/Filament/Resources/Languages/Pages/EditLanguage.php`
- Create: `app/Filament/Pages/TranslationManagement.php`
- Create: `resources/views/filament/pages/translation-management.blade.php`
- Create: `app/Http/Requests/Superadmin/PlatformNotifications/StorePlatformNotificationRequest.php`
- Modify: `app/Http/Middleware/SetAuthenticatedUserLocale.php`
- Modify: `app/Actions/Preferences/UpdateUserLocaleAction.php`
- Modify: `config/tenanto.php`
- Test: `tests/Feature/Superadmin/PlatformNotificationsResourceTest.php`
- Test: `tests/Feature/Superadmin/LanguagesResourceTest.php`
- Test: `tests/Feature/Superadmin/TranslationManagementPageTest.php`

- [ ] **Step 1: Write the failing localization/notifications tests**

Cover:

- draft/send-now platform notifications and recipient delivery counts
- languages list actions including default/activate/deactivate/delete constraints
- translation import/export/inline-update flows against real `lang/*.php` files inside a temporary filesystem sandbox

- [ ] **Step 2: Run the tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/PlatformNotificationsResourceTest.php tests/Feature/Superadmin/LanguagesResourceTest.php tests/Feature/Superadmin/TranslationManagementPageTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement notifications, languages, and translations**

Important rules:

- keep delivery fan-out in `SendPlatformNotificationAction`
- use the `languages` table as the source of truth for active/default locales
- keep translation storage file-backed so the application continues using Laravel’s native lang files
- when deleting a language, block deletion if any user currently has that locale

- [ ] **Step 4: Re-run the tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/PlatformNotificationsResourceTest.php tests/Feature/Superadmin/LanguagesResourceTest.php tests/Feature/Superadmin/TranslationManagementPageTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the localization and notifications slice**

Run:

```bash
git add app/Actions/Superadmin/Notifications app/Actions/Superadmin/Languages app/Actions/Superadmin/Translations app/Support/Superadmin/Translations app/Filament/Resources/PlatformNotifications app/Filament/Resources/Languages app/Filament/Pages/TranslationManagement.php resources/views/filament/pages/translation-management.blade.php app/Http/Requests/Superadmin/PlatformNotifications app/Http/Middleware/SetAuthenticatedUserLocale.php app/Actions/Preferences/UpdateUserLocaleAction.php config/tenanto.php tests/Feature/Superadmin/PlatformNotificationsResourceTest.php tests/Feature/Superadmin/LanguagesResourceTest.php tests/Feature/Superadmin/TranslationManagementPageTest.php
git commit -m "feat: add superadmin localization and notifications"
```

## Chunk 5: Security, Integration Health, and Verification

### Task 9: Implement security violations and integration health

**Files:**
- Create: `app/Actions/Superadmin/Security/BlockIpAddressAction.php`
- Create: `app/Actions/Superadmin/Integration/RunIntegrationHealthChecksAction.php`
- Create: `app/Actions/Superadmin/Integration/ResetIntegrationCircuitBreakerAction.php`
- Create: `app/Support/Superadmin/Integration/Contracts/IntegrationProbe.php`
- Create: `app/Support/Superadmin/Integration/IntegrationProbeRegistry.php`
- Create: `app/Support/Superadmin/Integration/Probes/DatabaseProbe.php`
- Create: `app/Support/Superadmin/Integration/Probes/QueueProbe.php`
- Create: `app/Support/Superadmin/Integration/Probes/MailProbe.php`
- Create: `app/Filament/Resources/SecurityViolations/SecurityViolationResource.php`
- Create: `app/Filament/Resources/SecurityViolations/Schemas/SecurityViolationTable.php`
- Create: `app/Filament/Resources/SecurityViolations/Pages/ListSecurityViolations.php`
- Create: `app/Filament/Pages/IntegrationHealth.php`
- Create: `resources/views/filament/pages/integration-health.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Test: `tests/Feature/Superadmin/SecurityViolationsResourceTest.php`
- Test: `tests/Feature/Superadmin/IntegrationHealthPageTest.php`

- [ ] **Step 1: Write the failing security/health tests**

Cover:

- security-violations filters and block-IP action
- blocked IPs being rejected by middleware
- integration health page cards, run-all, check-now, and reset-circuit-breaker behavior
- page polling metadata set to `30s`

- [ ] **Step 2: Run the tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SecurityViolationsResourceTest.php tests/Feature/Superadmin/IntegrationHealthPageTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement the resource, page, actions, and probe registry**

Use internal probes that are available in this repo today (database, queue/jobs, mail config) so the feature ships with real health cards rather than dead placeholders.

- [ ] **Step 4: Re-run the tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin/SecurityViolationsResourceTest.php tests/Feature/Superadmin/IntegrationHealthPageTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the security/health slice**

Run:

```bash
git add app/Actions/Superadmin/Security app/Actions/Superadmin/Integration app/Support/Superadmin/Integration app/Filament/Resources/SecurityViolations app/Filament/Pages/IntegrationHealth.php resources/views/filament/pages/integration-health.blade.php app/Providers/AppServiceProvider.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/Superadmin/SecurityViolationsResourceTest.php tests/Feature/Superadmin/IntegrationHealthPageTest.php
git commit -m "feat: add superadmin security and integration health"
```

### Task 10: Run the full superadmin verification pass

**Files:**
- Review only: all files touched in Tasks 1-9

- [ ] **Step 1: Run all superadmin tests**

Run:

```bash
php artisan test --compact tests/Feature/Superadmin
```

Expected: PASS.

- [ ] **Step 2: Run auth and shell regressions**

Run:

```bash
php artisan test --compact tests/Feature/Auth
php artisan test --compact tests/Feature/Shell
```

Expected: PASS.

- [ ] **Step 3: Run the full suite**

Run:

```bash
php artisan test --compact
```

Expected: PASS.

- [ ] **Step 4: Format changed PHP files**

Run:

```bash
vendor/bin/pint --dirty
```

Expected: PASS.

- [ ] **Step 5: Verify the production assets build**

Run:

```bash
npm run build
```

Expected: PASS.

- [ ] **Step 6: Commit the verified control plane**

Run:

```bash
git add app bootstrap config database lang resources tests
git commit -m "feat: deliver superadmin control plane"
```

## Follow-on Plans Recommended

- `docs/superpowers/plans/2026-03-17-property-billing-domain-foundation.md`
  - add buildings, properties, meters, meter readings, invoices, tariffs, providers, service configurations, and utility services so superadmin cross-domain tabs stop relying on null adapters
- `docs/superpowers/plans/2026-03-17-superadmin-reporting.md`
  - implement reports module and replace navigation placeholders with real resources/pages
- `docs/superpowers/plans/2026-03-17-platform-export-packaging.md`
  - replace the null organization export builder with full ZIP exports once invoice and meter-reading datasets exist

## Execution Notes

- Do not start this plan before the shared shell/sidebar/topbar plan is implemented; otherwise you will duplicate navigation work.
- Prefer null adapters over fake ad-hoc queries for data domains that do not exist yet.
- Keep every destructive Filament action behind `->requiresConfirmation()` and `->authorize()`.
- Use model factories in every superadmin test; do not seed by hand inside test methods when a factory will do.
- Keep observer-driven audit logging focused and explicit; never log inside Blade or table formatter closures.
