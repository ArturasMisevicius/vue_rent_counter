# Tenanto Feature Guide

> **AI agent usage:** This is the current feature entrypoint for Tenanto. Read it after `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`. Verify current code, routes, migrations, policies, tests, and translations before changing behavior.

Updated on 2026-06-15 from the live checkout, `php artisan route:list`, `php artisan about`, `php artisan list --raw`, `php artisan migrate:status`, current file inventory, and the complete git history from `f12619cd` through `8cbfc808`.

## Product Shape

Tenanto is a property, tenant, and utility billing system with four user roles:

| Role | Main entry | Scope |
| --- | --- | --- |
| `SUPERADMIN` | `/app/platform-dashboard`, `/app/organizations`, `/app/users`, platform resources | Platform support, governance, security, localization, integrations, and cross-organization inspection. |
| `ADMIN` | `/app`, organization resources | Own organization operations, billing, team, settings, reports, tenants, and property portfolio. |
| `MANAGER` | `/app`, manager-allowed resources | Own organization operations allowed by active manager membership and permission preset. |
| `TENANT` | `/tenant`, tenant Filament aliases | Own portal data: property, invoices, readings, documents, KYC, contracts, profile, and help. |

The app currently exposes 230 routes, 41 Filament resources, 27 Filament pages, 51 Livewire classes, 79 top-level models, and 222 tests.

## Public And Authentication Features

Routes and Livewire endpoints cover:

- Localized homepage at `/`.
- Login, registration, password reset request, password reset submission, invitation acceptance, logout, and onboarding.
- Guest and authenticated locale switching.
- Authenticated profile editing and avatar serving.
- Role-aware `/dashboard` redirect.
- Favicon route.
- CSP report intake with throttling and CSRF exemption for browser reports.
- Branded error pages.
- Session timeout and account accessibility middleware.

Main files:

- `routes/web.php`
- `app/Livewire/Auth`
- `app/Livewire/Onboarding`
- `app/Livewire/Profile`
- `app/Livewire/PublicSite`
- `app/Livewire/Security`
- `app/Http/Requests/Auth`

## Shared Workspace Shell

Authenticated surfaces share:

- Role-aware navigation from `config/tenanto.php`.
- Topbar and sidebar shell components.
- Tenant-oriented top navigation and portal aliases.
- Notification center and domain notifications.
- Live locale switcher.
- Global search providers for organizations, buildings, properties, tenants, invoices, and readings.
- Impersonation banner and stop flow.
- Breadcrumbs and reusable empty/error states.

Main files:

- `app/Livewire/Shell`
- `app/Filament/Support/Shell`
- `resources/views/livewire/shell`
- `resources/views/components/shell`
- `config/tenanto.php`

## Superadmin Features

Superadmin capabilities include:

- Platform dashboard and platform notifications.
- Organizations resource with list presets, health metrics, MRR, exports, detail dashboard snapshots, portfolio/financial/usage/subscription/security/integration cards, and relation managers.
- Organization actions: create/update, suspend/reinstate, plan change, ownership transfer, announcements, export queueing, limit overrides, feature flags, invoice write-offs, and impersonation.
- Users and organization users management, including password reset, role/status changes, memberships, invitations, and impersonation.
- Subscriptions, subscription payments, and subscription renewals.
- Languages and translation management.
- System configuration and settings.
- Security violations and audit logs.
- Integration health probes for database, mail, queue, and circuit-breaker reset.
- Projects, tasks, task assignments, time entries, comments, reactions, attachments, tags, property assignments, invoice items, invoice payments, invoice logs, and other platform inspection resources.

Main files:

- `app/Filament/Resources/Organizations`
- `app/Filament/Resources/Users`
- `app/Filament/Resources/OrganizationUsers`
- `app/Filament/Resources/Subscriptions`
- `app/Filament/Pages/PlatformDashboard.php`
- `app/Filament/Pages/IntegrationHealth.php`
- `app/Filament/Pages/TranslationManagement.php`
- `app/Filament/Actions/Superadmin`
- `app/Filament/Support/Superadmin`

## Admin And Manager Operations

Organization workspace features include:

- Buildings, properties, tenants, property assignments, meters, meter readings, providers, tariffs, utility services, service configurations, invoices, payments, reports, settings, notifications, and help center.
- Tenant creation with assignments, tenant invitation lifecycle, portal enable/disable, invitation resend/revoke, tenant status toggles, and lease agreement sync.
- Manager creation and invitation flows, manager membership lifecycle, manager permission matrix, and preset summaries.
- Admin dashboard attention cards for billing, readings, documents, and move-out follow-up.
- Reports for revenue, consumption, meter compliance, and outstanding balances.
- Help center and contextual help catalog.

