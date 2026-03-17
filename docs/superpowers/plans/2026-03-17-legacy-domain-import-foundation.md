# Legacy Domain Import Foundation Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Additively import the missing `_old` domain foundation into the current Tenanto project without removing or renaming any existing current-project fields or models.

**Architecture:** Build the import in dependency-ordered clusters. First create an explicit ledger that classifies every `_old` top-level model as merge, import, or defer. Then land additive schema and Eloquent support in three waves: reference/platform data, operations/billing support, and collaboration/content support. Keep current models canonical and extend them where `_old` overlaps instead of introducing duplicate replacements.

**Tech Stack:** Laravel 12, PHP 8.5, Eloquent, Pest 4, Filament 5, SQLite for focused tests, Vite only if frontend fixtures are needed.

---

## Chunk 1: Inventory And Reference/Platform Foundation

### Task 1: Build The Legacy Import Ledger

**Files:**
- Create: `docs/superpowers/legacy-domain-import-ledger.md`
- Modify: `docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md`
- Test: `tests/Feature/Admin/LegacyDomainImportLedgerTest.php`

- [ ] **Step 1: Write the failing ledger coverage test**

```php
it('tracks every top-level old model in the legacy import ledger', function () {
    $ledger = file_get_contents(base_path('docs/superpowers/legacy-domain-import-ledger.md'));

    expect($ledger)
        ->toContain('Activity')
        ->toContain('Currency')
        ->toContain('InvoiceItem')
        ->toContain('Translation')
        ->toContain('Task')
        ->toContain('merge')
        ->toContain('import')
        ->toContain('defer');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/LegacyDomainImportLedgerTest.php`
Expected: FAIL because the ledger file does not exist yet.

- [ ] **Step 3: Create the ledger**

Populate `docs/superpowers/legacy-domain-import-ledger.md` with one row per top-level `_old/app/Models/*.php` file and these columns:

- `Legacy Model`
- `Action` (`merge`, `import`, `defer`)
- `Current Target`
- `Missing Schema`
- `Missing Model Support`
- `Follow-Up Notes`

Use these initial classifications:

- Merge into existing current models:
  - `AuditLog`, `Building`, `IntegrationHealthCheck`, `Invoice`, `Language`, `Meter`, `MeterReading`, `Organization`, `OrganizationInvitation`, `PlatformNotification`, `Property`, `SecurityViolation`, `Subscription`, `User`
- Import as new current models:
  - `Activity`, `Attachment`, `BillingRecord`, `Comment`, `CommentReaction`, `Currency`, `DashboardCustomization`, `EnhancedTask`, `ExchangeRate`, `Faq`, `InvoiceGenerationAudit`, `InvoiceItem`, `Lease`, `MeterReadingAudit`, `OrganizationActivityLog`, `OrganizationUser`, `PlatformNotificationRecipient`, `PlatformOrganizationInvitation`, `Project`, `Provider`, `ServiceConfiguration`, `SharedService`, `SubscriptionRenewal`, `SuperAdminAuditLog`, `SystemConfiguration`, `SystemTenant`, `Tag`, `Tariff`, `Task`, `TaskAssignment`, `TimeEntry`, `Translation`, `UtilityReading`, `UtilityService`
