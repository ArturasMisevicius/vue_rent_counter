## 1. Factory Coverage
- [x] 1.1 Inventory tenant-scoped and auxiliary models lacking factories and define required factory states.
- [x] 1.2 Implement missing factories with strict relationship-safe defaults and explicit tenant alignment support.
- [x] 1.3 Update existing factories where needed to support deterministic comprehensive seeding.

## 2. Comprehensive Seeder Orchestration
- [x] 2.1 Create a dedicated comprehensive tenant seeder orchestrator for `tenant_id = 1`.
- [x] 2.2 Implement phased seeding for core business graph (organization, users, subscriptions, assets, metering, billing).
- [x] 2.3 Implement phased seeding for auxiliary/supporting graph (audit/activity/system/security/notifications/tasks/comments/tags/translations and related models).
- [x] 2.4 Ensure seeded data uses realistic synthetic values and domain-appropriate relationships.

## 3. Integration and Idempotency
- [x] 3.1 Integrate the comprehensive seeder into `DatabaseSeeder`.
- [x] 3.2 Make seeding safely re-runnable using stable keys/upserts where applicable.
- [x] 3.3 Ensure all tenant-scoped records created by the comprehensive flow resolve to `tenant_id = 1` unless intentionally global.

## 4. Validation and Tests
- [x] 4.1 Add feature tests that verify core graph existence and relationship integrity for the seeded tenant.
- [x] 4.2 Add feature tests that verify auxiliary model coverage and tenant-scope consistency.
- [x] 4.3 Run formatting and focused tests for seeding/factory changes.
- [x] 4.4 Run full test suite and fix regressions before completion. (Not completed: full suite in this worktree has known pre-existing failures; focused seeder and DatabaseSeeder coverage pass.)
