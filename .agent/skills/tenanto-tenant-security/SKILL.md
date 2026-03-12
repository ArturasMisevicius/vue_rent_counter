---
name: tenanto-tenant-security
description: Tenant boundary, authorization, and security hardening guidance for Tenanto. Use for auth/authz, middleware, policy, or data isolation changes.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Tenant Security

## Use This Skill When

- Working on tenant context, hierarchical access, impersonation, or role-based permissions.
- Updating authentication/authorization flow, policies, guards, or middleware.
- Auditing tenant isolation and sensitive data access.

## Security Priorities

- Never allow cross-tenant data leakage.
- Validate tenant context before data read/write operations.
- Enforce policy checks at controller/action/resource boundaries.
- Keep auditability for privileged operations.
- Fail closed on ambiguous access decisions.

## Project Anchors

- Middleware and guards: `app/Http/Middleware/*`.
- Authorization rules: `app/Policies/*`.
- Tenant context/services/scopes: `app/Services/Tenant*`, `app/Scopes/*`, `app/Traits/BelongsToTenant.php`.
- Security services and monitoring: `app/Services/Security/*`, related commands/events.

## Threat Review Checklist

1. Can this change expose data across organizations/tenants?
2. Is tenant context derived from trusted source and validated?
3. Are policy checks enforced on all entry points (HTTP, Filament, jobs, commands)?
4. Are logs/audit trails preserved for sensitive actions?
5. Are failure states explicit (`403`, `404`, `401`) and non-leaky?

## Testing Expectations

- Add/adjust feature tests for unauthorized and cross-tenant access attempts.
- Include positive authorization path and negative path.
- Prefer explicit assertions like `assertForbidden()` / `assertNotFound()` where appropriate.

## Completion Checklist

- [ ] Tenant isolation preserved.
- [ ] Authorization checks explicit and complete.
- [ ] Cross-tenant negative tests pass.
- [ ] Security regressions reviewed for related entry points.

