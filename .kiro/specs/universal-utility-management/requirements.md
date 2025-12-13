# Requirements Document

## Introduction

The Universal Utility Management System is a comprehensive refactoring of the existing Lithuanian-specific "gyvatukas" system into a flexible, dynamic utility management solution. This system will support multiple utility types (water, electricity, heating, gas, internet, etc.) with configurable pricing models (fixed monthly rates, consumption-based pricing, tiered rates) for different property types (residential flats, commercial properties, mixed-use buildings). The system provides role-based interfaces for SuperAdmin, Admin, Manager, and Tenant users with complete audit trails and multi-tenant data isolation.

## Glossary

- **System**: Universal Utility Management System
- **SuperAdmin**: System-wide administrator with access to all tenants and global configurations
- **Admin**: Tenant-level administrator with full access within their organization
- **Manager**: Property manager with operational access to buildings and utilities
- **Tenant**: End user (renter/owner) who receives utility bills
- **Property**: Individual unit (apartment, office, shop) within a building
- **Building**: Physical structure containing multiple properties
- **Utility_Service**: Configurable utility type (water, electricity, heating, etc.)
- **Service_Configuration**: Pricing and calculation rules for a utility service
- **Meter**: Physical or virtual measurement device for utility consumption
- **Reading**: Recorded consumption value from a meter
- **Invoice**: Generated bill for utility services
- **Pricing_Model**: Calculation method (fixed, consumption-based, tiered, etc.)
- **Rate_Schedule**: Time-based pricing rules (peak/off-peak, seasonal rates)
- **Distribution_Method**: How shared costs are allocated (equal, by area, by consumption)

## Requirements

### Requirement 1

**User Story:** As a SuperAdmin, I want to create and manage utility service types globally, so that all tenants can use standardized utility configurations while allowing customization.

#### Acceptance Criteria

1. WHEN a SuperAdmin creates a new Utility_Service THEN the System SHALL store the service with configurable attributes (name, unit of measurement, default pricing model, calculation formula)
2. WHEN a SuperAdmin defines a service template THEN the System SHALL allow specification of required fields, optional fields, and validation rules
3. WHEN a SuperAdmin publishes a service type THEN the System SHALL make it available to all tenant organizations for customization
4. WHEN a SuperAdmin updates a global service template THEN the System SHALL notify affected tenant admins of available updates without forcing changes
5. WHERE a service requires complex calculations THEN the System SHALL support custom formula definitions using mathematical expressions

### Requirement 2

**User Story:** As an Admin, I want to configure utility services for my organization with flexible pricing models, so that I can handle different property types and billing scenarios.

#### Acceptance Criteria

1. WHEN an Admin configures a utility service THEN the System SHALL allow selection from fixed monthly rate, consumption-based pricing, tiered rates, or hybrid models
2. WHEN setting up consumption-based pricing THEN the System SHALL support multiple rate schedules (peak/off-peak hours, seasonal rates, weekend rates)
3. WHEN configuring tiered pricing THEN the System SHALL allow definition of consumption brackets with different rates per bracket
4. WHEN creating a hybrid model THEN the System SHALL support combining fixed base fees with consumption charges
5. WHERE different property types exist THEN the System SHALL allow separate pricing configurations for residential, commercial, and mixed-use properties

### Requirement 3

**User Story:** As a Manager, I want to assign utility services to properties with individual configurations, so that each property can have appropriate service levels and pricing.

#### Acceptance Criteria

1. WHEN a Manager assigns a service to a Property THEN the System SHALL allow override of default pricing while maintaining audit trail
2. WHEN configuring property-specific services THEN the System SHALL support individual rate adjustments, service start/end dates, and special conditions
3. WHEN a property has multiple service configurations THEN the System SHALL validate that configurations don't conflict or overlap inappropriately
4. WHEN removing a service from a property THEN the System SHALL handle final billing and maintain historical data
5. WHERE shared services exist (building-wide heating) THEN the System SHALL support cost distribution among multiple properties

### Requirement 4

**User Story:** As a Manager, I want to record meter readings with flexible input methods, so that I can efficiently collect consumption data for various utility types.

#### Acceptance Criteria

