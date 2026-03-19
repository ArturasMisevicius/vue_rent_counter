# Phase 5: Billing Lifecycle Canonicalization - Research

**Researched:** 2026-03-19
**Domain:** Canonical invoice aging, billing pipeline parity, money policy, and invoice explainability
**Confidence:** MEDIUM

## Summary

Phase 5 should convert the billing layer from a set of large orchestration services into a consistent set of policies and smaller collaborators. The codebase already exposes one concrete overdue bug and already warns against making `BillingService` even larger. That makes this phase a good fit for incremental extraction guided by contract tests rather than a rewrite.

## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| BILL-01 | Canonical due-date-first overdue policy | Start with `OutstandingBalancesReportBuilder` and extend the policy to every invoice-aging surface. |
| BILL-02 | Billing preview and finalization parity | Drive both through one shared candidate and calculation pipeline. |
| BILL-03 | Canonical rounding and allocation policy | Extract explicit money semantics into testable policy code or value objects. |
| BILL-04 | Consistent reading validation and billing candidate selection | Reuse the same eligibility rules across admin, tenant, import, preview, and finalization flows. |
| BILL-05 | Explainable invoice views and stable downloadable artifacts | Align invoice views and PDFs to one canonical invoice representation. |

## Recommended Plan Shape

1. Fix and generalize due-date-first overdue behavior.
2. Unify preview and finalization around one billing pipeline.
3. Extract canonical money rounding and allocation policy.
4. Standardize meter-reading eligibility and billing candidate selection.
5. Align invoice presentation and PDF generation to the same canonical bill breakdown.

---

*Research date: 2026-03-19*
