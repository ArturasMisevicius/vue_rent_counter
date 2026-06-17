# Livewire Guidelines

> **AI agent usage:** Read this before changing Livewire components or tenant portal endpoints.

Updated on 2026-06-15.

## Rule

Livewire components are UI orchestration. They should keep state small, call actions/query presenters, and avoid owning cross-model business workflows.

## Component Responsibilities

- route-level or component-level access checks;
- form state;
- validation handoff;
- action calls;
- presenter/query calls;
- browser-friendly messages and redirects.

## Forbidden In Livewire

- direct payment confirmation;
- direct document visibility bypass;
- direct tenant assignment or move-out lifecycle mutation without action calls;
- unscoped model lookup for tenant or organization records;
- large model collections in public component state.

## Tenant Portal

Tenant portal data should use tenant-safe presenters and actions. Backend authorization must hold even when a tenant guesses a URL.

## Tests

Add feature or Livewire tests for tenant URL bypass, organization isolation, and component calls to the action path when the workflow is sensitive.
