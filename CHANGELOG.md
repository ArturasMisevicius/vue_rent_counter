# Changelog

## 2026-04-01

<!-- changelog:auto:start:staged-20260401000409 -->
### Commit updates

- updated `.planning/config.json`
- added `app/Filament/Actions/Superadmin/Projects/ExportProjectsCsvAction.php`
- updated `app/Filament/Resources/Projects/Pages/CreateProject.php`
- updated `app/Filament/Resources/Projects/Pages/EditProject.php`
- updated `app/Filament/Resources/Projects/Pages/ViewProject.php`
- updated `app/Filament/Resources/Projects/ProjectResource.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectForm.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectInfolist.php`
- updated `app/Filament/Resources/Projects/Tables/ProjectsTable.php`
- updated `app/Filament/Support/Superadmin/Dashboard/PlatformDashboardData.php`
- added `app/Filament/Support/Superadmin/Projects/ProjectOverviewData.php`
- updated `app/Http/Middleware/CheckManagerPermission.php`
- updated `app/Livewire/Pages/Dashboard/SuperadminDashboard.php`
- updated `app/Models/Project.php`
- updated `app/Models/Task.php`
- updated `app/Models/TimeEntry.php`
- added `app/Notifications/Projects/ProjectCancelledNotification.php`
- added `app/Notifications/Projects/ProjectCompletedNotification.php`
- updated `app/Observers/OrganizationObserver.php`
- updated `app/Observers/ProjectObserver.php`
- updated `app/Policies/ProjectPolicy.php`
- updated `app/Services/ProjectService.php`
- updated `lang/en/dashboard.php`
- updated `lang/es/dashboard.php`
- updated `lang/lt/dashboard.php`
- updated `lang/ru/dashboard.php`
- added `opencode.json`
- added `resources/views/filament/resources/projects/audit-log-modal.blade.php`
- added `resources/views/filament/resources/projects/overview.blade.php`
- added `resources/views/filament/tables/columns/project-progress-bar.blade.php`
- updated `resources/views/livewire/pages/dashboard/superadmin-dashboard.blade.php`
- updated `routes/console.php`
- added `tests/Feature/Admin/ManagerPermissionMiddlewareTest.php`
- updated `tests/Feature/Admin/ManagerPermissionSystemTest.php`
- updated `tests/Feature/Livewire/Dashboard/DashboardPageTest.php`
- updated `tests/Feature/Livewire/Dashboard/SuperadminDashboardComponentTest.php`
- updated `tests/Feature/Projects/ProjectCostsAndAlertsTest.php`
- updated `tests/Feature/Projects/ProjectLifecycleTest.php`
- updated `tests/Feature/Projects/ProjectResourceTest.php`
- updated `tests/Feature/Superadmin/SuperadminDashboardTest.php`
<!-- changelog:auto:end:staged-20260401000409 -->

## 2026-03-31

<!-- changelog:auto:start:staged-20260331200925 -->
### Commit updates

- updated `.codex/config.toml`
- updated `.planning/codebase/ARCHITECTURE.md`
- updated `.planning/codebase/CONCERNS.md`
- updated `.planning/codebase/CONVENTIONS.md`
- updated `.planning/codebase/INTEGRATIONS.md`
- updated `.planning/codebase/STACK.md`
- updated `.planning/codebase/STRUCTURE.md`
- updated `.planning/codebase/TESTING.md`
<!-- changelog:auto:end:staged-20260331200925 -->

<!-- changelog:auto:start:pending -->
### Pending staged changes

- updated `.codex/config.toml`
- updated `.planning/codebase/ARCHITECTURE.md`
- updated `.planning/codebase/CONCERNS.md`
- updated `.planning/codebase/CONVENTIONS.md`
- updated `.planning/codebase/INTEGRATIONS.md`
- updated `.planning/codebase/STACK.md`
- updated `.planning/codebase/STRUCTURE.md`
- updated `.planning/codebase/TESTING.md`
<!-- changelog:auto:end:pending -->

<!-- changelog:auto:start:staged-20260331194242 -->
### Commit updates

