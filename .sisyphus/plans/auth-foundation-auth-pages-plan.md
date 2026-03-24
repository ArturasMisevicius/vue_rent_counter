# Plan: Foundation + Authentication Pages

## 0) Intent, Scope, and Locked Decisions

### Intent
Deliver the **Foundation Principles + Authentication Pages** behavior set using existing Laravel/Filament/Livewire architecture, with TDD and tenant-safety-first guardrails.

### Locked by user
- Scope: **Foundation + auth**
- Testing: **TDD (recommended)**
- Destructive confirmations: **all pages touched by this plan**
- Default background refresh interval: **60 seconds**

### In-scope
- Shared login entrypoint for all roles and role-appropriate post-login destinations
- Login, register, forgot password, reset password, invitation acceptance UX/validation behavior
- Immediate per-field validation UX for auth flows (localized)
- Locale behavior for auth + touched shell/dashboard surfaces without full-page reload
- Background refresh behavior for touched data pages (no full-page flashes)
- Tenant isolation hardening/verification for touched flows

### Out-of-scope
- Billing/reporting feature expansion
- Repo-wide destructive-action remediation outside touched surfaces
- Large refactors unrelated to foundation/auth requirements

---

## 1) Current Baseline (from repository)

- Auth pages/routes already exist (`routes/web.php`, `app/Livewire/Auth/*`, `resources/views/auth/*`).
- Role enum and routing primitives already exist (`app/Enums/UserRole.php`, `LoginRedirector.php`, `DashboardPage.php`, unified `AppPanelProvider`).
- Tenant/workspace safety foundation exists (`WorkspaceResolver`, `OrganizationContext`, scoped resource queries, isolation tests).
- Locale switching foundation exists (`SetGuestLocale`, `SetAuthenticatedUserLocale`, `LanguageSwitcher`, supported locales `en/lt/ru/es`).
- Background refresh exists but defaults are mostly 30s in touched dashboard areas.
- Auth + tenancy + shell tests already exist and are strong (Pest feature/livewire suites).

---

## 2) Gap Targets to Close

1. **Immediate field validation UX** on auth forms is not fully Livewire-first/per-field reactive yet.
2. **Role landing semantics** must be explicitly validated against foundation text (superadmin platform, admin/manager admin dashboard, tenant home).
3. **60s refresh standard** must replace current touched 30s defaults.
4. **No-flash data refresh** needs explicit touched-surface verification.
5. **Destructive confirmation standard** (name item in confirmation) must be audited for touched surfaces.
6. **Tenant isolation** must remain fail-closed under URL tampering and cross-org record access for touched auth/shell flows.

---

## 3) Execution Phases (TDD)

## Phase 1 — Lock behavior with failing tests first (RED)

### Files (tests)
- `tests/Feature/Auth/LoginFlowTest.php`
- `tests/Feature/Auth/RegistrationAndOnboardingTest.php` (create if missing)
- `tests/Feature/Auth/PasswordResetTest.php`
- `tests/Feature/Auth/InvitationAcceptanceTest.php`
- `tests/Feature/Shell/LocaleSwitcherTest.php`
- `tests/Feature/Shell/AuthenticatedShellTest.php`
- `tests/Feature/Livewire/Dashboard/DashboardRealtimeEventsTest.php`
- `tests/Feature/Security/TenantIsolationTest.php`
- `tests/Feature/Security/TenantPortalIsolationTest.php`

### Add/adjust tests for required outcomes
- Login page copy/state: heading/subheading, invalid creds message, email retention, loading-state hooks.
- Register flow: admin role assignment, auto-login, onboarding redirect.
- Forgot/reset flow: non-enumerating reset responses, reset success copy/link behavior.
- Invitation flow: prefill name, expiration copy, successful role-bound login.
- Role destination assertions:
  - Superadmin → platform dashboard experience
  - Admin/Manager → admin dashboard experience
  - Tenant → tenant home experience
