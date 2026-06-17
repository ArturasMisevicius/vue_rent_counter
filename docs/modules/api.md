# API Module Contract

> **AI agent usage:** Read this before adding API routes, Sanctum endpoints, external integrations, or webhooks.

Updated on 2026-06-15.

## Purpose

API owns external JSON contracts and integration entrypoints. It must reuse the same actions and policies as internal UI surfaces.

## Invariants

- API routes are versioned and named;
- request validation uses Form Requests;
- responses use API Resources;
- mutations call actions;
- webhooks are idempotent;
- tenant/organization scope is enforced in the backend.

## Must Not

- mutate invoices, payments, documents, or tenant access directly in controllers;
- expose internal model arrays as public contracts;
- bypass feature/subscription/policy gates.

## Tests And Scenarios

API tests should cover auth, validation, successful action calls, permission denial, organization isolation, and idempotent webhook replay.