- removed `.sisyphus/ralph-loop.local.md`
- added `app/Console/Commands/LaravelMissingTranslationsPhpFilesCommand.php`
- updated `app/Providers/AppServiceProvider.php`
- added `app/Services/Localization/PhpFileMissingTranslationsScanner.php`
- updated `composer.json`
- updated `composer.lock`
- added `config/laravelmissingtranslations.php`
- updated `lang/en/admin.php`
- updated `lang/en/superadmin.php`
- updated `lang/es/admin.php`
- updated `lang/es/superadmin.php`
- updated `lang/lt/admin.php`
- updated `lang/lt/superadmin.php`
- updated `lang/ru/admin.php`
- updated `lang/ru/superadmin.php`
- added `lang/vendor/filament-actions/en/edit.php`
- added `lang/vendor/filament-actions/en/view.php`
- added `lang/vendor/filament-actions/es/edit.php`
- added `lang/vendor/filament-actions/es/view.php`
- added `lang/vendor/filament-actions/lt/edit.php`
- added `lang/vendor/filament-actions/lt/view.php`
- added `lang/vendor/filament-actions/ru/edit.php`
- added `lang/vendor/filament-actions/ru/view.php`
- added `lang/vendor/filament-panels/en/layout.php`
- added `lang/vendor/filament-panels/es/layout.php`
- added `lang/vendor/filament-panels/lt/layout.php`
- added `lang/vendor/filament-panels/ru/layout.php`
- updated `tests/Feature/Admin/AdminDashboardTest.php`
- updated `tests/Feature/Admin/BulkInvoiceGenerationTest.php`
- updated `tests/Feature/Admin/FinancialAuditTrailTest.php`
- updated `tests/Feature/Admin/InvoicesResourceTest.php`
- updated `tests/Feature/Admin/ReportsPageTest.php`
- updated `tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php`
- updated `tests/Feature/Billing/BillingEligibilityConsistencyTest.php`
- updated `tests/Feature/Billing/BillingModuleTest.php`
- updated `tests/Feature/Billing/InvoiceOverduePolicyTest.php`
- updated `tests/Feature/Billing/ReportsTest.php`
- updated `tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php`
- added `tests/Feature/Localization/MissingTranslationsPhpFilesTest.php`
- updated `tests/Feature/Models/ModelScopeOptimizationTest.php`
- updated `tests/Feature/Notifications/NotificationSystemTest.php`
- updated `tests/Support/TenantPortalFactory.php`
<!-- changelog:auto:end:staged-20260331194242 -->

<!-- changelog:auto:start:staged-20260331184152 -->
### Commit updates

