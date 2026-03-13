## ADDED Requirements

### Requirement: Role-Based Route Access Matrix Enforcement
The system SHALL enforce a deterministic role-based access matrix for every web route, with explicit outcomes for guest, superadmin, admin, manager, and tenant users.

#### Scenario: Guest access to protected route
- **GIVEN** a guest user
- **WHEN** the user requests any protected route
- **THEN** the response SHALL redirect to login

#### Scenario: Authorized role access succeeds
- **GIVEN** an authenticated user with a role authorized for a route group
- **WHEN** the user requests a route in that group
- **THEN** the response SHALL be allowed and return success

#### Scenario: Unauthorized role access is blocked
- **GIVEN** an authenticated user whose role is not authorized for a route group
- **WHEN** the user requests a route in that group
- **THEN** the response SHALL be forbidden

### Requirement: Route Access Shall Be Regression-Tested by Role
The system SHALL include automated feature tests validating route access behavior per role for all primary route groups (`superadmin`, `admin`, `manager`, `tenant`, and shared routes).

#### Scenario: Matrix test for superadmin route group
- **GIVEN** route access tests are executed
- **WHEN** superadmin routes are evaluated across all roles
- **THEN** only authorized roles SHALL succeed
- **AND** unauthorized roles SHALL be forbidden or redirected per policy

#### Scenario: Matrix test for tenant route group
- **GIVEN** route access tests are executed
- **WHEN** tenant routes are evaluated across all roles
- **THEN** only tenant-authorized users SHALL succeed
- **AND** all other roles SHALL be denied according to middleware behavior