- Intended URL restoration after auth for guest-protected pages.
- Locale switch assertions for touched auth/shell text in `en/lt/ru/es`.
- Refresh cadence assertions for touched dashboards (60s poll/event behavior).
- Tenant isolation assertions for touched auth/shell/tenant portal paths.

### Phase 1 exit
- New/updated tests fail for missing behavior and clearly identify required implementation deltas.

### QA checkpoint (must run)
- Tool: `php artisan test --compact tests/Feature/Auth/LoginFlowTest.php tests/Feature/Auth/RegistrationAndOnboardingTest.php tests/Feature/Auth/PasswordResetTest.php tests/Feature/Auth/InvitationAcceptanceTest.php`
- Expected result: tests fail only on newly asserted target behavior (RED baseline), not on unrelated regressions.

---

## Phase 2 — Auth page behavior alignment (GREEN)

### Files (primary)
- `app/Livewire/Auth/LoginPage.php`
- `app/Livewire/Auth/RegisterPage.php`
- `app/Livewire/Auth/ForgotPasswordPage.php`
- `app/Livewire/Auth/ResetPasswordPage.php`
- `app/Livewire/Auth/AcceptInvitationPage.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/accept-invitation.blade.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Auth/ForgotPasswordRequest.php`
- `app/Http/Requests/Auth/ResetPasswordRequest.php`
- `app/Http/Requests/Auth/AcceptInvitationRequest.php`

### Tasks
1. Move auth forms to consistent **Livewire per-field validation** behavior (blur/live-debounced) while keeping FormRequest-backed validation as source of truth.
2. Ensure field-level errors render directly below corresponding inputs across auth screens.
3. Preserve existing security invariants:
   - session regeneration on login/invitation acceptance
   - password reset token handling and throttles
   - generic reset-link response copy
4. Standardize loading states for submit buttons (spinner + disabled) across all auth forms.
5. Keep/verify intended URL restore and safe intended target filtering.

### Phase 2 exit
- Auth tests from Phase 1 pass for validation UX + flow behavior.

### QA checkpoint (must run)
- Tool: `php artisan test --compact tests/Feature/Auth/LoginFlowTest.php tests/Feature/Auth/RegistrationAndOnboardingTest.php tests/Feature/Auth/PasswordResetTest.php tests/Feature/Auth/InvitationAcceptanceTest.php`
- Expected result: all auth-flow assertions pass, including localized field errors and role-safe redirects.

---

## Phase 3 — Role destination + unified shell semantics

### Files
- `app/Filament/Support/Auth/LoginRedirector.php`
- `app/Http/Controllers/DashboardRedirectController.php`
- `app/Livewire/Pages/DashboardPage.php`
- `app/Providers/Filament/AppPanelProvider.php`
- `tests/Feature/Auth/LoginFlowTest.php`
- `tests/Feature/Filament/UnifiedPanelTest.php`
- `tests/Feature/Shell/AuthenticatedShellTest.php`

### Tasks
1. Make role destination semantics explicit and test-locked:
   - superadmin lands in platform dashboard context
   - admin/manager land in admin dashboard context
   - tenant lands in tenant home context
2. Keep single visual shell while preserving role-specific navigation groups/actions.
3. Confirm unauthenticated redirects preserve intended destination and return correctly post-login.

### Phase 3 exit
- Role-routing + shell tests pass with explicit assertions on role-specific landing and navigation boundaries.

### QA checkpoint (must run)
- Tool: `php artisan test --compact tests/Feature/Auth/LoginFlowTest.php tests/Feature/Filament/UnifiedPanelTest.php tests/Feature/Shell/AuthenticatedShellTest.php`
- Expected result: all role landing + shared-shell visibility assertions pass.

---

## Phase 4 — Locale switching without full-page reload (touched surfaces)

