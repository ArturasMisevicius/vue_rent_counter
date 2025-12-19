# Implementation Plan

- [ ] 1. Set up database schema and model relationships
  - Create migration for building_manager pivot table with proper indexes and foreign keys
  - Create migration for property_manager pivot table with proper indexes and foreign keys
  - Add relationship methods to User model (assignedBuildings, assignedProperties, buildingProperties)
  - Add relationship methods to Building model (assignedManagers)
  - Add relationship methods to Property model (assignedManagers)
  - _Requirements: 1.1, 2.2, 3.2, 5.3_

- [ ] 1.1 Write property test for model relationships
  - **Property 3: Building Assignment Persistence**
  - **Validates: Requirements 2.2**

- [ ] 1.2 Write property test for cascade deletion
  - **Property 7: Assignment Deletion Cascade**
  - **Validates: Requirements 5.3**

- [ ] 2. Implement core assignment services
  - Create ManagerAssignmentServiceInterface with assignment methods
  - Implement ManagerAssignmentService with building and property assignment logic
  - Add validation for tenant boundaries and role requirements
  - Implement assignment removal methods
  - _Requirements: 2.1, 2.2, 3.1, 3.2, 5.1_

- [ ] 2.1 Write property test for tenant boundary enforcement
  - **Property 2: Assignment Tenant Boundary Enforcement**
  - **Validates: Requirements 5.1**

- [ ] 2.2 Write property test for manager organization inheritance
  - **Property 1: Manager Organization Inheritance**
  - **Validates: Requirements 1.1, 1.4**

- [ ] 3. Implement access control service
  - Create ManagerAccessControlServiceInterface with access query methods
  - Implement ManagerAccessControlService with building and property access logic
  - Add methods for accessible meters, invoices, and tenants
  - Implement resource access validation methods
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 3.1 Write property test for building access restriction
  - **Property 5: Manager Building Access Restriction**
  - **Validates: Requirements 4.1**

- [ ] 3.2 Write property test for property access combination
  - **Property 6: Manager Property Access Combination**
  - **Validates: Requirements 4.2**

- [ ] 3.3 Write property test for resource access transitivity
  - **Property 8: Manager Resource Access Transitivity**
  - **Validates: Requirements 4.3, 4.4, 4.5**

- [ ] 4. Create enhanced policies and scopes
  - Extend BuildingPolicy with manager assignment authorization checks
  - Extend PropertyPolicy with manager assignment authorization checks
  - Create ManagerBuildingScope for filtering buildings by manager assignments
  - Create ManagerPropertyScope for filtering properties by manager assignments
  - Update UserPolicy for manager creation permissions
  - _Requirements: 1.1, 4.1, 4.2, 5.1_

- [ ] 4.1 Write unit tests for policy authorization
  - Test building and property policy methods for manager access
  - Test user policy methods for manager creation
  - _Requirements: 1.1, 4.1, 4.2_

- [ ] 5. Implement Filament form fields for assignments
  - Create BuildingManagersField multi-select component for Building edit forms
  - Create PropertyManagersField multi-select component for Property edit forms
  - Add manager filtering to ensure only same-tenant managers are shown
  - Implement field validation and error handling
  - _Requirements: 2.1, 3.1_

- [ ] 5.1 Write unit tests for form field components
  - Test manager filtering by tenant
  - Test field validation and error states
  - _Requirements: 2.1, 3.1_

- [ ] 6. Create bulk assignment actions
  - Create AssignBuildingsToManagerAction for bulk building assignments
  - Create AssignPropertiesToManagerAction for bulk property assignments
  - Add manager selection dropdown with tenant filtering
  - Implement confirmation dialogs and success notifications
  - _Requirements: 2.3, 2.4, 3.3, 3.4_

- [ ] 6.1 Write unit tests for bulk actions
  - Test bulk assignment functionality
  - Test manager dropdown filtering
  - Test confirmation and notification flows
  - _Requirements: 2.3, 2.4, 3.3, 3.4_

- [ ] 7. Update Filament resources with assignment fields
  - Add "Assigned Managers" field to BuildingResource edit form
  - Add "Assigned Managers" field to PropertyResource edit form
  - Add bulk assignment actions to BuildingResource table
  - Add bulk assignment actions to PropertyResource table
  - Update resource queries to use manager scopes for Manager role users
  - _Requirements: 2.1, 2.3, 3.1, 3.3, 4.1, 4.2_

- [ ] 7.1 Write integration tests for resource updates
  - Test assignment fields in building and property edit forms
  - Test bulk actions in resource tables
  - Test manager access filtering in resource lists
  - _Requirements: 2.1, 2.3, 3.1, 3.3, 4.1, 4.2_

- [ ] 8. Implement manager access filtering for related resources
  - Update MeterResource to filter by manager property access
  - Update InvoiceResource to filter by manager property access
  - Update TenantResource to filter by manager property access
  - Apply ManagerPropertyScope to related resource queries
  - _Requirements: 4.3, 4.4, 4.5_

- [ ] 8.1 Write property test for building-to-property cascade
  - **Property 4: Building-to-Property Access Cascade**
  - **Validates: Requirements 2.5**

- [ ] 9. Create assignment management interface for UserResource
  - Add "Assigned Buildings" field to Manager user edit forms
  - Add "Assigned Properties" field to Manager user edit forms
  - Create assignment summary display for Manager users
  - Implement assignment removal functionality
  - _Requirements: 1.2, 1.3, 2.1, 3.1_

- [ ] 9.1 Write unit tests for user assignment interface
  - Test assignment fields in user edit forms
  - Test assignment summary display
  - Test assignment removal functionality
  - _Requirements: 1.2, 1.3, 2.1, 3.1_

- [ ] 10. Add exception handling and validation
  - Implement CrossTenantAssignmentException for tenant boundary violations
  - Implement InvalidManagerRoleException for non-manager assignments
  - Implement DuplicateAssignmentException for duplicate assignments
  - Add comprehensive validation in assignment services
  - _Requirements: 5.1, 5.2, 5.4_

- [ ] 10.1 Write unit tests for exception handling
  - Test exception throwing for invalid assignments
  - Test validation error messages
  - Test error handling in services
  - _Requirements: 5.1, 5.2, 5.4_

- [ ] 11. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 12. Create assignment audit logging
  - Add assignment creation logging with assigned_by tracking
  - Add assignment removal logging
  - Create assignment history display in admin interface
  - Implement assignment change notifications
  - _Requirements: 5.5_

- [ ] 12.1 Write unit tests for audit logging
  - Test assignment creation logging
  - Test assignment removal logging
  - Test audit history display
  - _Requirements: 5.5_

- [ ] 13. Implement performance optimizations
  - Add database indexes for assignment queries
  - Implement query optimization for manager access filtering
  - Add caching for frequently accessed assignment data
  - Optimize cascade access queries for large datasets
  - _Requirements: Performance considerations_

- [ ] 13.1 Write performance tests
  - Test query performance with large datasets
  - Test bulk assignment operation performance
  - Test cascade access query performance
  - _Requirements: Performance considerations_

- [ ] 14. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.