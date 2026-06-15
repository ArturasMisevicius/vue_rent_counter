# Tenanto Historical Phase Gates

> **AI agent usage:** This is historical rollout guidance. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/PROJECT-CONTEXT.md`, and `docs/FEATURES.md` first. Verify current code before using any gate as implementation guidance.

Updated on 2026-06-15. The original phase gates described when to advance during the March rollout. The current application has already moved past those gates and added many later features.

## Current Meaning Of The Old Gates

| Old gate | Current evidence to verify |
| --- | --- |
| Auth/onboarding foundation | `routes/web.php`, `app/Livewire/Auth`, `app/Livewire/Onboarding`, `app/Http/Requests/Auth`, `tests/Feature/Auth`. |
| Shared interface shell | `app/Livewire/Shell`, `app/Filament/Support/Shell`, `config/tenanto.php`, `tests/Feature/Shell`. |
| Admin operations | `app/Filament/Resources`, `app/Filament/Actions/Admin`, `app/Filament/Support/Admin`, `tests/Feature/Admin`. |
| Manager parity | `App\Enums\Permission`, `EffectivePermissionsResolver`, manager permission resources/tests, `docs/PERMISSION-MATRIX.md`. |
| Tenant portal | `app/Livewire/Tenant`, `app/Filament/Pages/Tenant*`, tenant presenters/actions, `tests/Feature/Tenant`. |
| Superadmin control plane | `app/Filament/Actions/Superadmin`, `app/Filament/Support/Superadmin`, superadmin resources/pages/tests. |
| Cross-cutting rules | security, billing, subscription, localization, route, and architecture tests. |
| Missing closures | invitation, session, continuity, breadcrumbs, empty-state, public surface, and tenant isolation tests. |

## Current Done Definition

For new work, a feature is not done until:

- route and navigation behavior are verified where relevant;
- backend authorization backs UI visibility;
- tenant and organization scope are enforced server-side;
- request validation exists for user input;
- actions/support/presenters own business logic instead of Blade;
- focused tests cover success, denial, and state-transition paths;
- docs and changelog are updated when user-facing behavior changes.

## Current Verification By Domain

Billing/readings:

```bash
php artisan test tests/Feature/Billing --compact
php artisan test tests/Feature/Tenant/TenantReadingWorkflowConsistencyTest.php --compact
```

Tenant portal:

```bash
php artisan test tests/Feature/Tenant --compact
php artisan test tests/Feature/Security/TenantPortalIsolationTest.php --compact
```

Manager permissions:

```bash
php artisan test tests/Feature/Admin/RolePermissionMatrixTest.php --compact
php artisan test tests/Feature/Admin/ManagerPermissionSystemTest.php --compact
```

Public/security guardrails:

```bash
composer guard:phase1
```

Docs-only:

```bash
git diff --check -- $(rg --files -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**')
```

## Historical Note

Do not use this file to decide that an old phase still needs to be built. Use it only to understand the quality bar that current and future features should meet.
