---
phase: 3
slug: surface-and-read-path-unification
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-03-19
---

# Phase 3 — Validation Strategy

## Per-Task Verification Map

| Task ID | Plan | Requirement | Automated Command | Status |
|---------|------|-------------|-------------------|--------|
| 03-01-01 | 01 | ARCH-01 | `php artisan test tests/Feature/Auth/CanonicalEntryPathTest.php tests/Feature/Auth/LoginFlowTest.php --compact` | ⬜ pending |
| 03-01-02 | 01 | ARCH-02 | `php artisan test tests/Feature/Shell/NavigationSourceOfTruthTest.php tests/Feature/Shell/GlobalSearchTest.php --compact` | ⬜ pending |
| 03-01-03 | 01 | ARCH-03, PORT-03 | `php artisan test tests/Feature/Architecture/WorkspaceReadModelInventoryTest.php tests/Feature/Billing/ReportsTest.php tests/Feature/GlobalSearchTest.php --compact` | ⬜ pending |
| 03-01-04 | 01 | PORT-01, PORT-03 | `php artisan test tests/Feature/Tenant/InvoiceReadExperienceConsistencyTest.php tests/Feature/Tenant/TenantInvoiceHistoryTest.php tests/Feature/Admin/InvoicesResourceTest.php --compact` | ⬜ pending |

## Validation Sign-Off

- [x] Each task has an explicit automated verification command
- [x] Coverage spans auth redirects, shell navigation, shared read builders, and invoice read experience
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
