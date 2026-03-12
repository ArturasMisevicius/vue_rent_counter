---
name: laravel-development
description: "Builds and maintains Laravel 12 application code with framework conventions, validation, authorization, queues, Eloquent relations, and Pest tests. Activate for controller/service/model/request/test work in this repository."
license: MIT
metadata:
  author: tenanto
---

# Laravel Development

## When to Apply

- Creating or updating Laravel application features.
- Modifying routes, controllers, requests, policies, jobs, events, or models.
- Writing or fixing Pest tests for backend behavior.

## Version Context

- Laravel: `^12.54`
- PHP: `^8.2`
- Pest: `^4.4`
- Sanctum: `^4.3`

## Default Workflow

1. Prefer Form Requests for validation.
2. Prefer Eloquent relationships over raw queries.
3. Add or update Pest tests for every behavior change.
4. Run targeted tests with `php artisan test --compact`.
5. Run `vendor/bin/pint --dirty` before finalizing changes.

## MCP/AI Notes

- Use project MCP server handle `tenanto`.
- Start server: `php artisan mcp:start tenanto`.
- Inspect server manually: `php artisan mcp:inspector tenanto`.