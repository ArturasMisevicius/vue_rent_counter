---
inclusion_mode: "conditional"
file_patterns:
  - "app/Providers/Filament/**/*.php"
  - "app/Filament/Pages/Auth/**"
  - "app/Policies/**"
---

# Filament Auth & Tenancy

## Panel auth
- Configure guard/password broker explicitly; enable MFA when possible.
- Use custom login/register pages only if UX requires; keep reset/verify flows intact.
- Throttle logins; offer “logout other devices” if security-sensitive.

## Permissions
- Every resource/action should defer to policies or explicit `can()` checks.
- Bulk actions must gate on same ability as single actions.
- Hide navigation/resources for unauthorized users; don’t just disable actions.

## Tenancy
- Use `->tenant()` for team/org models; rely on v4 auto-scoping.
- Ensure created records inherit tenant ID; avoid manual `where('team_id')` unless overriding default.
- Prevent cross-tenant leakage in global search/widgets/reports.
- Shield roles and permissions are automatically scoped to current tenant.
- Assign roles within team context: `$user->assignRole('admin', $team)`.

## Impersonation / login-as
- If supported, track and display impersonation state; restrict to admins; log exit.

## Sessions
- Encourage short TTL for admin panels; rotate session on privilege changes.
- Consider device/session listing with revoke controls for security monitoring.
