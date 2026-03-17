# Admin Organization Operations Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the organization-scoped admin and manager workspace so buildings, properties, tenants, meters, readings, invoices, billing configuration, reports, profile, and settings all work end to end inside the shared Tenanto shell.

**Architecture:** Add the missing organization domain as a first-class Eloquent model set, then surface it through Filament 5 Resources, dashboard widgets, and custom Pages that are shared by Admin and Manager roles but gated where the spec diverges. Keep all business logic in Actions and support services: subscription-limit enforcement, tenant assignment, reading validation/import, invoice generation/finalization/payment, report aggregation, and organization settings should never live directly in Resource closures or Blade.

**Tech Stack:** Laravel 12, Filament 5, Livewire 4, Blade, Tailwind CSS v4, SQLite, Laravel mail/notifications, Pest 4, Laravel Pint.

---

## Spec Reference

- Spec: `docs/superpowers/specs/2026-03-17-admin-organization-operations-design.md`
- Supporting baselines: `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`, `docs/superpowers/specs/2026-03-17-shared-interface-elements-design.md`
- Skills to apply during execution: `@laravel-11-12-app-guidelines`, `@filament`, `@pest-testing`

## Scope Check

This specification spans multiple subsystems:

- organization-scoped core domain
- metering and reading validation/import
- billing and invoice generation
- provider/tariff/service configuration
- reports
- admin/manager account and settings pages

Because all of these depend on the same missing organization-scoped data model, this plan keeps them together as one staged rollout. If you want shorter branches, the natural split point is after Chunk 3:

- follow-on plan A: billing configuration + invoices
- follow-on plan B: reports + account/settings polish

## Prerequisites

- `docs/superpowers/plans/2026-03-17-foundation-auth-onboarding.md`
- `docs/superpowers/plans/2026-03-17-shared-interface-elements.md`

Recommended reuse if already implemented:

- any shared audit/logging/language primitives from `docs/superpowers/plans/2026-03-17-superadmin-control-plane.md`

## Scope Notes

- All admin/manager data must be organization-scoped by default.
- Manager uses the same operational resources as Admin, but does not see subscription usage bars on the dashboard and does not see admin-only sections on the Settings page.
- Tenant accounts remain `users.role = tenant`; do not create a separate “tenant user” table.
- Property assignment history needs timestamps because the spec shows “Assigned Since” and unassignment behavior.
- Reports must be built from the same invoice and reading data models used by the operational pages; do not create separate report-only tables.

## File Map

### Create

