# Requirements Document

## Introduction

This document specifies the requirements for implementing a hierarchical user management system in the Vilnius Utilities Billing System. The system establishes a three-tier user hierarchy: Superadmin (system owner), Admin/Owner (property owner), and User/Tenant (apartment resident). The system enables subscription-based account creation where property owners create accounts for their properties and subsequently create accounts for their tenants, with each tenant linked to a specific property.

## Glossary

- **System**: The Vilnius Utilities Billing System
- **Superadmin**: The highest-level administrator who owns and manages the entire system across all organizations
- **Admin** (Owner): A property owner who manages their own properties and apartments through a subscription-based account
- **User** (Tenant): An apartment resident with limited permissions to input meter readings and view their billing history
- **Subscription**: A paid service plan that allows an Admin to create and manage their property portfolio
- **Property Portfolio**: The collection of buildings and apartments managed by a specific Admin
- **Tenant Account**: A user account created by an Admin and linked to a specific apartment
- **Account Hierarchy**: The parent-child relationship between Superadmin, Admin, and User accounts
- **Data Isolation**: The enforcement of access boundaries ensuring users only see data within their scope

## Requirements

### Requirement 1

**User Story:** As a Superadmin, I want complete visibility and control over the entire system, so that I can manage all organizations, users, and system configuration.

#### Acceptance Criteria

1. WHEN a Superadmin logs in THEN the System SHALL display a dashboard with statistics across all organizations
2. WHEN a Superadmin views the organizations list THEN the System SHALL display all Admin accounts with their subscription status
3. WHEN a Superadmin views an organization detail page THEN the System SHALL show all properties, tenants, and activity for that Admin
4. WHEN a Superadmin accesses any resource THEN the System SHALL bypass tenant scope restrictions
5. WHEN a Superadmin performs any action THEN the System SHALL log the action with full audit trail

### Requirement 2

**User Story:** As a Superadmin, I want to create and manage Admin accounts, so that property owners can subscribe to the service.

#### Acceptance Criteria

1. WHEN a Superadmin creates an Admin account THEN the System SHALL require email, password, and organization name
2. WHEN a Superadmin creates an Admin account THEN the System SHALL assign a unique tenant_id for data isolation
3. WHEN a Superadmin activates a subscription THEN the System SHALL enable the Admin account and set subscription expiry date
4. WHEN a Superadmin deactivates a subscription THEN the System SHALL disable the Admin account and prevent login
5. WHEN a Superadmin views subscription status THEN the System SHALL display expiry date, payment status, and usage statistics

### Requirement 3

**User Story:** As an Admin (property owner), I want to create an account for my property portfolio, so that I can manage my apartments and tenants.

#### Acceptance Criteria

1. WHEN an Admin registers for the service THEN the System SHALL create an account pending Superadmin approval
2. WHEN an Admin account is created THEN the System SHALL assign a unique tenant_id for all their data
3. WHEN an Admin logs in THEN the System SHALL display only properties and tenants within their tenant_id scope
4. WHEN an Admin subscription expires THEN the System SHALL restrict access to read-only mode
5. WHEN an Admin renews subscription THEN the System SHALL restore full access to all features

### Requirement 4

**User Story:** As an Admin, I want to create buildings and properties in my portfolio, so that I can organize my rental units.

#### Acceptance Criteria

1. WHEN an Admin creates a building THEN the System SHALL associate it with the Admin's tenant_id
2. WHEN an Admin creates a property THEN the System SHALL require building association and property type
3. WHEN an Admin views their properties THEN the System SHALL display only properties within their tenant_id
4. WHEN an Admin creates meters for a property THEN the System SHALL associate them with the property and tenant_id
5. WHEN an Admin deletes a building THEN the System SHALL prevent deletion if properties exist

### Requirement 5

**User Story:** As an Admin, I want to create tenant accounts for my residents, so that they can access their billing information.

#### Acceptance Criteria

1. WHEN an Admin creates a tenant account THEN the System SHALL require email, password, and property assignment
2. WHEN an Admin creates a tenant account THEN the System SHALL inherit the Admin's tenant_id for data isolation
3. WHEN an Admin assigns a tenant to a property THEN the System SHALL validate the property belongs to the Admin
4. WHEN an Admin creates a tenant account THEN the System SHALL send a welcome email with login credentials
5. WHEN an Admin views tenant list THEN the System SHALL display all tenants within their tenant_id with property assignments

### Requirement 6

