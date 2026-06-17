# Architecture Overview

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/PROJECT-CONTEXT.md`, and `docs/FEATURES.md` before acting on this file. Verify live code before changing behavior.

Updated on 2026-06-15.

## Purpose

Tenanto is a Laravel and Filament property-management system with billing, payments, documents, KYC, contracts, tenant portal, support, and platform operations. These workflows affect money, legal documents, private files, and tenant access, so the application needs explicit engineering contracts.

The target architecture is a modular Laravel monolith:

- one application and database;
- clear module ownership;
- thin UI layers;
- shared action classes for business workflows;
- policies and organization scope enforced in the backend;
- events, notifications, jobs, and audits used for side effects;
- tests and docs that keep the rules visible.

## Current Placement

This checkout already has established placement rules:

- request validation: `app/Http/Requests`;
- Filament UI, resource schemas, table schemas, pages, and page actions: `app/Filament`;
- shared Filament action/support code: `app/Filament/Actions` and `app/Filament/Support`;
- current billing payment lifecycle actions: `app/Actions/Billing`;
- policies: `app/Policies`;
- operation services: `app/Services/Operations`;
- feature and architecture tests: `tests/Feature`.

Do not start a broad folder migration unless an ADR and migration plan explicitly approve it. New work should improve the existing action/support seams first.

## Layer Rule

The dependency direction is:

```text
UI layer -> actions -> domain services/support -> Eloquent models
actions -> audit/events/jobs/notifications
jobs/listeners -> actions/services
```

The forbidden direction is:

```text
domain/action code -> Filament resources/pages
domain/action code -> request() or auth() hidden globals
services -> Blade views or responses
models -> controllers or UI classes
```

## UI Layer

Filament resources, Filament pages, Livewire components, Blade views, routes, and console commands collect input, display output, and call actions. They may compose schemas, filters, table columns, notifications, redirects, and download responses.

They must not own financial state transitions, tenant access changes, document authorization, payment confirmation, ledger posting, or cross-model workflows.

## Action Layer

Important business workflows live in action classes. An action should:

- receive a typed actor or explicit actor argument;
- validate input through a Form Request or data object;
- authorize with policies, gates, or resolver-backed permissions;
- enforce organization and tenant scope;
- guard business invariants;
- wrap writes in a transaction;
- write audit records for sensitive mutations;
- emit or dispatch side effects after the core state transition;
- return a model or result object that the caller can use.

## Domain Services And Support

Reusable calculations and read models belong in services/support classes. They should stay side-effect-light when possible. Examples include billing calculators, invoice presenters, readiness builders, report builders, and tenant portal query classes.

## Side Effects

Emails, reminders, notifications, webhooks, exports, and search/index updates must not be buried in UI callbacks. Prefer events/outbox/jobs or clearly named notification actions. Jobs must be idempotent and call the same action/service path used by the UI.

## Enforcement

The first enforcement layer is intentionally lightweight:

- `php artisan architecture:check`;
- Pest architecture tests in `tests/Feature/Architecture`;
- module contracts in `docs/modules`;
- ADRs in `docs/adr`;
- PR checklist in `.github/pull_request_template.md`;
- release readiness reference to the architecture check.

These checks are not a substitute for code review. They are tripwires that catch drift early.