Managers use the same workspace but are constrained by `OrganizationUser` membership state, `App\Enums\Permission`, `EffectivePermissionsResolver`, middleware, policies, and action checks.

Main files:

- `app/Filament/Resources/Buildings`
- `app/Filament/Resources/Properties`
- `app/Filament/Resources/Tenants`
- `app/Filament/Resources/Meters`
- `app/Filament/Resources/MeterReadings`
- `app/Filament/Resources/Providers`
- `app/Filament/Resources/Tariffs`
- `app/Filament/Resources/ServiceConfigurations`
- `app/Filament/Resources/OrganizationUsers`
- `app/Livewire/Filament/ManagerPermissionMatrixPanel.php`
- `app/Services/Authorization/EffectivePermissionsResolver.php`
- `docs/PERMISSION-MATRIX.md`

## Billing, Readings, And Invoices

Tenanto now uses an invoice-driven meter-reading cycle:

1. Organizations configure automatic billing in Settings -> Billing: frequency, generation day, reading deadline, payment due offset, reminder days, timezone, currency, and notification toggles.
2. `php artisan billing:generate-draft-invoices` runs daily and uses `GenerateDraftInvoicesForBillingPeriod` to create or update the target `BillingPeriod`.
3. Eligible tenant/property assignments receive one active draft invoice per period. Duplicate active invoices, inactive tenants, inactive assignments, and properties without billable services are logged and skipped.
4. Metered drafts receive `automation_level = reading_request` and `approval_status = waiting_for_readings`; fixed-only drafts receive `approval_status = ready_for_review`; configuration errors receive `approval_status = configuration_error`.
5. Tenants receive `InvoiceReadingRequestNotification` only when the invoice is ready for readings. Missing tariffs or other blocking configuration errors prevent tenant notification.
6. Admins and managers can preview or manually generate from Billing Periods; both manual and scheduled runs use the same action and write Billing Generation Logs.
7. `CompleteReadingRequestInvoiceAction` marks submitted reading requests as `readings_submitted`.
8. Billing reviewers use Billing Review Center to approve, reject, correct, request resubmission, prepare invoice lines, and finalize invoices.
9. Finalized invoices become tenant-visible and can be downloaded or emailed.
10. Payment proof, confirmation, rejection, voiding, overdue marking, and reminders are handled through billing actions and notifications.

Additional billing features:

- Bulk invoice generation.
- Manual invoice line items and long tenant-facing descriptions.
- Extra charge types and extra charges with approval threshold.
- Billing cleanup center for duplicates/orphans.
- Invoice calculation preview and finalization guards.
- Payment reconciliation and overdue reminders.
- PDF generation and localized invoice presentation.

Main files:

- `app/Filament/Pages/BillingReviewCenter.php`
- `app/Filament/Pages/BillingInvoiceReview.php`
- `app/Filament/Pages/BillingCleanupCenter.php`
- `app/Filament/Pages/BillingSettings.php`
- `app/Filament/Actions/Admin/Billing`
- `app/Filament/Actions/Admin/Invoices`
- `app/Filament/Actions/Admin/BillingReview`
- `app/Filament/Actions/Admin/BillingIntegrity`
- `app/Filament/Actions/Tenant/Readings`
- `app/Actions/Billing`
- `app/Services/Billing`
- `app/Console/Commands/GenerateDraftInvoicesCommand.php`
- `app/Console/Commands/OpenReadingInvoiceCycleCommand.php`
- `app/Console/Commands/MarkOverdueInvoicesCommand.php`
- `app/Console/Commands/SendPaymentRemindersCommand.php`
- `docs/operations/billing-reading-invoice-workflow.md`
- `docs/operations/service-configuration-guide.md`

## Tenant Portal

Tenant portal features:

- `/tenant` home summary.
- `/tenant/readings/create` request-driven reading form.
- `/tenant/invoices` invoice history.
- `/tenant/invoices/{invoice}/download` authorized invoice download.
- `/tenant/property` property details.
- `/tenant/documents` tenant-visible documents.
- `/tenant/documents/{tenantDocument}/download` authorized document download.
- `/tenant/verification` KYC verification.
- `/tenant/kyc-documents/{tenantKycDocument}/download` authorized KYC document download.
- `/tenant/profile` tenant profile alias.
- `/tenant/rental-contracts/{rentalContract}/attachments/{attachment}/download` authorized rental contract download.
- `/tenant/attachments/{attachment}` tenant-safe attachment access.

