# Phase 4: Mutation Governance and Async Pipelines - Research

**Researched:** 2026-03-19
**Domain:** Shared Laravel request/action mutation paths, audit capture, and queued side effects
**Confidence:** MEDIUM

## Summary

Phase 4 should consolidate writes around the action foundation the repository already uses. The goal is not to introduce a second mutation framework, but to make representative high-risk flows reuse one validated entrypoint and then attach audit plus async side-effect handling at that shared seam.

The codebase already provides strong building blocks: role-prefixed Form Requests, action classes under `app/Filament/Actions/*`, reading validation support objects, and an audit logger. The current weaknesses are duplication between UI surfaces and synchronous email or export behavior that still runs in request time. Phase 4 should resolve those gaps so later billing work is layered onto predictable write pipelines.

## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| ARCH-04 | Standardized validated write paths | Recommends one request plus action contract per mutation family and an inventory test for representative flows. |
| GOV-01 | Traceable high-risk financial record changes | Recommends attaching actor/workspace/before-after capture to shared mutation seams. |
| GOV-02 | Consistent governance actions | Recommends standardized invoice approval, payment, and status transition recording. |
| PORT-02 | Tenant meter readings through one validated workflow | Recommends using the same reading validation support for tenant and operator flows. |
| OPS-02 | Slow side effects through queues | Recommends queue-backed jobs for reminders, emails, and exports instead of synchronous dispatch. |

## Recommended Plan Shape

1. Create a mutation inventory and standardize representative admin write flows.
2. Attach governance and audit capture to high-risk financial mutations.
3. Unify tenant meter reading writes with the shared validation pipeline.
4. Move notification and export side effects to queued jobs with focused integration proof.

---

*Research date: 2026-03-19*
