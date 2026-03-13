## ADDED Requirements
### Requirement: Canonical Filament and Livewire Surfaces
The system SHALL implement interactive admin, manager, superadmin, and tenant workflows using Filament and/or Livewire as the canonical UI delivery layers.

#### Scenario: Operational workflow uses Filament/Livewire
- **WHEN** a user creates or updates a billing record
- **THEN** the interaction is handled via Filament or Livewire without client-side API calls

### Requirement: No API-Driven UI Dependencies
The system SHALL not expose or depend on JSON API endpoints for browser-driven UI flows.

#### Scenario: API endpoints are not accessible
- **WHEN** a browser client requests a previously exposed `/api/*` endpoint
- **THEN** the request is rejected or not routed

### Requirement: Removal of API Controller Responses
The system SHALL remove or migrate JSON-returning controller methods that exist only to support browser UI flows.

#### Scenario: Controller responses are HTML/Livewire/Filament
- **WHEN** a UI controller action executes
- **THEN** it returns a Livewire-rendered view or Filament response instead of JSON payloads
