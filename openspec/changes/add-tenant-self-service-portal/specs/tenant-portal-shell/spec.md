# Delta for Tenant Portal Shell

## ADDED Requirements

### Requirement: Fixed Tenant Bottom Navigation

The system SHALL provide a tenant-only portal shell with a fixed bottom
navigation that contains exactly `Home`, `Readings`, `Invoices`, and `Profile`.

#### Scenario: Tenant sees the four portal destinations

- GIVEN an authenticated active tenant
- WHEN the tenant opens any tenant portal page
- THEN the response renders the four bottom-navigation items `Home`,
  `Readings`, `Invoices`, and `Profile`
- AND the response does not render the admin sidebar or admin navigation

#### Scenario: My Property remains a secondary route

- GIVEN an authenticated active tenant
- WHEN the tenant navigates to the property details page
- THEN the property page is reachable inside the tenant portal
- AND `My Property` is not rendered as a fifth bottom-navigation item

### Requirement: Tenant Portal Route Contract

The system SHALL expose tenant portal routes for home, reading submission,
invoice history, property details, and profile management inside the
authenticated locale-aware web middleware stack.

#### Scenario: Tenant route availability

- GIVEN an authenticated active tenant
- WHEN the tenant requests `tenant.home`, `tenant.readings.create`,
  `tenant.invoices.index`, `tenant.property.show`, or `tenant.profile.edit`
- THEN the system returns a successful tenant page response for each route
