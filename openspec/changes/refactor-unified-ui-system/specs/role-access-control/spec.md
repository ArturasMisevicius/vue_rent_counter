## ADDED Requirements
### Requirement: Permission-Aware Navigation
The system SHALL centralize navigation composition and only render links that the current role is authorized to access and that map to registered routes.

#### Scenario: Unauthorized links are hidden
- **WHEN** a manager views the navigation
- **THEN** links requiring admin or superadmin permissions are not rendered

#### Scenario: Route-safe links are rendered
- **WHEN** a role dashboard renders navigation
- **THEN** only routes that exist and pass authorization checks are included

### Requirement: Direct Route Access Enforcement
The system SHALL enforce role and scope authorization at the route and policy layers so direct URL access cannot bypass permissions.

#### Scenario: Direct access is denied
- **WHEN** a tenant attempts to access an admin route directly
- **THEN** the request is denied according to the configured authorization rules

### Requirement: Tenant and Property Isolation
The system SHALL scope data access by tenant_id and property_id for all non-superadmin roles, including dashboard summaries and navigation-driven pages.

#### Scenario: Tenant cannot access another tenant's data
- **WHEN** a tenant attempts to access a record outside their assigned property
- **THEN** access is denied and no cross-tenant data is returned