- updated `.sisyphus/ralph-loop.local.md`
- updated `app/Filament/Resources/Attachments/Pages/ViewAttachment.php`
- updated `app/Filament/Resources/Buildings/Pages/ViewBuilding.php`
- updated `app/Filament/Resources/CommentReactions/Pages/ViewCommentReaction.php`
- updated `app/Filament/Resources/Comments/Pages/ViewComment.php`
- updated `app/Filament/Resources/InvoiceEmailLogs/Pages/ViewInvoiceEmailLog.php`
- updated `app/Filament/Resources/InvoiceItems/Pages/ViewInvoiceItem.php`
- updated `app/Filament/Resources/InvoicePayments/Pages/ViewInvoicePayment.php`
- updated `app/Filament/Resources/InvoiceReminderLogs/Pages/ViewInvoiceReminderLog.php`
- updated `app/Filament/Resources/Invoices/Pages/ViewInvoice.php`
- updated `app/Filament/Resources/MeterReadings/Pages/ViewMeterReading.php`
- updated `app/Filament/Resources/Meters/Pages/ViewMeter.php`
- updated `app/Filament/Resources/OrganizationUsers/Pages/ViewOrganizationUser.php`
- updated `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- added `app/Filament/Resources/Pages/ViewRecord.php`
- updated `app/Filament/Resources/Projects/Pages/ViewProject.php`
- updated `app/Filament/Resources/Properties/Pages/ViewProperty.php`
- updated `app/Filament/Resources/PropertyAssignments/Pages/ViewPropertyAssignment.php`
- updated `app/Filament/Resources/Providers/Pages/ViewProvider.php`
- updated `app/Filament/Resources/ServiceConfigurations/Pages/ViewServiceConfiguration.php`
- updated `app/Filament/Resources/SubscriptionPayments/Pages/ViewSubscriptionPayment.php`
- updated `app/Filament/Resources/SubscriptionRenewals/Pages/ViewSubscriptionRenewal.php`
- updated `app/Filament/Resources/Subscriptions/Pages/ViewSubscription.php`
- updated `app/Filament/Resources/Tags/Pages/ViewTag.php`
- updated `app/Filament/Resources/Tariffs/Pages/ViewTariff.php`
- updated `app/Filament/Resources/TaskAssignments/Pages/ViewTaskAssignment.php`
- updated `app/Filament/Resources/Tasks/Pages/ViewTask.php`
- updated `app/Filament/Resources/Tenants/Pages/ViewTenant.php`
- updated `app/Filament/Resources/TimeEntries/Pages/ViewTimeEntry.php`
- updated `app/Filament/Resources/UserKycProfiles/Pages/ViewUserKycProfile.php`
- updated `app/Filament/Resources/Users/Pages/ViewUser.php`
- updated `app/Filament/Resources/Users/Schemas/UserInfolist.php`
- updated `app/Filament/Resources/UtilityServices/Pages/ViewUtilityService.php`
- updated `app/Filament/Support/Auth/LoginDemoAccountPresenter.php`
- added `app/Filament/Support/Superadmin/Users/UserDossierData.php`
- updated `app/Livewire/Auth/LoginPage.php`
- updated `resources/views/auth/login.blade.php`
- added `resources/views/filament/resources/users/dossier.blade.php`
- added `resources/views/filament/resources/users/partials/dossier-tree.blade.php`
- updated `tests/Feature/Auth/LoginDemoAccountsTest.php`
- added `tests/Feature/Filament/ViewRecordWidthContractTest.php`
- updated `tests/Feature/Superadmin/UsersResourceTest.php`
<!-- changelog:auto:end:staged-20260331184152 -->

## 2026-03-28

<!-- changelog:auto:start:staged-20260328183354 -->
### Commit updates

- updated `app/Filament/Resources/Organizations/Pages/CreateOrganization.php`
- updated `app/Filament/Resources/Organizations/Pages/EditOrganization.php`
- updated `app/Filament/Resources/Organizations/Schemas/OrganizationForm.php`
- added `app/Filament/Resources/Pages/Concerns/InteractsWithRecordFormValidationExceptions.php`
- updated `tests/Feature/Superadmin/OrganizationsCreateEditPageTest.php`
<!-- changelog:auto:end:staged-20260328183354 -->

<!-- changelog:auto:start:staged-20260328183013 -->
### Commit updates

- updated `database/factories/OrganizationUserFactory.php`
- updated `database/seeders/LegacyCollaborationFoundationSeeder.php`
- added `tests/Feature/Admin/LegacyCollaborationFoundationSeederTest.php`
<!-- changelog:auto:end:staged-20260328183013 -->

<!-- changelog:auto:start:staged-20260328182459 -->
### Commit updates

- updated `app/Filament/Support/Auth/LoginDemoAccountPresenter.php`
- updated `app/Models/User.php`
- updated `tests/Feature/Auth/LoginDemoAccountsTest.php`
<!-- changelog:auto:end:staged-20260328182459 -->

<!-- changelog:auto:start:staged-20260328180833 -->
### Commit updates

- added `app/Enums/ProjectCostRecordType.php`
- added `app/Enums/ProjectPriority.php`
- added `app/Enums/ProjectStatus.php`
- added `app/Enums/ProjectTeamRole.php`
- added `app/Enums/ProjectType.php`
- added `app/Exceptions/InvalidProjectTransitionException.php`
- added `app/Exceptions/ProjectApprovalRequiredException.php`
- added `app/Exceptions/ProjectCostPassthroughException.php`
- added `app/Exceptions/ProjectDeletionBlockedException.php`
- updated `app/Filament/Resources/Projects/Pages/CreateProject.php`
- updated `app/Filament/Resources/Projects/Pages/ViewProject.php`
- updated `app/Filament/Resources/Projects/ProjectResource.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectForm.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectInfolist.php`
- updated `app/Filament/Resources/Projects/Tables/ProjectsTable.php`
- added `app/Jobs/Projects/RescopeProjectChildrenJob.php`
- added `app/Models/CostRecord.php`
- updated `app/Models/InvoiceItem.php`
- updated `app/Models/OrganizationSetting.php`
- updated `app/Models/Project.php`
- added `app/Models/ProjectUser.php`
- updated `app/Models/Task.php`
- updated `app/Models/TimeEntry.php`
- added `app/Notifications/Projects/ProjectApprovalRequestedNotification.php`
- added `app/Notifications/Projects/ProjectApprovedNotification.php`
- added `app/Notifications/Projects/ProjectEmergencyCreatedNotification.php`
- added `app/Notifications/Projects/ProjectOverBudgetNotification.php`
- added `app/Notifications/Projects/ProjectOverdueAlertNotification.php`
- added `app/Notifications/Projects/ProjectStalledAlertNotification.php`
- added `app/Notifications/Projects/ProjectUnapprovedEscalationNotification.php`
- added `app/Notifications/Projects/ProjectUnapprovedReminderNotification.php`
- added `app/Observers/ProjectObserver.php`
- updated `app/Policies/ProjectPolicy.php`
- updated `app/Providers/AppServiceProvider.php`
- added `app/Services/ProjectService.php`
- added `database/factories/CostRecordFactory.php`
- updated `database/factories/OrganizationSettingFactory.php`
- updated `database/factories/ProjectFactory.php`
- added `database/factories/ProjectUserFactory.php`
- updated `database/factories/PropertyFactory.php`
- updated `database/factories/TaskFactory.php`
- updated `database/factories/TimeEntryFactory.php`
- added `database/migrations/2026_03_28_180000_expand_projects_module_tables.php`
- added `database/migrations/2026_03_28_180100_create_cost_records_table.php`
- added `database/migrations/2026_03_28_180200_create_project_users_table.php`
- updated `database/seeders/LegacyCollaborationFoundationSeeder.php`
- updated `database/seeders/OperationalDemoDatasetSeeder.php`
- added `docs/superpowers/plans/2026-03-28-projects-module-implementation.md`
- added `docs/superpowers/specs/2026-03-28-projects-module-design.md`
- updated `lang/en/enums.php`
- updated `lang/es/enums.php`
- updated `lang/lt/enums.php`
- updated `lang/ru/enums.php`
- updated `routes/console.php`
- added `tests/Feature/Projects/ProjectCostsAndAlertsTest.php`
- added `tests/Feature/Projects/ProjectLifecycleTest.php`
- added `tests/Feature/Projects/ProjectResourceTest.php`
- updated `tests/Feature/Superadmin/RelationCrudResourcesTest.php`
- updated `tests/Feature/Superadmin/RelationResourceListContextTest.php`
<!-- changelog:auto:end:staged-20260328180833 -->

### fix: keep legacy collaboration seeds aligned with roster roles

- stopped the legacy collaboration seeder from overwriting a demo tenant membership to `manager` just to attach collaboration data
- tied the seeded legacy collaboration project to a real manager and added regression coverage for the preserved tenant roster role
- normalized `OrganizationUserFactory` defaults to supported membership roles and removed the stale default legacy permissions payload

### fix: restrict manager billing permissions on starter and basic trials

- extended the manager permission matrix availability rules so billing-side resources are marked unavailable for starter and basic trial subscriptions, not only pending organizations
- kept the restricted billing resource set aligned across billing, invoices, tariffs, providers, service configurations, and utility services
- added Livewire regression coverage to ensure restricted trial plans surface the “not available on your current plan” state in the manager permission matrix

### feat: make seeded reports usable for superadmins and current-period demos

- added a superadmin organization selector on the unified reports page so cross-organization reporting works without requiring a tenant-scoped organization context
- defaulted superadmin reports to the seeded demo organization and wired report widgets, filters, exports, and option lists through the selected organization
- refreshed the login-demo and operational dataset seeders so current-period invoices and meter readings populate revenue, outstanding balances, and meter compliance reports with realistic paid, overdue, partial, pending, rejected, and missing states
- added focused admin report page and seed-coverage Pest coverage for the new superadmin selector and seeded current-period report output

### feat: implement the projects module end to end

- added the full projects domain contract with project status, priority, type, and cost-record enums plus project-specific exceptions, observer logic, service workflows, notifications, and scheduled alert commands
- expanded project persistence with cost records and project team memberships, added automatic project reference sequencing through organization settings, and wired project cost, approval, completion, and child-rescoping behavior into the live models
- rebuilt the Filament projects resource for cross-organization superadmin visibility and organization-scoped admin visibility with richer forms, list filters, infolists, header actions, and bulk actions
- added focused Pest coverage for project lifecycle, costs and alerts, resource behavior, and updated affected superadmin/admin regression contracts
- aligned legacy and demo seeders plus locale enum dictionaries with the new project enum contract so full reseeding and translation parity continue to pass

<!-- changelog:auto:start:staged-20260328171834 -->
### Commit updates

- updated `app/Filament/Resources/OrganizationUsers/Schemas/OrganizationUserInfolist.php`
- updated `app/Filament/Support/Admin/ManagerPermissions/ManagerPermissionCatalog.php`
- updated `lang/en/admin.php`
- updated `lang/es/admin.php`
- updated `lang/lt/admin.php`
- updated `lang/ru/admin.php`
- updated `tests/Feature/Admin/OrganizationUsersResourceTest.php`
<!-- changelog:auto:end:staged-20260328171834 -->

<!-- changelog:auto:start:staged-20260328170935 -->
### Commit updates

- updated `tests/Feature/Admin/OrganizationUsersResourceTest.php`
<!-- changelog:auto:end:staged-20260328170935 -->

<!-- changelog:auto:start:staged-20260328170244 -->
### Commit updates

- updated `app/Filament/Resources/OrganizationUsers/Pages/ListOrganizationUsers.php`
- updated `app/Filament/Resources/OrganizationUsers/Tables/OrganizationUsersTable.php`
- updated `tests/Feature/Admin/OrganizationUsersResourceTest.php`
<!-- changelog:auto:end:staged-20260328170244 -->

### feat: surface manager permission summaries on organization user views

- replaced the legacy raw membership `permissions` display for manager memberships with a read-only summary sourced from the live manager permission matrix
- added a read-only fallback summary for managers who still have zero granted write permissions across all resources
- localized the inviter label on the organization-user infolist and added focused Pest coverage for both granted and default-read-only summaries

### fix: tighten organization user list affordances

- removed the dead create action from the organization-user list for org admins while preserving it for superadmins
- limited organization-user bulk deletion affordances to superadmins and made the bulk action authorization explicit
- hid the redundant organization column for org-admin manager-membership views while keeping it visible in the superadmin list
- added focused Pest coverage for the admin versus superadmin organization-user list contract

### fix: seed showcase organization memberships

- synced `organization_user` membership rows for showcase admins, managers, and tenants inside `OperationalDemoDatasetSeeder`
- added seeder regression coverage to ensure every showcase user receives a scoped membership row with an inviter
- preserved idempotent reseeding while aligning showcase data with the organization-user admin surfaces

<!-- changelog:auto:start:staged-20260328165420 -->
### Commit updates

- updated `database/seeders/OperationalDemoDatasetSeeder.php`
<!-- changelog:auto:end:staged-20260328165420 -->

<!-- changelog:auto:start:staged-20260328165017 -->
### Commit updates

- updated `app/Filament/Resources/OrganizationUsers/Pages/EditOrganizationUser.php`
- updated `app/Filament/Resources/OrganizationUsers/Pages/ViewOrganizationUser.php`
- updated `app/Filament/Resources/OrganizationUsers/Schemas/OrganizationUserInfolist.php`
- updated `app/Filament/Resources/Pages/Concerns/HasContainedSuperadminSurface.php`
- updated `tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
- updated `tests/Feature/Admin/OrganizationUsersResourceTest.php`
<!-- changelog:auto:end:staged-20260328165017 -->