- `app/Enums/PropertyType.php`
- `app/Enums/PropertyOccupancyStatus.php`
- `app/Enums/MeterType.php`
- `app/Enums/MeterStatus.php`
- `app/Enums/MeterReadingValidationStatus.php`
- `app/Enums/MeterReadingSubmissionMethod.php`
- `app/Enums/InvoiceStatus.php`
- `app/Enums/InvoiceAdjustmentType.php`
- `app/Enums/PaymentMethod.php`
- `app/Enums/ProviderType.php`
- `app/Enums/TariffStatus.php`
- `app/Models/OrganizationSetting.php`
- `app/Models/Building.php`
- `app/Models/Property.php`
- `app/Models/PropertyAssignment.php`
- `app/Models/UtilityService.php`
- `app/Models/ServiceConfiguration.php`
- `app/Models/Provider.php`
- `app/Models/Tariff.php`
- `app/Models/Meter.php`
- `app/Models/MeterReading.php`
- `app/Models/Invoice.php`
- `app/Models/InvoiceLineItem.php`
- `app/Models/InvoicePayment.php`
- `app/Models/InvoiceEmailLog.php`
- `app/Models/InvoiceReminderLog.php`
- `database/factories/OrganizationSettingFactory.php`
- `database/factories/BuildingFactory.php`
- `database/factories/PropertyFactory.php`
- `database/factories/PropertyAssignmentFactory.php`
- `database/factories/UtilityServiceFactory.php`
- `database/factories/ServiceConfigurationFactory.php`
- `database/factories/ProviderFactory.php`
- `database/factories/TariffFactory.php`
- `database/factories/MeterFactory.php`
- `database/factories/MeterReadingFactory.php`
- `database/factories/InvoiceFactory.php`
- `database/factories/InvoiceLineItemFactory.php`
- `database/factories/InvoicePaymentFactory.php`
- `database/factories/InvoiceEmailLogFactory.php`
- `database/factories/InvoiceReminderLogFactory.php`
- `database/migrations/2026_03_17_110000_create_organization_settings_table.php`
- `database/migrations/2026_03_17_110100_create_buildings_table.php`
- `database/migrations/2026_03_17_110200_create_properties_table.php`
- `database/migrations/2026_03_17_110300_create_property_assignments_table.php`
- `database/migrations/2026_03_17_110400_create_utility_services_table.php`
- `database/migrations/2026_03_17_110500_create_service_configurations_table.php`
- `database/migrations/2026_03_17_110600_create_providers_table.php`
- `database/migrations/2026_03_17_110700_create_tariffs_table.php`
- `database/migrations/2026_03_17_110800_create_meters_table.php`
- `database/migrations/2026_03_17_110900_create_meter_readings_table.php`
- `database/migrations/2026_03_17_111000_create_invoices_table.php`
- `database/migrations/2026_03_17_111100_create_invoice_line_items_table.php`
- `database/migrations/2026_03_17_111200_create_invoice_payments_table.php`
- `database/migrations/2026_03_17_111300_create_invoice_email_logs_table.php`
- `database/migrations/2026_03_17_111400_create_invoice_reminder_logs_table.php`
- `database/seeders/UtilityServiceSeeder.php`
- `app/Policies/BuildingPolicy.php`
- `app/Policies/PropertyPolicy.php`
- `app/Policies/MeterPolicy.php`
- `app/Policies/MeterReadingPolicy.php`
- `app/Policies/InvoicePolicy.php`
- `app/Policies/TariffPolicy.php`
- `app/Policies/ProviderPolicy.php`
- `app/Policies/ServiceConfigurationPolicy.php`
- `app/Policies/OrganizationSettingPolicy.php`
- `app/Support/Admin/OrganizationContext.php`
- `app/Support/Admin/SubscriptionLimitGuard.php`
- `app/Support/Admin/ReadingValidation/ReadingValidationResult.php`
- `app/Support/Admin/ReadingValidation/ValidateReadingValue.php`
- `app/Support/Admin/Dashboard/AdminDashboardStats.php`
- `app/Support/Admin/Dashboard/UpcomingReadingDeadlineData.php`
- `app/Support/Admin/Reports/ConsumptionReportBuilder.php`
- `app/Support/Admin/Reports/RevenueReportBuilder.php`
- `app/Support/Admin/Reports/OutstandingBalancesReportBuilder.php`
- `app/Support/Admin/Reports/MeterComplianceReportBuilder.php`
- `app/Support/Admin/Invoices/InvoiceLineItemCalculator.php`
- `app/Support/Admin/Invoices/BulkInvoicePreviewBuilder.php`
- `app/Actions/Admin/Buildings/CreateBuildingAction.php`
- `app/Actions/Admin/Buildings/UpdateBuildingAction.php`
- `app/Actions/Admin/Buildings/DeleteBuildingAction.php`
- `app/Actions/Admin/Properties/CreatePropertyAction.php`
- `app/Actions/Admin/Properties/UpdatePropertyAction.php`
- `app/Actions/Admin/Properties/DeletePropertyAction.php`
- `app/Actions/Admin/Properties/AssignTenantToPropertyAction.php`
- `app/Actions/Admin/Properties/UnassignTenantFromPropertyAction.php`
- `app/Actions/Admin/Tenants/CreateTenantAction.php`
- `app/Actions/Admin/Tenants/UpdateTenantAction.php`
- `app/Actions/Admin/Tenants/ToggleTenantStatusAction.php`
- `app/Actions/Admin/Tenants/DeleteTenantAction.php`
- `app/Actions/Admin/Meters/CreateMeterAction.php`
- `app/Actions/Admin/Meters/UpdateMeterAction.php`
- `app/Actions/Admin/Meters/ToggleMeterStatusAction.php`
- `app/Actions/Admin/Meters/DeleteMeterAction.php`
- `app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php`
- `app/Actions/Admin/MeterReadings/UpdateMeterReadingAction.php`
- `app/Actions/Admin/MeterReadings/ValidateMeterReadingAction.php`
- `app/Actions/Admin/MeterReadings/ImportMeterReadingsAction.php`
- `app/Actions/Admin/Invoices/GenerateInvoiceLineItemsAction.php`
- `app/Actions/Admin/Invoices/SaveInvoiceDraftAction.php`
- `app/Actions/Admin/Invoices/FinalizeInvoiceAction.php`
- `app/Actions/Admin/Invoices/GenerateBulkInvoicesAction.php`
- `app/Actions/Admin/Invoices/RecordInvoicePaymentAction.php`
- `app/Actions/Admin/Invoices/SendInvoiceEmailAction.php`
- `app/Actions/Admin/Invoices/SendInvoiceReminderAction.php`
- `app/Actions/Admin/Settings/UpdateProfileAction.php`
- `app/Actions/Admin/Settings/UpdatePasswordAction.php`
- `app/Actions/Admin/Settings/UpdateOrganizationSettingsAction.php`
- `app/Actions/Admin/Settings/UpdateNotificationPreferenceAction.php`
- `app/Actions/Admin/Settings/RenewOrganizationSubscriptionAction.php`
- `app/Filament/Widgets/Admin/OrganizationStatsOverview.php`
- `app/Filament/Widgets/Admin/SubscriptionUsageOverview.php`
- `app/Filament/Widgets/Admin/RecentInvoicesWidget.php`
- `app/Filament/Widgets/Admin/UpcomingReadingDeadlinesWidget.php`
- `app/Filament/Resources/Buildings/BuildingResource.php`
- `app/Filament/Resources/Buildings/Schemas/BuildingForm.php`
- `app/Filament/Resources/Buildings/Schemas/BuildingTable.php`
- `app/Filament/Resources/Buildings/Pages/ListBuildings.php`
- `app/Filament/Resources/Buildings/Pages/CreateBuilding.php`
- `app/Filament/Resources/Buildings/Pages/EditBuilding.php`
- `app/Filament/Resources/Buildings/Pages/ViewBuilding.php`
- `app/Filament/Resources/Properties/PropertyResource.php`
- `app/Filament/Resources/Properties/Schemas/PropertyForm.php`
- `app/Filament/Resources/Properties/Schemas/PropertyTable.php`
- `app/Filament/Resources/Properties/Pages/ListProperties.php`
- `app/Filament/Resources/Properties/Pages/CreateProperty.php`
- `app/Filament/Resources/Properties/Pages/EditProperty.php`
- `app/Filament/Resources/Properties/Pages/ViewProperty.php`
- `app/Filament/Resources/Tenants/TenantResource.php`
- `app/Filament/Resources/Tenants/Schemas/TenantForm.php`
- `app/Filament/Resources/Tenants/Schemas/TenantTable.php`
- `app/Filament/Resources/Tenants/Pages/ListTenants.php`
- `app/Filament/Resources/Tenants/Pages/CreateTenant.php`
- `app/Filament/Resources/Tenants/Pages/EditTenant.php`
- `app/Filament/Resources/Tenants/Pages/ViewTenant.php`
- `app/Filament/Resources/Meters/MeterResource.php`
- `app/Filament/Resources/Meters/Schemas/MeterForm.php`
- `app/Filament/Resources/Meters/Schemas/MeterTable.php`
- `app/Filament/Resources/Meters/Pages/ListMeters.php`
- `app/Filament/Resources/Meters/Pages/CreateMeter.php`
- `app/Filament/Resources/Meters/Pages/EditMeter.php`
- `app/Filament/Resources/Meters/Pages/ViewMeter.php`
- `app/Filament/Resources/MeterReadings/MeterReadingResource.php`
- `app/Filament/Resources/MeterReadings/Schemas/MeterReadingForm.php`
- `app/Filament/Resources/MeterReadings/Schemas/MeterReadingTable.php`
- `app/Filament/Resources/MeterReadings/Pages/ListMeterReadings.php`
- `app/Filament/Resources/MeterReadings/Pages/CreateMeterReading.php`
- `app/Filament/Resources/MeterReadings/Pages/EditMeterReading.php`
- `app/Filament/Resources/MeterReadings/Pages/ViewMeterReading.php`
- `app/Filament/Resources/Invoices/InvoiceResource.php`
- `app/Filament/Resources/Invoices/Schemas/InvoiceForm.php`
- `app/Filament/Resources/Invoices/Schemas/InvoiceTable.php`
- `app/Filament/Resources/Invoices/Pages/ListInvoices.php`
- `app/Filament/Resources/Invoices/Pages/CreateInvoice.php`
- `app/Filament/Resources/Invoices/Pages/EditInvoice.php`
- `app/Filament/Resources/Invoices/Pages/ViewInvoice.php`
- `app/Filament/Pages/GenerateBulkInvoices.php`
- `app/Filament/Resources/Tariffs/TariffResource.php`
- `app/Filament/Resources/Tariffs/Schemas/TariffForm.php`
- `app/Filament/Resources/Tariffs/Schemas/TariffTable.php`
- `app/Filament/Resources/Tariffs/Pages/ListTariffs.php`
- `app/Filament/Resources/Tariffs/Pages/CreateTariff.php`
- `app/Filament/Resources/Tariffs/Pages/EditTariff.php`
- `app/Filament/Resources/Tariffs/Pages/ViewTariff.php`
- `app/Filament/Resources/Providers/ProviderResource.php`
- `app/Filament/Resources/Providers/Schemas/ProviderForm.php`
- `app/Filament/Resources/Providers/Schemas/ProviderTable.php`
- `app/Filament/Resources/Providers/Pages/ListProviders.php`
- `app/Filament/Resources/Providers/Pages/CreateProvider.php`
- `app/Filament/Resources/Providers/Pages/EditProvider.php`
- `app/Filament/Resources/Providers/Pages/ViewProvider.php`
- `app/Filament/Resources/ServiceConfigurations/ServiceConfigurationResource.php`
- `app/Filament/Resources/ServiceConfigurations/Schemas/ServiceConfigurationForm.php`
- `app/Filament/Resources/ServiceConfigurations/Schemas/ServiceConfigurationTable.php`
- `app/Filament/Resources/ServiceConfigurations/Pages/ListServiceConfigurations.php`
- `app/Filament/Resources/ServiceConfigurations/Pages/CreateServiceConfiguration.php`
- `app/Filament/Resources/ServiceConfigurations/Pages/EditServiceConfiguration.php`
- `app/Filament/Resources/UtilityServices/UtilityServiceResource.php`
- `app/Filament/Resources/UtilityServices/Schemas/UtilityServiceForm.php`
- `app/Filament/Resources/UtilityServices/Schemas/UtilityServiceTable.php`
- `app/Filament/Resources/UtilityServices/Pages/ListUtilityServices.php`
- `app/Filament/Resources/UtilityServices/Pages/CreateUtilityService.php`
- `app/Filament/Resources/UtilityServices/Pages/EditUtilityService.php`
- `app/Filament/Pages/Profile.php`
- `app/Filament/Pages/Settings.php`
- `app/Filament/Pages/Reports.php`
- `resources/views/filament/pages/organization-dashboard.blade.php`
- `resources/views/filament/pages/generate-bulk-invoices.blade.php`
- `resources/views/filament/pages/profile.blade.php`
- `resources/views/filament/pages/settings.blade.php`
- `resources/views/filament/pages/reports.blade.php`
- `lang/en/admin.php`
- `lang/lt/admin.php`
- `lang/ru/admin.php`
- `lang/es/admin.php`
- `tests/Feature/Admin/AdminDashboardTest.php`
- `tests/Feature/Admin/BuildingsResourceTest.php`
- `tests/Feature/Admin/PropertiesResourceTest.php`
- `tests/Feature/Admin/TenantsResourceTest.php`
- `tests/Feature/Admin/MetersResourceTest.php`
- `tests/Feature/Admin/MeterReadingsResourceTest.php`
- `tests/Feature/Admin/InvoicesResourceTest.php`
- `tests/Feature/Admin/BulkInvoiceGenerationTest.php`
- `tests/Feature/Admin/TariffsAndProvidersTest.php`
- `tests/Feature/Admin/ReportsPageTest.php`
- `tests/Feature/Admin/ProfileAndSettingsTest.php`

