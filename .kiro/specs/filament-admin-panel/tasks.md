# Implementation Plan

- [x] 1. Install and configure Filament foundation

- [x] 1.1 Install Filament packages via Composer
  - Run `composer require filament/filament:"^3.0"`
  - Verify installation in composer.json
  - _Requirements: 1.1_

- [x] 1.2 Publish Filament assets and create panel provider
  - Run `php artisan filament:install --panels`
  - Verify AdminPanelProvider is created
  - Verify config files are published
  - _Requirements: 1.2_

- [x] 1.3 Configure admin panel authentication
  - Update AdminPanelProvider to use existing User model
  - Configure authentication guard to use 'web'
  - Set panel path to '/admin'
  - Configure login route
  - _Requirements: 1.3, 1.4, 1.5_

- [x] 1.4 Write unit tests for panel configuration
  - Test panel uses correct authentication guard
  - Test panel path is '/admin'
  - Test User model is configured
  - _Requirements: 1.5_

- [x] 2. Implement MeterReadingResource

- [x] 2.1 Create MeterReadingResource with table and form schemas
  - Run `php artisan make:filament-resource MeterReading`
  - Define table columns: property, meter type, reading date, reading value, consumption
  - Define form fields: property select, meter select, reading date, reading value
  - Configure navigation icon and label
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 2.2 Integrate StoreMeterReadingRequest validation
  - Extract validation rules from StoreMeterReadingRequest
  - Apply rules to form schema
  - Integrate UpdateMeterReadingRequest for edit operations
  - _Requirements: 2.4, 2.6_

- [x] 2.3 Implement monotonicity validation
  - Add custom validation rule to check reading value against previous reading
  - Display appropriate error message for decreasing readings
  - _Requirements: 2.5_

- [x] 2.4 Write property test for tenant scope isolation
  - **Property 1: Tenant scope isolation for meter readings**
  - **Validates: Requirements 2.1, 2.7**

- [x] 2.5 Write property test for validation consistency
  - **Property 2: Meter reading validation consistency**
  - **Validates: Requirements 2.4, 2.6**

- [x] 2.6 Write property test for monotonicity enforcement
  - **Property 3: Monotonicity enforcement**
  - **Validates: Requirements 2.5**

- [x] 3. Implement PropertyResource

- [x] 3.1 Create PropertyResource with table and form schemas
  - Run `php artisan make:filament-resource Property`
  - Define table columns: address, property type, building, tenant, area
  - Define form fields: address, property type, building select, area, tenant select
  - Configure navigation icon and label
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 3.2 Integrate StorePropertyRequest validation and auto-tenant assignment
  - Extract validation rules from StorePropertyRequest
  - Apply rules to form schema
  - Implement automatic tenant_id assignment from session
  - Integrate UpdatePropertyRequest for edit operations
  - _Requirements: 3.4, 3.5_

- [x] 3.3 Write property test for tenant scope isolation
  - **Property 4: Tenant scope isolation for properties**
  - **Validates: Requirements 3.1**

- [x] 3.4 Write property test for validation consistency
  - **Property 5: Property validation consistency**
  - **Validates: Requirements 3.4**

- [x] 3.5 Write property test for automatic tenant assignment
  - **Property 6: Automatic tenant assignment**
  - **Validates: Requirements 3.5**

- [x] 4. Implement InvoiceResource

- [x] 4.1 Create InvoiceResource with table and form schemas
  - Run `php artisan make:filament-resource Invoice`
  - Define table columns: invoice number, property, billing period, total amount, status
  - Define form fields: property select, billing period dates, status
  - Configure navigation icon and label
  - Add status filter (draft, finalized, paid)
  - _Requirements: 4.1, 4.2, 4.4, 4.6_

- [x] 4.2 Implement invoice items relationship manager
  - Create relationship manager for invoice items
  - Display snapshotted pricing details
  - Configure columns for item display
  - _Requirements: 4.3_

