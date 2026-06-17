# Support Module Contract

> **AI agent usage:** Read this before changing impersonation, platform support actions, security violation handling, or support-mode access.

Updated on 2026-06-15.

## Purpose

Support owns safe platform assistance workflows, impersonation, support audit context, security violations, and support-only restrictions.

## Owns

- Models: `SecurityViolation`, `AuditLog`, `SuperAdminAuditLog`, support-related activity records.
- Actions: impersonation start/stop, security block actions, support diagnostics.
- Policies: security and audit policies.

## Invariants

- impersonation must be auditable;
- support access must not silently expand tenant/admin permissions;
- sensitive support actions require superadmin/platform permission;
- support workflows must not bypass organization scope.

## Tests And Scenarios

Primary tests include impersonation banner tests, superadmin resources, security tests, and permission matrix coverage.