### Modify

- `app/Providers/AppServiceProvider.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Pages/OrganizationDashboard.php`
- `app/Models/Organization.php`
- `app/Models/Subscription.php`
- `app/Models/User.php`
- `app/Enums/UserRole.php`
- `config/tenanto.php`
- `app/Support/Shell/Navigation/NavigationBuilder.php`
- `app/Actions/Auth/CreateOrganizationInvitationAction.php`
- `routes/web.php` (only if shared-shell profile routing must point into Filament pages)
- `tests/Feature/Auth/AccessIsolationTest.php`

### Follow-on Plans Recommended

- `docs/superpowers/plans/2026-03-17-tenant-portal.md`
- `docs/superpowers/plans/2026-03-17-superadmin-control-plane.md`

## Chunk 1: Organization Domain Foundation

### Task 1: Create the organization-scoped domain schema and factories

**Files:**
- Create: all migrations, models, enums, and factories for `OrganizationSetting`, `Building`, `Property`, `PropertyAssignment`, `UtilityService`, `ServiceConfiguration`, `Provider`, `Tariff`, `Meter`, `MeterReading`, `Invoice`, `InvoiceLineItem`, `InvoicePayment`, `InvoiceEmailLog`, `InvoiceReminderLog`
- Create: `database/seeders/UtilityServiceSeeder.php`
- Modify: `app/Models/Organization.php`
- Modify: `app/Models/Subscription.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Admin/AdminDashboardTest.php`

