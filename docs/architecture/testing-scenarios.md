# Testing And Scenario Conventions

> **AI agent usage:** Use this before writing tests for billing, payments, documents, KYC, tenant portal, permissions, or release guardrails.

Updated on 2026-06-15.

## Scenario Builders

Tests should build realistic business states through factories and helper builders, not by writing impossible database rows. Existing helpers include:

- `createOrgWithAdmin()` in `tests/Pest.php`;
- `createTenantInOrg()` in `tests/Pest.php`;
- feature-specific fixture helpers in billing and tenant tests;
- support factories under `tests/Support`.

New modules should add scenario helpers when a workflow needs repeated setup.

## Required Action Coverage

Critical actions need tests for:

- happy path;
- permission failure;
- business validation failure;
- organization isolation;
- audit/event/notification behavior when applicable.

## Architecture Tests

Architecture tests live in `tests/Feature/Architecture`. They should prefer explicit file scanning over brittle broad reflection when the codebase has known legacy exceptions.

Good architecture tests:

- check a known boundary;
- list violations clearly;
- allow documented exceptions;
- run quickly in isolation.

`php artisan architecture:check` also checks the critical workflow seams for billing payments, invoice sending, reading correction, invitation acceptance, and impersonation. Those actions must receive explicit actor/context inputs rather than calling `auth()` or `request()` directly, and the related focused tests must keep referencing the expected action classes and audit mutations.

## Docs Tests

Docs that act as contracts should be checked for existence through tests or `architecture:check`. This keeps the architecture feature from becoming stale.

## Verification Commands

For architecture-only changes:

```bash
php artisan architecture:check
php artisan test tests/Feature/Architecture --compact
git diff --check -- $(rg --files -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**')
```

For billing/payment behavior changes:

```bash
php artisan test tests/Feature/Billing --compact
```
