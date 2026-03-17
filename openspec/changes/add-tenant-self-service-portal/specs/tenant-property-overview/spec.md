# Delta for Tenant Property Overview

## ADDED Requirements

### Requirement: Read-Only Assigned Property View

The system SHALL provide tenants with a read-only property page that shows only
their assigned property details and assigned meters.

#### Scenario: Tenant views assigned property details

- GIVEN an authenticated tenant with an assigned property and one or more
  assigned meters
- WHEN the tenant opens the property page
- THEN the page shows `My Property`
- AND the page shows the assigned property details
- AND the page shows the tenant's assigned meters
- AND the page does not show property-edit or meter-edit controls

#### Scenario: Meter has no prior reading

- GIVEN an authenticated tenant with an assigned meter that has no recorded
  reading
- WHEN the tenant opens the property page
- THEN the page shows `Last reading: None recorded yet`
- AND the empty state links to the tenant reading-submission route