- [x] 4.3 Implement invoice finalization action
  - Create custom action for finalizing invoices
  - Integrate FinalizeInvoiceRequest validation
  - Disable editing for finalized invoices
  - _Requirements: 4.5_

- [x] 4.4 Write property test for tenant scope isolation
  - **Property 7: Tenant scope isolation for invoices**
  - **Validates: Requirements 4.1**

- [x] 4.5 Write property test for invoice items visibility
  - **Property 8: Invoice items visibility**
  - **Validates: Requirements 4.3**

- [x] 4.6 Write property test for finalization immutability
  - **Property 9: Invoice finalization immutability**
  - **Validates: Requirements 4.5**

- [x] 4.7 Write property test for status filtering
  - **Property 10: Invoice status filtering**
  - **Validates: Requirements 4.6**

- [x] 5. Implement TariffResource

- [x] 5.1 Create TariffResource with table and form schemas
  - Run `php artisan make:filament-resource Tariff`
  - Define table columns: provider, service type, tariff type, effective dates, status
  - Define form fields: provider select, service type, tariff type, effective dates
  - Configure navigation icon and label
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 5.2 Implement tariff configuration JSON editor
  - Create repeater or JSON editor for tariff_config
  - Add fields for time-of-use rates (day rate, night rate, time ranges)
  - Make fields conditional based on tariff type
  - _Requirements: 5.4_

- [x] 5.3 Integrate StoreTariffRequest validation
  - Extract validation rules from StoreTariffRequest
  - Apply rules to form schema
  - Validate JSON structure for tariff_config
  - _Requirements: 5.5, 5.6_

- [x] 5.4 Write property test for validation consistency

  - **Property 11: Tariff validation consistency**
  - **Validates: Requirements 5.5**

- [x] 5.5 Write property test for JSON persistence
  - **Property 12: Tariff configuration JSON persistence**
  - **Validates: Requirements 5.6**

- [x] 6. Implement UserResource

- [x] 6.1 Create UserResource with table and form schemas
  - Run `php artisan make:filament-resource User`
  - Define table columns: name, email, role, tenant
  - Define form fields: name, email, password, role select, tenant select
  - Configure navigation icon and label
  - _Requirements: 6.1, 6.2, 6.3_

- [x] 6.2 Implement conditional tenant field logic
  - Make tenant field required when role is manager or tenant
  - Allow null tenant when role is admin
  - Integrate StoreUserRequest and UpdateUserRequest validation
  - Hash password before saving
  - _Requirements: 6.4, 6.5, 6.6_

- [x] 6.3 Write property test for validation consistency
  - **Property 13: User validation consistency**
  - **Validates: Requirements 6.4**

- [x] 6.4 Write property test for conditional tenant requirement
  - **Property 14: Conditional tenant requirement for non-admin users**
  - **Validates: Requirements 6.5**

- [x] 6.5 Write property test for admin null tenant allowance
  - **Property 15: Null tenant allowance for admin users**
  - **Validates: Requirements 6.6**

- [x] 7. Implement BuildingResource

- [x] 7.1 Create BuildingResource with table and form schemas
  - Run `php artisan make:filament-resource Building`
  - Define table columns: name, address, total area, property count
  - Define form fields: name, address, total area
  - Configure navigation icon and label
  - _Requirements: 7.1, 7.2, 7.3_

- [x] 7.2 Implement properties relationship manager
  - Create relationship manager for building properties
  - Display associated properties in building detail view
  - Integrate StoreBuildingRequest and UpdateBuildingRequest validation
  - _Requirements: 7.4, 7.5_

- [x] 7.3 Write property test for tenant scope isolation
  - **Property 16: Tenant scope isolation for buildings**
  - **Validates: Requirements 7.1**

- [x] 7.4 Write property test for validation consistency
  - **Property 17: Building validation consistency**
  - **Validates: Requirements 7.4**

