## ADDED Requirements

### Requirement: Unified Backoffice Layout for Operational Roles
The system SHALL render `superadmin`, `admin`, and `manager` browser pages using one shared custom backoffice layout implemented with Tailwind CSS and Livewire-compatible Blade structure.

#### Scenario: Superadmin page uses shared backoffice layout
- **GIVEN** an authenticated superadmin user
- **WHEN** the user opens a superadmin web page (for example `/superadmin/dashboard`)
- **THEN** the response SHALL render the shared backoffice layout
- **AND** the page SHALL not require Filament panel rendering

#### Scenario: Admin page uses shared backoffice layout
- **GIVEN** an authenticated admin user
- **WHEN** the user opens an admin web page (for example `/admin/dashboard`)
- **THEN** the response SHALL render the same shared backoffice layout used by superadmin and manager

#### Scenario: Manager page uses shared backoffice layout
- **GIVEN** an authenticated manager user
- **WHEN** the user opens a manager web page (for example `/manager/dashboard`)
- **THEN** the response SHALL render the same shared backoffice layout used by superadmin and admin

### Requirement: Tenant Layout Remains Dedicated
The system SHALL keep tenant pages on a dedicated tenant-specific layout that is distinct from the shared backoffice layout.

#### Scenario: Tenant dashboard uses tenant layout
- **GIVEN** an authenticated tenant user
- **WHEN** the user opens `/tenant/dashboard`
- **THEN** the response SHALL render the tenant layout
- **AND** it SHALL not render the shared backoffice layout
