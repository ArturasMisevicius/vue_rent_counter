# Projects Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade the Projects module into a fully scoped operational domain with lifecycle, approvals, budgeting, passthrough billing, Filament diagnostics, and Pest coverage.

**Architecture:** Extend the existing `Project` resource/model in place, add the missing supporting tables and enums, centralize mutations in `App\Filament\Actions` plus `App\Services`, and reuse the existing `AuditLogger`, organization workspace context, and policy patterns already present in the codebase.

**Tech Stack:** Laravel 13, PHP 8.3, Filament 5, Pest 4, Eloquent, Blade, Livewire 4.

---

### Task 1: Upgrade the persistence layer

**Files:**
- Create: `database/migrations/2026_03_28_000100_expand_projects_module_tables.php`
- Create: `database/migrations/2026_03_28_000200_create_cost_records_table.php`
- Create: `database/migrations/2026_03_28_000300_create_project_users_table.php`
- Modify: `database/factories/ProjectFactory.php`
- Create: `database/factories/CostRecordFactory.php`

- [ ] Write migration and factory tests first
- [ ] Run focused tests and watch them fail
- [ ] Implement schema expansion and new factories
- [ ] Re-run focused tests

### Task 2: Add project enums and domain exceptions

**Files:**
- Create: `app/Enums/ProjectStatus.php`
- Create: `app/Enums/ProjectPriority.php`
- Create: `app/Enums/ProjectType.php`
- Create: `app/Enums/ProjectTeamRole.php`
- Create: `app/Enums/ProjectCostRecordType.php`
- Create: `app/Exceptions/InvalidProjectTransitionException.php`
- Create: `app/Exceptions/ProjectDeletionBlockedException.php`
- Create: `app/Exceptions/ProjectApprovalRequiredException.php`
- Create: `app/Exceptions/ProjectCostPassthroughException.php`
- Modify: `lang/enums.php`

- [ ] Write enum / exception tests first
- [ ] Run focused tests and verify red
- [ ] Implement enums and exceptions
- [ ] Re-run focused tests

### Task 3: Upgrade models and observers

**Files:**
- Modify: `app/Models/Project.php`
- Create: `app/Models/CostRecord.php`
- Modify: `app/Models/Task.php`
- Modify: `app/Models/TimeEntry.php`
- Modify: `app/Models/InvoiceItem.php`
- Modify: `app/Models/OrganizationSetting.php`
- Create: `app/Observers/ProjectObserver.php`
- Create: `app/Observers/TaskProjectObserver.php`
- Create: `app/Observers/TimeEntryProjectObserver.php`
- Create: `app/Observers/CostRecordObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] Write failing model behaviour tests first
- [ ] Verify failures
- [ ] Implement casts, scopes, relations, helpers, and observer hooks
- [ ] Re-run focused tests

### Task 4: Add service, events, listeners, jobs, and commands

**Files:**
- Create: `app/Services/ProjectService.php`
- Create: `app/Events/ProjectStatusChanged.php`
- Create: `app/Listeners/NotifyProjectStatusChange.php`
- Create: `app/Listeners/RecordProjectStatusChange.php`
- Create: `app/Jobs/Projects/RescopeProjectChildrenJob.php`
- Create: `app/Console/Commands/ProjectsAlertStalled.php`
- Create: `app/Console/Commands/ProjectsAlertOverdue.php`
- Create: `app/Console/Commands/ProjectsAlertUnapproved.php`
- Modify: `routes/console.php`

- [ ] Write failing service / command tests first
- [ ] Verify failures
- [ ] Implement service, event, listeners, job, and schedule hooks
- [ ] Re-run focused tests

### Task 5: Replace project policy and resource behavior

**Files:**
- Modify: `app/Policies/ProjectPolicy.php`
- Modify: `app/Filament/Resources/Projects/ProjectResource.php`
- Modify: `app/Filament/Resources/Projects/Schemas/ProjectForm.php`
- Modify: `app/Filament/Resources/Projects/Schemas/ProjectInfolist.php`
- Modify: `app/Filament/Resources/Projects/Tables/ProjectsTable.php`
- Modify: `app/Filament/Resources/Projects/Pages/ViewProject.php`
- Modify: `app/Filament/Resources/Projects/Pages/EditProject.php`
- Modify: `app/Filament/Resources/Projects/Pages/ListProjects.php`

- [ ] Write failing Filament / policy tests first
- [ ] Verify failures
- [ ] Implement dual-scope resource query, filters, actions, and infolist sections
- [ ] Re-run focused tests

### Task 6: Add project actions and request validation

**Files:**
- Create: `app/Http/Requests/Projects/StoreProjectRequest.php`
- Create: `app/Http/Requests/Projects/UpdateProjectRequest.php`
- Create: `app/Http/Requests/Projects/ChangeProjectStatusRequest.php`
- Create: `app/Http/Requests/Projects/AssignProjectManagerRequest.php`
- Create: `app/Http/Requests/Projects/ApproveProjectRequest.php`
- Create: `app/Http/Requests/Projects/GenerateProjectCostPassthroughRequest.php`
- Create: `app/Filament/Actions/Projects/CreateProjectAction.php`
- Create: `app/Filament/Actions/Projects/UpdateProjectAction.php`
- Create: `app/Filament/Actions/Projects/TransitionProjectStatusAction.php`
- Create: `app/Filament/Actions/Projects/ApproveProjectAction.php`
- Create: `app/Filament/Actions/Projects/AssignProjectManagerAction.php`
- Create: `app/Filament/Actions/Projects/GenerateProjectCostPassthroughAction.php`

- [ ] Write failing request/action tests first
- [ ] Verify failures
- [ ] Implement validation and action orchestration
- [ ] Re-run focused tests

### Task 7: Add module-specific notifications and translations

**Files:**
- Create: `app/Notifications/Projects/*.php`
- Modify: `lang/en/admin.php`
- Modify: `lang/lt/admin.php`
- Modify: `lang/es/admin.php`
- Modify: `lang/ru/admin.php`
- Modify: `lang/enums.php`

- [ ] Write failing notification assertions first
- [ ] Verify failures
- [ ] Implement notifications and translation keys
- [ ] Re-run focused tests

### Task 8: Add comprehensive Pest coverage

**Files:**
- Create: `tests/Feature/Projects/ProjectLifecycleTest.php`
- Create: `tests/Feature/Projects/ProjectApprovalTest.php`
- Create: `tests/Feature/Projects/ProjectCostPassthroughTest.php`
- Create: `tests/Feature/Projects/ProjectAlertsCommandTest.php`
- Create: `tests/Feature/Projects/ProjectAuthorizationTest.php`
- Modify: `tests/Feature/Superadmin/RelationCrudResourcesTest.php`
- Modify: `tests/Feature/Superadmin/RelationResourceListContextTest.php`
- Modify: `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`

- [ ] Add the requested behaviour coverage
- [ ] Run focused files
- [ ] Fix regressions until green

### Task 9: Verify, document, and ship

**Files:**
- Modify: `CHANGELOG.md`

- [ ] Run `vendor/bin/pint --dirty`
- [ ] Run `git diff --check`
- [ ] Run `php artisan test`
- [ ] Review `git status --short`
- [ ] Commit with a feature message
- [ ] Push `main`
