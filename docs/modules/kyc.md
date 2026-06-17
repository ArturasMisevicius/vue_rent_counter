# KYC Module Contract

> **AI agent usage:** Read this before changing tenant KYC profiles, KYC documents, review actions, downloads, expiry, or reminders.

Updated on 2026-06-15.

## Purpose

KYC owns tenant verification profiles, KYC document submission, admin review, expiry, reminders, and private tenant-safe downloads.

## Owns

- Models: `TenantKycProfile`, `TenantKycDocument`, `UserKycProfile`.
- Actions: tenant KYC submit/download/replace, admin approve/reject/expire/remind actions.
- Policies: `TenantKycProfilePolicy`, `TenantKycDocumentPolicy`.

## Invariants

- KYC files are private;
- tenant access is own-profile only;
- review state transitions require authorized admin/manager/platform actor;
- rejected documents need a reason;
- expiry/reminder jobs must be idempotent.

## Tests And Scenarios

Primary tests live under tenant KYC, admin KYC, security isolation, and console maintenance coverage.
