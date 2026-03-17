# Delta for Tenant Access Isolation

## ADDED Requirements

### Requirement: Tenant Assignment Scoping

The system SHALL scope every tenant portal read and write path to the signed-in
tenant's assigned property, meters, readings, and invoices, with no
cross-property fallback.

#### Scenario: Tenant cannot view another tenant's property page

- GIVEN two authenticated tenants with different assigned properties
- WHEN the first tenant requests the second tenant's property page
- THEN the system denies access

#### Scenario: Tenant cannot download another tenant's invoice

- GIVEN two authenticated tenants with different invoices
- WHEN the first tenant requests the second tenant's invoice download route
- THEN the system denies access

#### Scenario: Tenant cannot submit a reading for a foreign meter

- GIVEN an authenticated tenant and a meter that is not assigned to the
  tenant's property
- WHEN the tenant attempts to submit a reading for that meter
- THEN the system denies the request
