# Delta for CRUD Regression Coverage

## ADDED Requirements

### Requirement: CRUD Coverage For Existing Surfaces

The system SHALL provide regression coverage for every CRUD surface that exists
after the legacy import and reference-data slices are implemented.

#### Scenario: Existing CRUD surface has regression coverage

- GIVEN a CRUD surface exists in Filament, Blade controllers, or Livewire flows
- WHEN the regression suite is inspected
- THEN that surface has automated coverage for its supported operations

### Requirement: Authorization Coverage For CRUD Surfaces

The system SHALL verify authorization and isolation rules for CRUD surfaces in
addition to happy-path create, read, update, and delete behavior.

#### Scenario: Unauthorized CRUD access is denied

- GIVEN a user lacks permission for a CRUD surface or record
- WHEN that user attempts the protected operation
- THEN the system denies access
- AND the regression suite contains a test for that denial
