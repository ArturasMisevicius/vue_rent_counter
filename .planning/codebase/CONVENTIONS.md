# Codebase Conventions

## Scope

This map summarizes the repository’s current quality conventions for planning work in the Tenanto Laravel codebase. It is based on checked-in code and config, not on idealized framework defaults.

Primary reference files:

- `.editorconfig`
- `composer.json`
- `README.md`
- `routes/web.php`
- `app/Providers/AppServiceProvider.php`
- `app/Providers/AuthServiceProvider.php`

## Formatting and baseline style

- Global editor rules live in `.editorconfig`: UTF-8, LF, 4-space indentation, final newline, and trimmed trailing whitespace. YAML files use 2 spaces; Markdown keeps trailing whitespace intact.
- PHP formatting is Laravel/Pint-oriented via `laravel/pint` in `composer.json` and the repository guidance in `README.md`.
- Newer backend code commonly starts with `declare(strict_types=1);`, especially in requests, services, exceptions, and Livewire endpoints. Examples:
  - `app/Http/Requests/Admin/Tenants/StoreTenantRequest.php`
  - `app/Services/Billing/InvoiceService.php`
  - `app/Exceptions/InvalidProjectTransitionException.php`
  - `app/Livewire/Tenant/DownloadInvoiceEndpoint.php`
- Strict types are **not universal**. Older or more framework-heavy files often omit them, so edits should match sibling files instead of force-normalizing. Examples without strict types:
  - `app/Models/Invoice.php`
  - `app/Models/User.php`
  - `app/Policies/ProjectPolicy.php`
  - `app/Filament/Actions/Admin/Tenants/CreateTenantAction.php`
  - `app/Http/Middleware/CheckManagerPermission.php`

## Naming and namespace patterns

- Namespaces mirror product surfaces and domain boundaries rather than generic technical layers:
  - `app/Http/Requests/Admin/...`
  - `app/Http/Requests/Superadmin/...`
  - `app/Http/Requests/Tenant/...`
  - `app/Filament/Actions/Admin/...`
  - `app/Filament/Support/Admin/...`
  - `app/Livewire/Pages/...`
- Class names are descriptive and task-specific. Common suffixes:
  - `*Request` for validation classes, e.g. `app/Http/Requests/Admin/Invoices/ProcessPaymentRequest.php`
  - `*Action` for write/mutation logic, e.g. `app/Filament/Actions/Admin/Tenants/CreateTenantAction.php`
  - `*Service` for orchestrators, e.g. `app/Services/Billing/InvoiceService.php`
  - `*Builder` for report/read-model composition, e.g. `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php`
  - `*Policy` for access rules, e.g. `app/Policies/ProjectPolicy.php`
  - `*Endpoint` or `*Page` for Livewire route handlers, e.g. `app/Livewire/Tenant/DownloadInvoiceEndpoint.php`, `app/Livewire/Pages/DashboardPage.php`
- Enum classes live under `app/Enums/` and are grouped by domain concept. Cases are currently uppercase constants, not TitleCase. Example: `app/Enums/UserRole.php` defines `SUPERADMIN`, `ADMIN`, `MANAGER`, `TENANT`.
- Test names follow behavior-first naming and are grouped by product area. Examples:
  - `tests/Feature/Admin/InvoicesResourceTest.php`
  - `tests/Feature/Architecture/WorkspaceReadModelInventoryTest.php`
  - `tests/Unit/Requests/FormRequestValidationTest.php`

## Architectural conventions that affect code quality

- Routes are intentionally thin and point directly to Livewire pages/endpoints instead of controller-heavy flows. See `routes/web.php` for examples such as:
  - `Route::get('/', HomepagePage::class)`
  - `Route::get('/dashboard', [DashboardRedirectEndpoint::class, 'show'])`
  - `Route::get('/tenant/invoices/{invoice}/download', [DownloadInvoiceEndpoint::class, 'download'])`
- `app/Http/Controllers/` is effectively minimized; `app/Http/Controllers/Controller.php` exists as the base controller, while most interactive flows live in `app/Livewire/` and `app/Filament/`.
- App wiring is centralized in service providers:
  - `app/Providers/AppServiceProvider.php` registers singletons, scoped services, observers, rate limiters, and global Filament destructive-action confirmation rules.
  - `app/Providers/AuthServiceProvider.php` maps model policies and gives `SUPERADMIN` a global `Gate::before()` allow.
- Access control is layered rather than single-source:
  - policies in `app/Policies/*`
  - request `authorize()` methods in `app/Http/Requests/*`
  - custom middleware like `app/Http/Middleware/CheckManagerPermission.php`
  - role helpers on models such as `app/Models/User.php`

## Model and query conventions

- Eloquent models commonly keep:
  - `protected $fillable`
  - `casts()` methods instead of a `$casts` property
  - explicit relationship return types
  - reusable local scopes for tenant/workspace filtering
