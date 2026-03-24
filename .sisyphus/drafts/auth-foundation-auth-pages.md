# Draft: Auth Foundation + Authentication Pages

## Requirements (confirmed)
- Single shared login page for all roles (`SUPERADMIN`, `ADMIN`, `MANAGER`, `TENANT`).
- Post-login role detection must route users to role-specific start pages:
  - Superadmin → platform dashboard
  - Admin/Manager → admin dashboard
  - Tenant → tenant home
- Unified visual shell, but role-specific navigation/content/actions.
- Full i18n for all user-facing text in 4 languages: English, Lithuanian, Russian, Spanish.
- Language switch must update UI text immediately without page reload.
- Data pages auto-refresh in background at defined intervals; no full-page flashes/loading screens.
- Only data regions should update, and only when data has changed.
- Form validation should be per-field and immediate on blur/typing completion, with errors shown directly under the field in selected language.
- Destructive/irreversible actions require confirmation, explicitly naming the affected item.
- Tenant data isolation is absolute; URL tampering must never expose cross-organization data.

## Authentication Flows (confirmed)
- **Login page**:
  - Centered, Tenanto logo, heading/subheading, Email + Password fields, forgot password link, loading state on submit.
  - Invalid credentials message above form.
  - Preserve email value after failed login.
  - Redirect unauthenticated users to login, then back to originally requested page after success.
  - Link to Register page.
- **Register page**:
  - Fields: Full Name, Email Address, Password, Confirm Password (all required).
  - Password minimum 8 chars; confirm password must match with immediate error.
  - Successful registration auto-logs-in and redirects to trial/welcome onboarding screen.
  - Registration via public page always creates `ADMIN` role.
  - `TENANT` and `MANAGER` accounts are created by Admin inside app.
  - Link back to Login.
- **Forgot password page**:
  - Email input + Send Reset Link.
  - Always show non-enumerating confirmation message.
- **Password reset page**:
  - New Password + Confirm New Password.
  - Success message + link back to login.
- **Invitation acceptance page**:
  - Invitation context message includes organization name.
  - Fields: Full Name (prefill when available), Password, Confirm Password.
  - Accept action creates account + immediate login into assigned role.
  - Expired invitation shows explicit expiration message.

## Technical Decisions
- Follow existing Livewire + FormRequest auth stack (`app/Livewire/Auth/*`, `app/Http/Requests/Auth/*`) instead of introducing a new auth subsystem.
- Keep current role redirect architecture via `App\Filament\Support\Auth\LoginRedirector` and adjust mappings/targets only where required.
- Reuse existing workspace/tenant boundary model (`WorkspaceResolver`, `OrganizationContext`, resource query scoping) as security foundation.

## Test Strategy Decision
- **Infrastructure exists**: YES (Pest + PHPUnit + extensive Feature/Livewire tests)
- **Automated tests**: YES (TDD)
- **Agent-Executed QA**: Mandatory (to be included in final plan)

## Scope Boundaries
- INCLUDE: Foundation principles listed above + authentication pages/flows listed above.
- EXCLUDE: Billing, reporting, non-auth domain pages unless explicitly requested.
- Scope selection confirmed by user: **Foundation + auth (recommended)**.
- Destructive action confirmation standard: **apply to all pages touched by this plan**.
- Default background refresh interval: **60 seconds**.

## Open Questions
- Should language preference persistence remain the current hybrid model (authenticated users in DB + guests in session), or be changed to a single storage strategy?

## Research Findings
- **Auth routes/components already exist**:
  - Routes: `routes/web.php` (`/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/invitations/{token}/accept`)
  - Components: `app/Livewire/Auth/LoginPage.php`, `RegisterPage.php`, `ForgotPasswordPage.php`, `ResetPasswordPage.php`, `AcceptInvitationPage.php`
  - Redirect logic: `app/Filament/Support/Auth/LoginRedirector.php`
  - Intended URL handling: `LoginPage::mount()` + `redirect()->intended(...)`
- **Role/shell architecture exists and is unified**:
  - Unified panel + role-aware nav groups/items in `app/Providers/Filament/AppPanelProvider.php`
  - Role enum: `app/Enums/UserRole.php`
  - Role dashboard composition: `app/Livewire/Pages/DashboardPage.php`
- **Tenant isolation foundation exists**:
  - Workspace context resolution: `app/Filament/Support/Workspace/WorkspaceResolver.php`
  - Tenant-only middleware: `app/Http/Middleware/EnsureUserIsTenant.php`
  - Account/org accessibility guard: `app/Http/Middleware/EnsureAccountIsAccessible.php`
  - Org-scoped resource queries/policies in resources like `TenantResource.php`, `PropertyResource.php`, and policy checks like `TariffPolicy.php`
- **Localization foundation exists (4 locales preconfigured)**:
  - Supported locales in `config/app.php` and labels in `config/tenanto.php`
  - Guest + authenticated locale middleware: `SetGuestLocale`, `SetAuthenticatedUserLocale`
  - Runtime language switch component: `app/Livewire/Shell/LanguageSwitcher.php`
- **Background refresh foundation exists**:
  - Polling on data regions (example `wire:poll.30s` in dashboard views)
  - Event-based refresh hooks (`ListensForDashboardRefreshes`, dashboard realtime tests)
- **Test infrastructure exists and is strong**:
  - Pest 4 + PHPUnit 12 configured (`composer.json`, `tests/Pest.php`, `phpunit.xml`)
  - Auth flow tests: `tests/Feature/Auth/LoginFlowTest.php`, `PasswordResetTest.php`, `InvitationAcceptanceTest.php`
  - Isolation tests: `tests/Feature/Security/TenantIsolationTest.php`, `TenantPortalIsolationTest.php`, `WorkspaceContextResolutionTest.php`
  - Livewire realtime tests: `tests/Feature/Livewire/Dashboard/DashboardRealtimeEventsTest.php`
- **External guidance captured (official patterns)**:
  - Use `redirect()->intended(fallback)` + session regeneration after login.
  - Keep password reset responses non-enumerating and token expiry/rate limits enforced.
  - Invitation links should stay hashed, expiring, and single-use.
  - Prefer scoped/targeted refresh patterns (`wire:poll` intervals + event-driven updates) to avoid full-page loading flashes.

## Plan Artifact
- Final executable plan: `.sisyphus/plans/auth-foundation-auth-pages-plan.md`
- Plan QA status: **Momus reviewed and approved** after revisions.