Tenant portal data should flow through presenters and actions:

- `app/Filament/Support/Tenant/Portal/TenantHomePresenter.php`
- `app/Filament/Support/Tenant/Portal/TenantInvoiceIndexQuery.php`
- `app/Filament/Support/Tenant/Portal/TenantPropertyPresenter.php`
- `app/Filament/Support/Tenant/Portal/TenantDocumentIndexQuery.php`
- `app/Filament/Support/Tenant/Portal/TenantDocumentPresenter.php`
- `app/Filament/Support/Tenant/Portal/TenantKycPresenter.php`
- `app/Livewire/Tenant`

## Documents, KYC, And Rental Contracts

Tenant documents:

- Admin upload, metadata update, visibility toggle, verification, rejection, replacement, archive, expiry, and notification actions.
- Tenant-visible list and download route.
- Private storage assumptions; no public file URL should be treated as sufficient authorization.

Tenant KYC:

- `TenantKycProfile` and `TenantKycDocument` models.
- Tenant upload/download path through tenant portal.
- Admin review resource for profile/document approval and rejection.
- Completeness checks, replacement requests, expiry reminders, and KYC maintenance command.
- Organization settings gate through `TenantKycSettings`.

Rental contracts:

- Store/update, upload, renewal, termination, expiry, reminder, and tenant download actions.
- Assignment and move-out workflows can close or preserve contract state.

Main files:

- `app/Filament/Resources/Tenants/RelationManagers/TenantDocumentsRelationManager.php`
- `app/Filament/Resources/Tenants/RelationManagers/TenantKycDocumentsRelationManager.php`
- `app/Filament/Resources/TenantKycProfiles`
- `app/Filament/Actions/Admin/TenantDocuments`
- `app/Filament/Actions/TenantDocuments`
- `app/Filament/Actions/TenantKyc`
- `app/Filament/Actions/Admin/RentalContracts`
- `app/Livewire/Tenant/Documents.php`
- `app/Livewire/Tenant/Verification.php`

## Move-Out And Occupancy

Move-out features include:

- Move-out process state model.
- Property occupancy status.
- Schedule, cancel, final readings, final invoice generation, rental contract closure, portal access update, occupancy update, completion, and tenancy history actions.
- Admin dashboard attention cards and filtered properties/tenants table entry points.
- Tenant meter visibility after move-out constraints.

Main files:

- `app/Models/MoveOutProcess.php`
- `app/Enums/MoveOutProcessStatus.php`
- `app/Enums/PropertyOccupancyStatus.php`
- `app/Filament/Actions/Admin/TenantMoveOut`
- `app/Filament/Support/Admin/Dashboard/BuildAdminAttentionDashboard.php`
- `app/Filament/Resources/Properties/Tables/PropertiesTable.php`
- `app/Filament/Resources/Tenants/Tables/TenantsTable.php`

## Leads

Lead management includes:

- Listing leads resource with create/edit/view/list.
- Lead sources.
- Lead import batches.
- Lead outreach templates and activities.
- CSV import/mapping/validation.
- Duplicate detection, merge, assignment, follow-up scheduling, do-not-contact, archive, conversion to property, CSV export, and lead reports.

Main files:

- `app/Filament/Resources/ListingLeads`
- `app/Filament/Resources/LeadSources`
- `app/Filament/Resources/LeadImportBatches`
- `app/Filament/Resources/LeadOutreachTemplates`
- `app/Filament/Pages/LeadImport.php`
- `app/Filament/Pages/LeadReports.php`
- `app/Filament/Actions/Admin/Leads`

## Projects And Collaboration

Project/collaboration features include:

- Projects with statuses, priorities, types, cost records, project users, manager assignments, approvals, alerts, exports, and lifecycle rules.
- Tasks, enhanced tasks, task assignments, comments, reactions, attachments, tags, and time entries.
- Scheduled project alerts for stalled, overdue, and unapproved work.

Main files:

- `app/Filament/Resources/Projects`
- `app/Filament/Actions/Superadmin/Projects`
- `app/Services/ProjectService.php`
- `app/Jobs/Projects`
- `app/Notifications/Projects`
- `routes/console.php`

