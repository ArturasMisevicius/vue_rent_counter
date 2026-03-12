## ADDED Requirements
### Requirement: Role-Based Dashboard Routing
The system SHALL route authenticated users to the correct role dashboard using `RoleDashboardResolver` and enforce direct-route authorization via role middleware.

#### Scenario: Superadmin redirected to superadmin dashboard
- **WHEN** a superadmin visits `/dashboard`
- **THEN** they are redirected to the superadmin dashboard route

#### Scenario: Tenant blocked from admin dashboard
- **WHEN** a tenant visits an admin dashboard route
- **THEN** the request is denied with a forbidden response

### Requirement: Role-Specific Layouts and Navigation
The system SHALL render a shared backoffice layout for superadmin/admin/manager and a separate tenant layout with role-scoped navigation labels sourced from translations.

#### Scenario: Manager sees backoffice navigation
- **WHEN** a manager opens their dashboard
- **THEN** they see backoffice navigation and do not see tenant navigation links

#### Scenario: Tenant sees tenant navigation only
- **WHEN** a tenant opens their dashboard
- **THEN** they see tenant navigation only and do not see backoffice links

### Requirement: Role-Scoped Dashboard Data
Dashboard data loading MUST enforce tenant and property scopes for non-superadmin roles.

#### Scenario: Manager data scoped to tenant
- **WHEN** a manager loads the dashboard
- **THEN** statistics and lists include only records for their tenant_id

#### Scenario: Tenant data scoped to property
- **WHEN** a tenant loads the dashboard
- **THEN** data is limited to their assigned property and invoices
