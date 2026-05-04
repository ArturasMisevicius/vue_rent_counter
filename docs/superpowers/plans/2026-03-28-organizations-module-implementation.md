# Organizations Module Implementation Plan

> **AI agent usage:** This is an execution plan, not proof of current implementation. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify every referenced file, command, route, schema, and test before acting.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the superadmin Organizations control plane so Tenanto can manage organization lifecycle, billing/support operations, exports, impersonation, audit/security visibility, and org-scoped operational dashboards from one coherent module.

**Architecture:** Build on the existing `OrganizationResource` and related support/action classes instead of rewriting the module. Land the work in ordered waves: first normalize the list/query contracts and shared data builders, then add support mutations and queued side effects, then harden audit/impersonation/security behavior, and finally expand the org detail page widgets and compliance surfaces.

**Tech Stack:** Laravel 13, Filament 5, Blade views, Eloquent models/scopes, Pest feature tests, queued jobs, notifications, cached dashboard data builders.

---

## File Structure

### Existing files to extend

- `app/Models/Organization.php`
- `app/Models/Subscription.php`
- `app/Models/User.php`
- `app/Models/SecurityViolation.php`
- `app/Filament/Resources/Organizations/OrganizationResource.php`
- `app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`
- `app/Filament/Resources/Organizations/Pages/ListOrganizations.php`
- `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- `app/Filament/Resources/Organizations/RelationManagers/UsersRelationManager.php`
- `app/Filament/Resources/Organizations/RelationManagers/ActivityLogsRelationManager.php`
- `app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php`
- `app/Filament/Actions/Superadmin/Organizations/ExportOrganizationsSummaryAction.php`
- `app/Filament/Actions/Superadmin/Organizations/SendOrganizationNotificationAction.php`
- `app/Filament/Actions/Superadmin/Organizations/SuspendOrganizationAction.php`
- `app/Filament/Actions/Superadmin/Organizations/ReinstateOrganizationAction.php`
- `app/Filament/Actions/Superadmin/Organizations/StartOrganizationImpersonationAction.php`
- `app/Filament/Support/Audit/AuditLogger.php`
- `app/Filament/Support/Superadmin/Usage/OrganizationUsageReader.php`
- `app/Filament/Support/Superadmin/Usage/OrganizationUsageSnapshot.php`
- `app/Filament/Support/Superadmin/Integration/IntegrationHealthPageData.php`
- `app/Services/ImpersonationService.php`
- `app/Filament/Support/Auth/ImpersonationManager.php`
- `app/Notifications/Superadmin/OrganizationBroadcastNotification.php`
- `lang/en/superadmin.php`
- `lang/lt/superadmin.php`
- `lang/ru/superadmin.php`
- `lang/es/superadmin.php`

### New files to create

- `app/Models/OrganizationLimitOverride.php`
- `app/Models/OrganizationFeatureOverride.php`
- `app/Models/OrganizationInvoiceWriteOff.php`
- `app/Models/OrganizationSupportNote.php`
- `app/Jobs/Superadmin/Organizations/SendOrganizationAnnouncementJob.php`
- `app/Jobs/Superadmin/Organizations/GenerateOrganizationDataExportJob.php`
- `app/Jobs/Superadmin/Organizations/ExpireOrganizationLimitOverridesJob.php`
- `app/Filament/Actions/Superadmin/Organizations/ForceOrganizationPlanChangeAction.php`
- `app/Filament/Actions/Superadmin/Organizations/TransferOrganizationOwnershipAction.php`
- `app/Filament/Actions/Superadmin/Organizations/OverrideOrganizationLimitsAction.php`
- `app/Filament/Actions/Superadmin/Organizations/ToggleOrganizationFeatureAction.php`
- `app/Filament/Actions/Superadmin/Organizations/WriteOffOrganizationInvoicesAction.php`
- `app/Filament/Actions/Superadmin/Organizations/QueueOrganizationDataExportAction.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationListQuery.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationListPresetRegistry.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationMrrResolver.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationDashboardData.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationFinancialSnapshot.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationPortfolioSnapshot.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationSecuritySnapshot.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationIntegrationSnapshot.php`
- `app/Filament/Support/Superadmin/Organizations/OrganizationSubscriptionSnapshot.php`
- `app/Filament/Support/Audit/ImpersonationAuditContext.php`
- `app/Policies/OrganizationLimitOverridePolicy.php`
- `app/Policies/OrganizationFeatureOverridePolicy.php`
- `database/migrations/*_create_organization_limit_overrides_table.php`
- `database/migrations/*_create_organization_feature_overrides_table.php`
- `database/migrations/*_create_organization_invoice_write_offs_table.php`
- `database/migrations/*_create_organization_support_notes_table.php`
- `database/factories/OrganizationLimitOverrideFactory.php`
- `database/factories/OrganizationFeatureOverrideFactory.php`
- `database/factories/OrganizationInvoiceWriteOffFactory.php`
- `tests/Feature/Superadmin/OrganizationListPresetsTest.php`
- `tests/Feature/Superadmin/OrganizationExportsTest.php`
- `tests/Feature/Superadmin/OrganizationAnnouncementsTest.php`
- `tests/Feature/Superadmin/OrganizationOwnershipTransferTest.php`
- `tests/Feature/Superadmin/OrganizationPlanChangeTest.php`
- `tests/Feature/Superadmin/OrganizationLimitOverridesTest.php`
- `tests/Feature/Superadmin/OrganizationFeatureFlagsTest.php`
- `tests/Feature/Superadmin/OrganizationWriteOffsTest.php`
- `tests/Feature/Superadmin/OrganizationImpersonationAuditTest.php`
- `tests/Feature/Superadmin/OrganizationSecurityHealthTest.php`
- `tests/Feature/Superadmin/OrganizationIntegrationHealthTest.php`

### Responsibility boundaries

- Keep table/query/filter logic in `app/Filament/Support/Superadmin/Organizations/*`, not embedded directly in Filament closures.
- Keep write operations in `app/Filament/Actions/Superadmin/Organizations/*`.
- Keep per-org dashboard widget aggregation in `OrganizationDashboardData` and focused snapshot builders.
- Keep impersonation attribution out of random callers by centralizing it in audit support.

### Wave order

1. Normalize organization list query + table/export contracts.
2. Add support-side queued announcements and data export dispatch.
3. Add lifecycle/support mutations: plan changes, ownership transfer, limit overrides, feature flags, invoice write-offs.
4. Harden impersonation + audit + security rules.
5. Expand org detail page widgets and fresh support tables.

## Task 1: Normalize Organization List Query And MRR Contract

**Files:**
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationMrrResolver.php`
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationListQuery.php`
- Modify: `app/Models/Organization.php`
- Modify: `app/Models/Subscription.php`
- Modify: `app/Filament/Resources/Organizations/OrganizationResource.php`
- Test: `tests/Feature/Superadmin/OrganizationsListPageTest.php`

- [ ] **Step 1: Write the failing list-query tests**

```php
it('shows users count and mrr in the organizations list', function () {
    Livewire::test(ListOrganizations::class)
        ->assertTableColumnExists('users_count')
        ->assertTableColumnExists('mrr_display');
});
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test --filter=OrganizationsListPageTest`
Expected: FAIL because `users_count` / `mrr_display` are not available in the current table contract.

- [ ] **Step 3: Introduce the shared query and resolver**

```php
final class OrganizationMrrResolver
{
    public function monthlyAmountFor(Organization $organization): float
    {
        // Resolve latest active subscription charge and normalize to monthly.
    }
}
```

```php
final class OrganizationListQuery
{
    public function build(): Builder
    {
        return Organization::query()
            ->forSuperadminControlPlane()
            ->withCount('users');
    }
}
```

- [ ] **Step 4: Wire the resource and model scopes to the new contract**

Run: `php artisan test --filter=OrganizationsListPageTest`
Expected: PASS for the new list-query assertions.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Support/Superadmin/Organizations/OrganizationMrrResolver.php \
  app/Filament/Support/Superadmin/Organizations/OrganizationListQuery.php \
  app/Models/Organization.php app/Models/Subscription.php \
  app/Filament/Resources/Organizations/OrganizationResource.php \
  tests/Feature/Superadmin/OrganizationsListPageTest.php
git commit -m "feat: normalize organization list query and mrr"
```

## Task 2: Expand Superadmin List Table Columns, Filters, Search, And Presets

**Files:**
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationListPresetRegistry.php`
- Modify: `app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`
- Modify: `lang/en/superadmin.php`
- Modify: `lang/lt/superadmin.php`
- Modify: `lang/ru/superadmin.php`
- Modify: `lang/es/superadmin.php`
- Test: `tests/Feature/Superadmin/OrganizationsListPageTest.php`
- Test: `tests/Feature/Superadmin/OrganizationListPresetsTest.php`

- [ ] **Step 1: Write failing tests for columns, filters, presets, and search**

```php
it('supports status, plan, overdue, and security filters', function () {
    Livewire::test(ListOrganizations::class)
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('has_overdue_invoices')
        ->assertTableFilterExists('has_security_violations');
});
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test --filter=OrganizationsListPageTest --filter=OrganizationListPresetsTest`
Expected: FAIL because the filters/presets do not exist yet.

- [ ] **Step 3: Implement the table contract**

```php
TextColumn::make('mrr_display')->label(__('superadmin.organizations.columns.mrr'));
Filter::make('trial_expiry_range');
Filter::make('has_overdue_invoices');
Filter::make('has_security_violations');
```

```php
final class OrganizationListPresetRegistry
{
    public static function presets(): array
    {
        return [
            'overdue_orgs' => [...],
            'expiring_trials' => [...],
            'high_value' => [...],
            'new_this_month' => [...],
        ];
    }
}
```

- [ ] **Step 4: Verify list behavior and translations**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsListPageTest.php tests/Feature/Superadmin/OrganizationListPresetsTest.php`
Expected: PASS, including default sort and row-color assertions.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Support/Superadmin/Organizations/OrganizationListPresetRegistry.php \
  app/Filament/Resources/Organizations/Tables/OrganizationsTable.php \
  lang/en/superadmin.php lang/lt/superadmin.php lang/ru/superadmin.php lang/es/superadmin.php \
  tests/Feature/Superadmin/OrganizationsListPageTest.php tests/Feature/Superadmin/OrganizationListPresetsTest.php
git commit -m "feat: expand organizations list operations view"
```

## Task 3: Fix CSV Export To Match Selected Rows, Visible Columns, And Filter State

**Files:**
- Modify: `app/Filament/Actions/Superadmin/Organizations/ExportOrganizationsSummaryAction.php`
- Modify: `app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`
- Modify: `app/Filament/Support/Superadmin/Organizations/OrganizationListQuery.php`
- Test: `tests/Feature/Superadmin/OrganizationExportsTest.php`

- [ ] **Step 1: Write a failing export test**

```php
it('exports the selected organizations with visible summary columns', function () {
    expect($csv)->toContain('Name,Email,Status,Plan,Properties,Users,MRR,Created at');
});
```

- [ ] **Step 2: Run the export test**

Run: `php artisan test tests/Feature/Superadmin/OrganizationExportsTest.php`
Expected: FAIL because the export still writes the old fixed header and wrong counts.

- [ ] **Step 3: Rebuild the export action around the list contract**

```php
public function handle(Collection $organizations, array $visibleColumns = []): string
{
    // Map visible column keys to export headers and row values.
}
```

- [ ] **Step 4: Verify the CSV output**

Run: `php artisan test tests/Feature/Superadmin/OrganizationExportsTest.php`
Expected: PASS with `properties_count`, `users_count`, and `MRR` exported from the same query contract as the table.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Actions/Superadmin/Organizations/ExportOrganizationsSummaryAction.php \
  app/Filament/Resources/Organizations/Tables/OrganizationsTable.php \
  app/Filament/Support/Superadmin/Organizations/OrganizationListQuery.php \
  tests/Feature/Superadmin/OrganizationExportsTest.php
git commit -m "feat: align organization csv export with list contract"
```

## Task 4: Queue Organization Announcements Instead Of Sending Inline

**Files:**
- Create: `app/Jobs/Superadmin/Organizations/SendOrganizationAnnouncementJob.php`
- Modify: `app/Filament/Actions/Superadmin/Organizations/SendOrganizationNotificationAction.php`
- Modify: `app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`
- Modify: `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- Modify: `app/Notifications/Superadmin/OrganizationBroadcastNotification.php`
- Test: `tests/Feature/Superadmin/OrganizationAnnouncementsTest.php`

- [ ] **Step 1: Write the failing queued-announcement tests**

```php
it('queues org announcements instead of notifying inline', function () {
    Queue::fake();

    app(SendOrganizationNotificationAction::class)->handle($organization, 'Title', 'Body', 'warning');

    Queue::assertPushed(SendOrganizationAnnouncementJob::class);
});
```

- [ ] **Step 2: Run the test to see it fail**

Run: `php artisan test tests/Feature/Superadmin/OrganizationAnnouncementsTest.php`
Expected: FAIL because notifications are currently chunked and sent inside the request.

- [ ] **Step 3: Implement the job handoff**

```php
final class SendOrganizationAnnouncementJob implements ShouldQueue
{
    public function handle(): void
    {
        // Load org users in chunks and notify them.
    }
}
```

- [ ] **Step 4: Verify queue handoff and notification payload**

Run: `php artisan test tests/Feature/Superadmin/OrganizationAnnouncementsTest.php`
Expected: PASS with queue assertions and notification content checks.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/Superadmin/Organizations/SendOrganizationAnnouncementJob.php \
  app/Filament/Actions/Superadmin/Organizations/SendOrganizationNotificationAction.php \
  app/Filament/Resources/Organizations/Tables/OrganizationsTable.php \
  app/Filament/Resources/Organizations/Pages/ViewOrganization.php \
  app/Notifications/Superadmin/OrganizationBroadcastNotification.php \
  tests/Feature/Superadmin/OrganizationAnnouncementsTest.php
git commit -m "feat: queue organization announcements"
```

## Task 5: Add Support-Triggered GDPR Data Export Dispatch

**Files:**
- Create: `app/Jobs/Superadmin/Organizations/GenerateOrganizationDataExportJob.php`
- Create: `app/Filament/Actions/Superadmin/Organizations/QueueOrganizationDataExportAction.php`
- Modify: `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- Modify: `app/Filament/Actions/Superadmin/Organizations/ExportOrganizationDataAction.php`
- Test: `tests/Feature/Superadmin/OrganizationExportsTest.php`

- [ ] **Step 1: Add a failing support-export test**

```php
it('queues a gdpr export for the org owner', function () {
    Queue::fake();

    app(QueueOrganizationDataExportAction::class)->handle($organization, 'Support request');

    Queue::assertPushed(GenerateOrganizationDataExportJob::class);
});
```

- [ ] **Step 2: Run the failing test**

Run: `php artisan test tests/Feature/Superadmin/OrganizationExportsTest.php`
Expected: FAIL because the org export is still synchronous download-only.

- [ ] **Step 3: Create the queue-oriented action and job**

```php
final class QueueOrganizationDataExportAction
{
    public function handle(Organization $organization, string $reason): void
    {
        GenerateOrganizationDataExportJob::dispatch($organization->id, $reason, auth()->id());
    }
}
```

- [ ] **Step 4: Verify queueing and owner-delivery behavior**

Run: `php artisan test tests/Feature/Superadmin/OrganizationExportsTest.php`
Expected: PASS with queued job + audit assertions.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/Superadmin/Organizations/GenerateOrganizationDataExportJob.php \
  app/Filament/Actions/Superadmin/Organizations/QueueOrganizationDataExportAction.php \
  app/Filament/Resources/Organizations/Pages/ViewOrganization.php \
  app/Filament/Actions/Superadmin/Organizations/ExportOrganizationDataAction.php \
  tests/Feature/Superadmin/OrganizationExportsTest.php
git commit -m "feat: queue support-triggered organization exports"
```

## Task 6: Add Force Plan Change And Ownership Transfer Support Actions

**Files:**
- Create: `app/Filament/Actions/Superadmin/Organizations/ForceOrganizationPlanChangeAction.php`
- Create: `app/Filament/Actions/Superadmin/Organizations/TransferOrganizationOwnershipAction.php`
- Modify: `app/Models/Organization.php`
- Modify: `app/Models/Subscription.php`
- Modify: `app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`
- Modify: `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- Test: `tests/Feature/Superadmin/OrganizationPlanChangeTest.php`
- Test: `tests/Feature/Superadmin/OrganizationOwnershipTransferTest.php`

- [ ] **Step 1: Write failing tests for plan guardrails and ownership transfer**

```php
it('blocks force plan change when org usage exceeds the target plan', function () {
    expect(fn () => app(ForceOrganizationPlanChangeAction::class)->handle(...))->toThrow(ValidationException::class);
});
```

```php
it('transfers ownership to a verified user in the same org', function () {
    expect($organization->fresh()->owner_user_id)->toBe($newOwner->id);
});
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationPlanChangeTest.php tests/Feature/Superadmin/OrganizationOwnershipTransferTest.php`
Expected: FAIL because the actions do not exist yet.

- [ ] **Step 3: Implement the minimal control-plane actions**

```php
final class ForceOrganizationPlanChangeAction
{
    public function handle(Organization $organization, SubscriptionPlan $plan, string $reason): Subscription
    {
        // Read current usage, validate target limits, update current subscription and snapshots.
    }
}
```

```php
final class TransferOrganizationOwnershipAction
{
    public function handle(Organization $organization, User $newOwner, string $reason): Organization
    {
        // Validate membership, flip owner, notify old/new owner, audit.
    }
}
```

- [ ] **Step 4: Verify mutations and notifications**

Run: `php artisan test tests/Feature/Superadmin/OrganizationPlanChangeTest.php tests/Feature/Superadmin/OrganizationOwnershipTransferTest.php`
Expected: PASS with audit and notification assertions.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Actions/Superadmin/Organizations/ForceOrganizationPlanChangeAction.php \
  app/Filament/Actions/Superadmin/Organizations/TransferOrganizationOwnershipAction.php \
  app/Models/Organization.php app/Models/Subscription.php \
  app/Filament/Resources/Organizations/Tables/OrganizationsTable.php \
  app/Filament/Resources/Organizations/Pages/ViewOrganization.php \
  tests/Feature/Superadmin/OrganizationPlanChangeTest.php \
  tests/Feature/Superadmin/OrganizationOwnershipTransferTest.php
git commit -m "feat: add organization plan change and ownership transfer"
```

## Task 7: Implement Limit Overrides And Feature Flags

**Files:**
- Create: `database/migrations/*_create_organization_limit_overrides_table.php`
- Create: `database/migrations/*_create_organization_feature_overrides_table.php`
- Create: `app/Models/OrganizationLimitOverride.php`
- Create: `app/Models/OrganizationFeatureOverride.php`
- Create: `app/Filament/Actions/Superadmin/Organizations/OverrideOrganizationLimitsAction.php`
- Create: `app/Filament/Actions/Superadmin/Organizations/ToggleOrganizationFeatureAction.php`
- Create: `app/Jobs/Superadmin/Organizations/ExpireOrganizationLimitOverridesJob.php`
- Create: `database/factories/OrganizationLimitOverrideFactory.php`
- Create: `database/factories/OrganizationFeatureOverrideFactory.php`
- Modify: `app/Models/Organization.php`
- Modify: `app/Models/Subscription.php`
- Test: `tests/Feature/Superadmin/OrganizationLimitOverridesTest.php`
- Test: `tests/Feature/Superadmin/OrganizationFeatureFlagsTest.php`

- [ ] **Step 1: Write failing tests for overrides and expiry**

```php
it('prefers an active limit override over the subscription snapshot', function () {
    expect($organization->effectivePropertyLimit())->toBe(99);
});
```

```php
it('expires org limit overrides and reverts to subscription limits', function () {
    // run the expiry job and assert reversion
});
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationLimitOverridesTest.php tests/Feature/Superadmin/OrganizationFeatureFlagsTest.php`
Expected: FAIL because no override model or resolution logic exists.

- [ ] **Step 3: Add the tables, models, and resolution methods**

```php
final class OrganizationLimitOverride extends Model
{
    protected $fillable = ['organization_id', 'dimension', 'value', 'reason', 'expires_at', 'created_by'];
}
```

```php
public function effectivePropertyLimit(): int
{
    return $this->activePropertyOverride()?->value ?? $this->currentSubscription?->propertyLimit() ?? 0;
}
```

- [ ] **Step 4: Verify action behavior and automatic expiry**

Run: `php artisan test tests/Feature/Superadmin/OrganizationLimitOverridesTest.php tests/Feature/Superadmin/OrganizationFeatureFlagsTest.php`
Expected: PASS, including audit checks and expiry reversion.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/*organization_limit_overrides* \
  database/migrations/*organization_feature_overrides* \
  app/Models/OrganizationLimitOverride.php app/Models/OrganizationFeatureOverride.php \
  app/Filament/Actions/Superadmin/Organizations/OverrideOrganizationLimitsAction.php \
  app/Filament/Actions/Superadmin/Organizations/ToggleOrganizationFeatureAction.php \
  app/Jobs/Superadmin/Organizations/ExpireOrganizationLimitOverridesJob.php \
  database/factories/OrganizationLimitOverrideFactory.php database/factories/OrganizationFeatureOverrideFactory.php \
  app/Models/Organization.php app/Models/Subscription.php \
  tests/Feature/Superadmin/OrganizationLimitOverridesTest.php tests/Feature/Superadmin/OrganizationFeatureFlagsTest.php
git commit -m "feat: add organization limit overrides and feature flags"
```

## Task 8: Add Invoice Write-Off Workflow And Deletion Guard

**Files:**
- Create: `database/migrations/*_create_organization_invoice_write_offs_table.php`
- Create: `app/Models/OrganizationInvoiceWriteOff.php`
- Create: `database/factories/OrganizationInvoiceWriteOffFactory.php`
- Create: `app/Filament/Actions/Superadmin/Organizations/WriteOffOrganizationInvoicesAction.php`
- Modify: `app/Models/Invoice.php`
- Modify: `app/Filament/Actions/Superadmin/Organizations/ExportOrganizationDataAction.php`
- Modify: `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- Test: `tests/Feature/Superadmin/OrganizationWriteOffsTest.php`

- [ ] **Step 1: Write the failing write-off tests**

```php
it('requires a reason note before writing off outstanding invoices', function () {
    expect(fn () => app(WriteOffOrganizationInvoicesAction::class)->handle($organization, ''))->toThrow(ValidationException::class);
});
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationWriteOffsTest.php`
Expected: FAIL because the write-off workflow does not exist yet.

- [ ] **Step 3: Add the write-off record and invoice mutation path**

```php
final class WriteOffOrganizationInvoicesAction
{
    public function handle(Organization $organization, string $reason): int
    {
        // Create write-off rows and mark invoices as written off without deleting them.
    }
}
```

- [ ] **Step 4: Verify audit trail and deletion guard behavior**

Run: `php artisan test tests/Feature/Superadmin/OrganizationWriteOffsTest.php`
Expected: PASS with invoice-state, write-off-record, and audit assertions.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/*organization_invoice_write_offs* \
  app/Models/OrganizationInvoiceWriteOff.php database/factories/OrganizationInvoiceWriteOffFactory.php \
  app/Filament/Actions/Superadmin/Organizations/WriteOffOrganizationInvoicesAction.php \
  app/Models/Invoice.php app/Filament/Resources/Organizations/Pages/ViewOrganization.php \
  tests/Feature/Superadmin/OrganizationWriteOffsTest.php
git commit -m "feat: add organization invoice write-off workflow"
```

## Task 9: Harden Impersonation TTL, Incident Guard, And Dual Audit Attribution

**Files:**
- Create: `app/Filament/Support/Audit/ImpersonationAuditContext.php`
- Modify: `app/Services/ImpersonationService.php`
- Modify: `app/Filament/Support/Auth/ImpersonationManager.php`
- Modify: `app/Filament/Support/Audit/AuditLogger.php`
- Modify: `app/Filament/Actions/Superadmin/Organizations/StartOrganizationImpersonationAction.php`
- Modify: `app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`
- Modify: `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- Test: `tests/Feature/Superadmin/OrganizationImpersonationAuditTest.php`
- Test: `tests/Feature/Shell/ImpersonationBannerTest.php`

- [ ] **Step 1: Write failing impersonation and audit tests**

```php
it('expires impersonation after one hour', function () {
    travel(61)->minutes();
    expect(app(ImpersonationManager::class)->current(request()))->toBeNull();
});
```

```php
it('records both real and effective actors during impersonation', function () {
    expect(data_get($audit->metadata, 'impersonation.impersonator_name'))->toBe('Sarah Superadmin');
});
```

- [ ] **Step 2: Run the failing tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationImpersonationAuditTest.php tests/Feature/Shell/ImpersonationBannerTest.php`
Expected: FAIL because impersonation currently stores only the basic session keys and audit logging has no dual attribution.

- [ ] **Step 3: Add TTL + incident guard + audit context**

```php
$payload = [
    'impersonator_id' => $impersonator->id,
    'impersonator_name' => $impersonator->name,
    'impersonator_email' => $impersonator->email,
    'impersonated_at' => now()->toIso8601String(),
    'impersonated_target_id' => $target->id,
];
```

```php
'metadata' => [
    ...$metadata,
    'impersonation' => $this->impersonationAuditContext->current(),
]
```

- [ ] **Step 4: Verify TTL, banner, guard, and audit attribution**

Run: `php artisan test tests/Feature/Superadmin/OrganizationImpersonationAuditTest.php tests/Feature/Shell/ImpersonationBannerTest.php`
Expected: PASS with one-hour expiry, incident-blocking, and dual-actor assertions.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Support/Audit/ImpersonationAuditContext.php \
  app/Services/ImpersonationService.php app/Filament/Support/Auth/ImpersonationManager.php \
  app/Filament/Support/Audit/AuditLogger.php \
  app/Filament/Actions/Superadmin/Organizations/StartOrganizationImpersonationAction.php \
  app/Filament/Resources/Organizations/Tables/OrganizationsTable.php \
  app/Filament/Resources/Organizations/Pages/ViewOrganization.php \
  tests/Feature/Superadmin/OrganizationImpersonationAuditTest.php \
  tests/Feature/Shell/ImpersonationBannerTest.php
git commit -m "feat: harden organization impersonation audit contract"
```

## Task 10: Add Audit Timeline Filtering And Activity Feed Deep Links

**Files:**
- Modify: `app/Filament/Resources/Organizations/RelationManagers/ActivityLogsRelationManager.php`
- Modify: `app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php`
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationDashboardData.php`
- Test: `tests/Feature/Superadmin/OrganizationsViewPageTest.php`
- Test: `tests/Feature/Superadmin/AuditLogsResourceTest.php`

- [ ] **Step 1: Add failing tests for the dashboard feed and full audit filters**

```php
it('shows the latest ten org activity events on the detail page', function () {
    $this->get(...)->assertSeeText('Plan changed');
});
```

- [ ] **Step 2: Run the tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php tests/Feature/Superadmin/AuditLogsResourceTest.php`
Expected: FAIL because the view page has no feed contract yet.

- [ ] **Step 3: Move feed shaping into dashboard data**

```php
public function activityFeedFor(Organization $organization): array
{
    return AuditLog::query()->forOrganization(...)->latest()->limit(10)->get()->map(...)->all();
}
```

- [ ] **Step 4: Verify feed rows and deep links**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php tests/Feature/Superadmin/AuditLogsResourceTest.php`
Expected: PASS with latest-ten feed assertions and filter-link coverage.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/Organizations/RelationManagers/ActivityLogsRelationManager.php \
  app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php \
  app/Filament/Support/Superadmin/Organizations/OrganizationDashboardData.php \
  tests/Feature/Superadmin/OrganizationsViewPageTest.php tests/Feature/Superadmin/AuditLogsResourceTest.php
git commit -m "feat: add organization audit feed and deep links"
```

## Task 11: Build Portfolio, Financial, Usage, And Subscription Detail Widgets

**Files:**
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationPortfolioSnapshot.php`
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationFinancialSnapshot.php`
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationSubscriptionSnapshot.php`
- Modify: `app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php`
- Modify: `resources/views/filament/resources/organizations/overview.blade.php`
- Modify: `app/Filament/Support/Superadmin/Usage/OrganizationUsageReader.php`
- Modify: `app/Filament/Support/Superadmin/Usage/OrganizationUsageSnapshot.php`
- Test: `tests/Feature/Superadmin/OrganizationsViewPageTest.php`

- [ ] **Step 1: Write failing widget tests**

```php
it('shows portfolio, financial, usage, and subscription widgets on the org detail page', function () {
    $this->get(...)->assertSeeText(__('superadmin.organizations.overview.financial_snapshot_heading'));
});
```

- [ ] **Step 2: Run the tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php`
Expected: FAIL because the widget sections do not exist.

- [ ] **Step 3: Add snapshot builders and wire them into the overview**

```php
final readonly class OrganizationFinancialSnapshot
{
    public function __construct(
        public string $mrrDisplay,
        public string $outstandingDisplay,
        public string $overdueDisplay,
        public string $collectedThisMonthDisplay,
        public string $avgDaysToPayLabel,
    ) {}
}
```

- [ ] **Step 4: Verify widget rendering**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php`
Expected: PASS with portfolio counts, financial totals, usage meters, and subscription timeline assertions.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Support/Superadmin/Organizations/OrganizationPortfolioSnapshot.php \
  app/Filament/Support/Superadmin/Organizations/OrganizationFinancialSnapshot.php \
  app/Filament/Support/Superadmin/Organizations/OrganizationSubscriptionSnapshot.php \
  app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php \
  resources/views/filament/resources/organizations/overview.blade.php \
  app/Filament/Support/Superadmin/Usage/OrganizationUsageReader.php \
  app/Filament/Support/Superadmin/Usage/OrganizationUsageSnapshot.php \
  tests/Feature/Superadmin/OrganizationsViewPageTest.php
git commit -m "feat: add organization detail dashboard snapshots"
```

## Task 12: Add Security Health And Violations Review Workflow

**Files:**
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationSecuritySnapshot.php`
- Modify: `app/Models/SecurityViolation.php`
- Modify: `app/Filament/Resources/SecurityViolations/Schemas/SecurityViolationTable.php`
- Modify: `app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php`
- Test: `tests/Feature/Superadmin/OrganizationSecurityHealthTest.php`
- Test: `tests/Feature/Superadmin/SecurityViolationsResourceTest.php`

- [ ] **Step 1: Write failing tests for security health and review notes**

```php
it('shows security health counts and unreviewed violations on the org detail page', function () {
    $this->get(...)->assertSeeText('Critical');
});
```

- [ ] **Step 2: Run the tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationSecurityHealthTest.php tests/Feature/Superadmin/SecurityViolationsResourceTest.php`
Expected: FAIL because there is no org-level snapshot/review-note flow yet.

- [ ] **Step 3: Add review-state support and snapshot shaping**

```php
public function scopeReviewed(Builder $query): Builder
{
    return $query->whereNotNull('reviewed_at');
}
```

- [ ] **Step 4: Verify org security widgets and violation review**

Run: `php artisan test tests/Feature/Superadmin/OrganizationSecurityHealthTest.php tests/Feature/Superadmin/SecurityViolationsResourceTest.php`
Expected: PASS with reviewed/unreviewed counts and detail-page badge behavior.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Support/Superadmin/Organizations/OrganizationSecuritySnapshot.php \
  app/Models/SecurityViolation.php \
  app/Filament/Resources/SecurityViolations/Schemas/SecurityViolationTable.php \
  app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php \
  tests/Feature/Superadmin/OrganizationSecurityHealthTest.php \
  tests/Feature/Superadmin/SecurityViolationsResourceTest.php
git commit -m "feat: add organization security health and review workflow"
```

## Task 13: Add User Roster Support Actions

**Files:**
- Modify: `app/Filament/Resources/Organizations/RelationManagers/UsersRelationManager.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Superadmin/OrganizationsViewPageTest.php`
- Test: `tests/Feature/Superadmin/UsersResourceTest.php`

- [ ] **Step 1: Write failing roster-action tests**

```php
it('shows resend invite and change role actions for org users', function () {
    Livewire::test(UsersRelationManager::class, [...])
        ->assertTableActionExists('resendInvite', record: $invitedUser)
        ->assertTableActionExists('changeRole', record: $activeUser);
});
```

- [ ] **Step 2: Run the tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php tests/Feature/Superadmin/UsersResourceTest.php`
Expected: FAIL because the relation manager only supports suspend/reset-password today.

- [ ] **Step 3: Add the missing inline support actions**

```php
Action::make('changeRole')
    ->form([...])
    ->action(fn (User $record, array $data) => ...);
```

- [ ] **Step 4: Verify roster behavior**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php tests/Feature/Superadmin/UsersResourceTest.php`
Expected: PASS with role/status/invite action coverage.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/Organizations/RelationManagers/UsersRelationManager.php \
  app/Models/User.php \
  tests/Feature/Superadmin/OrganizationsViewPageTest.php \
  tests/Feature/Superadmin/UsersResourceTest.php
git commit -m "feat: expand organization user roster support actions"
```

## Task 14: Add Org-Scoped Integration Health Surface

**Files:**
- Create: `app/Filament/Support/Superadmin/Organizations/OrganizationIntegrationSnapshot.php`
- Modify: `app/Filament/Support/Superadmin/Integration/IntegrationHealthPageData.php`
- Modify: `app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php`
- Test: `tests/Feature/Superadmin/OrganizationIntegrationHealthTest.php`
- Test: `tests/Feature/Superadmin/IntegrationHealthPageTest.php`

- [ ] **Step 1: Write failing integration-widget tests**

```php
it('shows platform and org integration health on the organization detail page', function () {
    $this->get(...)->assertSeeText(__('superadmin.integration_health.probes.queue.label'));
});
```

- [ ] **Step 2: Run the tests**

Run: `php artisan test tests/Feature/Superadmin/OrganizationIntegrationHealthTest.php tests/Feature/Superadmin/IntegrationHealthPageTest.php`
Expected: FAIL because the org detail page has no integration-health widget yet.

- [ ] **Step 3: Add the snapshot and merge probe/config status**

```php
final class OrganizationIntegrationSnapshot
{
    public function forOrganization(Organization $organization): array
    {
        return [
            'platform' => [...],
            'organization' => [...],
        ];
    }
}
```

- [ ] **Step 4: Verify detail-page dots and last-checked labels**

Run: `php artisan test tests/Feature/Superadmin/OrganizationIntegrationHealthTest.php tests/Feature/Superadmin/IntegrationHealthPageTest.php`
Expected: PASS with status-color and timestamp assertions.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Support/Superadmin/Organizations/OrganizationIntegrationSnapshot.php \
  app/Filament/Support/Superadmin/Integration/IntegrationHealthPageData.php \
  app/Filament/Resources/Organizations/Schemas/OrganizationInfolist.php \
  tests/Feature/Superadmin/OrganizationIntegrationHealthTest.php \
  tests/Feature/Superadmin/IntegrationHealthPageTest.php
git commit -m "feat: add organization integration health snapshot"
```

## Task 15: Final Verification, Translation Sync, And Cleanup

**Files:**
- Modify: `lang/en/superadmin.php`
- Modify: `lang/lt/superadmin.php`
- Modify: `lang/ru/superadmin.php`
- Modify: `lang/es/superadmin.php`
- Test: `tests/Feature/Superadmin/*.php`

- [ ] **Step 1: Add any missing translation keys discovered during implementation**

```php
'actions' => [
    'force_plan_change' => 'Force plan change',
    'transfer_ownership' => 'Transfer ownership',
]
```

- [ ] **Step 2: Run the focused superadmin test slice**

Run: `php artisan test tests/Feature/Superadmin`
Expected: PASS for all touched organization, security, integration, and audit tests.

- [ ] **Step 3: Run formatting**

Run: `vendor/bin/pint --dirty`
Expected: PASS

- [ ] **Step 4: Run a final contract check**

Run: `git diff --check`
Expected: no whitespace errors

- [ ] **Step 5: Commit**

```bash
git add lang/en/superadmin.php lang/lt/superadmin.php lang/ru/superadmin.php lang/es/superadmin.php \
  app tests
git commit -m "chore: finalize organizations control plane implementation"
```

## Execution Notes

- Follow TDD strictly for each task: test first, fail, minimal implementation, verify, then commit.
- Do not mix support-side mutations with UI work in the same commit if the tests can be split cleanly.
- Prefer `OrganizationListQuery`, `OrganizationDashboardData`, and the focused snapshot builders over embedding query logic directly in Filament resources or Blade.
- Keep suspension enforcement, impersonation audit enrichment, and org-specific feature/limit resolution centralized; do not duplicate these rules in multiple actions.
