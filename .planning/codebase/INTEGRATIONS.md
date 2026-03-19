# External Integrations

**Analysis Date:** 2026-03-19

## APIs & External Services

**Payment Processing:**
- Not detected. No Stripe, PayPal, Paddle, or similar payment SDK/package was found in `composer.json`, `composer.lock`, `package.json`, `config/services.php`, or `app/`.

**Email Delivery:**
- Laravel mail notifications are wired for organization invitations and overdue invoice reminders in `app/Filament/Actions/Auth/CreateOrganizationInvitationAction.php`, `app/Filament/Actions/Admin/Invoices/SendInvoiceReminderAction.php`, `app/Notifications/Auth/OrganizationInvitationNotification.php`, and `app/Notifications/InvoiceOverdueReminderNotification.php`.
  - SDK/Client: Laravel notification + mail stack from `laravel/framework`, configured in `config/mail.php`.
  - Auth: current default mailer is `MAIL_MAILER=log` via `config/mail.php`, and `php artisan about` confirms the active mail driver is `log`.
  - Templates: email content is defined in code-backed notification classes under `app/Notifications/`.

**Hosted Fonts / Asset Delivery:**
- Bunny Fonts is loaded directly by browser clients from `https://fonts.bunny.net` in `resources/views/layouts/public.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/views/components/shell/app-frame.blade.php`, `resources/views/errors/layout.blade.php`, and `resources/views/components/shell/error-state.blade.php`.
  - Integration method: `<link rel="preconnect">` and stylesheet `<link>` tags in Blade templates.
  - Auth: none.
  - CSP allowlist: `app/Services/Security/CspHeaderBuilder.php`.

**Realtime / Broadcasting:**
- Laravel broadcasting is implemented in event classes `app/Events/InvoiceFinalized.php`, `app/Events/MeterReadingSubmitted.php`, and `app/Events/PlatformNotificationSent.php`, with private channel authorization in `routes/channels.php`.
  - Integration method: Laravel `ShouldBroadcastNow` events on `org.{organizationId}` private channels.
  - Auth: authenticated user + organization checks in `routes/channels.php`.
  - External broker: not active. `config/broadcasting.php` sets the default broadcaster to `log`, `config/filament.php` has its Echo config commented out, and no Pusher-compatible PHP package is present in `composer.lock`.

**Developer Tooling Integration:**
- Herd MCP is the only repo-local MCP server configured in `.mcp.json`.
  - Integration method: local MCP server command `php /Applications/Herd.app/Contents/Resources/herd-mcp.phar`.
  - Auth: none configured in the repository.
  - Scope: local developer tooling only; no app runtime dependency on this server is present in `app/` or `routes/`.

## Data Storage

**Databases:**
- SQLite is the current primary datastore, with the active driver reported as `sqlite` by `php artisan about`, the default connection defined in `config/database.php`, and the local database file checked in at `database/database.sqlite`.
  - Connection: `DB_CONNECTION`, `DB_DATABASE`, and optional `DB_URL` are defined via `config/database.php`.
  - Client: Laravel Eloquent models in `app/Models/` and migrations in `database/migrations/`.
  - Migrations: Laravel migration files under `database/migrations/`.
- Alternative connections for MySQL, MariaDB, PostgreSQL, and SQL Server are scaffolded in `config/database.php`, but no non-SQLite driver is active by default.

**File Storage:**
- Local filesystem storage is the active file storage integration, with `config/filesystems.php` defaulting `FILESYSTEM_DISK` to `local`.
  - SDK/Client: Laravel Storage facade from `laravel/framework`.
  - Auth: app-level authorization only; no external storage credentials are required for the active default.
  - Paths: private files live under `storage/app/private`; public assets and PWA files ship from `public/`.
- Concrete local-disk usage exists in `app/Filament/Actions/Tenant/Invoices/DownloadInvoiceAction.php` for invoice downloads and `app/Filament/Pages/TranslationManagement.php` for CSV uploads/imports on the `local` disk.
- An `s3` disk is scaffolded in `config/filesystems.php`, but no active S3 usage was found in `app/`, `routes/`, or `resources/`.

**Caching:**
- Database-backed cache is active by default, with `php artisan about` reporting `database` and `config/cache.php` setting the default cache store to `database`.
  - Connection: `CACHE_STORE`, `DB_CACHE_CONNECTION`, and `DB_CACHE_TABLE` in `config/cache.php`.
  - Client: Laravel Cache facade, used in `app/Services/SubscriptionChecker.php`, `app/Filament/Support/Dashboard/DashboardCacheService.php`, and performance-oriented tests such as `tests/Performance/DashboardPerformanceTest.php`.

**Queues and Sessions:**
- Database-backed queues and sessions are active by default according to `config/queue.php`, `config/session.php`, and `php artisan about`.
  - Queue connection: `QUEUE_CONNECTION`, plus `DB_QUEUE_CONNECTION` and `DB_QUEUE_TABLE` in `config/queue.php`.
  - Session storage: `SESSION_DRIVER`, `SESSION_CONNECTION`, and `SESSION_TABLE` in `config/session.php`.

## Authentication & Identity

**Auth Provider:**
- Custom Laravel session authentication is configured in `config/auth.php` using the `web` session guard and the Eloquent `App\Models\User` provider.
  - Implementation: guest/auth flows live in `app/Livewire/Auth/*` and `routes/web/guest.php`; authenticated flows live in `routes/web/authenticated.php`.
  - Token storage: session cookies configured in `config/session.php`.
  - Session management: database sessions are active by default per `config/session.php` and `php artisan about`.