- [ ] **Step 1: Write the first failing admin dashboard smoke test**

Create `tests/Feature/Admin/AdminDashboardTest.php` with a basic assertion that an admin can eventually see organization-scoped metrics:

```php
it('shows admin dashboard metrics for the current organization', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertOk()
        ->assertSeeText('Total Properties')
        ->assertSeeText('Active Tenants')
        ->assertSeeText('Pending Invoices')
        ->assertSeeText('Revenue This Month');
});
```

- [ ] **Step 2: Run the dashboard smoke test**

Run:

```bash
php artisan test --compact tests/Feature/Admin/AdminDashboardTest.php
```

Expected: FAIL because the underlying organization domain tables and metrics do not exist.

- [ ] **Step 3: Add the core schema and factories**

Important design rules:

- every operational model must carry `organization_id` except global reference tables like utility services
- `PropertyAssignment` stores `property_id`, `tenant_user_id`, `unit_area_sqm`, `assigned_at`, `unassigned_at`
- `MeterReading` stores `submitted_by_user_id`, `submission_method`, `validation_status`, optional `notes`
- `Invoice` stores finalized/paid dates and status, while line items/payments/emails/reminders live in separate tables
- `OrganizationSetting` stores billing email, footer notes, notification preferences, reading frequency, and compliance thresholds

- [ ] **Step 4: Migrate and seed the foundation**

Run:

```bash
php artisan migrate --force
php artisan db:seed --class=UtilityServiceSeeder
```

Expected: PASS.

- [ ] **Step 5: Re-run the smoke test**

Run:

```bash
php artisan test --compact tests/Feature/Admin/AdminDashboardTest.php
```

Expected: still FAIL on page content, but no longer on missing schema.

- [ ] **Step 6: Commit the organization domain foundation**

Run:

```bash
git add app/Enums app/Models database/migrations database/factories database/seeders tests/Feature/Admin/AdminDashboardTest.php
git commit -m "feat: add admin organization domain foundation"
```

### Task 2: Add organization scoping, subscription enforcement, and shared support services

**Files:**
- Create: `app/Policies/BuildingPolicy.php`
- Create: `app/Policies/PropertyPolicy.php`
- Create: `app/Policies/MeterPolicy.php`
- Create: `app/Policies/MeterReadingPolicy.php`
- Create: `app/Policies/InvoicePolicy.php`
- Create: `app/Policies/TariffPolicy.php`
- Create: `app/Policies/ProviderPolicy.php`
- Create: `app/Policies/ServiceConfigurationPolicy.php`
- Create: `app/Policies/OrganizationSettingPolicy.php`
- Create: `app/Support/Admin/OrganizationContext.php`
- Create: `app/Support/Admin/SubscriptionLimitGuard.php`
- Create: `app/Support/Admin/ReadingValidation/ReadingValidationResult.php`
- Create: `app/Support/Admin/ReadingValidation/ValidateReadingValue.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Enums/UserRole.php`
- Modify: `config/tenanto.php`
- Modify: `app/Support/Shell/Navigation/NavigationBuilder.php`
- Test: `tests/Feature/Auth/AccessIsolationTest.php`

- [ ] **Step 1: Write failing scoping and subscription guard tests**

Extend `tests/Feature/Auth/AccessIsolationTest.php` or add a focused admin assertion that:

- admins and managers can only read their own organization’s data
- superadmins cannot accidentally enter admin resources through organization scoping
- subscription limit guard blocks “new property” and “new tenant” flows when limits are reached

