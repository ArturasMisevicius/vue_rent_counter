# Code Generation Guardrails

> **AI agent usage:** Use this whenever AI, Laravel generators, or MCP tooling creates or modifies code.

Updated on 2026-06-15.

## Rule

Generated code must follow Tenanto boundaries. Generated CRUD is a starting point, not permission to skip actions, policies, tests, or docs.

## Required Checklist

For generated feature code, answer:

- What module owns this?
- What action class owns the workflow?
- What request/DTO validates input?
- What policy or permission protects it?
- What organization/tenant scope is enforced?
- What events, notifications, jobs, or audit records are emitted?
- What tests cover success, permission failure, and isolation?
- What docs or module contracts changed?

## Generator Defaults

When generating a new workflow:

- create the Form Request first;
- create or reuse an action;
- update policy/permission checks;
- add tests;
- update docs;
- wire Filament/Livewire/controller code last.

## Forbidden Generated Output

- business logic in Blade;
- business logic in Filament schema callbacks;
- direct state mutation in route closures;
- direct `Mail::` or webhook calls from resources/pages;
- unscoped model lookup for organization records;
- new `app/Actions/*` namespaces outside documented exceptions.
