# BillingService Legacy Merge Surface (Executed on 2026-03-19)

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md` before acting on this file. Treat examples as context; verify current code, routes, schema, translations, and tests before changing behavior.

## Sources reviewed
- `_old/app/Services/BillingService.php`
- `_old/app/Services/BillingServiceSecure.php`
- `_old/app/Services/Enhanced/BillingService.php`
- `app/Services/Billing/BillingService.php` (current canonical target)

## Public methods from legacy classes

### `App\Services\BillingService`
- `__construct(UniversalBillingCalculator $billingCalculator)`
- `generateInvoice(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): Invoice`
- `finalizeInvoice(Invoice $invoice): Invoice`
- `recalculateDraftInvoice(Invoice $invoice): Invoice`

### `App\Services\BillingServiceSecure`
- No public methods were present in the historical snapshot at `c9d0c172f7442dd6a852e2e1f40e0e9588ab57be`.

### `App\Services\Enhanced\BillingService`
- `__construct(GenerateInvoiceAction $generateInvoiceAction, UniversalBillingCalculator $billingCalculator, MeterReadingService $meterReadingService, ConsumptionCalculationService $consumptionService)`
- `generateInvoice(InvoiceGenerationDTO $dto): ServiceResponse`
- `generateBulkInvoices(Collection $tenants, Carbon $periodStart, Carbon $periodEnd): ServiceResponse`
- `finalizeInvoice(Invoice $invoice): ServiceResponse`
- `calculateConsumption(Property $property, Carbon $periodStart, Carbon $periodEnd): ServiceResponse`
- `getBillingHistory(Tenant $tenant, int $months = 12): ServiceResponse`

## Canonical target status
- `app/Services/Billing/BillingService.php` exists and is the active canonical service implementation.
- It already implements `App\Contracts\BillingServiceInterface`.
- It uses constructor property promotion for:
  - `TariffResolver`
  - `UniversalBillingCalculator`
  - `InvoiceService`
  - `SharedServiceCostDistributorService`
  - plus request/guard collaborators already needed by current use cases.
- Monetary arithmetic in the service is routed through `UniversalBillingCalculator` (BCMath-backed).

## Merge mapping decision
- Legacy methods that no longer exist in the active architecture (`generateInvoice`, `calculateConsumption`, `getBillingHistory`, `recalculateDraftInvoice`, old `generateBulkInvoices`/`finalizeInvoice` signatures) were intentionally not copied forward because the live interface contracts now target bulk draft/finalize/publish workflows.
- There was no legacy `BillingServiceSecure` functional implementation to override overlapping methods in this snapshot.
- No references to the deleted legacy service class paths were found in `app/**` or `tests/**`.
