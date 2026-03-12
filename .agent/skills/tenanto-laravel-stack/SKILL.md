---
name: tenanto-laravel-stack
description: Tenanto-specific Laravel 12 + Filament 4 + Livewire 3 implementation playbook. Use when changing backend/frontend app code in this repository.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Laravel Stack

## Use This Skill When

- Editing Laravel application logic under `app/`, `routes/`, `resources/`, `database/`, or `tests/`.
- Implementing Filament resources/pages/widgets or Livewire components.
- Making architecture or refactor changes that touch multiple modules.

## Project Facts

- PHP `8.4.x`, Laravel `12`, Filament `4`, Livewire `3`, Sanctum `4`.
- This project keeps the Laravel 10-style structure (no migration to new streamlined layout unless explicitly requested).
- Tests are Pest-based and are required for behavior changes.

## Required Implementation Rules

- Follow existing conventions in sibling files before introducing new patterns.
- Prefer Form Requests for validation, not inline controller validation.
- Prefer Eloquent models/relationships over raw SQL and avoid `DB::` unless unavoidable.
- Keep tenant boundaries explicit when accessing data.
- Use strict typing and explicit return types in PHP code.
- Run focused tests for changed behavior.

## Working Sequence

1. Identify the feature path (controller/service/resource/component/request/policy).
2. Reuse existing abstractions if available (`Actions`, `Services`, `Repositories`, `Policies`).
3. Implement minimal change with clear boundaries.
4. Add or update Pest tests for happy path + failure path.
5. Run targeted test file/filter.
6. Run formatter/linting required by the repo before finalizing.

## Quick File Map

- `app/Filament/*`: Admin resources, pages, widgets.
- `app/Livewire/*` and `resources/views/livewire/*`: Livewire components.
- `app/Http/Requests/*`: Validation rules.
- `app/Services/*`, `app/Actions/*`, `app/Repositories/*`: Core business logic.
- `app/Policies/*`, `app/Http/Middleware/*`: Authorization and access control.
- `tests/Feature/*`, `tests/Unit/*`: Regression coverage.

## Completion Checklist

- [ ] Change follows existing project patterns.
- [ ] Tenant/authorization implications reviewed.
- [ ] Pest tests added/updated and executed.
- [ ] Formatting/linting run for touched files.

