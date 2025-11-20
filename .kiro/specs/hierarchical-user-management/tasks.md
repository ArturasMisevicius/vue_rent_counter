# Implementation Plan

- [ ] 1. Update database schema and models
  - [ ] 1.1 Create migration to add new columns to users table
    - Add property_id, parent_user_id, is_active, organization_name columns
    - Add indexes for performance
    - _Requirements: 2.2, 3.2, 5.2_
  
  - [ ] 1.2 Create subscriptions table migration
    - Create table with plan_type, status, dates, and limits
    - Add foreign key to users table
    - Add indexes on user_id and status
    - _Requirements: 2.3, 2.4, 2.5_
  
  - [ ] 1.3 Create user_assignments_audit table migration
    - Create audit table for tracking account actions
    - Add foreign keys and indexes
    - _Requirements: 14.1, 14.2, 14.3, 14.4_
  
  - [ ] 1.4 Update UserRole enum to include superadmin
    - Add 'superadmin' to existing enum values
    - _Requirements: 1.1, 1.2_
  
  - [ ] 1.5 Update User model with new relationships and fields
    - Add property, parentUser, childUsers, subscription relationships
    - Add fillable fields and casts
    - _Requirements: 5.1, 5.2_

- [ ] 2. Create Subscription model and service
  - [ ] 2.1 Create Subscription model
    - Define fields, relationships, and casts
    - Add helper methods: isActive(), isExpired(), daysUntilExpiry()
    - _Requirements: 2.3, 2.4, 2.5_
  
  - [ ] 2.2 Create SubscriptionService
    - Implement createSubscription() method
    - Implement renewSubscription() method
    - Implement suspendSubscription() and cancelSubscription() methods
    - Implement checkSubscriptionStatus() and enforceSubscriptionLimits() methods
    - _Requirements: 2.3, 2.4, 2.5, 3.4, 3.5_
  
  - [ ]* 2.3 Write property test for subscription status
    - **Property 9: Subscription status affects access**
    - **Validates: Requirements 3.4**
  
  - [ ]* 2.4 Write property test for subscription renewal
    - **Property 10: Subscription renewal restores access**
    - **Validates: Requirements 3.5**
  
  - [ ]* 2.5 Write property test for subscription limits
    - **Property 17: Subscription limits enforcement**
    - **Validates: Requirements 2.5**

- [ ] 3. Create AccountManagementService
  - [ ] 3.1 Implement createAdminAccount() method
    - Validate input data
    - Create user with admin role and unique tenant_id
    - Create associated subscription
    - _Requirements: 2.1, 2.2, 3.1, 3.2_
  
  - [ ] 3.2 Implement createTenantAccount() method
    - Validate input data and property ownership
    - Create user with tenant role inheriting admin's tenant_id
    - Set parent_user_id and property_id
    - Queue welcome email notification
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  
  - [ ] 3.3 Implement assignTenantToProperty() and reassignTenant() methods
    - Validate property ownership
    - Update property assignment
    - Create audit log entry
    - Queue notification email
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
  
  - [ ] 3.4 Implement deactivateAccount() and reactivateAccount() methods
    - Update is_active status
    - Create audit log entry
    - Preserve historical data
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  
  - [ ] 3.5 Implement deleteAccount() method with validation
    - Check for dependencies (historical data)
    - Prevent deletion if dependencies exist
    - Suggest deactivation as alternative
    - _Requirements: 7.5_
  
  - [ ]* 3.6 Write property test for tenant_id uniqueness
    - **Property 4: Unique tenant_id assignment**
    - **Validates: Requirements 2.2, 3.2**
  
  - [ ]* 3.7 Write property test for tenant_id inheritance
    - **Property 5: Tenant inherits admin tenant_id**
    - **Validates: Requirements 5.2**
  
  - [ ]* 3.8 Write property test for audit logging
    - **Property 13: Audit logging completeness**
    - **Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4**

- [ ] 4. Implement HierarchicalScope
  - [ ] 4.1 Create HierarchicalScope class
    - Implement apply() method with role-based filtering
    - Superadmin: no filtering
    - Admin: filter by tenant_id
    - Tenant: filter by tenant_id and property_id
    - _Requirements: 12.1, 12.2, 12.3, 12.4_
  
  - [ ] 4.2 Apply HierarchicalScope to relevant models
    - Add scope to Property, Building, Meter, MeterReading, Invoice models
    - Ensure scope is applied in booted() method
    - _Requirements: 3.3, 4.3, 8.2, 9.1, 11.1_
  
  - [ ]* 4.3 Write property test for superadmin unrestricted access
    - **Property 1: Superadmin unrestricted access**
    - **Validates: Requirements 1.4, 12.2, 13.1**
  
  - [ ]* 4.4 Write property test for admin tenant isolation
    - **Property 2: Admin tenant isolation**
    - **Validates: Requirements 3.3, 4.3, 12.3**
  
  - [ ]* 4.5 Write property test for tenant property isolation
    - **Property 3: Tenant property isolation**
    - **Validates: Requirements 8.2, 9.1, 11.1, 12.4**

