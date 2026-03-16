## ADDED Requirements
### Requirement: Canonical Non-Filament Layout
The system SHALL render all non-Filament role pages through `resources/views/layouts/app.blade.php` as the only shared layout shell.

#### Scenario: Tenant page renders through the canonical layout
- **WHEN** a tenant opens a non-Filament page
- **THEN** the page renders through `resources/views/layouts/app.blade.php`
- **AND** tenant-specific navigation or content regions are supplied through shared layout inputs instead of a separate tenant layout file

#### Scenario: Superadmin page renders through the canonical layout
- **WHEN** a superadmin opens a non-Filament page
- **THEN** the page renders through `resources/views/layouts/app.blade.php`
- **AND** role-specific actions are controlled without a separate superadmin layout file

### Requirement: Shared Non-Filament Component Library
The system SHALL provide a single shared non-Filament component namespace for structural and interactive UI primitives.

#### Scenario: Role pages use shared components
- **WHEN** a non-Filament page renders a page shell, section card, stat card, quick action, alert, stack, or button
- **THEN** it uses the shared `x-ui.*` component library
- **AND** it does not depend on role-scoped component folders

### Requirement: Policy-Driven Presentation
The system SHALL express role-specific presentation differences through authorization-aware navigation data, component props, and slots instead of separate component trees.

#### Scenario: Role-specific action visibility remains shared
- **WHEN** a shared component renders actions for different roles
- **THEN** visibility is controlled through authorization checks or shared view data
- **AND** the underlying component remains the same shared component

### Requirement: Legacy UI Removal
The system SHALL remove superseded layout files and duplicate component implementations after migration verification.

#### Scenario: Legacy layout and role component references are gone
- **WHEN** the repository is searched after the migration
- **THEN** there are no references to `layouts.tenant` or `layouts.superadmin`
- **AND** there are no remaining role-scoped component directories for `backoffice`, `manager`, or `tenant`

#### Scenario: Canonical button component is the only button source of truth
- **WHEN** a non-Filament page renders a button
- **THEN** it uses the canonical `x-ui.button` implementation
- **AND** the legacy anonymous button component is not used
