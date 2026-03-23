# Quick Task 260319-pqf: Merge three BillingService classes into one

## Outcome

The live repository already contained the canonical billing merge requested in this task:

- `app/Services/Billing/BillingService.php` exists and implements `App\Contracts\BillingServiceInterface`
- `AppServiceProvider` already binds `BillingServiceInterface` to the canonical billing service
- the legacy files `app/Services/BillingService.php`, `app/Services/BillingServiceSecure.php`, `app/Services/Enhanced/BillingService.php`, `app/Services/SecurityMonitoringService.php`, and `app/Services/Testing/*` were already deleted in commit `c9d0c172` on 2026-03-17
- current billing math flows through `App\Services\Billing\UniversalBillingCalculator`, which uses BCMath

## Historical Public Method Inventory

From git history:

- Legacy `app/Services/BillingService.php`
  - `generateInvoice(Tenant, Carbon, Carbon): Invoice`
  - `finalizeInvoice(Invoice): Invoice`
  - `recalculateDraftInvoice(Invoice): Invoice`
- Legacy `app/Services/BillingServiceSecure.php`
  - no public methods found in the last tracked file content
- Legacy `app/Services/Enhanced/BillingService.php`
  - `generateInvoice(InvoiceGenerationDTO): ServiceResponse`
  - `generateBulkInvoices(Collection, Carbon, Carbon): ServiceResponse`
  - `finalizeInvoice(Invoice): ServiceResponse`
  - `calculateConsumption(Property, Carbon, Carbon): ServiceResponse`
  - `getBillingHistory(Tenant, int): ServiceResponse`

## Work Completed In This Pass

One requested merge detail was still missing: the unique root-level security monitoring methods were not present in `app/Services/Security/SecurityMonitoringService.php`.

This pass added:

- `recordPolicyRegistration(array $policyResults, array $gateResults): ?SecurityViolation`
- `getSecurityMetrics(): array`

Tests added:

- `tests/Unit/Services/SecurityMonitoringServiceTest.php`

## Verification

- `php artisan config:clear`
- `php artisan test tests/Unit/Services/SecurityMonitoringServiceTest.php --compact`
- `php artisan test tests/Unit/Services/BillingServiceTest.php --compact`

Both test runs passed.

## Caveats

- Laravel Boost MCP was not available in this session, so Laravel service-binding verification used the current checked-in `AppServiceProvider` plus the official Laravel container docs as fallback.
- The requested live-data invoice verification via Boost `database-query` could not be performed in this session because that MCP server is unavailable here and the local SQLite file should not be treated as trusted live data.
