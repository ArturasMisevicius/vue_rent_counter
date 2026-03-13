## ADDED Requirements

### Requirement: Filament Browser Interface Shall Not Be Exposed
The system SHALL not expose Filament panel UI routes, Filament authentication pages, or Filament resource pages in browser-accessible web navigation.

#### Scenario: Filament panel endpoints are not available
- **GIVEN** the application route table is generated
- **WHEN** browser-facing web routes are inspected
- **THEN** Filament panel endpoints SHALL not be present as active browser UI routes

#### Scenario: Direct Filament URL attempts do not render Filament UI
- **GIVEN** a user requests a previously known Filament panel/auth URL
- **WHEN** the request is processed
- **THEN** the response SHALL not render Filament interface pages
- **AND** the request SHALL be redirected to equivalent custom routes or return not found

### Requirement: Custom Browser UI Shall Use Blade, Tailwind, and Livewire
The system SHALL render role-facing browser interfaces through custom Blade templates and Livewire components styled with Tailwind CSS.

#### Scenario: Backoffice pages render without Filament view dependencies
- **GIVEN** a backoffice page is requested
- **WHEN** the response is generated
- **THEN** the rendered interface SHALL come from custom templates/components
- **AND** it SHALL not depend on Filament view wrappers for core page rendering
