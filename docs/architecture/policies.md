# Policy And Permission Conventions

> **AI agent usage:** Read this with `docs/PERMISSION-MATRIX.md` before changing authorization, manager permissions, tenant access, documents, KYC, billing, payments, or impersonation.

Updated on 2026-06-15.

## Rule

UI visibility is not authorization. Every sensitive workflow must be protected by a policy, gate, middleware, resolver-backed permission, or action-level guard that survives direct URL and programmatic calls.

## Policy Surface

Sensitive models should have policies with methods such as:

- `viewAny`;
- `view`;
- `create`;
- `update`;
- `delete` or archive equivalent;
- domain abilities such as `download`, `approve`, `send`, `void`, or `export`.

## Permission Vocabulary

Canonical permission keys live in `App\Enums\Permission`. New permissions should use dotted business names:

```text
payments.confirm
payments.void
documents.download
tenant_portal.invoices.download_own
```

Avoid ad hoc variants:

```text
canApproveInvoice
invoice_approve
approve-invoices
```

## Manager Permissions

Manager write access is resolved through the manager permission service and policy traits. Do not duplicate manager preset checks in UI callbacks. UI action visibility may mirror policy decisions, but action/policy enforcement is authoritative.

## Tenant Access

Tenant policies must check:

- tenant identity;
- organization identity;
- tenant-visible flags for documents/contracts;
- portal/account status where the workflow depends on active access.

## Tests

Sensitive authorization changes need URL bypass or direct action tests. At minimum, assert that a user from another organization cannot view, mutate, download, or confirm a record.
