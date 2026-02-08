## 1. Factory Coverage
- [ ] 1.1 Inventory tenant-scoped and auxiliary models lacking factories and define required factory states.
- [ ] 1.2 Implement missing factories with strict relationship-safe defaults and explicit tenant alignment support.
- [ ] 1.3 Update existing factories where needed to support deterministic comprehensive seeding.

## 2. Comprehensive Seeder Orchestration
- [ ] 2.1 Create a dedicated comprehensive tenant seeder orchestrator for `tenant_id = 1`.
- [ ] 2.2 Implement phased seeding for core business graph (organization, users, subscriptions, assets, metering, billing).
- [ ] 2.3 Implement phased seeding for auxiliary/supporting graph (audit/activity/system/security/notifications/tasks/comments/tags/translations and related models).
- [ ] 2.4 Ensure seeded data uses realistic synthetic values and domain-appropriate relationships.

## 3. Integration and Idempotency
- [ ] 3.1 Integrate the comprehensive seeder into `DatabaseSeeder`.
- [ ] 3.2 Make seeding safely re-runnable using stable keys/upserts where applicable.
- [ ] 3.3 Ensure all tenant-scoped records created by the comprehensive flow resolve to `tenant_id = 1` unless intentionally global.

## 4. Validation and Tests
- [ ] 4.1 Add feature tests that verify core graph existence and relationship integrity for the seeded tenant.
- [ ] 4.2 Add feature tests that verify auxiliary model coverage and tenant-scope consistency.
- [ ] 4.3 Run formatting and focused tests for seeding/factory changes.
- [ ] 4.4 Run full test suite and fix regressions before completion.