- [ ] 5. Update authorization policies
  - [ ] 5.1 Update UserPolicy with hierarchical checks
    - Update viewAny() to respect role hierarchy
    - Update view() to check parent-child relationships
    - Update create() to allow superadmin→admin, admin→tenant
    - Update update() and delete() with ownership checks
    - _Requirements: 13.1, 13.2, 13.3, 13.4_
  
  - [ ] 5.2 Update PropertyPolicy with admin ownership checks
    - Verify property belongs to admin's tenant_id
    - Allow tenant to view only their assigned property
    - _Requirements: 4.3, 8.2_
  
  - [ ] 5.3 Create SubscriptionPolicy
    - Implement view(), update(), renew() methods
    - Allow superadmin full access, admin can view/renew own
    - _Requirements: 2.5, 15.3_
  
  - [ ] 5.4 Update BuildingPolicy, MeterPolicy, InvoicePolicy
    - Add tenant_id ownership checks
    - Ensure tenant can only access their property's data
    - _Requirements: 4.5, 9.1, 11.1_
  
  - [ ]* 5.5 Write property test for cross-tenant access denial
    - **Property 7: Cross-tenant access denial**
    - **Validates: Requirements 12.5, 13.3**
  
  - [ ]* 5.6 Write property test for property assignment validation
    - **Property 8: Property assignment validation**
    - **Validates: Requirements 5.3, 6.1**
  
  - [ ]* 5.7 Write property test for user role-based permissions
    - **Property 18: User role-based permissions**
    - **Validates: Requirements 13.4**

- [ ] 6. Create middleware for subscription and hierarchical access
  - [ ] 6.1 Create CheckSubscriptionStatus middleware
    - Check if user is admin role
    - Verify subscription exists and is active
    - Allow read-only for expired subscriptions
    - Redirect to subscription page if needed
    - _Requirements: 3.4, 3.5_
  
  - [ ] 6.2 Create EnsureHierarchicalAccess middleware
    - Validate user can access requested resource
    - Check tenant_id and property_id relationships
    - Return 403 if access denied
    - _Requirements: 12.5, 13.3_
  
  - [ ] 6.3 Register middleware in HTTP Kernel
    - Add to route middleware groups
    - Apply to appropriate route groups
    - _Requirements: 3.4, 12.5_

- [ ] 7. Create exception classes
  - [ ] 7.1 Create subscription-related exceptions
    - SubscriptionExpiredException
    - SubscriptionLimitExceededException
    - _Requirements: 3.4, 2.5_
  
  - [ ] 7.2 Create authorization-related exceptions
    - UnauthorizedAccessException
    - CrossTenantAccessException
    - _Requirements: 12.5, 13.3_
  
  - [ ] 7.3 Create account management exceptions
    - AccountDeactivatedException
    - InvalidPropertyAssignmentException
    - CannotDeleteWithDependenciesException
    - _Requirements: 7.1, 5.3, 7.5_
  
  - [ ] 7.4 Create validation exceptions
    - DuplicateEmailException
    - InvalidRoleAssignmentException
    - _Requirements: 15.2_

- [ ] 8. Implement Superadmin dashboard and management interfaces
  - [ ] 8.1 Create SuperadminController with dashboard method
    - Display statistics across all organizations
    - Show total active subscriptions
    - Display usage metrics
    - _Requirements: 1.1, 17.1, 17.3_
  
  - [ ] 8.2 Create organization management views
    - List all admin accounts with subscription status
    - Organization detail page with properties and tenants
    - Admin activity tracking
    - _Requirements: 1.2, 1.3, 17.5_
  
  - [ ] 8.3 Create admin account creation form
    - Form for creating new admin accounts
    - Subscription activation interface
    - _Requirements: 2.1, 2.2, 2.3_
  
  - [ ] 8.4 Create subscription management interface
    - View and update subscription details
    - Renewal and cancellation actions
    - _Requirements: 2.4, 2.5_
  
  - [ ]* 8.5 Write property test for data aggregation accuracy
    - **Property 19: Data aggregation accuracy**
    - **Validates: Requirements 17.1, 17.3, 18.1**

- [ ] 9. Implement Admin dashboard and tenant management
  - [ ] 9.1 Update AdminController dashboard method
    - Display portfolio statistics (properties, tenants, pending tasks)
    - Show subscription status and limits
    - Display usage statistics
    - _Requirements: 18.1, 18.2, 15.3_
  
  - [ ] 9.2 Create tenant account management views
    - List tenants with property assignments
    - Create tenant form with property selection
    - Tenant detail page with history
    - _Requirements: 5.5, 5.1, 6.4_
  
  - [ ] 9.3 Implement tenant reassignment interface
    - Form for reassigning tenant to different property
    - Display reassignment history
    - _Requirements: 6.1, 6.2, 6.4_
  
  - [ ] 9.4 Implement account activation/deactivation
    - Toggle active status
    - Display active/inactive indicators
    - _Requirements: 7.1, 7.3, 7.4_
  
  - [ ] 9.5 Create admin profile management
    - View and update organization profile
    - Display subscription details
    - Show renewal reminders
    - _Requirements: 15.1, 15.2, 15.4_
  
  - [ ]* 9.6 Write property test for resource creation inherits tenant_id
    - **Property 6: Resource creation inherits tenant_id**
    - **Validates: Requirements 4.1, 4.4, 13.2**

