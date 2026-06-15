# Tenanto Superpowers Docs Map

> **AI agent usage:** This folder is historical planning and execution context. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/PROJECT-CONTEXT.md`, and `docs/FEATURES.md` first. Verify current code before changing behavior.

Updated on 2026-06-15. The current product docs live outside this folder:

- Current feature guide: `../FEATURES.md`
- Current project context: `../PROJECT-CONTEXT.md`
- Current role matrix: `../PERMISSION-MATRIX.md`
- Current billing workflow: `../operations/billing-reading-invoice-workflow.md`

## What This Folder Is

`docs/superpowers/**` preserves historical design and implementation artifacts from the March 2026 planning work. These documents are useful when you need product intent, tradeoffs, or rollout history, but they are not proof that the live system is incomplete or still shaped exactly as planned.

Use this folder for:

- understanding why auth, shell, admin, tenant, superadmin, and organization modules were split;
- comparing implementation against old design intent;
- preserving plan/spec context when resuming a historical slice;
- seeing prior risk and verification thinking.

Do not use this folder as the first place for current usage instructions. Start with `../FEATURES.md`.

## Current Historical Index

### Plans

| File | Historical topic | Current status hint |
| --- | --- | --- |
| `plans/2026-03-28-organization-single-subscription-relation.md` | Organization current subscription relation | Implemented/iterated through March commits; verify current relation/resource behavior before changing. |
| `plans/2026-03-28-organization-user-admin-access.md` | Admin access to organization users | Implemented/iterated through organization-user resource and manager membership work. |
| `plans/2026-03-28-organizations-module-implementation.md` | Superadmin organizations module | Broadly implemented through March organization commits; verify current resources/actions. |
| `plans/2026-03-28-organizations-seeding-implementation.md` | Showcase/demo organization seeding | Implemented through showcase seed commits; verify current seeders. |
| `plans/2026-03-28-projects-module-implementation.md` | Projects module | Implemented through March/April project commits; verify current project resource/service/tests. |
| `plans/2026-03-28-tenant-phone-consistency.md` | Tenant phone/profile consistency | Historical hardening plan; verify current profile/KYC/tenant forms. |

### Specs

| File | Historical topic | Current status hint |
| --- | --- | --- |
| `specs/2026-03-17-admin-organization-operations-design.md` | Admin organization operations | Much of this became the current admin workspace, then gained June billing/KYC/leads/move-out features. |
| `specs/2026-03-17-cross-cutting-behavioral-rules-design.md` | Shared access, validation, immutability, UX behavior | Use as design intent; current behavior is in policies/actions/tests. |
| `specs/2026-03-17-foundation-auth-onboarding-design.md` | Auth, onboarding, invitations | Current auth routes and onboarding exist; verify Livewire/auth requests before changing. |
| `specs/2026-03-17-legacy-domain-expansion-design.md` | Legacy domain import | Current import status is also summarized in `legacy-domain-import-ledger.md`. |
| `specs/2026-03-28-organization-single-subscription-relation-design.md` | Current subscription relation design | Verify current `Organization` relation and subscription resource behavior. |
| `specs/2026-03-28-organizations-module-design.md` | Organizations module design | Current module now includes extra health, feature flags, limits, write-offs, integration snapshots, and manager/team actions. |
| `specs/2026-03-28-organizations-seeding-design.md` | Showcase organization seeding | Verify current seeders before relying on dataset volumes. |
| `specs/2026-03-28-projects-module-design.md` | Projects module design | Current project module includes alerts, exports, costs, users, and lifecycle tests. |
| `specs/2026-03-28-tenant-phone-consistency-design.md` | Tenant phone consistency | Verify current profile and tenant forms, especially KYC/profile interactions. |

## Support Files

- `EXECUTION-ROADMAP.md`: historical rollout interpretation updated with current status.
- `PHASE-GATES.md`: historical phase gates updated with current status.
- `BRANCH-PLAYBOOK.md`: historical branch strategy updated with current status.
- `legacy-domain-import-ledger.md`: historical import ledger, still useful for model provenance.

## How To Use Historical Docs Safely

1. Read the current docs first.
2. Find the historical plan/spec only when you need intent.
3. Verify live code, migrations, routes, policies, translations, and tests.
4. When a historical doc and current code disagree, trust current code and update current docs if behavior changed.
5. Do not add new implementation instructions to historical files unless the task explicitly resumes that historical planning track.