1. WHEN recording a meter reading THEN the System SHALL support manual entry, photo upload with OCR, CSV import, and API integration
2. WHEN a reading is submitted THEN the System SHALL validate against previous readings, reasonable consumption ranges, and meter specifications
3. WHEN multiple meters serve one property THEN the System SHALL support composite readings and automatic aggregation
4. WHEN a meter reading is corrected THEN the System SHALL maintain full audit trail and recalculate affected invoices
5. WHERE estimated readings are used THEN the System SHALL clearly mark estimates and support true-up calculations when actual readings are available

### Requirement 5

**User Story:** As a Manager, I want the system to automatically calculate utility bills using configured pricing models, so that invoices are accurate and consistent.

#### Acceptance Criteria

1. WHEN generating an invoice THEN the System SHALL apply the correct pricing model based on property type, service configuration, and billing period
2. WHEN calculating consumption charges THEN the System SHALL handle tiered rates, time-of-use pricing, and seasonal adjustments automatically
3. WHEN processing shared services THEN the System SHALL distribute costs according to configured distribution methods (equal, by area, by consumption ratio)
4. WHEN applying rate changes THEN the System SHALL use effective dates to ensure correct historical pricing
5. WHERE complex calculations are required THEN the System SHALL support custom formulas with variables for consumption, property attributes, and external factors

### Requirement 6

**User Story:** As a Manager, I want to configure cost distribution methods for shared utilities, so that building-wide services are fairly allocated among tenants.

#### Acceptance Criteria

1. WHEN configuring shared service distribution THEN the System SHALL support equal division, area-based allocation, consumption-based allocation, and custom formulas
2. WHEN using area-based distribution THEN the System SHALL allow different area types (total area, heated area, commercial area) as basis for calculation
3. WHEN applying consumption-based distribution THEN the System SHALL support historical consumption averages or current period ratios
4. WHEN creating custom distribution formulas THEN the System SHALL provide variables for property attributes, tenant count, and service-specific factors
5. WHERE distribution methods change THEN the System SHALL apply changes prospectively while maintaining historical calculation methods for past invoices

### Requirement 7

**User Story:** As an Admin, I want to set up automated billing cycles with configurable schedules, so that invoices are generated consistently and on time.

#### Acceptance Criteria

1. WHEN configuring billing cycles THEN the System SHALL support monthly, quarterly, and custom period schedules
2. WHEN a billing cycle executes THEN the System SHALL automatically collect meter readings, calculate charges, and generate invoices
3. WHEN automated billing encounters errors THEN the System SHALL log issues, notify administrators, and continue processing other properties
4. WHEN invoices are generated THEN the System SHALL snapshot all pricing data to ensure invoice immutability
5. WHERE manual intervention is required THEN the System SHALL support partial automation with approval workflows

### Requirement 8

**User Story:** As a Tenant, I want to view my utility consumption and bills through a user-friendly interface, so that I can understand my usage patterns and verify charges.

#### Acceptance Criteria

1. WHEN a Tenant accesses their dashboard THEN the System SHALL display current consumption, recent bills, and usage trends
2. WHEN viewing bill details THEN the System SHALL show itemized charges, rate information, and consumption comparisons
3. WHEN examining usage history THEN the System SHALL provide charts and graphs showing consumption patterns over time
4. WHEN comparing periods THEN the System SHALL highlight significant changes and provide explanations for variations
5. WHERE multiple properties are associated with a tenant THEN the System SHALL allow switching between properties and consolidated views

### Requirement 9

**User Story:** As a SuperAdmin, I want comprehensive audit trails and reporting capabilities, so that I can monitor system usage and ensure compliance.

#### Acceptance Criteria

1. WHEN any data modification occurs THEN the System SHALL record the change with user identification, timestamp, old values, and new values
2. WHEN generating audit reports THEN the System SHALL provide filtering by user, date range, entity type, and action type
3. WHEN tracking billing accuracy THEN the System SHALL maintain calculation logs showing formulas used, inputs, and results
4. WHEN monitoring system performance THEN the System SHALL log calculation times, error rates, and usage statistics
5. WHERE compliance reporting is required THEN the System SHALL generate standardized reports for regulatory or audit purposes

### Requirement 10

**User Story:** As a SuperAdmin, I want to manage multi-tenant data isolation with hierarchical access control, so that organizations remain completely separated while allowing system-wide administration.

