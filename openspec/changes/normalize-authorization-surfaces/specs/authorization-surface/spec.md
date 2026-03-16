## ADDED Requirements
### Requirement: Complete Policy Coverage for Core Resources
The system SHALL define and register policy coverage for the core resources used across controllers, Blade, Livewire, and Filament surfaces.

#### Scenario: Covered resources have canonical policies
- **WHEN** authorization is evaluated for `Organization`, `Tenant`, `Property`, `Building`, `Meter`, `MeterReading`, `Invoice`, `Subscription`, `User`, `Tariff`, `Provider`, `Language`, or `Translation`
- **THEN** the application resolves a canonical policy for that resource
- **AND** the policy exposes the required abilities for the surface using it

### Requirement: Policy-Driven Sensitive UI Visibility
The system SHALL use policy-backed authorization as the source of truth for sensitive action visibility across Blade, Livewire, and Filament.

#### Scenario: Sensitive actions are hidden when unauthorized
- **WHEN** a user opens a page or component containing a privileged action
- **THEN** the action is shown only if the relevant policy ability authorizes it
- **AND** the UI does not rely on a raw role-string conditional as the primary decision path

### Requirement: Superadmin Global Full Control
The system SHALL grant superadmin global full-control access across managed resources and privileged operational actions.

#### Scenario: Superadmin can manage protected resources globally
- **WHEN** a superadmin accesses any protected resource surface
- **THEN** they can create, view, update, delete, restore, and force-delete where those operations exist
- **AND** they can access export, impersonation, audit, and system-configuration actions without tenant or property scoping

### Requirement: Scoped Non-Superadmin Access
The system SHALL preserve tenant-scoped and property-scoped access boundaries for admin, manager, and tenant roles.

#### Scenario: Non-superadmin remains scoped
- **WHEN** an admin, manager, or tenant accesses a protected resource outside their scope
- **THEN** the action is denied according to the configured policy and middleware rules
- **AND** no cross-tenant or cross-property data is rendered

### Requirement: Superadmin-Only Deep Metadata
The system SHALL limit privileged operational metadata to superadmin-authorized surfaces.

#### Scenario: Deep metadata is hidden for non-superadmin users
- **WHEN** a non-superadmin opens a resource detail, widget, modal, or page
- **THEN** internal IDs, audit fields, privileged timestamps, relationship diagnostics, and workflow state are hidden unless explicitly authorized

#### Scenario: Deep metadata is visible for superadmin users
- **WHEN** a superadmin opens a resource detail, widget, modal, or page
- **THEN** privileged operational metadata is available where the surface supports it
