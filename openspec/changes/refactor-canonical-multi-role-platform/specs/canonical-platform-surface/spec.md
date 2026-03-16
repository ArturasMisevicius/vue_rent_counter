## ADDED Requirements
### Requirement: Canonical Shared Non-Filament UI Shell
The system SHALL render custom non-Filament role pages through one shared Blade layout and one shared component system.

#### Scenario: Shared layout drives non-Filament role pages
- **WHEN** a non-Filament page is rendered for superadmin, admin, manager, or tenant
- **THEN** it uses the canonical shared layout shell
- **AND** shared UI primitives are rendered from the shared component system rather than role-specific component folders

### Requirement: Policy-Driven Visibility and Superadmin Full Control
The system SHALL use policies and authorization hooks as the source of truth for action visibility and access control, with superadmin as the only global full-control role.

#### Scenario: Sensitive actions are policy-driven
- **WHEN** a page, Livewire module, or Filament resource renders a sensitive action
- **THEN** visibility and execution are controlled by policy-backed authorization instead of raw role-string template checks

#### Scenario: Superadmin has global CRUD and deep visibility
- **WHEN** a superadmin accesses a managed resource
- **THEN** they can perform the full supported CRUD lifecycle and privileged operational actions
- **AND** deep metadata is visible where the surface supports it

### Requirement: Canonical Validation Architecture
The system SHALL centralize validation into `FormRequest` classes for HTTP mutations and dedicated Livewire form/validator classes for Livewire interactions.

#### Scenario: HTTP mutations use dedicated requests
- **WHEN** a mutating controller action is invoked
- **THEN** validation is provided by a dedicated `FormRequest`
- **AND** validation is not declared inline in the controller or route closure

#### Scenario: Livewire components do not define inline rules
- **WHEN** a Livewire component performs validation
- **THEN** the rules are provided by a dedicated form object or validator class
- **AND** the component class does not serve as the inline validation source of truth

### Requirement: Canonical Interaction Surfaces by Role
The system SHALL make tenant-facing custom pages Livewire-first and backoffice CRUD Filament-first.

#### Scenario: Tenant portal uses Livewire modules
- **WHEN** a tenant uses dashboard, property, meters, meter readings, invoices, notifications, or profile flows
- **THEN** the canonical implementation is Livewire-driven

#### Scenario: Backoffice CRUD uses Filament
- **WHEN** an admin, manager, or superadmin performs CRUD on managed resources
- **THEN** the canonical implementation is Filament-driven unless a documented exception applies

### Requirement: Canonical Enabled Locale Support
The system SHALL keep translations complete for every enabled locale and SHALL use `lang/` as the only canonical translation tree.

#### Scenario: Enabled locales are complete
- **WHEN** the application loads translations for an enabled locale
- **THEN** the canonical translation files and required keys are present under `lang/`
- **AND** automated verification fails if an enabled locale is incomplete

#### Scenario: Translation fallback bridges are removed
- **WHEN** canonical translation keys are fully migrated
- **THEN** runtime missing-key fallback bridges are no longer registered

### Requirement: Legacy Cleanup and Guardrails
The system SHALL remove dead legacy files, compatibility shims, and obsolete compatibility routes after migration verification and SHALL protect the end state with Pest guardrails.

#### Scenario: Legacy bridges are removed after migration
- **WHEN** a legacy layout, role-scoped component, compatibility alias, or compatibility route no longer has live callers
- **THEN** it is deleted from the repository

#### Scenario: Guardrails block architectural regressions
- **WHEN** a forbidden legacy pattern is reintroduced after migration
- **THEN** Pest or related verification checks fail
