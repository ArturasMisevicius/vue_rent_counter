# Organization User Admin Access Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let org admins manage manager permission matrices from the `OrganizationUserResource` within their current organization without reopening superadmin-only membership management.

**Architecture:** Keep `OrganizationUserResource` dual-surface. Superadmins retain full CRUD and global visibility. Org admins and owners gain scoped `index`, `view`, and `edit` access only for manager memberships in their current organization, with the main form fields locked down so the page acts as a manager-permission editor rather than a general roster editor.

**Tech Stack:** Laravel, Filament 5 resources/pages/forms, Pest feature tests, Eloquent policies, shell navigation config.

---

### Task 1: Lock regression coverage for scoped admin access

**Files:**
- Create: `tests/Feature/Admin/OrganizationUsersResourceTest.php`
- Modify: `tests/Feature/Filament/ManagerPermissionMatrixTest.php`
- Modify: `tests/Feature/Superadmin/RelationCrudResourcesTest.php`
- Modify: `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`
- Modify: `tests/Feature/Shell/AuthenticatedShellTest.php`

- [ ] Add failing Pest coverage for admin `index`, `view`, and `edit` access to manager memberships in the current organization.
- [ ] Add failing Pest coverage for admin denial on `create`, other-organization memberships, and non-manager memberships.
- [ ] Add failing Pest coverage for the admin edit page rendering the manager permission matrix without the superadmin banner.
- [ ] Update the superadmin-only regression test so it no longer expects admins to be blocked from every `organization-users` route.
- [ ] Update shell coverage so the admin account navigation expects the organization user entry.

### Task 2: Scope OrganizationUserResource for admins and owners

**Files:**
- Modify: `app/Filament/Resources/OrganizationUsers/OrganizationUserResource.php`
- Modify: `app/Policies/OrganizationUserPolicy.php`
- Modify: `app/Filament/Resources/OrganizationUsers/Schemas/OrganizationUserForm.php`
- Modify: `config/tenanto.php`

- [ ] Replace the blanket superadmin resource gate with policy-backed access and scoped resource queries.
- [ ] Add policy rules so admins and owners can only `viewAny`, `view`, and `update` manager memberships inside their current organization.
- [ ] Keep `create`, `delete`, and bulk destructive flows superadmin-only.
- [ ] Lock membership fields for non-superadmins so admins use the page as a permission-management surface, not a full roster editor.
- [ ] Add the organization-users route to admin shell navigation.

### Task 3: Refresh docs, verification, and commit hygiene

**Files:**
- Modify: `CHANGELOG.md`
- Modify if still needed: `.githooks/commit-msg`
- Modify if still needed: `.agent/skills/update-changelog-before-commit/SKILL.md`
- Modify if still needed: `.ai/skills/update-changelog-before-commit/SKILL.md`
- Modify if still needed: `.claude/skills/update-changelog-before-commit/SKILL.md`
- Modify if still needed: `.cursor/skills/update-changelog-before-commit/SKILL.md`
- Modify if still needed: `.gemini/skills/update-changelog-before-commit/SKILL.md`

- [ ] Keep the changelog and hook behavior consistent with the final code changes.
- [ ] Run the focused test file first, then the broader regression suite, then `vendor/bin/pint --dirty`, then `git diff --check`.
- [ ] Commit the verified changes on `main` and push to `origin/main`.
