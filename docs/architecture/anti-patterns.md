# Architecture Anti-Patterns

> **AI agent usage:** Use this as a review checklist when changing business workflows.

Updated on 2026-06-15.

## Banned Patterns

- business logic inside Filament form save callbacks;
- business logic inside Blade views;
- controllers directly mutating multiple financial models;
- jobs duplicating action logic;
- observers sending emails, posting ledger entries, or creating payments;
- model boot methods changing unrelated aggregates;
- `auth()` or `request()` inside reusable domain/action code;
- hardcoded status strings when an enum exists;
- raw file paths for sensitive documents;
- public storage for sensitive tenant/KYC/contract files;
- direct DB update for sent/finalized invoices;
- manual financial repair without audit.

## Review Labels

Use these labels for architecture issues and PR findings:

- `architecture`;
- `boundary-violation`;
- `business-logic-in-ui`;
- `missing-action`;
- `missing-policy`;
- `missing-event`;
- `missing-test`;
- `missing-docs`;
- `financial-risk`;
- `security-risk`.

## Refactor Priority

When an anti-pattern is found, prefer extracting one workflow into an action with a regression test before attempting a broad refactor.
