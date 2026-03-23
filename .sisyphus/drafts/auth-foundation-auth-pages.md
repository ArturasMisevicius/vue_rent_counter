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
- Pending: implementation boundaries against existing codebase patterns.
- Pending: exact refresh mechanism (Livewire polling/events/streaming strategy).
- Pending: role-shell implementation approach and guard strategy.

## Test Strategy Decision
- **Infrastructure exists**: Pending assessment
- **Automated tests**: Pending decision
- **Agent-Executed QA**: Mandatory (to be included in final plan)

## Scope Boundaries
- INCLUDE: Foundation principles listed above + authentication pages/flows listed above.
- EXCLUDE (tentative): Billing, reporting, non-auth domain pages unless explicitly requested.

## Open Questions
- Should the upcoming work plan cover only the foundation + auth pages, or also include rollout tasks for all existing role-specific pages to comply with the same principles?
- What background refresh interval(s) should be used by default (global vs per-page)?
- Should language preference be persisted per-user in DB, browser storage, or both?

## Research Findings
- In progress: repository exploration launched for auth flow patterns, role-shell/navigation conventions, tenant isolation mechanisms, official guidance, and test infrastructure assessment.
