## ADDED Requirements

### Requirement: Core Page Modules SHALL Be Livewire-Driven
The system SHALL render and orchestrate the `profile`, `dashboard`, and `settings` modules through Livewire page components as the canonical execution path for those modules.

#### Scenario: Profile routes use Livewire module execution
- **GIVEN** an authenticated user with role `superadmin`, `admin`, `manager`, or `tenant`
- **WHEN** the user requests the role-specific profile route
- **THEN** the response SHALL be produced by the profile Livewire module
- **AND** controller-based profile page rendering SHALL not be used for that route

#### Scenario: Dashboard routes use Livewire module execution
- **GIVEN** an authenticated user with role `superadmin`, `admin`, `manager`, or `tenant`
- **WHEN** the user requests the role-specific dashboard route
- **THEN** the response SHALL be produced by the dashboard Livewire module
- **AND** controller-based dashboard page rendering SHALL not be used for that route

#### Scenario: Settings route uses Livewire module execution
- **GIVEN** an authenticated `admin` user
- **WHEN** the user requests `/admin/settings`
- **THEN** the response SHALL be produced by the settings Livewire module
- **AND** controller-based settings page rendering SHALL not be used for that route

### Requirement: Migrated Modules SHALL Preserve Route Contracts
The system SHALL preserve current route URLs, route names, middleware chains, and role access outcomes for migrated modules.

#### Scenario: Existing route names remain valid
- **GIVEN** existing navigation and tests reference named routes for profile, dashboard, and settings
- **WHEN** module migration is completed
- **THEN** those named routes SHALL continue to resolve to the same URLs
- **AND** middleware-based access behavior SHALL remain unchanged

### Requirement: Migrated Modules SHALL Not Render Duplicate Full-Page Content
The system SHALL render exactly one full-page module view for each migrated route response.

#### Scenario: Admin dashboard renders a single module shell
- **GIVEN** an authenticated `admin` user
- **WHEN** the user requests `/admin/dashboard`
- **THEN** the response SHALL contain one dashboard module shell
- **AND** it SHALL not contain duplicated repeated full-page dashboard blocks

#### Scenario: Admin profile renders a single module shell
- **GIVEN** an authenticated `admin` user
- **WHEN** the user requests `/admin/profile`
- **THEN** the response SHALL contain one profile module shell
- **AND** it SHALL not contain duplicated repeated full-page profile blocks

### Requirement: Migrated Modules SHALL Keep Role-Specific Behavior
The system SHALL preserve role-specific data, actions, and authorization behavior after module migration.

#### Scenario: Tenant profile restrictions are preserved
- **GIVEN** an authenticated `tenant` user
- **WHEN** the tenant interacts with the profile module
- **THEN** tenant-specific read/write restrictions SHALL still apply
- **AND** tenant-only UI behavior SHALL remain intact

#### Scenario: Admin settings authorization is preserved
- **GIVEN** an authenticated `admin` user lacking settings permissions
- **WHEN** the user attempts settings actions
- **THEN** authorization SHALL deny the action according to existing policy/gate behavior
