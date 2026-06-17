# API Guidelines

> **AI agent usage:** Use this before adding API routes, public JSON contracts, webhooks, or Sanctum-facing endpoints.

Updated on 2026-06-15.

## Rule

API endpoints must call the same actions and policies as Filament, Livewire, jobs, and commands. The API must not become a second implementation of business workflows.

## Versioning

External API routes should be versioned and named:

```text
api.v1.invoices.index
api.v1.payments.store
```

## Request And Response Shape

- use Form Requests for validation;
- use API Resources for JSON output;
- use DTOs/result objects where the action input or output is non-trivial;
- avoid returning raw model internals for sensitive documents, payments, or tenant data.

## Security

API endpoints must enforce:

- authentication;
- organization scope;
- policy/action authorization;
- rate limits where public or integration-facing;
- audit for sensitive mutations.

## Webhooks

Webhook handlers must be idempotent and call actions. Store provider IDs and processed state to prevent duplicate mutations.