## Localization

Current locales are `en`, `es`, `lt`, and `ru`.

Localization features include:

- Managed language records.
- Translation management page.
- PHP translation file scanning and sync.
- Enum labels through translated enum patterns.
- Localized billing/invoice content, dates, numbers, currency, and measurement formatting.
- Guest and authenticated locale persistence.

Main files:

- `lang/*`
- `app/Filament/Pages/TranslationManagement.php`
- `app/Filament/Support/Superadmin/Translations`
- `app/Services/Localization/PhpFileMissingTranslationsScanner.php`
- `app/Console/Commands/LaravelMissingTranslationsPhpFilesCommand.php`
- `app/Console/Commands/SyncTranslationsCommand.php`

## Notifications And Mail

Notifications exist for:

- Billing readings, invoice review, invoice ready, reminders, payment proof, payment confirmation/rejection, overdue invoices.
- Tenant documents and KYC.
- Rental contracts.
- Tenant and organization invitations.
- Superadmin organization announcements, ownership transfers, exports, and plan changes.
- Projects.
- Domain notification mail.

Main files:

- `app/Notifications`
- `app/Mail/DomainNotificationMail.php`
- `app/Filament/Support/Notifications`
- `app/Filament/Actions/Notifications`
- `app/Models/NotificationDeliveryLog.php`

## Security, Audit, And Guardrails

Security features include:

- Security headers and CSP report handling.
- Public debug surface regression tests.
- Tenant isolation and organization isolation tests.
- Account status and tenant-only middleware.
- Manager permission middleware.
- Superadmin impersonation audit context.
- Audit logs, organization activity logs, superadmin audit logs, and security violations.
- Blocked IP addresses.
- Phase 1 guardrail workflow and command.

Main files:

- `app/Http/Middleware`
- `app/Policies`
- `app/Filament/Support/Audit`
- `app/Services/Security`
- `tests/Feature/Security`
- `tests/Feature/Architecture`
- `docs/security/2026-03-18-csp-rate-limits-threat-model.md`
- `docs/operations/phase-1-guardrails-branch-protection.md`

## Operations And Deployment Readiness

Operations commands:

- `ops:backup-restore-readiness`
- `ops:release-readiness`
- `ops:phase1-guardrails-branch-protection`
- `billing:generate-draft-invoices`
- `billing:open-reading-invoice-cycle`
- `billing:mark-overdue-invoices`
- `billing:send-reading-reminders`
- `billing:send-payment-reminders`
- `rental-contracts:maintain`
- `kyc:maintain`
- `projects:alert-stalled`
- `projects:alert-overdue`
- `projects:alert-unapproved`
- `translations:sync`

Local `.env.example` uses database-backed queue, cache, and session drivers, SQLite, log mail, and `TENANTO_EXTRA_CHARGE_MANAGER_APPROVAL_THRESHOLD`.

## Testing Map

High-value test families:

- Auth: `tests/Feature/Auth`
- Tenant portal: `tests/Feature/Tenant`
- Admin resources/workflows: `tests/Feature/Admin`
- Billing: `tests/Feature/Billing`
- Superadmin: `tests/Feature/Superadmin`
- Security/isolation: `tests/Feature/Security`
- Architecture guardrails: `tests/Feature/Architecture`
- Console/ops: `tests/Feature/Console`, `tests/Feature/Operations`
- Localization: `tests/Feature/Localization`
- Projects: `tests/Feature/Projects`
- Browser smoke coverage: `tests/Browser`

For docs-only changes, use markdown diff checks. For behavior changes, run the focused test file(s), then broaden to related feature folders.

## Known Current Caveats

- `php artisan migrate:status` on 2026-06-15 showed `2026_06_15_000000_create_tenant_kyc_verification_tables.php` as pending in the local SQLite database, even though the migration is checked in. Run `php artisan migrate` after pulling KYC work before testing KYC UI against the local DB.
- `boost:mcp` and `mcp:start` Artisan commands are not currently registered in this checkout. Repo-local MCP is provided through `.mcp.json`.
- `boost.json` still lists `spatie/laravel-backup`, but `composer show --direct` on 2026-06-15 did not show `spatie/laravel-backup` installed. Use the local operations readiness command unless that package is explicitly added.
- `docs/superpowers/**` is historical planning context. Do not treat a checked box or planned branch there as proof of live behavior.
