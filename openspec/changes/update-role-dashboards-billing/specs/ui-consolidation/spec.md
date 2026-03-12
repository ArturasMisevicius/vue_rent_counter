## ADDED Requirements
### Requirement: Canonical Livewire/Filament Surfaces
The system SHALL render dashboards, profiles, and settings through Livewire/Filament components and SHALL remove duplicate controller-rendered routes once replacements are validated.

#### Scenario: Admin dashboard renders Livewire page
- **WHEN** an admin accesses their dashboard route
- **THEN** the response is rendered by the Livewire dashboard component

### Requirement: Validation Centralization
Livewire and Filament actions MUST delegate validation rules to dedicated Form Request classes that enforce tenant/property ownership.

#### Scenario: Cross-tenant meter reading submission denied
- **WHEN** a manager submits a meter reading for a meter outside their tenant scope
- **THEN** validation fails and the reading is not stored

### Requirement: No API Dependency for UI Workflows
The system SHALL not rely on JSON API endpoints for dashboard or billing workflows.

#### Scenario: Dashboard loads without API calls
- **WHEN** any role loads a dashboard page
- **THEN** the data is rendered server-side without API endpoint dependencies
