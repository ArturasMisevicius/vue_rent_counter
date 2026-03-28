# Changelog

## 2026-03-28

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