- `app/Models/Invoice.php` and `app/Models/User.php` are representative:
  - workspace-specific `select()` column lists are stored as private constants
  - scopes encapsulate business slices like `outstanding()`, `overdue()`, `adminLike()`, `withCurrentPropertySummary()`
  - relationships are strongly typed (`BelongsTo`, `HasMany`, `HasOne`)
- Raw SQL is avoided for ordinary CRUD, but query builder expressions are accepted for high-value read models and aggregates. `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php` uses joins and SQL expressions to produce reporting rows.
- `DB::` is used selectively for transactions and special cases rather than as the default read/write pattern. Example: `app/Services/Billing/InvoiceService.php` wraps multi-step mutations in `DB::transaction()` and uses `DB::afterCommit()`.

## Validation conventions

- Request validation is strongly standardized around `FormRequest` classes under `app/Http/Requests/`.
- Request classes usually include:
  - `authorize(): bool`
  - `rules(): array`
  - `messages(): array`
  - `attributes(): array`
  - `prepareForValidation(): void`
- `app/Http/Requests/Admin/Tenants/StoreTenantRequest.php` is a good example of current style:
  - inline array rules
  - `Rule::exists()` scoped by organization
  - custom closure validation for property assignment eligibility
  - localized messages and attributes
  - pre-validation normalization via trimming and empty-string-to-null conversion
- The trait `app/Http/Requests/Concerns/InteractsWithValidationPayload.php` is a major convention. It allows request classes to be reused outside controllers by validating arbitrary payload arrays through `validatePayload()` and `authorizePayload()`.
- That trait is actively consumed from mutation classes. Example: `app/Filament/Actions/Admin/Tenants/CreateTenantAction.php` instantiates `StoreTenantRequest` and validates data before creating records.

## Error-handling conventions

- Error handling depends on surface area:
  - domain/runtime failures throw typed exceptions or framework exceptions
  - web routes often use `abort(...)`
  - Filament UI flows use `Filament\Notifications\Notification`
  - JSON-aware middleware may return JSON error payloads directly
- Representative patterns:
  - typed domain exception factory: `app/Exceptions/InvalidProjectTransitionException.php`
  - HTTP access exception: `app/Services/ImpersonationService.php`
  - UI notification + redirect + JSON split: `app/Http/Middleware/CheckManagerPermission.php`
  - low-level runtime failure: `app/Services/Billing/InvoicePdfRenderer.php`
- The codebase favors explicit failure messages and translated user-facing text rather than silent failure.

## Localization and user-facing strings

- User-facing text is usually translated with `__()` rather than hard-coded English.
- This applies across services, notifications, commands, and UI support classes. Examples:
  - `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php`
  - `app/Services/Billing/InvoicePdfDocumentFactory.php`
  - `app/Notifications/InvoiceOverdueReminderNotification.php`
  - `app/Console/Commands/LaravelMissingTranslationsPhpFilesCommand.php`
- Validation requests also translate attribute labels and messages through `InteractsWithValidationPayload`.

## Filament and Livewire conventions

- Filament hosts most authenticated CRUD and admin/superadmin workflows under `app/Filament/`.
- Shared support logic is intentionally extracted away from page/resource classes into `app/Filament/Support/...` and `app/Filament/Actions/...`.
- Livewire components under `app/Livewire/` handle page shells, endpoints, dashboard composition, public routes, and tenant self-service.
- `app/Livewire/Pages/DashboardPage.php` shows the pattern of keeping composition in a parent component and delegating role-specific data to injected presenters/support services.
- Livewire endpoint classes are often tiny wrappers over actions, e.g. `app/Livewire/Tenant/DownloadInvoiceEndpoint.php`.

## Constructors, typing, and PHPDoc usage

- Constructor property promotion with `private readonly` dependencies is common in newer classes:
  - `app/Services/Billing/InvoiceService.php`
  - `app/Http/Middleware/CheckManagerPermission.php`
  - `app/Filament/Actions/Admin/Tenants/CreateTenantAction.php`
- Array-shape PHPDoc is used heavily where payloads are complex. Examples:
  - `app/Http/Requests/Admin/Tenants/StoreTenantRequest.php`
  - `app/Filament/Support/Admin/Reports/RevenueReportBuilder.php`
  - `app/Filament/Actions/Admin/Tenants/CreateTenantAction.php`
- Many service/support classes are marked `final`; models, policies, and a number of Filament classes are not. Plan edits according to local neighborhood style.

## Practical planning notes

- Match the strictness level of sibling files before editing. Do not assume repository-wide strict types or `final` usage.
- Prefer adding behavior in existing `FormRequest`, `Filament\Actions`, or `Filament\Support` areas instead of expanding controllers.
- Reuse request validation through `validatePayload()` when implementing Filament or Livewire mutations.
- Preserve translated messaging with `__()` for anything user-facing.
- Keep multi-step writes transactional and cache/event side effects in `afterCommit()` when following service-layer patterns like `app/Services/Billing/InvoiceService.php`.
- There are no `TODO`/`FIXME` markers in `app/**/*.php` from the current scan, so open concerns need to be inferred from tests, structure, or behavior rather than inline comments.
