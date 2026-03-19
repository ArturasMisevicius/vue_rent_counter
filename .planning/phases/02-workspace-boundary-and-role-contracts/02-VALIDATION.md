---
phase: 2
slug: workspace-boundary-and-role-contracts
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-03-19
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 4 on PHPUnit 12 |
| **Quick run command** | Run the active task's `<verify>` command |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~10 seconds quick runs, ~60 seconds broader boundary suite |

---

## Sampling Rate

- After every task commit: run that task's `<verify>` command.
- After the full phase plan: run the expanded security and architecture bundle plus the full suite if any policy or middleware contract changed broadly.
- Before phase sign-off: rerun all Phase 2 verification commands plus `php artisan test --compact`.

---

## Per-Task Verification Map

| Task ID | Plan | Requirement | Test Type | Automated Command | Status |
|---------|------|-------------|-----------|-------------------|--------|
| 02-01-01 | 01 | SEC-01 | feature | `php artisan test tests/Feature/Security/WorkspaceContextResolutionTest.php --compact` | ⬜ pending |
| 02-01-02 | 01 | SEC-02, SEC-03 | feature | `php artisan test tests/Feature/Security/RoleAuthorityContractTest.php tests/Feature/Admin/ManagerPolicyParityTest.php --compact` | ⬜ pending |
| 02-01-03 | 01 | SEC-04 | feature | `php artisan test tests/Feature/Security/TenantPropertyBoundaryContractTest.php tests/Feature/Tenant/TenantAccessIsolationTest.php tests/Feature/Security/TenantPortalIsolationTest.php --compact` | ⬜ pending |
| 02-01-04 | 01 | SEC-01..SEC-04 | architecture + regression | `php artisan test tests/Feature/Architecture/WorkspaceBoundaryInventoryTest.php tests/Feature/Security/WorkspaceContextResolutionTest.php tests/Feature/Security/RoleAuthorityContractTest.php tests/Feature/Security/TenantPropertyBoundaryContractTest.php --compact` | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠ flaky*

---

## Validation Sign-Off

- [x] Every planned task has an explicit automated verification command
- [x] The phase validates boundary behavior through both feature and architecture coverage
- [x] No manual-only verifications are required for the initial implementation pass
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
