# Requirements Document

## Introduction

This document specifies the requirements for integrating Filament PHP framework into the Vilnius Utilities Billing System. Filament will provide a modern, feature-rich administration panel to replace the existing Blade-based admin interface, offering improved user experience for managing utility billing data including meter readings, properties, tenants, invoices, and tariffs.

## Glossary

- **Filament**: A collection of Laravel packages that provides a full-stack admin panel framework
- **Resource**: A Filament component that represents a database model with CRUD operations
- **Panel**: The main Filament admin interface container
- **System**: The Vilnius Utilities Billing System
- **Admin User**: A user with the admin role who manages system-wide configurations
- **Manager User**: A user with the manager role who handles day-to-day operations
- **Tenant User**: A user with the tenant role who views their own data
- **Meter Reading**: A recorded utility meter value at a specific point in time
- **Property**: An individual apartment or house unit
- **Building**: A multi-unit structure containing multiple properties
- **Invoice**: A generated bill for utility consumption
- **Tariff**: A pricing configuration for utility services
- **Provider**: A utility service company (Ignitis, Vilniaus Vandenys, Vilniaus Energija)

## Requirements

### Requirement 1

**User Story:** As a system administrator, I want to install and configure Filament in the existing Laravel application, so that I can begin building the admin panel interface.

#### Acceptance Criteria

1. WHEN the Filament package is installed THEN the System SHALL include all required Filament dependencies in the composer.json file
2. WHEN Filament assets are published THEN the System SHALL create necessary configuration files and assets in the appropriate directories
3. WHEN the admin panel is accessed THEN the System SHALL display the Filament login interface at the configured route
4. WHEN an admin user logs in THEN the System SHALL authenticate using the existing User model and role system
5. WHEN the panel is configured THEN the System SHALL use the existing authentication guard and user provider

### Requirement 2

**User Story:** As a manager, I want to manage meter readings through the Filament admin panel, so that I can efficiently enter and track utility consumption data.

#### Acceptance Criteria

1. WHEN a manager accesses the meter readings resource THEN the System SHALL display a table listing all meter readings with tenant scope applied
2. WHEN viewing the meter readings table THEN the System SHALL show columns for property identifier, meter type, reading date, reading value, and consumption
3. WHEN a manager creates a new meter reading THEN the System SHALL provide a form with fields for property selection, meter selection, reading date, and reading value
4. WHEN a manager submits a meter reading form THEN the System SHALL validate the data using existing StoreMeterReadingRequest validation rules
5. WHEN a meter reading is saved THEN the System SHALL apply monotonicity validation ensuring readings do not decrease
6. WHEN a manager edits an existing meter reading THEN the System SHALL use UpdateMeterReadingRequest validation rules
7. WHEN meter readings are filtered THEN the System SHALL respect tenant scope isolation

### Requirement 3

**User Story:** As a manager, I want to manage properties through the Filament admin panel, so that I can maintain accurate property information.

#### Acceptance Criteria

1. WHEN a manager accesses the properties resource THEN the System SHALL display a table listing all properties with tenant scope applied
2. WHEN viewing the properties table THEN the System SHALL show columns for address, property type, building association, and tenant assignment
3. WHEN a manager creates a new property THEN the System SHALL provide a form with fields for address, property type, area, and building selection
4. WHEN a manager submits a property form THEN the System SHALL validate the data using existing StorePropertyRequest validation rules
5. WHEN a property is saved THEN the System SHALL automatically apply the current tenant_id from session context

### Requirement 4

**User Story:** As a manager, I want to manage invoices through the Filament admin panel, so that I can generate and track utility bills.

#### Acceptance Criteria

1. WHEN a manager accesses the invoices resource THEN the System SHALL display a table listing all invoices with tenant scope applied
2. WHEN viewing the invoices table THEN the System SHALL show columns for invoice number, property, billing period, total amount, and status
3. WHEN a manager views an invoice THEN the System SHALL display all invoice items with snapshotted pricing details
4. WHEN a manager creates a draft invoice THEN the System SHALL provide a form with fields for property selection and billing period
5. WHEN a manager finalizes an invoice THEN the System SHALL validate using FinalizeInvoiceRequest and prevent further modifications
6. WHEN invoices are filtered by status THEN the System SHALL support filtering by draft, finalized, and paid statuses

