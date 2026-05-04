# Organization Single Subscription Relation Implementation Plan

> **AI agent usage:** This is an execution plan, not proof of current implementation. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify every referenced file, command, route, schema, and test before acting.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the organization subscriptions relation tab manage only the current subscription, with create available only when none exists.

**Architecture:** Keep the existing standalone subscriptions resource intact, and narrow only `SubscriptionsRelationManager` so the organization detail page exposes a single current subscription row plus relation-scoped create/edit/manage actions. Reuse existing subscription form fields and management actions instead of inventing a new subscription workflow.

**Tech Stack:** Laravel, Filament, Eloquent, Pest

---

### Task 1: Lock The Relation Contract With Failing Tests

**Files:**
- Modify: `tests/Feature/Superadmin/OrganizationsViewPageTest.php`
- Verify: `app/Filament/Resources/Organizations/RelationManagers/SubscriptionsRelationManager.php`

- [ ] **Step 1: Write the failing tests**

Add tests proving:
- create action exists when the organization has no subscription
- create action is hidden when the organization already has one
- edit action exists on the current subscription row
- only the current/latest subscription row is visible from the relation manager

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php --filter=subscriptions`

Expected: FAIL because the relation manager is currently history-oriented and has no create/edit flow.

- [ ] **Step 3: Write minimal implementation**

Patch only the relation manager and any tightly related support needed to make the new tests pass.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php --filter=subscriptions`

Expected: PASS

### Task 2: Implement Single Current-Subscription Relation CRUD

**Files:**
- Modify: `app/Filament/Resources/Organizations/RelationManagers/SubscriptionsRelationManager.php`
- Verify: `app/Filament/Resources/Subscriptions/Schemas/SubscriptionForm.php`
- Verify: `app/Models/Organization.php`
- Verify: `app/Models/Subscription.php`

- [ ] **Step 1: Scope the relation query**

Change the relation manager query so the table shows only the organization’s current/latest subscription record.

- [ ] **Step 2: Add create action when missing**

Add a relation header `CreateAction` that is visible only when the organization has no subscription rows. Scope `organization_id` to the parent org.

- [ ] **Step 3: Add edit action when current subscription exists**

Add row-level `EditAction` and keep the existing history/manage actions for the current row.

- [ ] **Step 4: Preserve history access**

Keep `viewHistory` working for the current subscription record so support still has access to previous renewals and changes.

- [ ] **Step 5: Run focused verification**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php --filter=subscriptions`

Expected: PASS

### Task 3: Final Verification

**Files:**
- Verify: `tests/Feature/Superadmin/OrganizationsViewPageTest.php`
- Verify: `app/Filament/Resources/Organizations/RelationManagers/SubscriptionsRelationManager.php`

- [ ] **Step 1: Run the targeted file**

Run: `php artisan test tests/Feature/Superadmin/OrganizationsViewPageTest.php`

Expected: PASS

- [ ] **Step 2: Run formatting**

Run: `vendor/bin/pint --dirty`

Expected: exit 0

- [ ] **Step 3: Review final diff**

Run: `git diff -- docs/superpowers/specs/2026-03-28-organization-single-subscription-relation-design.md docs/superpowers/plans/2026-03-28-organization-single-subscription-relation.md app/Filament/Resources/Organizations/RelationManagers/SubscriptionsRelationManager.php tests/Feature/Superadmin/OrganizationsViewPageTest.php`

Expected: only the documented single-subscription relation changes