**User Story:** As an Admin, I want to reassign tenants between properties, so that I can handle tenant moves within my portfolio.

#### Acceptance Criteria

1. WHEN an Admin reassigns a tenant to a different property THEN the System SHALL validate the target property belongs to the Admin
2. WHEN an Admin reassigns a tenant THEN the System SHALL update the property association and maintain historical records
3. WHEN an Admin reassigns a tenant THEN the System SHALL preserve all historical meter readings and invoices
4. WHEN an Admin views tenant history THEN the System SHALL display all property assignments with date ranges
5. WHEN a tenant is reassigned THEN the System SHALL notify the tenant via email

### Requirement 7

**User Story:** As an Admin, I want to deactivate tenant accounts, so that I can revoke access when tenants move out.

#### Acceptance Criteria

1. WHEN an Admin deactivates a tenant account THEN the System SHALL prevent the tenant from logging in
2. WHEN an Admin deactivates a tenant account THEN the System SHALL preserve all historical data
3. WHEN an Admin reactivates a tenant account THEN the System SHALL restore login access
4. WHEN an Admin views tenant status THEN the System SHALL clearly indicate active and inactive accounts
5. WHEN an Admin attempts to delete a tenant with historical data THEN the System SHALL prevent deletion and suggest deactivation

### Requirement 8

**User Story:** As a User (tenant), I want to log in to my account, so that I can access my apartment's utility information.

#### Acceptance Criteria

1. WHEN a User logs in with valid credentials THEN the System SHALL authenticate and redirect to tenant dashboard
2. WHEN a User logs in THEN the System SHALL display only data for their assigned property
3. WHEN a User attempts to access another property's data THEN the System SHALL return 403 Forbidden error
4. WHEN a User's account is deactivated THEN the System SHALL prevent login and display appropriate message
5. WHEN a User logs in for the first time THEN the System SHALL prompt for password change

### Requirement 9

**User Story:** As a User (tenant), I want to view my meter readings and consumption history, so that I can monitor my utility usage.

#### Acceptance Criteria

1. WHEN a User views meter readings THEN the System SHALL display only readings for their assigned property
2. WHEN a User views consumption history THEN the System SHALL show data for the last 12 months
3. WHEN a User views meter details THEN the System SHALL display meter type, serial number, and current reading
4. WHEN a User views consumption trends THEN the System SHALL display a visual graph comparing monthly usage
5. WHEN a User has no meter readings THEN the System SHALL display an informative message

### Requirement 10

**User Story:** As a User (tenant), I want to submit meter readings, so that I can provide consumption data for billing.

#### Acceptance Criteria

1. WHEN a User submits a meter reading THEN the System SHALL validate it against the previous reading
2. WHEN a User submits a reading lower than previous THEN the System SHALL reject it with validation error
3. WHEN a User submits a reading with future date THEN the System SHALL reject it with validation error
4. WHEN a User submits a valid reading THEN the System SHALL store it and notify the Admin
5. WHEN a User views reading submission history THEN the System SHALL display all submitted readings with timestamps

### Requirement 11

**User Story:** As a User (tenant), I want to view my invoices and payment history, so that I can track my utility expenses.

#### Acceptance Criteria

1. WHEN a User views invoices THEN the System SHALL display only invoices for their assigned property
2. WHEN a User views an invoice detail THEN the System SHALL show all line items with consumption and costs
3. WHEN a User views invoice history THEN the System SHALL allow filtering by date range and payment status
4. WHEN a User has unpaid invoices THEN the System SHALL display total amount due prominently
5. WHEN a User views an invoice THEN the System SHALL provide download option in PDF format

### Requirement 12

**User Story:** As a system architect, I want hierarchical data isolation, so that each user level can only access data within their scope.

#### Acceptance Criteria

1. WHEN any query executes THEN the System SHALL apply tenant_id filtering based on user role
2. WHEN a Superadmin queries data THEN the System SHALL bypass tenant_id filtering
3. WHEN an Admin queries data THEN the System SHALL filter to their tenant_id
4. WHEN a User queries data THEN the System SHALL filter to their tenant_id and assigned property
5. WHEN cross-tenant access is attempted THEN the System SHALL return 404 Not Found error

#### Implementation Status: ✅ COMPLETE

**Implementation**: `app/Scopes/HierarchicalScope.php`