- [ ] 10. Implement Tenant dashboard and profile
  - [ ] 10.1 Update TenantController dashboard method
    - Display assigned property information
    - Show current meter readings and consumption
    - Display unpaid invoice balance
    - _Requirements: 8.1, 8.2_
  
  - [ ] 10.2 Update tenant property and meter views
    - Ensure only assigned property is visible
    - Display meter details and consumption history
    - Show consumption trends graph
    - _Requirements: 9.1, 9.2, 9.3, 9.4_
  
  - [ ] 10.3 Update tenant invoice views
    - Filter to only assigned property invoices
    - Display invoice details with line items
    - Show payment status and history
    - Provide PDF download option
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_
  
  - [ ] 10.4 Create tenant profile management
    - View and update profile information
    - Display admin contact information
    - Password change functionality
    - _Requirements: 16.1, 16.2, 16.3, 16.4_
  
  - [ ]* 10.5 Write property test for profile data completeness
    - **Property 20: Profile data completeness**
    - **Validates: Requirements 15.1, 16.1**

- [ ] 11. Implement email notifications
  - [ ] 11.1 Create WelcomeEmail notification
    - Send to new tenant accounts
    - Include login credentials and property information
    - _Requirements: 5.4_
  
  - [ ] 11.2 Create TenantReassignedEmail notification
    - Send when tenant is reassigned to new property
    - Include old and new property details
    - _Requirements: 6.5_
  
  - [ ] 11.3 Create SubscriptionExpiryWarningEmail notification
    - Send when subscription is near expiry
    - Include renewal instructions
    - _Requirements: 15.4_
  
  - [ ] 11.4 Create MeterReadingSubmittedEmail notification
    - Send to admin when tenant submits reading
    - Include reading details
    - _Requirements: 10.4_
  
  - [ ]* 11.5 Write property test for email notifications
    - **Property 16: Email notification on account actions**
    - **Validates: Requirements 5.4, 6.5**

- [ ] 12. Update authentication and routing
  - [ ] 12.1 Update login redirect logic
    - Redirect superadmin to /superadmin/dashboard
    - Redirect admin to /admin/dashboard (or /manager/dashboard)
    - Redirect tenant to /tenant/dashboard
    - _Requirements: 1.1, 8.1_
  
  - [ ] 12.2 Create route groups for each role
    - Superadmin routes with superadmin middleware
    - Admin routes with subscription check middleware
    - Tenant routes with property access middleware
    - _Requirements: 1.1, 3.4, 8.2_
  
  - [ ] 12.3 Update authentication checks for deactivated accounts
    - Prevent login for is_active = false
    - Display appropriate error message
    - _Requirements: 7.1, 8.4_
  
  - [ ]* 12.4 Write property test for account deactivation prevents login
    - **Property 11: Account deactivation prevents login**
    - **Validates: Requirements 7.1, 8.4**
  
  - [ ]* 12.5 Write property test for account reactivation restores login
    - **Property 12: Account reactivation restores login**
    - **Validates: Requirements 7.3**

- [ ] 13. Create database seeders and factories
  - [ ] 13.1 Create SubscriptionFactory
    - Generate subscriptions with various statuses and plans
    - _Requirements: 2.3, 2.4_
  
  - [ ] 13.2 Update UserFactory for hierarchical users
    - Generate superadmin, admin, and tenant users
    - Set appropriate tenant_id and property_id
    - Set parent_user_id for tenants
    - _Requirements: 2.2, 5.2_
  
  - [ ] 13.3 Create HierarchicalUsersSeeder
    - Seed one superadmin
    - Seed multiple admins with subscriptions
    - Seed tenants for each admin with property assignments
    - _Requirements: 1.1, 2.1, 5.1_
  
  - [ ] 13.4 Update existing seeders to respect tenant_id
    - Ensure all seeded data has appropriate tenant_id
    - _Requirements: 4.1, 4.4_

- [ ] 14. Create data migration script
  - [ ] 14.1 Create migration command to update existing data
    - Assign default tenant_id to existing users
    - Convert existing 'manager' role to 'admin'
    - Create default subscriptions for existing admins
    - _Requirements: 2.2, 3.2_
  
  - [ ] 14.2 Create rollback script
    - Revert users to previous structure if needed
    - _Requirements: N/A (deployment safety)_

- [ ] 15. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 16. Update documentation
  - [ ] 16.1 Update README with hierarchical user structure
    - Document three-tier hierarchy
    - Explain subscription model
    - _Requirements: N/A (documentation)_
  
  - [ ] 16.2 Create user guide for each role
    - Superadmin guide for managing organizations
    - Admin guide for managing tenants
    - Tenant guide for using the system
    - _Requirements: N/A (documentation)_
  
  - [ ] 16.3 Document API endpoints for hierarchical access
    - List endpoints for each role
    - Document authorization requirements
    - _Requirements: N/A (documentation)_

- [ ] 17. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
