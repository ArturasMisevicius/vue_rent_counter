# ADR 0005: Use File Records For Sensitive Storage

## Status

Accepted

## Date

2026-06-15

## Context

Tenant documents, KYC files, rental contracts, invoice proofs, and attachments are sensitive. Public paths or raw storage URLs are not enough authorization.

## Decision

Sensitive file features must use database-backed records such as `Attachment`, `TenantDocument`, `TenantKycDocument`, or module-specific file records. Downloads must pass through authorized routes/actions.

## Alternatives Considered

### Public Disk URLs

- Pros: simple and cheap.
- Cons: exposes private tenant and legal documents.
- Rejected for sensitive files.

### Raw Path Columns Only

- Pros: fewer tables.
- Cons: weak metadata, weak authorization, poor auditability.
- Rejected for new sensitive features.

## Consequences

- File features need policies and download actions.
- Module docs must identify owned file records.
- Tests should cover cross-tenant and cross-organization download denial.
