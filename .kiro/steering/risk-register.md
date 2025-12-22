# Risk Register

| Risk | Impact | Likelihood | Mitigation |
| --- | --- | --- | --- |
| Cross-tenant data leakage (manager/admin/tenant) | System integrity compromised; potential data breach | Medium | Apply `BelongsToTenant`, `TenantScope`, `TenantContext`, and policies on every Filament resource/controller; property tests cover isolation (`MultiTenancyTest`, `InvoiceMultiTenancyTest`). |
| Tariff miscalculation | Incorrect invoicing, refunds, compliance issues | High | Snapshot tariffs in `InvoiceItemData`, validate using `TariffResolver`, and cover with tariff calculation tests plus property tests for time-of-use zones. |
| Missing or invalid meter readings | Draft invoices stuck and inaccurate totals | Medium | Enforce monotonic/time validation in meter reading controllers/Filament, create audits via `MeterReadingObserver`, and log warnings when readings are absent; build UI feedback in meter-reading forms. |
| Subscription/tenant quota drift | Admins blocked from onboarding tenants or tenants lose access | Medium | Lock tenant creation when quotas hit (`SubscriptionService`), surface expiring subscriptions through superadmin dashboard, and send `SubscriptionExpiryWarningEmail` ahead of renewals. |
| Filament permission drift (navigation, bulk actions) | Unauthorized edits or hidden features | Medium | Test `shouldRegisterNavigation` per role, tie `can*` methods to policies, and double-check bulk actions (invoice status updates, property deletion) with property tests. |
| Backup/WAL inconsistency | Data loss during deploys or concurrent writes | Low | Keep SQLite/WAL enabled, run Spatie backups nightly (`spatie/laravel-backup` config for SQLite), and add monitoring for `php artisan backup:run` + queue workers. |
