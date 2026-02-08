# Requirements Document

## Introduction

The Annual Tax Declaration feature enables Lithuanian landlords to generate compliant tax reports in FR0572/VMI format for submission to the State Tax Inspectorate (VMI). This feature aggregates rental income data from invoices and exports it in formats required by Lithuanian tax authorities.

## Glossary

- **VMI**: Valstybinė mokesčių inspekcija (State Tax Inspectorate of Lithuania)
- **FR0572**: Official VMI form number for rental income declaration
- **Tax_Report_Generator**: System component that aggregates annual rental income data
- **Export_Service**: System component that generates VMI-compliant export formats
- **Organization**: Landlord entity that owns rental properties and must file tax returns
- **Annual_Tax_Report**: Aggregated rental income data for a specific tax year
- **Billing_Period**: Time range for which rental income is calculated and reported

## Requirements

### Requirement 1: Annual Income Aggregation

**User Story:** As a landlord, I want to aggregate all rental income for a specific tax year, so that I can prepare accurate tax declarations.

#### Acceptance Criteria

1. WHEN an admin selects a tax year, THE Tax_Report_Generator SHALL aggregate all paid invoices within that calendar year
2. WHEN aggregating income, THE Tax_Report_Generator SHALL group data by organization, property, and tenant
3. WHEN calculating totals, THE Tax_Report_Generator SHALL include only invoices with status 'paid' or 'finalized'
4. WHEN processing multi-currency invoices, THE Tax_Report_Generator SHALL convert amounts to EUR using historical exchange rates
5. THE Tax_Report_Generator SHALL exclude cancelled or draft invoices from tax calculations

### Requirement 2: FR0572 Format Export

**User Story:** As a landlord, I want to export tax data in FR0572/VMI format, so that I can submit compliant tax declarations to Lithuanian authorities.

#### Acceptance Criteria

1. WHEN generating FR0572 export, THE Export_Service SHALL include landlord identification data (name, email, phone)
2. WHEN exporting rental income, THE Export_Service SHALL list each property with total annual income
3. WHEN including tenant data, THE Export_Service SHALL provide tenant identification and lease period dates
4. THE Export_Service SHALL format monetary amounts according to VMI requirements (EUR, two decimal places)
5. WHEN creating Excel export, THE Export_Service SHALL use VMI-compatible column headers and data structure

### Requirement 3: Multi-Format Export Support

**User Story:** As a landlord, I want to choose between Excel, PDF, and JSON export formats, so that I can use the format most suitable for my submission method.

#### Acceptance Criteria

1. WHEN exporting to Excel, THE Export_Service SHALL create VMI FR0572-compatible spreadsheet format
2. WHEN exporting to PDF, THE Export_Service SHALL generate human-readable summary report with property breakdowns
3. WHEN exporting to JSON, THE Export_Service SHALL structure data for digital submission to VMI electronic portal
4. THE Export_Service SHALL include export timestamp and generation metadata in all formats
5. WHEN generating exports, THE Export_Service SHALL apply organization-specific branding to PDF reports

### Requirement 4: Administrative Interface

**User Story:** As an admin, I want to access tax report generation through Filament admin panel, so that I can efficiently manage tax compliance for my organization.

#### Acceptance Criteria

1. WHEN accessing tax reports, THE System SHALL display "Annual Tax Reports" menu item for Admin and Superadmin roles only
2. WHEN generating reports, THE System SHALL provide year selection dropdown with available data years
3. WHEN previewing reports, THE System SHALL display summary statistics before export generation
4. THE System SHALL require confirmation before generating large reports (>100 properties)
5. WHEN downloads complete, THE System SHALL log report generation in audit trail with user and timestamp

### Requirement 5: Data Validation and Compliance

**User Story:** As a landlord, I want to ensure my tax data is accurate and compliant, so that I can avoid penalties from tax authorities.

#### Acceptance Criteria

1. WHEN validating data, THE Tax_Report_Generator SHALL verify all required landlord information is present
2. WHEN checking completeness, THE Tax_Report_Generator SHALL identify properties with missing rental income data
3. WHEN detecting anomalies, THE Tax_Report_Generator SHALL flag unusual income patterns for review
4. THE Tax_Report_Generator SHALL validate that lease periods align with invoice billing periods
5. WHEN generating reports, THE System SHALL include data completeness warnings in export summary

### Requirement 6: Multi-Tenant Data Isolation

**User Story:** As a system administrator, I want to ensure tax reports only include data for the requesting organization, so that tenant data isolation is maintained.

#### Acceptance Criteria

1. WHEN generating reports, THE Tax_Report_Generator SHALL filter all data by current organization tenant_id
2. WHEN accessing tax features, THE System SHALL verify user authorization for the requesting organization
3. THE Tax_Report_Generator SHALL prevent cross-tenant data leakage in aggregation queries
4. WHEN exporting data, THE System SHALL include only properties and tenants belonging to the requesting organization
5. THE System SHALL log all tax report access attempts with organization and user identification

### Requirement 7: Historical Data Processing

**User Story:** As a landlord, I want to generate tax reports for previous years, so that I can handle amended returns or historical compliance requirements.

#### Acceptance Criteria

1. WHEN selecting years, THE System SHALL provide dropdown with all years containing invoice data
2. WHEN processing historical data, THE Tax_Report_Generator SHALL use invoice data as recorded at time of payment
3. THE Tax_Report_Generator SHALL handle properties that were sold or transferred during the tax year
4. WHEN calculating historical totals, THE System SHALL account for partial-year rental periods
5. THE System SHALL preserve historical exchange rates for accurate multi-year currency conversion

### Requirement 8: Performance and Scalability

**User Story:** As a system administrator, I want tax report generation to complete efficiently, so that users can access reports without system performance degradation.

#### Acceptance Criteria

1. WHEN processing large datasets, THE Tax_Report_Generator SHALL complete reports for up to 1000 properties within 30 seconds
2. WHEN generating exports, THE System SHALL use background job processing for reports exceeding 100 properties
3. THE Tax_Report_Generator SHALL implement query optimization to prevent N+1 database issues
4. WHEN caching results, THE System SHALL store generated reports for 24 hours to enable re-downloads
5. THE System SHALL provide progress indicators for long-running report generation tasks