- [ ] **Step 2: Run the isolation test**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AccessIsolationTest.php
```

Expected: FAIL because the new domain models are not yet scoped or guarded.

- [ ] **Step 3: Implement policies and support services**

Important rules:

- keep organization scoping in query helpers / resource `getEloquentQuery()` methods, not in Blade
- `SubscriptionLimitGuard` should know both hard limits and grace-period read-only behavior
- `ValidateReadingValue` must centralize all reading rules: non-decreasing values, no future dates, anomaly detection, and 60-day gap notes

- [ ] **Step 4: Re-run the isolation test**

Run:

```bash
php artisan test --compact tests/Feature/Auth/AccessIsolationTest.php
```

Expected: PASS or at least narrow failures to not-yet-built UI pages instead of authorization leaks.

- [ ] **Step 5: Commit the scoping and enforcement layer**

Run:

```bash
git add app/Policies app/Support/Admin app/Providers/AppServiceProvider.php app/Providers/Filament/AdminPanelProvider.php app/Enums/UserRole.php config/tenanto.php app/Support/Shell/Navigation/NavigationBuilder.php tests/Feature/Auth/AccessIsolationTest.php
git commit -m "feat: add admin organization scoping and limit guards"
```

## Chunk 2: Dashboard, Profile, and Settings

### Task 3: Replace the placeholder organization dashboard with real admin/manager widgets

**Files:**
- Create: `app/Support/Admin/Dashboard/AdminDashboardStats.php`
- Create: `app/Support/Admin/Dashboard/UpcomingReadingDeadlineData.php`
- Create: `app/Filament/Widgets/Admin/OrganizationStatsOverview.php`
- Create: `app/Filament/Widgets/Admin/SubscriptionUsageOverview.php`
- Create: `app/Filament/Widgets/Admin/RecentInvoicesWidget.php`
- Create: `app/Filament/Widgets/Admin/UpcomingReadingDeadlinesWidget.php`
- Modify: `app/Filament/Pages/OrganizationDashboard.php`
- Modify: `resources/views/filament/pages/organization-dashboard.blade.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Test: `tests/Feature/Admin/AdminDashboardTest.php`

- [ ] **Step 1: Expand the failing dashboard test**

Add assertions for:

- four count cards
- subscription usage row visible for admins only
- recent invoices section
- upcoming reading deadlines section
- dashboard polling interval set to 30 seconds

- [ ] **Step 2: Run the dashboard tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/AdminDashboardTest.php
```

Expected: FAIL because the organization dashboard is still a placeholder page.

- [ ] **Step 3: Implement the widgets and manager/admin role difference**

Implementation rules:

- hide the usage row for managers
- use explicit eager loading/selects for recent invoices and deadlines
- do not compute counts inside Blade

- [ ] **Step 4: Re-run the dashboard tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/AdminDashboardTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the dashboard**

Run:

```bash
git add app/Support/Admin/Dashboard app/Filament/Widgets/Admin app/Filament/Pages/OrganizationDashboard.php resources/views/filament/pages/organization-dashboard.blade.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/Admin/AdminDashboardTest.php
git commit -m "feat: add admin organization dashboard"
```

### Task 4: Build admin profile and settings pages

**Files:**
- Create: `app/Actions/Admin/Settings/UpdateProfileAction.php`
- Create: `app/Actions/Admin/Settings/UpdatePasswordAction.php`
- Create: `app/Actions/Admin/Settings/UpdateOrganizationSettingsAction.php`
- Create: `app/Actions/Admin/Settings/UpdateNotificationPreferenceAction.php`
- Create: `app/Actions/Admin/Settings/RenewOrganizationSubscriptionAction.php`
- Create: `app/Filament/Pages/Profile.php`
- Create: `app/Filament/Pages/Settings.php`
- Create: `resources/views/filament/pages/profile.blade.php`
- Create: `resources/views/filament/pages/settings.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Support/Shell/Navigation/NavigationBuilder.php`
- Test: `tests/Feature/Admin/ProfileAndSettingsTest.php`

- [ ] **Step 1: Write the failing profile/settings tests**

Cover:

- profile page shows personal information and change-password sections
- language changes save immediately
- admin settings shows organization settings, notification preferences, and subscription section
- manager settings hides the admin-only sections completely

- [ ] **Step 2: Run the profile/settings tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/ProfileAndSettingsTest.php
```

Expected: FAIL because the Filament pages and update actions do not exist.

- [ ] **Step 3: Implement profile and settings pages**

Important rules:

- if the shared-shell profile route already exists, redirect admin-like users to the Filament profile page instead of keeping two profile editors
- notification toggles should save independently
- the subscription renewal modal may call the same plan/duration update action used by superadmin flows, but must be scoped to the current organization only

- [ ] **Step 4: Re-run the profile/settings tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/ProfileAndSettingsTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit profile/settings**

Run:

```bash
git add app/Actions/Admin/Settings app/Filament/Pages/Profile.php app/Filament/Pages/Settings.php resources/views/filament/pages/profile.blade.php resources/views/filament/pages/settings.blade.php routes/web.php app/Support/Shell/Navigation/NavigationBuilder.php tests/Feature/Admin/ProfileAndSettingsTest.php
git commit -m "feat: add admin profile and settings pages"
```

## Chunk 3: Buildings, Properties, and Tenants

### Task 5: Implement buildings and properties resources

**Files:**
- Create: `app/Actions/Admin/Buildings/CreateBuildingAction.php`
- Create: `app/Actions/Admin/Buildings/UpdateBuildingAction.php`
- Create: `app/Actions/Admin/Buildings/DeleteBuildingAction.php`
- Create: `app/Actions/Admin/Properties/CreatePropertyAction.php`
- Create: `app/Actions/Admin/Properties/UpdatePropertyAction.php`
- Create: `app/Actions/Admin/Properties/DeletePropertyAction.php`
- Create: `app/Actions/Admin/Properties/AssignTenantToPropertyAction.php`
- Create: `app/Actions/Admin/Properties/UnassignTenantFromPropertyAction.php`
- Create: `app/Filament/Resources/Buildings/BuildingResource.php`
- Create: `app/Filament/Resources/Buildings/Schemas/BuildingForm.php`
- Create: `app/Filament/Resources/Buildings/Schemas/BuildingTable.php`
- Create: `app/Filament/Resources/Buildings/Pages/ListBuildings.php`
- Create: `app/Filament/Resources/Buildings/Pages/CreateBuilding.php`
- Create: `app/Filament/Resources/Buildings/Pages/EditBuilding.php`
- Create: `app/Filament/Resources/Buildings/Pages/ViewBuilding.php`
- Create: `app/Filament/Resources/Properties/PropertyResource.php`
- Create: `app/Filament/Resources/Properties/Schemas/PropertyForm.php`
- Create: `app/Filament/Resources/Properties/Schemas/PropertyTable.php`
- Create: `app/Filament/Resources/Properties/Pages/ListProperties.php`
- Create: `app/Filament/Resources/Properties/Pages/CreateProperty.php`
- Create: `app/Filament/Resources/Properties/Pages/EditProperty.php`
- Create: `app/Filament/Resources/Properties/Pages/ViewProperty.php`
- Test: `tests/Feature/Admin/BuildingsResourceTest.php`
- Test: `tests/Feature/Admin/PropertiesResourceTest.php`

