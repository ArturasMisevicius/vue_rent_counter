---
name: tenanto-billing-reporting
description: Use for Tenanto billing, tariffs, invoices, readings, statements, exports, or reporting changes that must follow the repo's financial calculation and lifecycle rules.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Billing Reporting

## Use This Skill When

- Implementing or changing tariff resolution, invoice generation, payment handling, meter-reading billing, or shared-service allocation.
- Building report builders, CSV exports, PDF exports, or billing dashboards.
- Reviewing invoice lifecycle or money calculation correctness.

## Canonical Billing Service

- Interface: `App\Contracts\BillingServiceInterface`
- Canonical implementation: `App\Services\Billing\BillingService`
- Container binding: `App\Providers\AppServiceProvider`
- Prefer the contract in consuming code; do not create competing billing service entry points.

## Money Rule: BCMath Only

- All monetary values are decimal strings, not floats.
- All money math flows through BCMath-backed helpers, especially `App\Services\Billing\UniversalBillingCalculator`.
- Use calculator helpers like `money()`, `rate()`, `quantity()`, `add()`, `subtract()`, `multiply()`, `divide()`, and `compare()`.
- Do not use native float addition, subtraction, multiplication, division, or `round()` for billable amounts.

## Invoice Lifecycle

- `draft` is the editable pre-finalization state.
- Moving from `draft` to `finalized` is one-way for billable structure.
- After finalization, invoice contents are effectively immutable; only limited payment/status fields may change.
- Downstream states such as `partially_paid`, `paid`, `overdue`, and `void` still represent finalized invoices, not editable drafts.

## Tariff Resolution Hierarchy

`App\Services\Billing\TariffResolver` resolves effective tariff data in this order:

- `type`: `rate_schedule.type` -> `tariff.configuration.type` -> `pricing_model->value` -> `'flat'`
- `unit_rate`: `configuration_overrides.unit_rate` -> `rate_schedule.unit_rate` -> `tariff.configuration.rate` -> `0`
- `base_fee`: `configuration_overrides.base_fee` -> `rate_schedule.base_fee` -> `tariff.configuration.base_fee` -> `0`
- `zones`: `rate_schedule.zones` -> `tariff.configuration.zones` -> `configuration_overrides.zones` -> `[]`

Do not invent a different precedence order without updating tests and docs together.

## Shared Service Cost Distribution

`App\Services\Billing\SharedServiceCostDistributorService` is the canonical allocator:

- `EQUAL`: divide total cost by `participant_count`
- `AREA`: prorate by `participant_area / total_area`
- `BY_CONSUMPTION`: prorate by `participant_consumption / total_consumption`
- `CUSTOM_FORMULA`: use `custom_share`

All outputs must be normalized back through the calculator's money formatting.

## Billing Period Convention

- Billing period `from` and `to` dates are both inclusive.
- Period filters, eligibility windows, and report ranges must treat both endpoints as part of the billing window.
- Do not silently switch to exclusive end dates in reports, exports, or invoice generation logic.

## Export Conventions

CSV exports:

- first row is the report title
- summary rows come next
- then a blank row
- then the column header row
- then flat scalar data rows
- content type is `text/csv; charset=UTF-8`

PDF exports:

- use the report PDF exporter path already wired through `ReportExportService`
- keep the same title, summary, columns, rows, and empty-state semantics as CSV
- content type is `application/pdf`

## Working Rules

- Reuse existing billing support classes before adding new calculators.
- Keep report builders query-focused and Blade query-free.
- Scope every invoice, reading, tariff, and report query to the actor's organization unless the actor is explicitly superadmin.
- Add regression tests for totals, edge dates, lifecycle transitions, and export formatting when behavior changes.

## Suggested Verification

- `tests/Unit/Services/BillingServiceTest.php`
- `tests/Feature/Billing/ReportsTest.php`
- invoice lifecycle or payment tests touching finalization behavior

## Completion Checklist

- [ ] Billing code used the canonical service or calculator path
- [ ] All money math used BCMath-backed helpers
- [ ] Invoice lifecycle rules stayed intact
- [ ] Tariff precedence stayed correct
- [ ] Shared-service distribution logic stayed canonical
- [ ] Billing periods remained inclusive
- [ ] CSV and PDF output stayed aligned
