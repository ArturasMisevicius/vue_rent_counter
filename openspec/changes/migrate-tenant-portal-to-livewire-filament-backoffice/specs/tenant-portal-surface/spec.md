## ADDED Requirements
### Requirement: Tenant Portal Is Livewire-First
The system SHALL render the tenant-facing portal through Livewire-first modules for dashboard, property, meters, meter readings, invoices, notifications, and profile.

#### Scenario: Tenant dashboard uses Livewire module
- **WHEN** a tenant opens the dashboard route
- **THEN** the canonical render path is a Livewire module
- **AND** the route does not depend on a legacy controller+Blade page flow as the primary implementation

#### Scenario: Tenant invoice and meter-reading flows use Livewire modules
- **WHEN** a tenant views invoices or submits meter readings
- **THEN** the canonical UI behavior is handled by Livewire modules
- **AND** tenant/property isolation is preserved

### Requirement: Backoffice CRUD Is Filament-First
The system SHALL treat Filament resources and pages as the canonical CRUD surface for admin, manager, and superadmin roles.

#### Scenario: Backoffice CRUD routes resolve to Filament-first ownership
- **WHEN** an admin, manager, or superadmin performs a CRUD workflow that exists in both custom controller+Blade and Filament
- **THEN** Filament is the canonical CRUD surface
- **AND** duplicate custom CRUD paths are removed or deprecated after verification

### Requirement: No Duplicate Tenant Controller+Blade Flows
The system SHALL remove duplicate controller-rendered tenant page flows once their Livewire replacements are verified.

#### Scenario: Tenant page duplication is eliminated
- **WHEN** the tenant portal migration is complete
- **THEN** there are no duplicated controller+Blade tenant flows for dashboard, property, meters, meter readings, invoices, notifications, or profile

### Requirement: Realtime Uses the Simplest Viable Mechanism
The system SHALL use events and broadcasting only when multi-user realtime behavior is necessary.

#### Scenario: Single-user tenant interaction stays local
- **WHEN** a tenant performs a normal dashboard, form, or list interaction
- **THEN** the surface uses Livewire reactivity or polling when needed
- **AND** it does not require broadcasting by default

#### Scenario: Cross-user realtime uses broadcasting only when justified
- **WHEN** a workflow requires multiple users to observe state changes in near real time
- **THEN** events and broadcasting may be used for that workflow
- **AND** the requirement is explicitly justified by the interaction model