**Password Reset and Invitation Identity Flows:**
- Password reset links and invitation acceptance are implemented as first-party identity flows in `routes/web/guest.php`, `app/Livewire/Auth/ForgotPasswordPage.php`, `app/Livewire/Auth/ResetPasswordPage.php`, `app/Livewire/Auth/AcceptInvitationPage.php`, and `app/Filament/Actions/Auth/CreateOrganizationInvitationAction.php`.
  - Delivery channel: Laravel mail notifications.
  - Tokens: password reset tokens use the configured table in `config/auth.php`; organization invitation acceptance tokens are handled by `app/Models/OrganizationInvitation.php`.

**OAuth Integrations:**
- Not detected. No Socialite package, OAuth client package, or provider-specific auth flow was found in `composer.lock`, `config/services.php`, `app/`, or `routes/`.

## Monitoring & Observability

**Error Tracking:**
- Not detected. No Sentry, Bugsnag, Rollbar, or similar package is present in `composer.lock`.

**Analytics:**
- Not detected. No Mixpanel, PostHog, Segment, Google Analytics, or similar browser/server SDK was found in `app/`, `resources/`, `package.json`, or `composer.lock`.

**Logs:**
- Laravel file-based logging is active through the `stack / single` channels reported by `php artisan about` and defined in `config/logging.php`.
  - Integration: Monolog-backed Laravel log channels from `config/logging.php`.
  - Storage: `storage/logs/laravel.log`.
- Slack and Papertrail channels are scaffolded in `config/logging.php`, but no active use of those channels was found in `app/` or `routes/`.

**Health Checks:**
- Superadmin integration health checks are wired for database, queue, and mail through `app/Filament/Support/Superadmin/Integration/Probes/DatabaseProbe.php`, `app/Filament/Support/Superadmin/Integration/Probes/QueueProbe.php`, `app/Filament/Support/Superadmin/Integration/Probes/MailProbe.php`, and `app/Filament/Actions/Superadmin/Integration/RunIntegrationHealthChecksAction.php`.
  - Scope: these probes verify current application infrastructure, not third-party SaaS APIs.
  - Surface: results are shown in `app/Filament/Pages/IntegrationHealth.php` and stored in `app/Models/IntegrationHealthCheck.php`.

## CI/CD & Deployment

**Hosting:**
- Not detected. No Render, Vercel, Netlify, Fly.io, Docker, Kubernetes, or Forge deployment manifest was found in the repository root or under `.github/`.

**CI Pipeline:**
- Not detected. The `.github` directory exists, but no workflow files were found under `.github/workflows/`.

**Local Runtime Surface:**
- Local development is explicitly supported through the scripts in `composer.json`, which run `php artisan serve`, `php artisan queue:listen`, `php artisan pail`, and `npm run dev`.
- `php artisan about` reports the current local URL as `tenanto.test`, which aligns with the Herd-centric local tooling setup in `.mcp.json`.

## Environment Configuration

**Development:**
- Required env surfaces come from `config/app.php`, `config/database.php`, `config/filesystems.php`, `config/mail.php`, `config/cache.php`, `config/queue.php`, `config/session.php`, and `config/logging.php`.
- Critical keys for the active default stack are `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`, `DB_CONNECTION`, `DB_DATABASE` or `DB_URL`, `FILESYSTEM_DISK`, `MAIL_MAILER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER`, and `LOG_CHANNEL`.
- Secrets location: `.env` is present in the repo root and `.env.example` is present as the template source, but neither file was read.
- Mock/stub services: current defaults keep infrastructure local or internal by using SQLite plus database-backed cache/queue/session and the `log` mailer.

**Staging:**
- Not detected. No staging-only config file, workflow, or deployment manifest was found in the repository.

**Production:**
- Secrets management is not declared in the repository; production values are expected through environment variables consumed by `config/*.php`.
- `app/Providers/AppServiceProvider.php` forces HTTPS in production.
- `app/Services/Security/CspHeaderBuilder.php` emits CSP headers and a `report-uri` for browser violation reports.

## Webhooks & Callbacks

**Incoming:**
- Browser CSP violation reports post to `/csp/report` via `routes/web.php` and are handled by `app/Http/Controllers/CspViolationReportController.php`.
  - Verification: request validation is handled by `app/Http/Requests/Security/CspViolationRequest.php`.
  - Events: CSP/resource policy violations only.
- Private channel authorization callbacks for `org.{organizationId}` are registered in `routes/channels.php`.
  - Verification: user role/organization checks in `routes/channels.php`.
  - Events: `invoice.finalized`, `reading.submitted`, and `platform-notification.sent` from `app/Events/*.php`.

**Outgoing:**
- Outgoing mail notifications are triggered by `app/Filament/Actions/Auth/CreateOrganizationInvitationAction.php` and `app/Filament/Actions/Admin/Invoices/SendInvoiceReminderAction.php`.
  - Endpoint: determined by the active mail transport in `config/mail.php`; the current default writes to application logs rather than an external SMTP/API provider.
  - Retry logic: the notification classes in `app/Notifications/` use `Queueable`, but no `ShouldQueue` implementation was found, so queued delivery is not active by default.
- Outbound webhooks or third-party HTTP API calls were not detected in `app/`, `routes/`, `resources/`, or `tests/`.

---

*Integration audit: 2026-03-19*
*Update when adding/removing external services*
