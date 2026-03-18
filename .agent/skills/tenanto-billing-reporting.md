---
name: tenanto-billing-reporting
description: Use for Tenanto billing, tariffs, invoices, readings, statements, exports, or reporting changes that must follow the repo's financial calculation and lifecycle rules.
---

# Tenanto Billing Reporting

Mirror entry for the canonical skill at `.agent/skills/tenanto-billing-reporting/SKILL.md`.

- Canonical contract: `App\Contracts\BillingServiceInterface`
- Canonical implementation: `App\Services\Billing\BillingService`
- All monetary values and calculations use BCMath-backed helpers such as `UniversalBillingCalculator`
- Invoice lifecycle: `draft` is editable; after `finalized`, billable structure is immutable and later states remain finalized invoices
- Tariff resolution precedence is defined by `TariffResolver`
- Shared-service distribution is defined by `SharedServiceCostDistributorService`
- Billing period `from` and `to` dates are inclusive
- CSV and PDF report exports must stay aligned with `ReportExportService`

Read the canonical `SKILL.md` for the full lifecycle, precedence, and export rules.