#### Acceptance Criteria

1. WHEN a user authenticates THEN the System SHALL establish tenant context and enforce data isolation at the database level
2. WHEN a SuperAdmin accesses the system THEN the System SHALL allow switching between tenant contexts while maintaining audit trails
3. WHEN tenant-level operations occur THEN the System SHALL prevent cross-tenant data access through automatic query scoping
4. WHEN creating new tenant organizations THEN the System SHALL initialize isolated data structures and default configurations
5. WHERE global configurations exist THEN the System SHALL allow SuperAdmin management while respecting tenant customizations

### Requirement 11

**User Story:** As an Admin, I want to configure service-specific validation rules and business logic, so that the system enforces appropriate constraints for different utility types.

#### Acceptance Criteria

1. WHEN configuring a utility service THEN the System SHALL allow definition of consumption limits, rate change restrictions, and seasonal adjustments
2. WHEN setting validation rules THEN the System SHALL support minimum/maximum consumption thresholds, reading frequency requirements, and data quality checks
3. WHEN defining business logic THEN the System SHALL allow conditional pricing, automatic adjustments, and exception handling
4. WHEN rules are violated THEN the System SHALL generate alerts, prevent processing, or apply corrective actions based on configuration
5. WHERE complex validation is required THEN the System SHALL support custom validation scripts and external API integrations

### Requirement 12

**User Story:** As a Manager, I want to handle utility service transitions and property changes, so that billing remains accurate during tenant moves and service modifications.

#### Acceptance Criteria

1. WHEN a tenant moves out THEN the System SHALL generate final readings, calculate pro-rated charges, and close service accounts
2. WHEN a new tenant moves in THEN the System SHALL establish new service accounts, record initial readings, and apply appropriate rate schedules
3. WHEN service configurations change THEN the System SHALL handle transitions smoothly with proper effective dating and pro-ration
4. WHEN properties are renovated or reconfigured THEN the System SHALL support temporary service suspensions and reactivations
5. WHERE service disputes occur THEN the System SHALL maintain detailed records and support adjustment workflows

### Requirement 13

**User Story:** As a SuperAdmin, I want to migrate existing gyvatukas data to the new universal system, so that historical data is preserved and operations continue seamlessly.

#### Acceptance Criteria

1. WHEN migration begins THEN the System SHALL create a complete backup of existing gyvatukas data and configurations
2. WHEN converting gyvatukas calculations THEN the System SHALL map existing logic to equivalent universal service configurations
3. WHEN migrating historical data THEN the System SHALL preserve all meter readings, invoices, and audit trails with proper data lineage
4. WHEN testing migrated data THEN the System SHALL validate that recalculated bills match original amounts within acceptable tolerance
5. WHERE migration issues occur THEN the System SHALL provide rollback capabilities and detailed error reporting

### Requirement 14

**User Story:** As an Admin, I want to integrate with external utility provider APIs, so that I can automatically import rates, validate readings, and synchronize billing data.

#### Acceptance Criteria

1. WHEN configuring external integrations THEN the System SHALL support REST APIs, file imports, and webhook notifications
2. WHEN importing rate data THEN the System SHALL validate against existing configurations and highlight discrepancies
3. WHEN synchronizing meter readings THEN the System SHALL handle duplicate detection, data validation, and conflict resolution
4. WHEN external systems are unavailable THEN the System SHALL continue operations using cached data and queue updates for later processing
5. WHERE data format differences exist THEN the System SHALL provide mapping tools and transformation capabilities

### Requirement 15

**User Story:** As a Manager, I want mobile-responsive interfaces for field data collection, so that I can record meter readings and manage properties from any device.

#### Acceptance Criteria

1. WHEN accessing the system on mobile devices THEN the System SHALL provide optimized interfaces for common tasks
2. WHEN recording readings in the field THEN the System SHALL support offline data collection with automatic synchronization
3. WHEN taking meter photos THEN the System SHALL provide camera integration with automatic reading extraction where possible
4. WHEN working without internet connection THEN the System SHALL cache essential data and queue updates for later transmission
5. WHERE location services are available THEN the System SHALL use GPS data to verify meter reading locations and detect anomalies