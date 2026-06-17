# Documents Module Contract

> **AI agent usage:** Read this before changing tenant documents, attachments, private downloads, document visibility, or document notifications.

Updated on 2026-06-15.

## Purpose

Documents owns tenant-visible documents, private download authorization, document metadata, visibility, verification, rejection, replacement, archiving, and document notifications.

## Owns

- Models: `TenantDocument`, `Attachment` records used for tenant/payment/KYC/contract files.
- Actions: admin tenant document actions, tenant document download actions.
- Policy: `TenantDocumentPolicy`.
- Support: tenant document file and presenter/query classes.

## Public Actions

| Action | Purpose | Callers |
| --- | --- | --- |
| `UploadTenantDocument` | Admin uploads private tenant document | tenant relation manager |
| `ReplaceTenantDocumentFile` | Replace stored file | tenant document UI |
| `DownloadTenantDocument` | Tenant-safe document download | tenant download route |
| `VerifyTenantDocument` | Mark document verified | admin review |
| `RejectTenantDocument` | Reject document with reason | admin review |
| `ArchiveTenantDocument` | Remove from active use | admin review |

## Events And Side Effects

- audit records for upload, replacement, visibility, verification, rejection, and archive;
- tenant notifications when enabled;
- expiry/maintenance notifications where applicable.

## Permissions

- `documents.view`;
- `documents.upload`;
- `documents.change_visibility`;
- `documents.download`;
- `documents.archive`;
- `documents.delete`;
- tenant portal own-visible document permissions.

## Invariants

- sensitive files are private;
- tenant downloads require tenant ownership, organization scope, and visibility;
- internal notes must not leak to tenant portal;
- public paths are not authorization.

## Dependencies

Documents depends on tenants, properties, storage, audit, and notifications.

## Must Not

- expose raw storage URLs;
- serve sensitive files from public storage;
- authorize by UI hiding only;
- bypass `TenantDocumentPolicy` or download actions.

## Tests And Scenarios

Primary tests:

- tenant document center and download tests;
- KYC and rental contract download tests for shared attachment behavior;
- security isolation tests.
