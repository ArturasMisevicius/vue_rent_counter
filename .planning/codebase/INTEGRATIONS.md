# Integrations Map

## Integration posture at a glance

- The codebase is mostly self-contained and Laravel-native.
- Active integration categories are database access, queued jobs, email notifications, local file/document generation, security telemetry, and small ops/dev tooling integrations.
- External vendor hooks are present mostly as optional configuration in `config/*.php` rather than extensive custom API client code.

## Database integration

### Primary application database

- Local default is SQLite via `.env.example` and `config/database.php`.
- The schema is large and domain-heavy, with migrations in `database/migrations` covering billing, tenants, subscriptions, security, reporting, exports, and collaboration tables.
- Example integration-focused tables/files:
  - jobs/cache foundations: `database/migrations/0001_01_01_000001_create_cache_table.php`, `database/migrations/0001_01_01_000002_create_jobs_table.php`
  - integration health: `database/migrations/2026_03_17_101000_create_integration_health_checks_table.php`
  - security telemetry: `database/migrations/2026_03_17_100800_create_security_violations_table.php`
  - audit trails: `database/migrations/2026_03_17_100400_create_audit_logs_table.php`

### Alternative DB backends

- `config/database.php` also ships connection templates for MySQL, MariaDB, PostgreSQL, and SQL Server.
- Planning implication: infra can move beyond SQLite, but local/test assumptions are still SQLite-first.

## Authentication and authorization integration

### First-party auth only

- Auth guard/provider setup is defined in `config/auth.php`.
- The app uses Laravel session auth with Eloquent `App\\Models\\User`.
- There is no external IdP, OAuth, Socialite, or SSO provider wired in the current checkout.

### Role/policy/tenant enforcement

- The user model contains tenant/admin/superadmin behavior in `app/Models/User.php`.
- Policy registration is centralized in `app/Providers/AuthServiceProvider.php`.
- Route/panel middleware aliases are declared in `bootstrap/app.php`.
- Key middleware boundaries:
  - tenant-only access: `app/Http/Middleware/EnsureUserIsTenant.php`
  - account accessibility: `app/Http/Middleware/EnsureAccountIsAccessible.php`
  - subscription gating: `app/Http/Middleware/CheckSubscriptionStatus.php`
  - admin panel auth: `app/Http/Middleware/AuthenticateAdminPanel.php`

## Queue and scheduler integration

### Queue backend

- Default queue connection is `database` in `.env.example` and `config/queue.php`.
- Alternate queue drivers are preconfigured but not defaulted: `redis`, `sqs`, `beanstalkd`, `sync`, `background`, `deferred`, `failover` in `config/queue.php`.

### Active queued jobs

- Invoice reminder mail flow: `app/Jobs/SendInvoiceReminderJob.php`
- Invoice email logging flow: `app/Jobs/SendInvoiceEmailJob.php`
- Admin report export generation: `app/Jobs/GenerateAdminReportExportJob.php`
- Organization export packaging/delivery: `app/Jobs/Superadmin/Organizations/GenerateOrganizationDataExportJob.php`
- Project hierarchy rescoping: `app/Jobs/Projects/RescopeProjectChildrenJob.php`

### Scheduled work

- All checked-in schedules are registered in `routes/console.php`.
- Current recurring tasks include:
  - `model:prune` for `SecurityViolation`
  - `erag:sync-disposable-email-list`
  - `projects:alert-stalled`
  - `projects:alert-overdue`
  - `projects:alert-unapproved`

## Mail and notification integrations

### Mail transports

- Mail configuration is in `config/mail.php`.
- Default local mailer is `log` via `.env.example`.
- Optional transports are configured but not required locally:
  - SMTP
  - SES
  - Postmark
  - Resend
  - sendmail
  - failover / roundrobin

### Service credentials

- Third-party mail/service credentials live in `config/services.php`.
- Current service keys configured by convention:
  - Postmark: `config/services.php`
  - Resend: `config/services.php`
  - SES/AWS: `config/services.php`
  - Slack bot token/channel: `config/services.php`
  - GitHub token: `config/services.php`

### Active notification flows