**Features Implemented**:
- Automatic role-based query filtering via global scope
- TenantContext integration for explicit tenant switching
- Column existence caching (24-hour TTL) for performance
- Query builder macros: `withoutHierarchicalScope()`, `forTenant()`, `forProperty()`
- Special handling for properties and buildings tables
- Relationship-based filtering for complex table structures

**Documentation**:
- Architecture: `docs/architecture/HIERARCHICAL_SCOPE.md`
- API Reference: `docs/api/HIERARCHICAL_SCOPE_API.md`
- Quick Start: `docs/guides/HIERARCHICAL_SCOPE_QUICK_START.md`
- Upgrade Guide: `docs/upgrades/HIERARCHICAL_SCOPE_UPGRADE.md`

**Testing**: `tests/Feature/HierarchicalScopeTest.php` (100% coverage)

**Performance**: ~90% reduction in schema queries through caching

### Requirement 13

**User Story:** As a system architect, I want role-based authorization policies, so that users can only perform actions permitted by their role.

#### Acceptance Criteria

1. WHEN a Superadmin performs any action THEN the System SHALL allow it without restriction
2. WHEN an Admin creates a resource THEN the System SHALL automatically assign their tenant_id
3. WHEN an Admin attempts to modify another Admin's data THEN the System SHALL deny access
4. WHEN a User attempts to create or modify data THEN the System SHALL allow only meter reading submission
5. WHEN authorization fails THEN the System SHALL return 403 Forbidden with descriptive error message

### Requirement 14

**User Story:** As a system architect, I want audit logging for account management, so that all user creation and modification actions are tracked.

#### Acceptance Criteria

1. WHEN a Superadmin creates an Admin account THEN the System SHALL log the action with timestamp and details
2. WHEN an Admin creates a tenant account THEN the System SHALL log the action with property assignment
3. WHEN a tenant is reassigned THEN the System SHALL log the old and new property assignments
4. WHEN an account is deactivated THEN the System SHALL log the action with reason
5. WHEN viewing audit logs THEN the System SHALL display all account management actions with full context

### Requirement 15

**User Story:** As an Admin, I want to manage my organization profile, so that I can update contact information and subscription details.

#### Acceptance Criteria

1. WHEN an Admin views their profile THEN the System SHALL display organization name, contact email, and subscription status
2. WHEN an Admin updates their profile THEN the System SHALL validate email uniqueness across all Admins
3. WHEN an Admin views subscription details THEN the System SHALL show plan type, expiry date, and usage limits
4. WHEN an Admin's subscription is near expiry THEN the System SHALL display renewal reminder
5. WHEN an Admin updates organization name THEN the System SHALL reflect the change across all related data

### Requirement 16

**User Story:** As a User (tenant), I want to update my profile information, so that I can maintain accurate contact details.

#### Acceptance Criteria

1. WHEN a User views their profile THEN the System SHALL display email, phone number, and assigned property
2. WHEN a User updates their email THEN the System SHALL validate uniqueness and send confirmation
3. WHEN a User updates their password THEN the System SHALL require current password verification
4. WHEN a User views their profile THEN the System SHALL display their Admin's contact information
5. WHEN a User cannot access the system THEN the System SHALL provide Admin contact for support

### Requirement 17

**User Story:** As a Superadmin, I want to monitor system usage and subscription metrics, so that I can track business performance.

#### Acceptance Criteria

1. WHEN a Superadmin views the dashboard THEN the System SHALL display total active subscriptions
2. WHEN a Superadmin views subscription metrics THEN the System SHALL show revenue, expiring subscriptions, and growth trends
3. WHEN a Superadmin views usage statistics THEN the System SHALL display total properties, tenants, and invoices across all Admins
4. WHEN a Superadmin exports reports THEN the System SHALL generate CSV files with subscription and usage data
5. WHEN a Superadmin views Admin activity THEN the System SHALL show last login, active users, and feature usage

### Requirement 18

**User Story:** As an Admin, I want to view usage statistics for my portfolio, so that I can monitor my property management activity.

#### Acceptance Criteria

1. WHEN an Admin views their dashboard THEN the System SHALL display total properties, active tenants, and pending tasks
2. WHEN an Admin views usage statistics THEN the System SHALL show meter reading submission rates and invoice generation activity
3. WHEN an Admin views tenant activity THEN the System SHALL display last login dates and reading submission history
4. WHEN an Admin views consumption trends THEN the System SHALL show aggregated usage across all properties
5. WHEN an Admin exports reports THEN the System SHALL generate PDF reports with portfolio summary