<!-- changelog:auto:start:staged-20260328163744 -->
### Commit updates

- updated `.githooks/post-commit`
<!-- changelog:auto:end:staged-20260328163744 -->

<!-- changelog:auto:start:staged-20260328163633 -->
### Commit updates

- updated `.agent/skills/update-changelog-before-commit/SKILL.md`
- updated `.ai/skills/update-changelog-before-commit/SKILL.md`
- updated `.claude/skills/update-changelog-before-commit/SKILL.md`
- updated `.cursor/skills/update-changelog-before-commit/SKILL.md`
- updated `.gemini/skills/update-changelog-before-commit/SKILL.md`
- added `.githooks/post-commit`
- updated `.githooks/pre-commit`
- updated `scripts/update_changelog.php`
- added `tests/Feature/Console/UpdateChangelogScriptTest.php`
<!-- changelog:auto:end:staged-20260328163633 -->

### test: avoid admin nav label substring collisions

- tightened the admin unified-panel regression to assert the superadmin users route is absent instead of matching the raw "Users" label text
- preserved the admin Organization Users navigation entry without letting substring collisions create false failures

### feat: let admins manage org manager memberships

- opened the Organization Users resource to org admins and owners only for manager memberships inside their current organization
- scoped the resource query and policy checks so non-manager memberships and outside-organization records stay inaccessible
- locked admin-side membership fields while keeping the manager permission matrix available on the edit surface
- added focused admin, shell, matrix, and coverage-inventory Pest regression tests for the new access path

