# ADR 0001: Use A Modular Laravel Monolith

## Status

Accepted

## Date

2026-06-15

## Context

Tenanto has multiple business areas in one Laravel application: billing, payments, tenant portal, documents, KYC, contracts, support, notifications, and platform operations. The app already uses shared Eloquent models, Filament resources, Livewire routes, action classes, policies, and tests.

Splitting into services would add operational cost before module contracts are stable. Keeping one unstructured application would let business logic drift across UI callbacks, jobs, observers, and tests.

## Decision

Use a modular Laravel monolith. Modules are documented ownership contracts with explicit dependencies and invariants. Code can remain in the current Laravel/Filament namespaces while module contracts guide new work and future refactors.

## Alternatives Considered

### Microservices

- Pros: physical boundaries and independent deployment.
- Cons: premature distributed transactions, integration complexity, higher ops load.
- Rejected for now.

### Flat Laravel Application

- Pros: simplest short-term structure.
- Cons: weak ownership, duplicated workflows, higher financial/security risk.
- Rejected as the long-term direction.

## Consequences

- Module docs become part of the engineering contract.
- New high-risk features must identify module ownership.
- Folder migrations can happen gradually after ADR-backed plans.
- Code review must protect boundaries even before tooling enforces every rule.
