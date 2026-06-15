# Role & Permission Matrix

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/FEATURES.md`, and this document before changing role, policy, Filament navigation, tenant portal, invitation, billing, document, KYC, contract, audit, or impersonation behavior.

Updated on 2026-06-15 from the current code inventory and the June billing/document/KYC/move-out commits.

Tenanto uses four product roles:

- `SUPERADMIN`: platform operator, scoped to platform support and security operations.
- `ADMIN`: organization owner/admin, scoped to one organization.
- `MANAGER`: organization staff member, scoped to one organization and a manager permission preset/matrix.
- `TENANT`: self-service portal user, scoped to their own tenant profile and visible portal data.

Permissions are enforced in layers:

1. Role and organization membership.
2. Central permission vocabulary in `App\Enums\Permission`.
3. Effective permission resolution in `App\Services\Authorization\EffectivePermissionsResolver`.
4. Policies, middleware, and action-level authorization.
5. Audit logs for sensitive actions and forbidden attempts.

UI visibility is never the only control. Hidden buttons must be backed by a policy, middleware, or action authorization check.

## Matrix

| Resource / Action | SUPERADMIN | ADMIN | MANAGER | TENANT |
| --- | --- | --- | --- | --- |
| View platform dashboard | Yes | No | No | No |
| Manage organizations | Yes | No | No | No |
| Manage subscriptions | Yes | Own organization billing/subscription settings only | No | No |
| Manage languages/translations | Yes | No | No | No |
| Impersonate users | If enabled and audited | No | No | No |
| View own organization dashboard | Support/inspect | Yes | Yes | No |
| Manage buildings | Support/inspect | Yes, own org | If preset allows | No |
| Manage properties | Support/inspect | Yes, own org | If preset allows | No |
| Manage tenants | Support/inspect | Yes, own org | If preset allows | No |
| Send tenant invitations | Support/inspect | Yes, own org | If preset/policy allows | No |
| Create manager | Yes | Yes, own org | No | No |
| Change manager permissions | Yes | Yes, own org, not self | No | No |
| Manage meters | Support/inspect | Yes, own org | If preset allows | No |
| Submit readings | No by default | On behalf, own org | On behalf if preset allows | Own active portal/invoice only |
| Approve/reject/correct readings | Support/inspect | Yes, own org | If preset allows | No |
| Generate invoices | Support/inspect | Yes, own org | If preset allows | No |
| Approve/send invoices | Support/inspect | Yes, own org | If preset allows | No |
| Void/cancel invoices | Support/inspect and audited | Admin only unless special manager permission is granted | Special permission only, audited | No |
| Record/confirm payments | Support/inspect | Yes, own org | If preset allows | Own proof upload only |
| Void payments | Support/inspect and audited | Yes, own org, audited | Special permission only, audited | No |
| View documents | Support/inspect | Own org | If preset allows | Own tenant-visible only |
| Upload documents | Support/inspect | Own org | If preset allows | KYC/payment proof only |
| Delete/archive documents | Support/inspect and audited | Own org, audited | Archive if preset allows | No |
| Review tenant KYC | Support/inspect | Own org | If preset allows | No |
| Submit tenant KYC | No | No | No | Own active portal only |
| Manage contracts | Support/inspect | Yes, own org | If preset allows | Own visible/downloadable only |
| Manage move-out lifecycle | Support/inspect | Own org | If preset allows | No |
| View move-out-affected portal data | No | No | No | Only allowed post-move-out data |
| Manage leads | Support/inspect | Own org | If preset allows | No |
| Manage projects/tasks | Platform/inspect | Own org if enabled | If preset allows | No |
| Manage tariffs/providers/services | Support/inspect | Yes, own org | Full manager only unless explicitly granted | No |
| View reports | Support/inspect | Own org | If preset allows | No |
| View audit logs | Yes | Own org | No by default | No |
| Export audit logs | Yes and audited | Own org if explicitly allowed, audited | No | No |
| Access admin workspace | Platform workspace | Organization workspace | Organization workspace if active | No |
| Access tenant portal | No | No | No | Own active portal only |

## Manager Presets

Manager presets are implemented through the manager permission matrix and exposed as stable preset keys:

| Preset | Intended powers | Not allowed by default |
| --- | --- | --- |
| `full_manager` | Operational create/edit/delete across organization resources, including billing support resources. | Platform settings, subscription management, team management, superadmin actions, self-escalation. |
| `billing_manager` | Readings review, invoice generation/recalculation/review/send, extra charges, payment confirmation/rejection, billing reports. | Team management, subscriptions, contracts, property core edits, tariffs/providers/service configuration changes. |
| `property_manager` | Buildings/properties/tenants/meters/documents/contracts management. | Invoice approval, payment confirmation, tariff/configuration management, team management. |
| `read_only_manager` | View organization operational data and reports. | Create, update, delete, approve, send, void, archive. |

Legacy preset keys are still accepted for compatibility:

- `full_access` maps to `full_manager`.
- `read_only` maps to `read_only_manager`.

## Permission Groups

The canonical permission values live in `App\Enums\Permission` and cover:

- Platform: organizations, subscriptions, languages, platform audit, system settings, impersonation.
- Organization workspace: dashboard, buildings, properties, tenants, meters, readings, invoices, payments, extra charges, documents, contracts, services, tariffs, providers, reports, notifications, leads, team, audit.
- Tenant portal: own property, own invoices, own readings, own visible documents, own profile, own KYC, own payment proof.

When adding a new sensitive action, update `App\Enums\Permission`, the manager permission catalog, the relevant policies/actions, tests, and this matrix in the same change.

## Sensitive Permissions

Sensitive permissions must be audited and require an explicit reason where the action is destructive:

- `invoices.void`
- `payments.void`
- `documents.delete`
- `documents.visibility`
- `kyc.approve`
- `kyc.reject`
- `contracts.terminate`
- `move_out.complete`
- `audit.export`
- `settings.billing`
- `settings.subscription`
- `team.manage`
- `tariffs.manage`
- `impersonation.start`

## Organization Isolation

Organization data access must satisfy:

```text
record.organization_id == current organization id
```

This applies to tenants, buildings, properties, meters, readings, invoices, payments, documents, contracts, services, tariffs, reports, notifications, and audit logs.

Managers must have an active `organization_user` membership or active direct organization manager role. Disabled manager memberships block workspace permissions even if the user account still exists.

Admins can manage only their own organization. Admins cannot create superadmins, platform admins, users without organization scope, or users in another organization.

## Tenant Isolation

Tenant portal access requires:

```text
user.role == tenant
user.status == active
user.portal_access_enabled == true
```

Tenant data access must satisfy both:

```text
record.organization_id == tenant.organization_id
record belongs to the current tenant profile or current active assignment
```

Tenants cannot access admin workspace, internal documents, other tenant invoices, other tenant readings, organization settings, tariffs, billing approval actions, or audit logs.

## Action Authorization Pattern

Sensitive and business-critical actions must follow this sequence:

```text
authorize actor
validate organization or tenant scope
validate state transition
perform mutation
write audit log
notify affected users if required
```

Examples:

- `GenerateDraftInvoices`
- `ApproveReading`
- `RejectReading`
- `CorrectReading`
- `ApproveInvoice`
- `SendInvoice`
- `ConfirmPayment`
- `VoidPayment`
- `InviteTenant`
- `InviteManager`
- `UploadDocument`
- `ApproveKycDocument`
- `RejectTenantKycProfile`
- `TerminateContract`
- `ScheduleTenantMoveOut`
- `CompleteTenantMoveOut`

## Required Policy Coverage

Policies must cover:

- `Organization`
- `Building`
- `Property`
- `Tenant/User`
- `Meter`
- `MeterReading`
- `Invoice`
- `InvoicePayment`
- `TenantDocument`
- `TenantKycProfile`
- `TenantKycDocument`
- `RentalContract`
- `ServiceConfiguration`
- `Tariff`
- `OrganizationUser`
- `AuditLog`
- `Notification`

Every Filament resource action and backend action must call the matching policy or resolver-backed authorization path.

## Audit And Security Events

Write audit or security violation entries for:

- Role assigned or changed.
- Permission preset changed.
- Manager disabled or reactivated.
- Manager invitation sent, resent, revoked, or accepted.
- Admin attempted to create a superadmin.
- Manager attempted to change own permissions.
- Tenant attempted admin route access.
- Cross-organization access attempt.
- Cross-tenant invoice/document access attempt.
- Cross-tenant KYC/document/contract download attempt.
- Superadmin impersonation started or stopped.
- Sensitive permission used.

Audit metadata should include actor, target user, organization, old/new role or permissions, IP, user agent, action, and severity where applicable.

## Test Checklist

Role and permission work must include tests for:

- Superadmin platform access and sensitive audit.
- Admin own-organization access and cross-organization denial.
- Admin cannot create superadmin.
- Admin can invite managers only inside own organization.
- Manager preset permissions resolve correctly.
- Read-only manager cannot create or mutate.
- Billing manager can review billing work but cannot manage tariffs/team.
- Property manager cannot approve invoices or confirm payments.
- Manager cannot change own permissions.
- Disabled manager cannot access workspace permissions.
- Tenant can access tenant portal.
- Tenant cannot access admin panel.
- Tenant can view/download only own visible data.
- Tenant KYC upload/download is scoped to the current tenant.
- Admin/manager KYC review is scoped to own organization and audited.
- Move-out lifecycle cannot expose another tenant's readings, documents, invoices, contracts, or portal data.
- Cross-organization and cross-tenant access is blocked.
- Sensitive actions write audit logs.
- Forbidden access attempts are logged.