### fix: keep changelog updates inside the current commit

- documented the hook lifecycle change that moved changelog mutation away from `commit-msg`
- aligned the shared changelog-update skill copies with the repository hook behavior

<!-- changelog:auto:start:commit-20260328160318 -->
### fix: align changelog updater support namespace

- renamed `app/Support/Changelog/GitChangelogUpdater.php` to `app/Filament/Support/Changelog/GitChangelogUpdater.php`
- updated `scripts/update_changelog.php`
- renamed `tests/Unit/Support/Changelog/GitChangelogUpdaterTest.php` to `tests/Unit/Filament/Support/Changelog/GitChangelogUpdaterTest.php`
<!-- changelog:auto:end:commit-20260328160318 -->

<!-- changelog:auto:start:commit-20260328155531 -->
### feat: automate changelog updates before commit

- added `.agent/skills/update-changelog-before-commit/SKILL.md`
- added `.ai/skills/update-changelog-before-commit/SKILL.md`
- added `.claude/skills/update-changelog-before-commit/SKILL.md`
- added `.cursor/skills/update-changelog-before-commit/SKILL.md`
- added `.gemini/skills/update-changelog-before-commit/SKILL.md`
- added `.githooks/commit-msg`
- added `.githooks/pre-commit`
- added `app/Filament/Support/Changelog/GitChangelogUpdater.php`
- added `scripts/install-git-hooks.sh`
- added `scripts/update_changelog.php`
- added `tests/Unit/Filament/Support/Changelog/GitChangelogUpdaterTest.php`
<!-- changelog:auto:end:commit-20260328155531 -->

