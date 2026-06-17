# Module Boundaries

> **AI agent usage:** Read the architecture overview and the affected `docs/modules/*.md` contract before changing a module. Verify live models, policies, routes, and tests before relying on this file.

Updated on 2026-06-15.

## Boundary Model

Tenanto modules are ownership contracts inside the Laravel monolith. A module owns workflows, invariants, permissions, events, and documentation for a business area. Modules do not require a matching PHP namespace today.

## Module Contract Fields

Every module contract should document:

- purpose;
- owned models;
- public actions;
- DTO/result expectations;
- events emitted;
- policies and permissions;
- invariants;
- dependencies;
- UI surfaces;
- tests and scenarios;
- common failures;
- audit requirements.

Use `docs/architecture/module-contract-template.md` for new modules.

## Dependency Direction

| Module | Can depend on | Must not depend on |
| --- | --- | --- |
| Billing | Tenants, Properties, Meters, Tariffs, Payments read state, Documents, Notifications | Filament internals as business rules, controller state |
| Payments | Billing invoices, Documents/Attachments, Notifications, Audit | Filament callbacks as payment authority |
| Documents | Tenants, Properties, Storage, KYC/Contracts metadata | public file paths for sensitive files |
| KYC | Tenants, Documents/Attachments, Notifications, Audit | tenant-visible shortcuts around review policies |
| Accounting | Billing, Payments, Vendors, Owners | UI layer and direct payment status mutation |
| Email | Notification content, events, jobs | direct financial mutation |
| API | actions, policies, DTOs/resources | direct model mutation that bypasses actions |
| Support | users, organizations, audit/impersonation state | tenant data access without explicit scope |

## Cross-Module Rules

- UI can call actions from multiple modules.
- Actions may compose actions when the workflow genuinely spans modules.
- Side effects should cross module boundaries through events, notifications, jobs, or explicit action calls.
- A module must not import Filament resources/pages as part of domain behavior.
- A module must not read `request()` or `auth()` inside reusable action/domain code unless the class is explicitly UI-bound and documented as such.

## Organization Scope

Organization-scoped models must be loaded through scoped queries or policy checks:

```php
Invoice::query()->forOrganization($organizationId)->findOrFail($id);
```

Avoid unscoped lookup followed by hopeful UI filtering:

```php
Invoice::query()->findOrFail($id);
```

## Current Exceptions

`app/Actions/Billing` is a current billing-payment action namespace and is allowed. Do not use this exception as permission to create unrelated `app/Actions/*` namespaces without an ADR.
