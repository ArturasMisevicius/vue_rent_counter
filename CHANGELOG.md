# Changelog

## 2026-03-28

<!-- changelog:auto:start:pending -->
### Pending staged changes

- updated `app/Filament/Resources/OrganizationUsers/OrganizationUserResource.php`
- updated `app/Filament/Resources/OrganizationUsers/Pages/EditOrganizationUser.php`
- updated `app/Filament/Resources/OrganizationUsers/Schemas/OrganizationUserForm.php`
- updated `app/Policies/OrganizationUserPolicy.php`
- updated `config/tenanto.php`
- added `docs/superpowers/plans/2026-03-28-organization-user-admin-access.md`
- updated `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`
- added `tests/Feature/Admin/OrganizationUsersResourceTest.php`
- updated `tests/Feature/Filament/ManagerPermissionMatrixTest.php`
- updated `tests/Feature/Shell/AuthenticatedShellTest.php`
- updated `tests/Feature/Superadmin/RelationCrudResourcesTest.php`
<!-- changelog:auto:end:pending -->

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