- [x] 7.5 Write property test for relationship visibility
  - **Property 18: Building-property relationship visibility**
  - **Validates: Requirements 7.5**

- [x] 8. Implement ProviderResource

- [x] 8.1 Create ProviderResource with table and form schemas
  - Run `php artisan make:filament-resource Provider`
  - Define table columns: name, service types, contact information, tariff count
  - Define form fields: name, service types checkbox list, contact information
  - Configure navigation icon and label
  - _Requirements: 8.1, 8.2, 8.3_

- [x] 8.2 Implement tariffs relationship manager
  - Create relationship manager for provider tariffs
  - Display associated tariffs in provider detail view
  - _Requirements: 8.4_

- [x] 8.3 Write property test for relationship visibility
  - **Property 19: Provider-tariff relationship visibility**
  - **Validates: Requirements 8.4**

- [x] 9. Implement MeterResource

- [x] 9.1 Create MeterResource with table and form schemas
  - Run `php artisan make:filament-resource Meter`
  - Define table columns: property, meter type, serial number, installation date
  - Define form fields: property select, meter type, serial number, installation date
  - Configure navigation icon and label
  - Integrate StoreMeterRequest and UpdateMeterRequest validation
  - _Requirements: Related to meter management_

- [x] 10. Implement role-based authorization

- [x] 10.1 Integrate existing policy classes with resources
  - Configure each resource to use existing policy classes
  - Map Filament actions to policy methods (viewAny, view, create, update, delete)
  - Test policy integration for all resources
  - _Requirements: 9.5_

- [x] 10.2 Configure role-based navigation visibility
  - Hide admin-only resources from managers and tenants
  - Hide operational resources from tenants
  - Configure navigation groups by role
  - _Requirements: 9.1, 9.2, 9.3_

- [x] 10.3 Implement authorization error handling
  - Configure 403 error pages for unauthorized access
  - Display user-friendly error messages
  - Log authorization failures
  - _Requirements: 9.4_

- [x] 10.4 Write property test for tenant role restrictions
  - **Property 20: Tenant role resource restriction**
  - **Validates: Requirements 9.1**

- [x] 10.5 Write property test for manager role access
  - **Property 21: Manager role resource access with tenant scope**
  - **Validates: Requirements 9.2**

- [x] 10.6 Write property test for admin role access
  - **Property 22: Admin role full resource access**
  - **Validates: Requirements 9.3**

- [x] 10.7 Write property test for authorization denial
  - **Property 23: Authorization denial for restricted resources**
  - **Validates: Requirements 9.4**

- [x] 10.8 Write property test for policy integration
  - **Property 24: Policy integration**
  - **Validates: Requirements 9.5**

- [x] 11. Implement advanced features

- [x] 11.1 Add filters to resources
  - Implement status filters for invoices
  - Implement date range filters for meter readings
  - Implement type filters for properties and meters
  - _Requirements: Related to filtering functionality_

- [x] 11.2 Add search functionality
  - Configure searchable columns for each resource
  - Test search across all resources
  - _Requirements: Related to search functionality_

- [x] 11.3 Add bulk actions where appropriate
  - Implement bulk delete for appropriate resources
  - Implement bulk status update for invoices
  - _Requirements: Related to bulk operations_

- [x] 12. Clean up obsolete frontend configuration

- [x] 12.1 Remove Vue.js configuration files
  - Remove Vue.js related files from resources/js
  - Remove Vue.js dependencies from package.json
  - _Requirements: 10.1_

- [x] 12.2 Simplify Vite configuration
  - Remove SPA-specific Vite configuration
  - Keep only necessary configuration for Filament assets
  - _Requirements: 10.2_

- [x] 12.3 Clean up package.json
  - Remove unnecessary frontend build scripts
  - Remove unused npm dependencies
  - Retain Alpine.js CDN references in Blade templates
  - Verify only Filament and necessary dependencies remain
  - _Requirements: 10.3, 10.4, 10.5_

- [x] 13. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