- [ ] **Step 1: Write the failing buildings/properties tests**

Cover:

- buildings list filters, columns, and delete restriction
- buildings create/edit/view flows
- properties list filters, columns, and delete restriction
- property tenant assign/reassign/unassign flows
- property view cards and tabs

- [ ] **Step 2: Run the tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/BuildingsResourceTest.php tests/Feature/Admin/PropertiesResourceTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement buildings and properties**

Implementation rules:

- use subscription guard before create-property entry points
- compute property occupancy from current assignment, not a duplicated mutable flag
- prevent building deletion when related properties exist

- [ ] **Step 4: Re-run the tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/BuildingsResourceTest.php tests/Feature/Admin/PropertiesResourceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit buildings and properties**

Run:

```bash
git add app/Actions/Admin/Buildings app/Actions/Admin/Properties app/Filament/Resources/Buildings app/Filament/Resources/Properties tests/Feature/Admin/BuildingsResourceTest.php tests/Feature/Admin/PropertiesResourceTest.php
git commit -m "feat: add admin building and property management"
```

### Task 6: Implement tenants resource and assignment-aware tenant views

**Files:**
- Create: `app/Actions/Admin/Tenants/CreateTenantAction.php`
- Create: `app/Actions/Admin/Tenants/UpdateTenantAction.php`
- Create: `app/Actions/Admin/Tenants/ToggleTenantStatusAction.php`
- Create: `app/Actions/Admin/Tenants/DeleteTenantAction.php`
- Create: `app/Filament/Resources/Tenants/TenantResource.php`
- Create: `app/Filament/Resources/Tenants/Schemas/TenantForm.php`
- Create: `app/Filament/Resources/Tenants/Schemas/TenantTable.php`
- Create: `app/Filament/Resources/Tenants/Pages/ListTenants.php`
- Create: `app/Filament/Resources/Tenants/Pages/CreateTenant.php`
- Create: `app/Filament/Resources/Tenants/Pages/EditTenant.php`
- Create: `app/Filament/Resources/Tenants/Pages/ViewTenant.php`
- Modify: `app/Actions/Auth/CreateOrganizationInvitationAction.php`
- Test: `tests/Feature/Admin/TenantsResourceTest.php`

- [ ] **Step 1: Write the failing tenants tests**

Cover:

- tenant list filters and columns
- tenant create flow with preferred language and optional property assignment
- subscription limit block on “New Tenant”
- activation/deactivation/delete rules
- tenant view cards and tabs
- invitation email is sent after tenant creation

- [ ] **Step 2: Run the tenants tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/TenantsResourceTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement tenant management**

Implementation rules:

- creating a tenant should reuse the existing invitation machinery instead of duplicating token/email logic
- if a property is assigned at create time, persist the assignment history row
- delete must remain unavailable when invoice history exists

- [ ] **Step 4: Re-run the tenants tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/TenantsResourceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit tenant management**

Run:

```bash
git add app/Actions/Admin/Tenants app/Filament/Resources/Tenants app/Actions/Auth/CreateOrganizationInvitationAction.php tests/Feature/Admin/TenantsResourceTest.php
git commit -m "feat: add admin tenant management"
```

## Chunk 4: Meters and Readings

### Task 7: Implement meters resource and meter detail view

**Files:**
- Create: `app/Actions/Admin/Meters/CreateMeterAction.php`
- Create: `app/Actions/Admin/Meters/UpdateMeterAction.php`
- Create: `app/Actions/Admin/Meters/ToggleMeterStatusAction.php`
- Create: `app/Actions/Admin/Meters/DeleteMeterAction.php`
- Create: `app/Filament/Resources/Meters/MeterResource.php`
- Create: `app/Filament/Resources/Meters/Schemas/MeterForm.php`
- Create: `app/Filament/Resources/Meters/Schemas/MeterTable.php`
- Create: `app/Filament/Resources/Meters/Pages/ListMeters.php`
- Create: `app/Filament/Resources/Meters/Pages/CreateMeter.php`
- Create: `app/Filament/Resources/Meters/Pages/EditMeter.php`
- Create: `app/Filament/Resources/Meters/Pages/ViewMeter.php`
- Test: `tests/Feature/Admin/MetersResourceTest.php`

- [ ] **Step 1: Write the failing meters tests**

Cover:

- list filters by building/property/type/status
- create flow with prefilled measurement unit
- activate/deactivate behavior
- delete only when no readings exist
- meter view history table and chart shell

- [ ] **Step 2: Run the meters tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/MetersResourceTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement meter management**

Use type-to-default-unit mapping in one support location, not repeated across forms.

- [ ] **Step 4: Re-run the meters tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/MetersResourceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit meters**

Run:

```bash
git add app/Actions/Admin/Meters app/Filament/Resources/Meters tests/Feature/Admin/MetersResourceTest.php
git commit -m "feat: add admin meter management"
```

### Task 8: Implement meter readings list/create/edit/import/validation flows