### Requirement 5

**User Story:** As an admin, I want to manage tariffs through the Filament admin panel, so that I can configure utility pricing structures.

#### Acceptance Criteria

1. WHEN an admin accesses the tariffs resource THEN the System SHALL display a table listing all tariffs
2. WHEN viewing the tariffs table THEN the System SHALL show columns for provider, service type, tariff type, effective date range, and status
3. WHEN an admin creates a new tariff THEN the System SHALL provide a form with fields for provider, service type, tariff type, and pricing configuration
4. WHEN a tariff form includes time-of-use pricing THEN the System SHALL provide fields for day rate, night rate, and time range definitions
5. WHEN an admin submits a tariff form THEN the System SHALL validate the data using existing StoreTariffRequest validation rules
6. WHEN a tariff configuration is saved THEN the System SHALL store the pricing structure as JSON in the tariff_config column

### Requirement 6

**User Story:** As an admin, I want to manage users through the Filament admin panel, so that I can control system access and permissions.

#### Acceptance Criteria

1. WHEN an admin accesses the users resource THEN the System SHALL display a table listing all users
2. WHEN viewing the users table THEN the System SHALL show columns for name, email, role, and tenant association
3. WHEN an admin creates a new user THEN the System SHALL provide a form with fields for name, email, password, role, and tenant assignment
4. WHEN an admin submits a user form THEN the System SHALL validate the data using existing StoreUserRequest validation rules
5. WHEN a user role is set to manager or tenant THEN the System SHALL require tenant_id assignment
6. WHEN a user role is set to admin THEN the System SHALL allow null tenant_id

### Requirement 7

**User Story:** As an admin, I want to manage buildings through the Filament admin panel, so that I can maintain building information for gyvatukas calculations.

#### Acceptance Criteria

1. WHEN an admin accesses the buildings resource THEN the System SHALL display a table listing all buildings with tenant scope applied
2. WHEN viewing the buildings table THEN the System SHALL show columns for name, address, total area, and property count
3. WHEN an admin creates a new building THEN the System SHALL provide a form with fields for name, address, and total area
4. WHEN an admin submits a building form THEN the System SHALL validate the data using existing StoreBuildingRequest validation rules
5. WHEN viewing a building detail THEN the System SHALL display associated properties as a relationship

### Requirement 8

**User Story:** As an admin, I want to manage providers through the Filament admin panel, so that I can maintain utility service provider information.

#### Acceptance Criteria

1. WHEN an admin accesses the providers resource THEN the System SHALL display a table listing all providers
2. WHEN viewing the providers table THEN the System SHALL show columns for name, service types, and contact information
3. WHEN an admin creates a new provider THEN the System SHALL provide a form with fields for name, service types, and contact details
4. WHEN viewing a provider detail THEN the System SHALL display associated tariffs as a relationship

### Requirement 9

**User Story:** As a user, I want the Filament panel to respect role-based access control, so that I only see and can modify data appropriate to my role.

#### Acceptance Criteria

1. WHEN a tenant user logs into the panel THEN the System SHALL restrict access to only tenant-specific resources
2. WHEN a manager user logs into the panel THEN the System SHALL provide access to operational resources within their tenant scope
3. WHEN an admin user logs into the panel THEN the System SHALL provide access to all resources including system configuration
4. WHEN a user attempts to access a restricted resource THEN the System SHALL deny access and display an appropriate error message
5. WHEN resources are displayed THEN the System SHALL apply existing policy classes for authorization

### Requirement 10

**User Story:** As a developer, I want to remove obsolete frontend configuration, so that the codebase remains clean and maintainable.

#### Acceptance Criteria

1. WHEN Filament is fully integrated THEN the System SHALL remove unused Vue.js configuration files
2. WHEN frontend assets are reviewed THEN the System SHALL remove unnecessary Vite configuration for SPA builds
3. WHEN package.json is cleaned THEN the System SHALL remove frontend build scripts that are no longer needed
4. WHEN Alpine.js is still required THEN the System SHALL retain Alpine.js CDN references for Blade components
5. WHEN cleanup is complete THEN the System SHALL maintain only necessary frontend dependencies for Filament and existing Blade views
