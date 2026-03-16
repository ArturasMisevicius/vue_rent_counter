## Context
The repository already includes many `FormRequest` classes under `app/Http/Requests`, and Filament has a helper trait for translation-backed validation messages in `App\\Filament\\Concerns\\HasTranslatedValidation`. However, validation is not fully centralized yet. Current examples include inline controller validation in multiple controllers, a route closure in `routes/web.php` that performs `$request->validate()`, and the Livewire manager meter-reading form that defines local `rules()` and `messages()` and calls `$this->validate(...)` from the component class.

## Goals / Non-Goals
- Goals:
  - Dedicated `FormRequest` classes for every mutating HTTP controller action.
  - No inline validation in controllers or route closures.
  - No inline validation rule/message definitions in Livewire component classes.
  - Translated validation messages reused consistently across requests, Livewire, and Filament.
  - Preserve tenant/property ownership rules and existing domain validation semantics.
- Non-Goals:
  - Rewriting domain validation rules beyond what is needed to relocate them.
  - Converting all validation to a single technical abstraction regardless of surface.
  - Broad route or UI redesign outside validation architecture.

## Decisions
- HTTP controller actions use dedicated `FormRequest` classes as the canonical validation layer.
- Route closures that mutate state are replaced by proper controller actions plus `FormRequest` classes.
- Livewire components delegate validation to either:
  - dedicated Livewire form objects, or
  - dedicated validator/rules classes when richer rule composition is needed.
- Livewire component classes should not keep local `rules()` or `messages()` methods for business forms after migration.
- Translation-backed messages should come from canonical translation keys reused across `FormRequest` classes and validation helpers; Filament’s existing translated validation trait remains part of the shared message strategy.

## Risks / Trade-offs
- Some existing tests may rely on current validation payload shapes or message keys.
  - Mitigation: preserve translated keys and add focused regression tests before removing inline definitions.
- Livewire extraction can accidentally change when validation is triggered.
  - Mitigation: keep the trigger semantics explicit in the extracted form objects or validators and test them directly.
- Route-closure replacement may affect tests or helper flows that currently depend on named closures.
  - Mitigation: preserve route names and HTTP contracts when moving closure logic into controllers.

## Migration Plan
1. Inventory all remaining inline validation sites in controllers, routes, and Livewire.
2. Introduce missing `FormRequest` classes for controller and route endpoints.
3. Replace inline route closures with controller methods that type-hint the new request classes.
4. Extract Livewire validation into form objects or dedicated validator classes.
5. Normalize translated validation message reuse through canonical helpers and translation keys.
6. Add regression tests and produce a validation-normalization report.