**Files:**
- Create: `app/Actions/Admin/MeterReadings/CreateMeterReadingAction.php`
- Create: `app/Actions/Admin/MeterReadings/UpdateMeterReadingAction.php`
- Create: `app/Actions/Admin/MeterReadings/ValidateMeterReadingAction.php`
- Create: `app/Actions/Admin/MeterReadings/ImportMeterReadingsAction.php`
- Create: `app/Filament/Resources/MeterReadings/MeterReadingResource.php`
- Create: `app/Filament/Resources/MeterReadings/Schemas/MeterReadingForm.php`
- Create: `app/Filament/Resources/MeterReadings/Schemas/MeterReadingTable.php`
- Create: `app/Filament/Resources/MeterReadings/Pages/ListMeterReadings.php`
- Create: `app/Filament/Resources/MeterReadings/Pages/CreateMeterReading.php`
- Create: `app/Filament/Resources/MeterReadings/Pages/EditMeterReading.php`
- Create: `app/Filament/Resources/MeterReadings/Pages/ViewMeterReading.php`
- Test: `tests/Feature/Admin/MeterReadingsResourceTest.php`

- [ ] **Step 1: Write the failing meter readings tests**

Cover:

- list filters, columns, and validation-status badges
- create/edit rules for increasing values and no future dates
- anomaly detection and 60-day gap notes
- validate action only on pending rows
- bulk import preview with invalid-row reporting

- [ ] **Step 2: Run the meter readings tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/MeterReadingsResourceTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement readings and import flow**

Implementation rules:

- reuse `ValidateReadingValue` everywhere: manual create, edit, and bulk import
- keep import parsing in an action/service, not in a Filament page closure
- lock edits when a reading is already invoiced/finalized

- [ ] **Step 4: Re-run the meter readings tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/MeterReadingsResourceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit meter readings**

Run:

```bash
git add app/Actions/Admin/MeterReadings app/Filament/Resources/MeterReadings tests/Feature/Admin/MeterReadingsResourceTest.php
git commit -m "feat: add admin meter readings workflows"
```

## Chunk 5: Billing Configuration and Invoices

### Task 9: Implement tariffs, providers, service configurations, and utility services

**Files:**
- Create: `app/Filament/Resources/Tariffs/TariffResource.php`
- Create: `app/Filament/Resources/Tariffs/Schemas/TariffForm.php`
- Create: `app/Filament/Resources/Tariffs/Schemas/TariffTable.php`
- Create: `app/Filament/Resources/Tariffs/Pages/ListTariffs.php`
- Create: `app/Filament/Resources/Tariffs/Pages/CreateTariff.php`
- Create: `app/Filament/Resources/Tariffs/Pages/EditTariff.php`
- Create: `app/Filament/Resources/Tariffs/Pages/ViewTariff.php`
- Create: `app/Filament/Resources/Providers/ProviderResource.php`
- Create: `app/Filament/Resources/Providers/Schemas/ProviderForm.php`
- Create: `app/Filament/Resources/Providers/Schemas/ProviderTable.php`
- Create: `app/Filament/Resources/Providers/Pages/ListProviders.php`
- Create: `app/Filament/Resources/Providers/Pages/CreateProvider.php`
- Create: `app/Filament/Resources/Providers/Pages/EditProvider.php`
- Create: `app/Filament/Resources/Providers/Pages/ViewProvider.php`
- Create: `app/Filament/Resources/ServiceConfigurations/ServiceConfigurationResource.php`
- Create: `app/Filament/Resources/ServiceConfigurations/Schemas/ServiceConfigurationForm.php`
- Create: `app/Filament/Resources/ServiceConfigurations/Schemas/ServiceConfigurationTable.php`
- Create: `app/Filament/Resources/ServiceConfigurations/Pages/ListServiceConfigurations.php`
- Create: `app/Filament/Resources/ServiceConfigurations/Pages/CreateServiceConfiguration.php`
- Create: `app/Filament/Resources/ServiceConfigurations/Pages/EditServiceConfiguration.php`
- Create: `app/Filament/Resources/UtilityServices/UtilityServiceResource.php`
- Create: `app/Filament/Resources/UtilityServices/Schemas/UtilityServiceForm.php`
- Create: `app/Filament/Resources/UtilityServices/Schemas/UtilityServiceTable.php`
- Create: `app/Filament/Resources/UtilityServices/Pages/ListUtilityServices.php`
- Create: `app/Filament/Resources/UtilityServices/Pages/CreateUtilityService.php`
- Create: `app/Filament/Resources/UtilityServices/Pages/EditUtilityService.php`
- Test: `tests/Feature/Admin/TariffsAndProvidersTest.php`

- [ ] **Step 1: Write the failing billing-config tests**

Cover:

- tariff filters and delete restrictions
- provider list and delete restrictions
- service configuration CRUD
- utility service CRUD

- [ ] **Step 2: Run the billing-config tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/TariffsAndProvidersTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement billing configuration resources**

Keep “rate per unit” semantics and unit labels centralized so invoice calculation can consume the same data without duplicate conversion rules.

- [ ] **Step 4: Re-run the billing-config tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/TariffsAndProvidersTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit billing configuration**

Run:

```bash
git add app/Filament/Resources/Tariffs app/Filament/Resources/Providers app/Filament/Resources/ServiceConfigurations app/Filament/Resources/UtilityServices tests/Feature/Admin/TariffsAndProvidersTest.php
git commit -m "feat: add admin billing configuration resources"
```

### Task 10: Implement invoice list/create/view/bulk/payment/email flows

