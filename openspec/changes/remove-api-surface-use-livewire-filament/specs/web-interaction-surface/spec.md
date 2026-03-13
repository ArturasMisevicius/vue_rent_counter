## ADDED Requirements
### Requirement: Web-Only Interaction Surface
The system SHALL provide all user-facing interactive workflows through web routes powered by Livewire and/or Filament components, without exposing application API routes.

#### Scenario: API route bootstrap disabled
- **WHEN** the application boots routing configuration
- **THEN** no API route file is registered
- **AND** `/api/*` requests resolve as not found

### Requirement: Manager Meter Reading Uses Livewire Flow
The manager meter-reading creation workflow SHALL run through a Livewire component that performs server-side reads/writes directly, without client-side API fetch calls.

#### Scenario: Livewire meter reading submission
- **WHEN** a manager submits a valid meter reading in the create page
- **THEN** the Livewire component validates input and stores the reading
- **AND** the manager is redirected to the meter readings index with a success message

#### Scenario: Monotonic validation without API
- **WHEN** a submitted reading value is lower than the previous reading for the meter/zone
- **THEN** validation fails with a monotonicity error
- **AND** no meter reading is persisted
