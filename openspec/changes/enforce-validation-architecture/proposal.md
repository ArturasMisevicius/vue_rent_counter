# Change: Enforce Validation Architecture Across Controllers, Livewire, and Route Endpoints

## Why
The application already contains a substantial `FormRequest` layer, but validation is still inconsistent across execution surfaces. Several mutating controller actions still call `$request->validate()`, one route closure performs inline validation directly in `routes/web.php`, and at least one Livewire component keeps `rules()` and `messages()` inside the component class. This creates drift in validation behavior, duplicates translated messages, and makes policy and ownership checks harder to standardize.

This change establishes one validation architecture for the whole web surface: dedicated `FormRequest` classes for mutating HTTP controller actions, dedicated Livewire form objects or validator classes for Livewire, and no inline validation in route closures.

## What Changes
- Require every mutating controller action to use a dedicated `FormRequest`.
- Remove `request()->validate()` and `$request->validate()` from controllers.
- Remove route-closure validation and move those endpoints into controller actions backed by `FormRequest` classes.
- Remove `$this->validate()` with inline `rules()` / `messages()` definitions from Livewire component classes.
- Move Livewire validation to dedicated Livewire form objects or dedicated validator/rules classes.
- Reuse translated validation messages consistently through request classes, shared validation helpers, and Filament validation-message utilities where applicable.
- Add targeted Pest coverage and a validation-normalization report.

## Impact
- **BREAKING**: mutating controller signatures, route handlers, and Livewire component internals will change to use the canonical validation architecture.
- Affected specs:
  - `validation-architecture`
- Related pending changes:
  - `normalize-authorization-surfaces`
  - `update-role-dashboards-billing`
  - `refactor-shared-layout-components`

## Scope Notes
- This change does **not** change the underlying domain rules themselves; it changes where those rules are declared and executed.
- This change does **not** require Livewire to use `FormRequest` classes directly; Livewire may use form objects or dedicated validator classes instead, as long as validation is not defined inline in the component class.