### Hidden generated slugs in admin UI

- removed organization slugs from the superadmin organizations list, organization detail overview, record subheading, and organization summary exports
- removed organization slugs from superadmin global search results and stopped matching organizations by slug in the visible search UI
- removed tag slugs from the tag list and tag detail pages while keeping slug auto-generation at the model layer
- removed slug display from the recently created organizations widget and added regression coverage for the hidden-slug contract

### Deferred relation tab count badges

- enabled deferred relation-tab badge counts across building, property, tenant, meter, and organization record views so relation tabs consistently show right-aligned object counts
- added fallback badge counting for relation managers that are rendered without preloaded `*_count` attributes, keeping badges correct on direct record views and after tab switches
- added focused Pest coverage for deferred relation-tab badges across the superadmin organizations view and the admin building, property, tenant, and meter resources

### Organization single-subscription relation

- changed the organization `Subscriptions` relation tab to manage only the current subscription record
- added relation-scoped create when an organization has no subscription yet
- added relation-scoped edit for the current subscription while preserving history access through the existing modal
- added request and action classes for creating and updating organization subscriptions from the relation manager
- added focused Pest coverage for the new single-subscription relation behavior

### Manager permission matrix

- added a manager permission matrix system with dedicated model, factory, migration, exceptions, catalog, service, notification, and Livewire-backed superadmin editor
- gated manager write access through new policies, resource middleware, and navigation filtering so manager mutations are explicitly permissioned per resource
- synchronized manager membership state through observers and seeded the login demo workspace with organization memberships and a default property-manager preset
- added focused manager permission regression coverage across admin resources, the superadmin organization-user editor, and manager workspace parity
- aligned legacy admin resource and tenant UI coverage with explicit manager permission fixtures so managers stay read-only by default unless their matrix grants write access
- flushed the in-memory manager permission cache in Pest bootstrap so request-scoped permission checks stay isolated across feature tests
- keyed the in-memory manager permission cache by organization and user identity so equivalent model instances reuse the same per-request permission matrix
- scoped the “changes take effect immediately” banner to superadmin-only matrix contexts instead of showing it for every manager-permission editor

### Demo manager presets

- added a second seeded demo manager account with the billing-manager preset so both property and billing permission profiles are visible in demo data
- updated the curated login demo account presenter and Pest coverage to include the new billing manager example

### Showcase manager permission presets

- seeded the five Baltic showcase organizations with deterministic manager permission profiles so the operational demo dataset now exercises read-only, property, billing, full-access, and custom utility-manager matrices
- added Pest coverage to keep those showcase manager permission rows idempotent across repeated database seeding runs

### Subscription request validation coverage

- added request-structure and validation scenario coverage for superadmin organization subscription create and update requests

### Superadmin organization roster management

- added create, edit, and delete roster actions to the superadmin organization users relation manager with shared roster form components
- added organization roster store and update requests plus validation scenario coverage for the new superadmin user-management flow
- added translated action and notification strings for the superadmin organization user roster in English, Lithuanian, Russian, and Spanish