**Files:**
- Create: `app/Support/Admin/Invoices/InvoiceLineItemCalculator.php`
- Create: `app/Support/Admin/Invoices/BulkInvoicePreviewBuilder.php`
- Create: `app/Actions/Admin/Invoices/GenerateInvoiceLineItemsAction.php`
- Create: `app/Actions/Admin/Invoices/SaveInvoiceDraftAction.php`
- Create: `app/Actions/Admin/Invoices/FinalizeInvoiceAction.php`
- Create: `app/Actions/Admin/Invoices/GenerateBulkInvoicesAction.php`
- Create: `app/Actions/Admin/Invoices/RecordInvoicePaymentAction.php`
- Create: `app/Actions/Admin/Invoices/SendInvoiceEmailAction.php`
- Create: `app/Actions/Admin/Invoices/SendInvoiceReminderAction.php`
- Create: `app/Filament/Resources/Invoices/InvoiceResource.php`
- Create: `app/Filament/Resources/Invoices/Schemas/InvoiceForm.php`
- Create: `app/Filament/Resources/Invoices/Schemas/InvoiceTable.php`
- Create: `app/Filament/Resources/Invoices/Pages/ListInvoices.php`
- Create: `app/Filament/Resources/Invoices/Pages/CreateInvoice.php`
- Create: `app/Filament/Resources/Invoices/Pages/EditInvoice.php`
- Create: `app/Filament/Resources/Invoices/Pages/ViewInvoice.php`
- Create: `app/Filament/Pages/GenerateBulkInvoices.php`
- Create: `resources/views/filament/pages/generate-bulk-invoices.blade.php`
- Test: `tests/Feature/Admin/InvoicesResourceTest.php`
- Test: `tests/Feature/Admin/BulkInvoiceGenerationTest.php`

- [ ] **Step 1: Write the failing invoice tests**

Cover:

- invoice quick filters and secondary filters
- draft/finalized/paid/overdue row actions
- single invoice generation with editable line items
- bulk generation preview and result summary
- finalize lock behavior
- payment recording
- email history and reminder sending

- [ ] **Step 2: Run the invoice tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/InvoicesResourceTest.php tests/Feature/Admin/BulkInvoiceGenerationTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement invoice workflows**

Implementation rules:

- finalized invoices must be immutable except for status/payment fields
- calculations must come from tariffs + meter readings through `InvoiceLineItemCalculator`
- bulk generation must skip tenants who already have an invoice for the selected period
- PDF download can initially render a Blade/PDF view using the existing invoice data model; do not hardcode HTML into controller actions

- [ ] **Step 4: Re-run the invoice tests**

Run:

```bash
php artisan test --compact tests/Feature/Admin/InvoicesResourceTest.php tests/Feature/Admin/BulkInvoiceGenerationTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit invoicing**

Run:

```bash
git add app/Support/Admin/Invoices app/Actions/Admin/Invoices app/Filament/Resources/Invoices app/Filament/Pages/GenerateBulkInvoices.php resources/views/filament/pages/generate-bulk-invoices.blade.php tests/Feature/Admin/InvoicesResourceTest.php tests/Feature/Admin/BulkInvoiceGenerationTest.php
git commit -m "feat: add admin invoice generation and billing flows"
```

## Chunk 6: Reports and Verification

### Task 11: Implement the reports page and export flows

**Files:**
- Create: `app/Support/Admin/Reports/ConsumptionReportBuilder.php`
- Create: `app/Support/Admin/Reports/RevenueReportBuilder.php`
- Create: `app/Support/Admin/Reports/OutstandingBalancesReportBuilder.php`
- Create: `app/Support/Admin/Reports/MeterComplianceReportBuilder.php`
- Create: `app/Filament/Pages/Reports.php`
- Create: `resources/views/filament/pages/reports.blade.php`
- Modify: `app/Support/Shell/Navigation/NavigationBuilder.php`
- Test: `tests/Feature/Admin/ReportsPageTest.php`

- [ ] **Step 1: Write the failing reports test**

Cover:

- four report tabs
- date-range filter shared across tabs
- tab-specific filters and result tables
- export actions only visible after data loads

- [ ] **Step 2: Run the reports test**

Run:

```bash
php artisan test --compact tests/Feature/Admin/ReportsPageTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement the reports page**

Implementation rules:

- keep report aggregation in dedicated builder classes
- use session-persisted filter state so list/report filters survive navigation
- export CSV/PDF through the same filtered datasets the page is currently displaying

- [ ] **Step 4: Re-run the reports test**

Run:

```bash
php artisan test --compact tests/Feature/Admin/ReportsPageTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit reports**

Run:

```bash
git add app/Support/Admin/Reports app/Filament/Pages/Reports.php resources/views/filament/pages/reports.blade.php app/Support/Shell/Navigation/NavigationBuilder.php tests/Feature/Admin/ReportsPageTest.php
git commit -m "feat: add admin reports page"
```

### Task 12: Run the full admin verification pass

**Files:**
- Review only: every file touched in Tasks 1-11

- [ ] **Step 1: Run the admin feature suite**

Run:

```bash
php artisan test --compact tests/Feature/Admin
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

- [ ] **Step 5: Verify production assets build**

Run:

```bash
npm run build
```

Expected: PASS.

- [ ] **Step 6: Commit the admin operations rollout**

Run:

```bash
git add app config database lang resources routes tests
git commit -m "feat: deliver admin organization operations"
```

## Execution Notes

- Do not build this before the shared shell plan; otherwise the admin sidebar, topbar, profile routing, and language switcher work will be duplicated.
- Keep all organization scoping in queries, policies, and actions. Never rely on hidden form fields or URL parameters alone.
- Reuse the existing invitation flow for tenant activation rather than creating a second invitation mechanism.
- If any superadmin platform primitives already exist by the time this plan is executed, reuse them instead of duplicating models such as audit logging or language metadata.