- Organization invitations are sent by email from `app/Filament/Actions/Auth/CreateOrganizationInvitationAction.php` using `app/Notifications/Auth/OrganizationInvitationNotification.php`.
- Overdue invoice reminders are sent on-demand to arbitrary mail recipients from `app/Jobs/SendInvoiceReminderJob.php` using `app/Notifications/InvoiceOverdueReminderNotification.php`.
- Organization export completion is mailed with an attached ZIP from `app/Jobs/Superadmin/Organizations/GenerateOrganizationDataExportJob.php` using `app/Notifications/Superadmin/OrganizationDataExportReadyNotification.php`.
- Project alert notifications are emitted from scheduled commands in `routes/console.php` and implemented in `app/Notifications/Projects/*`.

### In-app notifications

- Filament database notifications are enabled in `app/Providers/Filament/AppPanelProvider.php`.
- Notification persistence depends on the notifications table created by `database/migrations/2026_03_17_100000_create_notifications_table.php`.

## File storage and document integrations

### Filesystem usage

- Filesystem configuration is in `config/filesystems.php`.
- Default storage disk is `local`; optional cloud disk is `s3`.
- The app uses Storage actively in checked-in code, for example:
  - report exports: `app/Jobs/GenerateAdminReportExportJob.php`
  - invoice downloads: `app/Filament/Actions/Tenant/Invoices/DownloadInvoiceAction.php`
  - KYC attachment streaming: `app/Livewire/Kyc/ShowKycAttachmentEndpoint.php`
  - KYC attachment sync/delete flows: `app/Filament/Actions/Admin/Kyc/SyncKycProfileAttachmentsAction.php`, `app/Filament/Actions/Profile/UpsertKycProfileAction.php`

### Invoice PDF generation

- Invoice PDF rendering is an internal document-generation integration, not an external SaaS.
- Rendering pipeline:
  - document assembly: `app/Services/Billing/InvoicePdfDocumentFactory.php`
  - PDF/image rendering: `app/Services/Billing/InvoicePdfRenderer.php`
  - download streaming: `app/Services/Billing/InvoicePdfService.php`
- `app/Services/Billing/InvoicePdfRenderer.php` requires the Imagick PHP extension and a local Unicode-capable font path.

### Report exports

- CSV/PDF exports are generated internally:
  - CSV + stream responses: `app/Services/ExportService.php`
  - PDF export wrapper: `app/Services/PdfReportService.php`
  - queued persistence to `storage/app/report-exports`: `app/Jobs/GenerateAdminReportExportJob.php`

## Security telemetry and webhook-style endpoints

### CSP violation intake

- The only clear webhook-style public endpoint in the active app is the CSP report collector.
- Route definition: `routes/web.php` (`POST /security/csp-report`).
- Request validation: `app/Http/Requests/Security/CspViolationRequest.php`.
- Processing endpoint: `app/Livewire/Security/CspViolationReportEndpoint.php`.
- It is intentionally exempted from CSRF in `routes/web.php` and throttled via the `security-csp-report` rate limiter in `app/Providers/AppServiceProvider.php`.

### Security monitoring pipeline

- Violation recording, aggregation, and rate detection live in `app/Services/Security/SecurityMonitoringService.php`.
- Security headers middleware is `app/Http/Middleware/SecurityHeaders.php`.
- Violations persist in `app/Models/SecurityViolation.php` and the corresponding migrations under `database/migrations`.

### Blocked IP enforcement

- Request blocking is inserted into the web middleware stack in `bootstrap/app.php`.
- The blocking middleware class is `app/Http/Middleware/BlockBlockedIpAddresses.php`.
- The backing model/table are `app/Models/BlockedIpAddress.php` and `database/migrations/2026_03_17_100900_create_blocked_ip_addresses_table.php`.

## Integration health subsystem

### What is actively probed

- The superadmin integration health page is `app/Filament/Pages/IntegrationHealth.php`.
- Probe registry is composed in `app/Providers/AppServiceProvider.php` and implemented in:
  - `app/Filament/Support/Superadmin/Integration/Probes/DatabaseProbe.php`
  - `app/Filament/Support/Superadmin/Integration/Probes/QueueProbe.php`
  - `app/Filament/Support/Superadmin/Integration/Probes/MailProbe.php`
- Probe results are stored in `app/Models/IntegrationHealthCheck.php`.

### Interpretation

- Database health is considered healthy when the DB connects and the `migrations` table exists.
- Queue health is downgraded when using non-worker backends like `sync`, `deferred`, or `null`.
- Mail health is downgraded when using local-only transports like `array` or `log`.
- This means the codebase already distinguishes between “configured for local development” and “production-ready integration health.”

