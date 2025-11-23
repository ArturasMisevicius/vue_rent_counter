# Product Overview

## Vilnius Utilities Billing Platform

Modern, multi-tenant utility and rental management for Lithuanian property portfolios. Built on Laravel 11 with Filament, it tracks buildings, meters, invoices, and tenants through role-aware dashboards, guards every request with tenant scope, and keeps gyvatukas/tariff math auditable.

## Core Purpose

Deliver a single-pane control center where:
- Superadmins monitor organizations, subscriptions, and system health.
- Admins/property owners manage portfolios, tariffs, meters, and invoice workflows without leaving the app.
- Managers capture meter readings, finalize invoices, and review reports per building, while gyvatukas and zone pricing live inside `BillingService`.
- Tenants inspect their property, meter history, and invoices with clear breakdowns (PDF export + status badges) and localized copy (EN/LT/RU).

## Value Proposition

- **Subscription visibility:** Superadmin dashboards surface expiring subscriptions, tenant usage stats, and organization activity with `SubscriptionService` and `AccountManagementService`.
- **Accurate billing:** `TariffResolver`, `GyvatukasCalculator`, and `MeterReadingObserver` snapshot tariffs/meter readings, recalc drafts, and prevent finalized edits.
- **Multi-tier security:** `BelongsToTenant`, tenant policies, and `TenantContext` ensure admins/managers/tenants cannot cross data boundaries; every critical action logs via observers/notifications.
- **Tenant-friendly UX:** Blade + Filament components (stat cards, data tables, modals) paired with CDN-delivered Tailwind & Alpine keep interactions quick without a full SPA stack.
- **Reproducible ops:** `php artisan test:setup`, deterministic seeders (buildings, meters, invoices), and Spatie backup + WAL readiness make deployments predictable.

## Key Features

### Superadmin & Platform Operations
- Dashboard with totals for organizations, properties, buildings, and invoices plus expiring subscriptions.
- Organization, subscription, and audit management CRUD under `Superadmin` controllers.
- Account hierarchy helpers, quota enforcement, and email notifications for tenant reassignment and subscription warnings.
- Global WAL/backups plus tenant-switching helpers that respect `spatie/laravel-backup` configuration.

### Admin & Manager Workflows
- Filament resources for `Property`, `Building`, `Meter`, `MeterReading`, `Invoice`, `Tariff`, `Provider`, `User`, and `Subscription` with multi-select bulk actions, validation, tenant filtering, and `Invoice` itemization forms.
- Precision billing for electricity (day/night zones), water/heating, gyvatukas circulation fees, and meter-specific tariffs stored per invoice item.
- Manager routes to create readings, finalize invoices, upload meter collections, export reports, and view compliance dashboards.
- Notifications for meter reading changes (`MeterReadingSubmittedEmail`) and tenant onboarding (`WelcomeEmail`, `TenantReassignedEmail`).

### Tenant Experience
- Tenant-specific dashboard exposing property details, meter readings per meter (with zone breakdowns), and invoice history with statuses (draft, finalized, paid).
- Downloadable invoice PDFs, localized copy (EN/LT/RU), and restricted profile edits (email, password with confirmation).
- Breadcrumb navigation, status badges, and filterable tables share components with Filament to keep the UI consistent.

## Success Metrics

- 100% of generated invoices snapshot tariff rates and gyvatukas logic; no finalized invoice recalculations occur due to subsequent tariff changes.
- Manager meter readings validate monotonicity/temporal rules with <2% rollbacks and guaranteed audit trails via `MeterReadingAudit`.
- Superadmin sees every expiring subscription (14-day window) and enforces tenant quotas before onboarding new users.
- Tenant and manager dashboards render within 300ms on cached pages; Filament tables remain searchable/sortable with PostgreSQL/MySQL/SQLite indexing.
- CI runs (`composer test`, Pest property suites, `php artisan test:setup --fresh`) stay green before merges.

## Target Users

- Superadmins overseeing multiple organizations and their subscriptions.
- Admins/property owners who manage buildings, tenants, and invoices for a tenant_id-scoped portfolio.
- Managers capturing meter readings, reviewing reports, and finalizing invoices per building.
- Tenants verifying their usage, invoices, and staying in sync with their admin.

## Non-Goals

- Building a public headless CMS or marketing site; focus is utility/account operations.
- Billing for markets outside the Lithuanian gyvatukas/tariff rules.
- Adding rich WYSIWYG editors or third-party payment processors.