- Defer only with rationale if the current repository has no schema/UI dependency yet.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Admin/LegacyDomainImportLedgerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add docs/superpowers/legacy-domain-import-ledger.md docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md tests/Feature/Admin/LegacyDomainImportLedgerTest.php
git commit -m "docs: add legacy domain import ledger"
```

### Task 2: Add Failing Reference/Platform Foundation Coverage

**Files:**
- Create: `tests/Feature/Admin/LegacyReferenceFoundationTest.php`
- Modify: `tests/Feature/Admin/OrganizationDomainFoundationTest.php`
- Test: `tests/Feature/Admin/LegacyReferenceFoundationTest.php`

- [ ] **Step 1: Write the failing schema/model smoke test**

```php
it('creates the legacy reference foundation tables and models', function () {
    expect(Schema::hasTable('currencies'))->toBeTrue()
        ->and(Schema::hasTable('exchange_rates'))->toBeTrue()
        ->and(Schema::hasTable('faqs'))->toBeTrue()
        ->and(Schema::hasTable('translations'))->toBeTrue()
        ->and(Schema::hasTable('providers'))->toBeTrue()
        ->and(Schema::hasTable('tariffs'))->toBeTrue()
        ->and(Schema::hasTable('utility_services'))->toBeTrue()
        ->and(Schema::hasTable('service_configurations'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/LegacyReferenceFoundationTest.php`
Expected: FAIL because these tables/models do not exist yet.

- [ ] **Step 3: Write minimal reference/platform implementation**

Create:

- `app/Models/Currency.php`
- `app/Models/ExchangeRate.php`
- `app/Models/Faq.php`
- `app/Models/Translation.php`
- `app/Models/Provider.php`
- `app/Models/Tariff.php`
- `app/Models/UtilityService.php`
- `app/Models/ServiceConfiguration.php`
- `app/Enums/PricingModel.php`
- `app/Enums/ServiceType.php`
- `app/Enums/TariffType.php`
- `app/Enums/TariffZone.php`
- `app/Enums/WeekendLogic.php`
- `database/factories/CurrencyFactory.php`
- `database/factories/ExchangeRateFactory.php`
- `database/factories/FaqFactory.php`
- `database/factories/ProviderFactory.php`
- `database/factories/TariffFactory.php`
- `database/factories/UtilityServiceFactory.php`
- `database/factories/ServiceConfigurationFactory.php`
- `database/migrations/2026_03_17_120000_create_currencies_table.php`
- `database/migrations/2026_03_17_120100_create_exchange_rates_table.php`
- `database/migrations/2026_03_17_120200_create_faqs_table.php`
- `database/migrations/2026_03_17_120300_create_translations_table.php`
- `database/migrations/2026_03_17_120400_create_providers_table.php`
- `database/migrations/2026_03_17_120500_create_tariffs_table.php`
- `database/migrations/2026_03_17_120600_create_utility_services_table.php`
- `database/migrations/2026_03_17_120700_create_service_configurations_table.php`
- `database/seeders/LegacyReferenceFoundationSeeder.php`

Modify:

- `database/seeders/DatabaseSeeder.php`
- `app/Models/Language.php`
- `app/Models/Organization.php`
- `app/Models/Invoice.php`

Use explicit `->select([...])`, casts, and Eloquent relations only. No raw SQL.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Admin/LegacyReferenceFoundationTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Currency.php app/Models/ExchangeRate.php app/Models/Faq.php app/Models/Translation.php app/Models/Provider.php app/Models/Tariff.php app/Models/UtilityService.php app/Models/ServiceConfiguration.php app/Enums/PricingModel.php app/Enums/ServiceType.php app/Enums/TariffType.php app/Enums/TariffZone.php app/Enums/WeekendLogic.php database/factories/CurrencyFactory.php database/factories/ExchangeRateFactory.php database/factories/FaqFactory.php database/factories/ProviderFactory.php database/factories/TariffFactory.php database/factories/UtilityServiceFactory.php database/factories/ServiceConfigurationFactory.php database/migrations/2026_03_17_120000_create_currencies_table.php database/migrations/2026_03_17_120100_create_exchange_rates_table.php database/migrations/2026_03_17_120200_create_faqs_table.php database/migrations/2026_03_17_120300_create_translations_table.php database/migrations/2026_03_17_120400_create_providers_table.php database/migrations/2026_03_17_120500_create_tariffs_table.php database/migrations/2026_03_17_120600_create_utility_services_table.php database/migrations/2026_03_17_120700_create_service_configurations_table.php database/seeders/LegacyReferenceFoundationSeeder.php database/seeders/DatabaseSeeder.php app/Models/Language.php app/Models/Organization.php app/Models/Invoice.php tests/Feature/Admin/LegacyReferenceFoundationTest.php tests/Feature/Admin/OrganizationDomainFoundationTest.php
git commit -m "feat: import legacy reference foundation"
```

## Chunk 2: Operations And Billing Foundation

### Task 3: Add Failing Operations/Billing Foundation Coverage

**Files:**
- Create: `tests/Feature/Admin/LegacyOperationsFoundationTest.php`
- Modify: `tests/Feature/Admin/OrganizationDomainFoundationTest.php`
- Test: `tests/Feature/Admin/LegacyOperationsFoundationTest.php`

- [ ] **Step 1: Write the failing operations smoke test**

```php
it('creates the legacy operations foundation tables and additive columns', function () {
    expect(Schema::hasTable('invoice_items'))->toBeTrue()
        ->and(Schema::hasTable('invoice_generation_audits'))->toBeTrue()
        ->and(Schema::hasTable('billing_records'))->toBeTrue()
        ->and(Schema::hasTable('meter_reading_audits'))->toBeTrue()
        ->and(Schema::hasTable('leases'))->toBeTrue()
        ->and(Schema::hasTable('subscription_renewals'))->toBeTrue()
        ->and(Schema::hasColumns('users', ['last_login_at']))->toBeTrue()
        ->and(Schema::hasColumns('invoices', ['due_date']))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/LegacyOperationsFoundationTest.php`
Expected: FAIL because the operations support layer is incomplete.

- [ ] **Step 3: Write minimal operations/billing implementation**

Create:

- `app/Models/InvoiceItem.php`
- `app/Models/InvoiceGenerationAudit.php`
- `app/Models/BillingRecord.php`
- `app/Models/MeterReadingAudit.php`
- `app/Models/Lease.php`
- `app/Models/SubscriptionRenewal.php`
- `database/factories/InvoiceItemFactory.php`
- `database/factories/InvoiceGenerationAuditFactory.php`
- `database/factories/BillingRecordFactory.php`
- `database/factories/MeterReadingAuditFactory.php`
- `database/factories/LeaseFactory.php`
- `database/factories/SubscriptionRenewalFactory.php`
- `database/migrations/2026_03_17_121000_create_invoice_items_table.php`
- `database/migrations/2026_03_17_121100_create_invoice_generation_audits_table.php`
- `database/migrations/2026_03_17_121200_create_billing_records_table.php`
- `database/migrations/2026_03_17_121300_create_meter_reading_audits_table.php`
- `database/migrations/2026_03_17_121400_create_leases_table.php`
- `database/migrations/2026_03_17_121500_create_subscription_renewals_table.php`
- `database/migrations/2026_03_17_121600_add_legacy_billing_fields_to_invoices_table.php`
- `database/migrations/2026_03_17_121700_add_legacy_support_fields_to_users_table.php`
- `database/seeders/LegacyOperationsFoundationSeeder.php`

Modify:

- `app/Models/Invoice.php`
- `app/Models/MeterReading.php`
- `app/Models/Subscription.php`
- `app/Models/User.php`

Preserve all current fields and add only missing legacy-compatible columns and relations.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Admin/LegacyOperationsFoundationTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/InvoiceItem.php app/Models/InvoiceGenerationAudit.php app/Models/BillingRecord.php app/Models/MeterReadingAudit.php app/Models/Lease.php app/Models/SubscriptionRenewal.php database/factories/InvoiceItemFactory.php database/factories/InvoiceGenerationAuditFactory.php database/factories/BillingRecordFactory.php database/factories/MeterReadingAuditFactory.php database/factories/LeaseFactory.php database/factories/SubscriptionRenewalFactory.php database/migrations/2026_03_17_121000_create_invoice_items_table.php database/migrations/2026_03_17_121100_create_invoice_generation_audits_table.php database/migrations/2026_03_17_121200_create_billing_records_table.php database/migrations/2026_03_17_121300_create_meter_reading_audits_table.php database/migrations/2026_03_17_121400_create_leases_table.php database/migrations/2026_03_17_121500_create_subscription_renewals_table.php database/migrations/2026_03_17_121600_add_legacy_billing_fields_to_invoices_table.php database/migrations/2026_03_17_121700_add_legacy_support_fields_to_users_table.php database/seeders/LegacyOperationsFoundationSeeder.php app/Models/Invoice.php app/Models/MeterReading.php app/Models/Subscription.php app/Models/User.php tests/Feature/Admin/LegacyOperationsFoundationTest.php tests/Feature/Admin/OrganizationDomainFoundationTest.php
git commit -m "feat: import legacy operations foundation"
```

### Task 4: Add Failing Platform Support Foundation Coverage

**Files:**
- Create: `tests/Feature/Admin/LegacyPlatformFoundationTest.php`
- Test: `tests/Feature/Admin/LegacyPlatformFoundationTest.php`

- [ ] **Step 1: Write the failing platform support test**

```php
it('creates the legacy platform support tables and models', function () {
    expect(Schema::hasTable('system_tenants'))->toBeTrue()
        ->and(Schema::hasTable('system_configurations'))->toBeTrue()
        ->and(Schema::hasTable('platform_notification_recipients'))->toBeTrue()
        ->and(Schema::hasTable('platform_organization_invitations'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/LegacyPlatformFoundationTest.php`
Expected: FAIL because these support tables are absent.

- [ ] **Step 3: Write minimal platform support implementation**

Create:

- `app/Models/SystemTenant.php`
- `app/Models/SystemConfiguration.php`
- `app/Models/PlatformNotificationRecipient.php`
- `app/Models/PlatformOrganizationInvitation.php`
- `database/factories/SystemTenantFactory.php`
- `database/factories/SystemConfigurationFactory.php`
- `database/factories/PlatformNotificationRecipientFactory.php`
- `database/factories/PlatformOrganizationInvitationFactory.php`
- `database/migrations/2026_03_17_121800_create_system_tenants_table.php`
- `database/migrations/2026_03_17_121900_create_system_configurations_table.php`
- `database/migrations/2026_03_17_122000_create_platform_notification_recipients_table.php`
- `database/migrations/2026_03_17_122100_create_platform_organization_invitations_table.php`
- `database/seeders/LegacyPlatformFoundationSeeder.php`

Modify:

- `app/Models/PlatformNotification.php`
- `app/Models/Organization.php`
- `app/Models/User.php`
- `database/seeders/DatabaseSeeder.php`

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Admin/LegacyPlatformFoundationTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/SystemTenant.php app/Models/SystemConfiguration.php app/Models/PlatformNotificationRecipient.php app/Models/PlatformOrganizationInvitation.php database/factories/SystemTenantFactory.php database/factories/SystemConfigurationFactory.php database/factories/PlatformNotificationRecipientFactory.php database/factories/PlatformOrganizationInvitationFactory.php database/migrations/2026_03_17_121800_create_system_tenants_table.php database/migrations/2026_03_17_121900_create_system_configurations_table.php database/migrations/2026_03_17_122000_create_platform_notification_recipients_table.php database/migrations/2026_03_17_122100_create_platform_organization_invitations_table.php database/seeders/LegacyPlatformFoundationSeeder.php app/Models/PlatformNotification.php app/Models/Organization.php app/Models/User.php database/seeders/DatabaseSeeder.php tests/Feature/Admin/LegacyPlatformFoundationTest.php
git commit -m "feat: import legacy platform support foundation"
```

## Chunk 3: Collaboration/Content Foundation And Verification

### Task 5: Add Failing Collaboration/Content Foundation Coverage

**Files:**
- Create: `tests/Feature/Admin/LegacyCollaborationFoundationTest.php`
- Test: `tests/Feature/Admin/LegacyCollaborationFoundationTest.php`

- [ ] **Step 1: Write the failing collaboration/content test**

```php
it('creates the legacy collaboration and content foundation tables', function () {
    expect(Schema::hasTable('projects'))->toBeTrue()
        ->and(Schema::hasTable('tasks'))->toBeTrue()
        ->and(Schema::hasTable('task_assignments'))->toBeTrue()
        ->and(Schema::hasTable('comments'))->toBeTrue()
        ->and(Schema::hasTable('attachments'))->toBeTrue()
        ->and(Schema::hasTable('tags'))->toBeTrue()
        ->and(Schema::hasTable('activities'))->toBeTrue()
        ->and(Schema::hasTable('dashboard_customizations'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/LegacyCollaborationFoundationTest.php`
Expected: FAIL because these collaboration/content tables are absent.

- [ ] **Step 3: Write minimal collaboration/content implementation**

Create:

- `app/Models/Project.php`
- `app/Models/Task.php`
- `app/Models/TaskAssignment.php`
- `app/Models/EnhancedTask.php`
- `app/Models/Comment.php`
- `app/Models/CommentReaction.php`
- `app/Models/Attachment.php`
- `app/Models/Tag.php`
- `app/Models/Activity.php`
- `app/Models/DashboardCustomization.php`
- `app/Models/TimeEntry.php`
- `database/factories/ProjectFactory.php`
- `database/factories/TaskFactory.php`
- `database/factories/TaskAssignmentFactory.php`
- `database/factories/EnhancedTaskFactory.php`
- `database/factories/CommentFactory.php`
- `database/factories/CommentReactionFactory.php`
- `database/factories/AttachmentFactory.php`
- `database/factories/TagFactory.php`
- `database/factories/ActivityFactory.php`
- `database/factories/DashboardCustomizationFactory.php`
- `database/factories/TimeEntryFactory.php`
- `database/migrations/2026_03_17_122200_create_projects_table.php`
- `database/migrations/2026_03_17_122300_create_tasks_table.php`
- `database/migrations/2026_03_17_122400_create_task_assignments_table.php`
- `database/migrations/2026_03_17_122500_create_enhanced_tasks_table.php`
- `database/migrations/2026_03_17_122600_create_comments_table.php`
- `database/migrations/2026_03_17_122700_create_comment_reactions_table.php`
- `database/migrations/2026_03_17_122800_create_attachments_table.php`
- `database/migrations/2026_03_17_122900_create_tags_and_taggables_table.php`
- `database/migrations/2026_03_17_123000_create_activities_table.php`
- `database/migrations/2026_03_17_123100_create_dashboard_customizations_table.php`
- `database/migrations/2026_03_17_123200_create_time_entries_table.php`
- `database/seeders/LegacyCollaborationFoundationSeeder.php`

Modify:

- `database/seeders/DatabaseSeeder.php`
- `app/Models/User.php`
- `app/Models/Organization.php`

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Admin/LegacyCollaborationFoundationTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Project.php app/Models/Task.php app/Models/TaskAssignment.php app/Models/EnhancedTask.php app/Models/Comment.php app/Models/CommentReaction.php app/Models/Attachment.php app/Models/Tag.php app/Models/Activity.php app/Models/DashboardCustomization.php app/Models/TimeEntry.php database/factories/ProjectFactory.php database/factories/TaskFactory.php database/factories/TaskAssignmentFactory.php database/factories/EnhancedTaskFactory.php database/factories/CommentFactory.php database/factories/CommentReactionFactory.php database/factories/AttachmentFactory.php database/factories/TagFactory.php database/factories/ActivityFactory.php database/factories/DashboardCustomizationFactory.php database/factories/TimeEntryFactory.php database/migrations/2026_03_17_122200_create_projects_table.php database/migrations/2026_03_17_122300_create_tasks_table.php database/migrations/2026_03_17_122400_create_task_assignments_table.php database/migrations/2026_03_17_122500_create_enhanced_tasks_table.php database/migrations/2026_03_17_122600_create_comments_table.php database/migrations/2026_03_17_122700_create_comment_reactions_table.php database/migrations/2026_03_17_122800_create_attachments_table.php database/migrations/2026_03_17_122900_create_tags_and_taggables_table.php database/migrations/2026_03_17_123000_create_activities_table.php database/migrations/2026_03_17_123100_create_dashboard_customizations_table.php database/migrations/2026_03_17_123200_create_time_entries_table.php database/seeders/LegacyCollaborationFoundationSeeder.php database/seeders/DatabaseSeeder.php app/Models/User.php app/Models/Organization.php tests/Feature/Admin/LegacyCollaborationFoundationTest.php
git commit -m "feat: import legacy collaboration foundation"
```

### Task 6: Finalize Foundation Verification

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `tests/Feature/Admin/OrganizationDomainFoundationTest.php`
- Create: `tests/Feature/Admin/LegacyDomainImportFoundationTest.php`
- Test: `tests/Feature/Auth/LoginFlowTest.php`
- Test: `tests/Feature/Admin/AdminDashboardTest.php`
- Test: `tests/Feature/Tenant`

- [ ] **Step 1: Write the final failing verification test**

```php
it('boots the additive legacy domain foundation without breaking current auth, admin, and tenant flows', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    expect(Schema::hasTable('currencies'))->toBeTrue()
        ->and(Schema::hasTable('projects'))->toBeTrue()
        ->and(Schema::hasTable('system_tenants'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Admin/LegacyDomainImportFoundationTest.php`
Expected: FAIL until all previous chunks are wired into the default seed and migration flow.

- [ ] **Step 3: Wire the default foundation and clean up**

Ensure `database/seeders/DatabaseSeeder.php` calls:

- `LanguageSeeder::class`
- `SystemSettingSeeder::class`
- `IntegrationHealthCheckSeeder::class`
- `LoginDemoUsersSeeder::class`
- `LegacyReferenceFoundationSeeder::class`
- `LegacyOperationsFoundationSeeder::class`
- `LegacyPlatformFoundationSeeder::class`
- `LegacyCollaborationFoundationSeeder::class`

Reconcile any overlapping helper classes, remove abandoned placeholders, and keep current login/demo/auth behavior green.

- [ ] **Step 4: Run the final verification suite**

Run:

```bash
php artisan migrate:fresh --seed --force
php artisan test --compact tests/Feature/Admin/LegacyDomainImportLedgerTest.php tests/Feature/Admin/LegacyReferenceFoundationTest.php tests/Feature/Admin/LegacyOperationsFoundationTest.php tests/Feature/Admin/LegacyPlatformFoundationTest.php tests/Feature/Admin/LegacyCollaborationFoundationTest.php tests/Feature/Admin/LegacyDomainImportFoundationTest.php tests/Feature/Admin/OrganizationDomainFoundationTest.php tests/Feature/Auth/LoginFlowTest.php tests/Feature/Admin/AdminDashboardTest.php
vendor/bin/pint --dirty
```

Expected: all listed tests PASS and `pint` reports `pass`.

- [ ] **Step 5: Commit**

```bash
git add app/Models app/Enums database/factories database/migrations database/seeders tests/Feature/Admin docs/superpowers/legacy-domain-import-ledger.md
git commit -m "feat: complete additive legacy domain foundation"
```