## External network and vendor touchpoints

### Disposable email blacklist sync

- The app depends on `erag/laravel-disposable-email` in `composer.json`.
- Config is in `config/disposable-email.php`.
- The blacklist sync source is a raw GitHub URL in `config/disposable-email.php`:
  - `https://raw.githubusercontent.com/eramitgupta/disposable-email/main/disposable_email.txt`
- Email validation actively uses that rule in:
  - `app/Http/Requests/Auth/RegisterRequest.php`
  - `app/Filament/Actions/Auth/CreateOrganizationInvitationAction.php`
- Scheduled refresh is wired in `routes/console.php` via `erag:sync-disposable-email-list`.

### GitHub API helper

- GitHub token config lives in `config/services.php` and `.env.example`.
- The active GitHub-related code is operational guidance generation, not an outbound HTTP client.
- `app/Services/Operations/PhaseOneGuardrailsBranchProtectionService.php` builds exact `curl` commands against `https://api.github.com/...` for manual branch protection changes.
- Supporting CI/ops evidence also exists in:
  - `.github/workflows/phase-1-guardrails.yml`
  - `docs/operations/phase-1-guardrails-branch-protection.md`
  - `tests/Feature/Console/PhaseOneGuardrailsBranchProtectionTest.php`

### Slack

- Slack credentials are defined conventionally in `config/services.php` and a Slack log channel exists in `config/logging.php`.
- No active checked-in application flow was found that routes notifications to Slack in the current `app/` tree.
- Treat Slack as configured capability, not confirmed active integration.

### AWS / S3 / SQS

- AWS env/config is present in `.env.example`, `config/filesystems.php`, `config/mail.php`, `config/services.php`, and `config/queue.php`.
- Current local defaults do not use S3, SES, or SQS.
- These are optional infra targets available for deployment rather than active local defaults.

## Broadcasting and realtime

- Broadcast channels are defined in `routes/channels.php`, including `org.{organizationId}` authorization.
- Base broadcast connection defaults to `log` in `config/broadcasting.php`.
- Filament broadcasting config in `config/filament.php` contains a commented Pusher/Echo example only.
- Planning implication: the app has authorization/channel groundwork, but realtime transport is not fully switched on by default.

## Localization and content management integrations

- Supported locales are configured in `config/app.php` and `config/tenanto.php`.
- Persistent localization entities are modeled via `app/Models/Language.php` and `app/Models/Translation.php`.
- Translation management UI exists at `app/Filament/Pages/TranslationManagement.php` with the corresponding view `resources/views/filament/pages/translation-management.blade.php`.
- Missing-translation scanning is customized in `app/Services/Localization/PhpFileMissingTranslationsScanner.php` and registered in `app/Providers/AppServiceProvider.php`.

## Development and editor tooling integrations

- Herd MCP is configured in `.mcp.json` and points at the current workspace path.
- Boost/editor integration metadata is stored in `boost.json`.
- Git hook automation is integrated through `.githooks/pre-commit`, `.githooks/post-commit`, and `scripts/update_changelog.php`.

## Negative findings worth remembering

- No active payment gateway integration was found in the current `app/` code paths.
- No external auth provider integration was found beyond Laravel’s own session auth.
- No checked-in outbound HTTP client layer was found for arbitrary third-party APIs; active external-facing behavior is mostly mail delivery, disposable-email sync, and GitHub command generation.
- No active PWA/service-worker integration is intended; this is reinforced by `tests/Feature/Public/PwaIntegrationTest.php`.

## Planning shortcuts

- If you are changing email behavior, inspect `config/mail.php`, `config/services.php`, `app/Notifications/*`, and the queue jobs in `app/Jobs/*` together.
- If you are changing file/download behavior, inspect `config/filesystems.php`, `app/Models/Attachment.php`, `app/Services/Billing/*`, and `app/Services/ExportService.php`.
- If you are changing ops readiness or health checks, start with `routes/console.php`, `app/Services/Operations/*`, `app/Filament/Pages/IntegrationHealth.php`, and `app/Filament/Support/Superadmin/Integration/*`.
- If you are changing tenant/security boundaries, start with `bootstrap/app.php`, `routes/web.php`, `app/Providers/AuthServiceProvider.php`, and `app/Services/Security/SecurityMonitoringService.php`.
