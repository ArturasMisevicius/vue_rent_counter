# Delta for Tenant Meter Readings

## ADDED Requirements

### Requirement: Property-Scoped Reading Submission

The system SHALL let a tenant submit a new meter reading only for meters
assigned to the tenant's property.

#### Scenario: Tenant submits a reading for an assigned meter

- GIVEN an authenticated tenant with an assigned meter
- WHEN the tenant submits a valid reading value and reading date for that meter
- THEN the system accepts the submission
- AND the submission is recorded through the shared domain validation and write
  path

#### Scenario: Single-meter tenant sees a locked meter selection

- GIVEN an authenticated tenant with exactly one assigned meter
- WHEN the tenant opens the reading-submission page
- THEN the meter is preselected
- AND the tenant cannot switch the submission to a different meter

#### Scenario: Tenant enters an invalid lower reading

- GIVEN an authenticated tenant whose meter has a previous reading
- WHEN the tenant submits a value that is lower than the previous reading
- THEN the system rejects the submission with a validation error

#### Scenario: Tenant enters a future reading date

- GIVEN an authenticated tenant on the reading-submission page
- WHEN the tenant submits a reading date in the future
- THEN the system rejects the submission with a validation error

### Requirement: Reading Submission Feedback

The system SHALL show immediate submission guidance and a full confirmation
state after a tenant successfully submits a reading.

#### Scenario: Tenant reviews the pending submission

- GIVEN an authenticated tenant entering a reading for a meter with a previous
  reading
- WHEN the tenant selects a meter and types a candidate value
- THEN the page shows the previous reading context
- AND the page shows the computed consumption preview before submission

#### Scenario: Tenant completes a successful submission

- GIVEN an authenticated tenant who submits a valid reading
- WHEN the submission succeeds
- THEN the page shows a success confirmation state
- AND the state includes the submitted value, unit, and meter identifier