### Files
- `app/Livewire/Shell/LanguageSwitcher.php`
- `app/Filament/Actions/Preferences/UpdateUserLocaleAction.php`
- `app/Http/Middleware/SetGuestLocale.php`
- `app/Http/Middleware/SetAuthenticatedUserLocale.php`
- `resources/views/livewire/shell/language-switcher.blade.php`
- `resources/views/auth/*.blade.php` (touched auth pages)
- `tests/Feature/Shell/LocaleSwitcherTest.php`
- `tests/Feature/Public/GuestAuthLocaleSwitcherTest.php`

### Tasks
1. Keep hybrid locale persistence (authenticated user DB + guest session) unless changed later.
2. Ensure language switch dispatch updates touched Livewire surfaces immediately without full-page reload.
3. Verify translated labels/messages/errors for touched auth + shell areas in all four locales.

### Phase 4 exit
- Locale-switch tests pass for immediate visible updates on touched surfaces.

### QA checkpoint (must run)
- Tool: `php artisan test --compact tests/Feature/Shell/LocaleSwitcherTest.php tests/Feature/Public/GuestAuthLocaleSwitcherTest.php tests/Feature/Auth/InvitationLocalePersistenceTest.php tests/Feature/Auth/LocalePersistenceTest.php`
- Expected result: locale switch behavior passes for authenticated + guest flows in supported locales.

---

## Phase 5 — Background refresh standardization to 60s + no flash

### Files
- `config/tenanto.php`
- `resources/views/livewire/pages/dashboard/admin-dashboard.blade.php`
- `resources/views/livewire/pages/dashboard/superadmin-dashboard.blade.php`
- `resources/views/livewire/pages/dashboard/tenant-dashboard.blade.php`
- `app/Livewire/Concerns/ListensForDashboardRefreshes.php`
- `tests/Feature/Livewire/Dashboard/DashboardRealtimeEventsTest.php`

### Tasks
1. Change touched dashboard polling defaults to **60s**.
2. Keep refreshes scoped to data regions (`wire:poll` blocks/events), not full page reload.
3. Ensure stale/loading flash regressions are covered by view-level + component behavior assertions.
4. Keep event-driven refresh path (`invoice.finalized`, `reading.submitted`) intact.

### Phase 5 exit
- Realtime/dashboard tests pass with 60s expectations and no full-page refresh regressions.

### QA checkpoint (must run)
- Tool: `php artisan test --compact tests/Feature/Livewire/Dashboard/DashboardRealtimeEventsTest.php tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php tests/Feature/Livewire/Dashboard/SuperadminDashboardComponentTest.php tests/Feature/Livewire/Dashboard/TenantDashboardComponentTest.php`
- Expected result: dashboard refresh semantics pass with event-driven updates and touched poll interval assertions.

---

## Phase 6 — Destructive action confirmation standard (touched pages only)

### Files (audit + remediation targets)
- UI trigger definitions (primary targets for confirmation copy):
  - `app/Filament/Resources/Tenants/Pages/EditTenant.php`
  - `app/Filament/Resources/Tenants/Tables/TenantsTable.php`
  - `app/Filament/Resources/Buildings/Tables/BuildingsTable.php`
  - `app/Filament/Resources/Tariffs/Pages/EditTariff.php`
  - `app/Filament/Resources/Tariffs/Tables/TariffsTable.php`
- Handler/action classes to keep behavior aligned after UI-copy changes:
  - `app/Filament/Actions/Admin/Tenants/DeleteTenantAction.php`
  - `app/Filament/Actions/Admin/Buildings/DeleteBuildingAction.php`
  - `app/Filament/Actions/Admin/Tariffs/DeleteTariffAction.php`

### Tasks
1. Audit touched destructive/irreversible actions.
2. Ensure confirmation dialogs explicitly include the specific item name.
3. Add/update tests for dialog copy contract where covered by Filament/Livewire test APIs.

### Phase 6 exit
- All touched destructive actions present explicit item-named confirmation copy.

