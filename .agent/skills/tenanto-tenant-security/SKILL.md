---
name: tenanto-tenant-security
description: Use for Tenanto auth, authorization, impersonation, tenant isolation, policy, or sensitive data boundary changes.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Tenant Security

## Use This Skill When

- Working on tenant context, hierarchical access, impersonation, or role-based permissions.
- Updating authentication or authorization flow, policies, guards, middleware, or Filament access.
- Auditing tenant isolation and sensitive data access.

## Security Priorities

- Never allow cross-tenant data leakage.
- Validate tenant context before data read or write operations.
- Enforce policy checks at controller, action, resource, page, and component boundaries.
- Keep auditability for privileged operations.
- Fail closed on ambiguous access decisions.

## Project Anchors

- Middleware and guards: `app/Http/Middleware/*`
- Authorization rules: `app/Policies/*`
- Tenant context and visibility rules: model scopes in `app/Models/*`, especially `User`, `Invoice`, `PropertyAssignment`, `Meter`, and `MeterReading`
- Shell and auth support: `app/Filament/Support/Auth/*`, `app/Filament/Support/Shell/*`, and `app/Livewire/Shell/*`
- Tenant-facing queries and presenters: `app/Filament/Support/Tenant/Portal/*`
- Write-side validation: `app/Filament/Requests/*`
- Public and auth entry points: `app/Livewire/Auth/*`, `app/Livewire/Preferences/*`, `app/Livewire/PublicSite/*`
- Public web root currently exposes only `public/index.php`; do not add public debug entrypoints

## Threat Review Checklist

1. Can this change expose data across organizations or tenants?
2. Is tenant context derived from a trusted source and validated?
3. Are policy checks enforced on all entry points, including Filament and Livewire actions?
4. Are logs or audit trails preserved for sensitive actions?
5. Are failure states explicit (`403`, `404`, `401`) and non-leaky?
6. Are request-level `exists` rules scoped to the actor tenant when IDs reference tenant-owned models?
7. Are `#[Locked]` Livewire properties or other server-owned identifiers protected from client-side mutation where needed?

## Testing Expectations

- Add or adjust feature tests for unauthorized and cross-tenant access attempts.
- Include positive authorization path and negative path.
- Prefer explicit assertions like `assertForbidden()` and `assertNotFound()` where appropriate.
- Cover impersonation stop or start behavior when that surface is touched.

## Completion Checklist

- [ ] Tenant isolation preserved.
- [ ] Authorization checks explicit and complete.
- [ ] Cross-tenant negative tests pass.
- [ ] Security regressions reviewed for related entry points.