### QA checkpoint (must run)
- Tool: `php artisan test --compact tests/Feature/Admin/TenantsResourceTest.php tests/Feature/Admin/BuildingsResourceTest.php tests/Feature/Admin/TariffsResourceTest.php tests/Feature/Admin/DeleteTariffAuthorizationTest.php`
- Expected result: touched delete/destructive flows remain authorized/scoped and render item-specific confirmation copy where asserted.

---

## Phase 7 — Tenant isolation hardening + final regression net

### Files
- `app/Filament/Support/Workspace/WorkspaceResolver.php`
- `app/Filament/Support/Admin/OrganizationContext.php`
- `app/Http/Middleware/EnsureAccountIsAccessible.php`
- `app/Http/Middleware/EnsureUserIsTenant.php`
- touched resources/controllers from earlier phases
- security tests listed in Phase 1

### Tasks
1. Re-verify fail-closed behavior on cross-org URL/resource access after auth/shell changes.
2. Ensure no touched query path can bypass organization/tenant scope.
3. Keep policy/resource query constraints consistent for admin/manager/tenant/superadmin access.

### Phase 7 exit
- Security/isolation tests pass; no cross-organization data access regressions.

### QA checkpoint (must run)
- Tool: `php artisan test --compact tests/Feature/Security/TenantIsolationTest.php tests/Feature/Security/TenantPortalIsolationTest.php tests/Feature/Security/WorkspaceContextResolutionTest.php`
- Expected result: all cross-org isolation assertions pass after auth/shell/locale/refresh changes.

---

## 4) Verification Matrix (must pass before completion)

## Lint/format/static
- `vendor/bin/pint --dirty --format agent`
- `lsp_diagnostics` on all modified files (0 errors)

## Focused test runs (incremental)
- `php artisan test --compact tests/Feature/Auth/LoginFlowTest.php`
- `php artisan test --compact tests/Feature/Auth/RegistrationAndOnboardingTest.php`
- `php artisan test --compact tests/Feature/Auth/PasswordResetTest.php`
- `php artisan test --compact tests/Feature/Auth/InvitationAcceptanceTest.php`
- `php artisan test --compact tests/Feature/Shell/LocaleSwitcherTest.php`
- `php artisan test --compact tests/Feature/Public/GuestAuthLocaleSwitcherTest.php`
- `php artisan test --compact tests/Feature/Shell/AuthenticatedShellTest.php`
- `php artisan test --compact tests/Feature/Filament/UnifiedPanelTest.php`
- `php artisan test --compact tests/Feature/Livewire/Dashboard/DashboardRealtimeEventsTest.php`
- `php artisan test --compact tests/Feature/Security/TenantIsolationTest.php`
- `php artisan test --compact tests/Feature/Security/TenantPortalIsolationTest.php`

## Final confidence sweep (related surface)
- `php artisan test --compact --filter=Auth`
- `php artisan test --compact --filter=Shell`
- `php artisan test --compact --filter=Tenant`

---

## 5) Risks and Mitigations

- **Risk:** Validation logic drift between FormRequest and Livewire interactions.
  - **Mitigation:** keep FormRequest-driven payload validation as canonical; test rules/messages in one place.

- **Risk:** Locale changes update only partial UI.
  - **Mitigation:** event-driven rerender assertions on touched components + locale tests in all 4 languages.

- **Risk:** Polling interval change impacts perceived freshness.
  - **Mitigation:** retain event-driven immediate refresh for critical updates, poll as fallback.

- **Risk:** Auth/shell tweaks introduce tenancy leakage.
  - **Mitigation:** rerun isolation suites and fail-closed middleware/resource checks before completion.

---

## 6) Definition of Done

1. All Phase 1–7 acceptance conditions satisfied.
2. TDD cycle followed for each behavior change.
3. Touched auth/shell/dashboard/security tests pass.
4. Pint + diagnostics clean on modified files.
5. Final behavior matches foundation/auth requirements for:
   - role-aware landing in unified shell
   - localized auth UX in 4 languages
   - immediate field-level validation feedback
   - safe reset/invitation flows
   - 60s background refresh without full-page flashes
   - strict tenant isolation in touched paths.
