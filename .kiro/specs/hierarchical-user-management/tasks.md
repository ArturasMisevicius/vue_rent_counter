# Implementation Plan

- [x] 1. Update database schema and models
  - [x] 1.1 Create migration to add new columns to users table
    - Add property_id, parent_user_id, is_active, organization_name columns
    - Add indexes for performance
    - _Requirements: 2.2, 3.2, 5.2_
  
  - [x] 1.2 Create subscriptions table migration
    - Create table with plan_type, status, dates, and limits
    - Add foreign key to users table
    - Add indexes on user_id and status
    - _Requirements: 2.3, 2.4, 2.5_
  
  - [x] 1.3 Create user_assignments_audit table migration
    - Create audit table for tracking account actions
    - Add foreign keys and indexes
    - _Requirements: 14.1, 14.2, 14.3, 14.4_
  
  - [x] 1.4 Update UserRole enum to include superadmin
    - Add 'superadmin' to existing enum values
    - _Requirements: 1.1, 1.2_
  
  - [x] 1.5 Update User model with new relationships and fields
    - Add property, parentUser, childUsers, subscription relationships
    - Add fillable fields and casts
    - _Requirements: 5.1, 5.2_

- [x] 2. Create Subscription model and service
  - [x] 2.1 Create Subscription model
    - Define fields, relationships, and casts
    - Add helper methods: isActive(), isExpired(), daysUntilExpiry()
    - _Requirements: 2.3, 2.4, 2.5_
  
  - [x] 2.2 Create SubscriptionService
    - Implement createSubscription() method
    - Implement renewSubscription() method
    - Implement suspendSubscription() and cancelSubscription() methods
    - Implement checkSubscriptionStatus() and enforceSubscriptionLimits() methods
    - _Requirements: 2.3, 2.4, 2.5, 3.4, 3.5_
  
  - [x] 2.3 Write property test for subscription status
    - **Property 9: Subscription status affects access**
    - **Validates: Requirements 3.4**
  
  - [x] 2.4 Write property test for subscription renewal
    - **Property 10: Subscription renewal restores access**
    - **Validates: Requirements 3.5**
  
  - [x] 2.5 Write property test for subscription limits
    - **Property 17: Subscription limits enforcement**
    - **Validates: Requirements 2.5**

- [x] 3. Create AccountManagementService
  - [x] 3.1 Implement createAdminAccount() method
    - Validate input data
    - Create user with admin role and unique tenant_id
    - Create associated subscription
    - _Requirements: 2.1, 2.2, 3.1, 3.2_
  
  - [x] 3.2 Implement createTenantAccount() method
    - Validate input data and property ownership
    - Create user with tenant role inheriting admin's tenant_id
    - Set parent_user_id and property_id
    - Queue welcome email notification
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  
  - [x] 3.3 Implement assignTenantToProperty() and reassignTenant() methods
    - Validate property ownership
    - Update property assignment
    - Create audit log entry
    - Queue notification email
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
  
  - [x] 3.4 Implement deactivateAccount() and reactivateAccount() methods
    - Update is_active status
    - Create audit log entry
    - Preserve historical data
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  
  - [x] 3.5 Implement deleteAccount() method with validation
    - Check for dependencies (historical data)
    - Prevent deletion if dependencies exist
    - Suggest deactivation as alternative
    - _Requirements: 7.5_
  
  - [x] 3.6 Write property test for tenant_id uniqueness
    - **Property 4: Unique tenant_id assignment**
    - **Validates: Requirements 2.2, 3.2**
  
  - [x] 3.7 Write property test for tenant_id inheritance
    - **Property 5: Tenant inherits admin tenant_id**
    - **Validates: Requirements 5.2**
  
  - [x] 3.8 Write property test for audit logging
    - **Property 13: Audit logging completeness**
    - **Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4**

- [x] 4. Implement HierarchicalScope
  - [x] 4.1 Create HierarchicalScope class
    - Implement apply() method with role-based filtering
    - Superadmin: no filtering
    - Admin: filter by tenant_id
    - Tenant: filter by tenant_id and property_id
    - _Requirements: 12.1, 12.2, 12.3, 12.4_
  
  - [x] 4.2 Apply HierarchicalScope to relevant models
    - Add scope to Property, Building, Meter, MeterReading, Invoice models
    - Ensure scope is applied in booted() method
    - _Requirements: 3.3, 4.3, 8.2, 9.1, 11.1_
  
  - [x] 4.3 Write property test for superadmin unrestricted access
    - **Property 1: Superadmin unrestricted access**
    - **Validates: Requirements 1.4, 12.2, 13.1**
  
  - [ ]* 4.4 Write property test for admin tenant isolation
    - **Property 2: Admin tenant isolation**
    - **Validates: Requirements 3.3, 4.3, 12.3**
  
  - [ ]* 4.5 Write property test for tenant property isolation
    - **Property 3: Tenant property isolation**
    - **Validates: Requirements 8.2, 9.1, 11.1, 12.4**

- [x] 5. Update authorization policies
  - [x] 5.1 Update UserPolicy with hierarchical checks
    - Update viewAny() to respect role hierarchy
    - Update view() to check parent-child relationships
    - Update create() to allow superadmin→admin, admin→tenant
    - Update update() and delete() with ownership checks
    - _Requirements: 13.1, 13.2, 13.3, 13.4_
  
  - [x] 5.2 Update PropertyPolicy with admin ownership checks
    - Verify property belongs to admin's tenant_id
    - Allow tenant to view only their assigned property
    - _Requirements: 4.3, 8.2_
  
  - [x] 5.3 Create SubscriptionPolicy
    - Implement view(), update(), renew() methods
    - Allow superadmin full access, admin can view/renew own
    - _Requirements: 2.5, 15.3_
  
  - [x] 5.4 Update BuildingPolicy, MeterPolicy, InvoicePolicy
    - Add tenant_id ownership checks
    - Ensure tenant can only access their property's data
    - _Requirements: 4.5, 9.1, 11.1_
  
  - [x] 5.5 Write property test for cross-tenant access denial
    - **Property 7: Cross-tenant access denial**
    - **Validates: Requirements 12.5, 13.3**
  
  - [ ]* 5.6 Write property test for property assignment validation
    - **Property 8: Property assignment validation**
    - **Validates: Requirements 5.3, 6.1**
  
  - [ ]* 5.7 Write property test for user role-based permissions
    - **Property 18: User role-based permissions**
    - **Validates: Requirements 13.4**

- [x] 6. Create middleware for subscription and hierarchical access
  - [x] 6.1 Create CheckSubscriptionStatus middleware
    - Check if user is admin role
    - Verify subscription exists and is active
    - Allow read-only for expired subscriptions
    - Redirect to subscription page if needed
    - _Requirements: 3.4, 3.5_
  
  - [x] 6.2 Create EnsureHierarchicalAccess middleware
    - Validate user can access requested resource
    - Check tenant_id and property_id relationships
    - Return 403 if access denied
    - _Requirements: 12.5, 13.3_
  
  - [x] 6.3 Register middleware in HTTP Kernel
    - Add to route middleware groups
    - Apply to appropriate route groups
    - _Requirements: 3.4, 12.5_

- [x] 7. Create exception classes
  - [x] 7.1 Create subscription-related exceptions
    - SubscriptionExpiredException
    - SubscriptionLimitExceededException
    - _Requirements: 3.4, 2.5_
  
  - [x] 7.2 Create account management exceptions
    - InvalidPropertyAssignmentException
    - CannotDeleteWithDependenciesException
    - _Requirements: 7.1, 5.3, 7.5_

- [x] 8. Implement Superadmin dashboard and management interfaces



  - [x] 8.1 Create SuperadminController with dashboard method


    - Display statistics across all organizations
    - Show total active subscriptions and subscription status breakdown
    - Display usage metrics (total properties, tenants, invoices across all admins)
    - Show recent admin activity
    - _Requirements: 1.1, 17.1, 17.3_
  
  - [x] 8.2 Create superadmin dashboard view


    - Create resources/views/superadmin/dashboard.blade.php
    - Display organization statistics with subscription status indicators
    - Show system-wide metrics and trends
    - _Requirements: 1.1, 17.1, 17.3_
  
  - [x] 8.3 Create organization management views


    - Create resources/views/superadmin/organizations/index.blade.php - list all admin accounts with subscription status
    - Create resources/views/superadmin/organizations/show.blade.php - organization detail page with properties and tenants
    - Display admin activity tracking
    - _Requirements: 1.2, 1.3, 17.5_
  
  - [x] 8.4 Create admin account creation interface


    - Create resources/views/superadmin/organizations/create.blade.php
    - Form for creating new admin accounts with organization name
    - Subscription plan selection and activation interface
    - _Requirements: 2.1, 2.2, 2.3_
  
  - [x] 8.5 Create subscription management interface


    - Create resources/views/superadmin/subscriptions/index.blade.php
    - Create resources/views/superadmin/subscriptions/show.blade.php
    - View and update subscription details
    - Renewal and cancellation actions
    - _Requirements: 2.4, 2.5_
  
  - [ ]* 8.6 Write property test for data aggregation accuracy
    - **Property 19: Data aggregation accuracy**
    - **Validates: Requirements 17.1, 17.3, 18.1**

- [x] 9. Implement Admin dashboard and tenant management




  - [x] 9.1 Update Admin/DashboardController dashboard method


    - Display portfolio statistics (properties, tenants, pending tasks)
    - Show subscription status and limits with expiry warnings
    - Display usage statistics against subscription limits
    - _Requirements: 18.1, 18.2, 15.3_
  
  - [x] 9.2 Update admin dashboard view


    - Update resources/views/admin/dashboard.blade.php to show subscription status
    - Add subscription limit indicators (properties used/max, tenants used/max)
    - Display renewal reminders when subscription is near expiry
    - _Requirements: 15.3, 15.4, 18.1_
  
  - [x] 9.3 Create tenant account management views


    - Create resources/views/admin/tenants/index.blade.php - list tenants with property assignments
    - Create resources/views/admin/tenants/create.blade.php - create tenant form with property selection
    - Create resources/views/admin/tenants/show.blade.php - tenant detail page with history
    - _Requirements: 5.5, 5.1, 6.4_
  
  - [x] 9.4 Implement tenant reassignment interface


    - Create resources/views/admin/tenants/reassign.blade.php - form for reassigning tenant to different property
    - Display reassignment history in tenant detail view
    - _Requirements: 6.1, 6.2, 6.4_
  
  - [x] 9.5 Implement account activation/deactivation UI

    - Add toggle active status button in tenant views
    - Display active/inactive indicators in tenant list
    - Show deactivation reason and date
    - _Requirements: 7.1, 7.3, 7.4_
  
  - [x] 9.6 Create admin profile management views


    - Create resources/views/admin/profile/show.blade.php - view and update organization profile
    - Display subscription details (plan type, expiry date, limits)
    - Show renewal reminders
    - _Requirements: 15.1, 15.2, 15.4_
  
  - [ ]* 9.7 Write property test for resource creation inherits tenant_id
    - **Property 6: Resource creation inherits tenant_id**
    - **Validates: Requirements 4.1, 4.4, 13.2**

- [-] 10. Implement Tenant dashboard and profile


  - [x] 10.1 Update Tenant/DashboardController dashboard method

    - Display assigned property information
    - Show current meter readings and consumption
    - Display unpaid invoice balance
    - Verify property_id filtering is applied
    - _Requirements: 8.1, 8.2_
  
  - [ ] 10.2 Update tenant dashboard view
    - Update resources/views/tenant/dashboard.blade.php to show assigned property details
    - Display meter readings and consumption for assigned property only
    - Show unpaid invoice balance prominently
    - _Requirements: 8.1, 8.2_
  
  - [ ] 10.3 Verify tenant property and meter views
    - Verify resources/views/tenant/property/show.blade.php filters to assigned property
    - Verify resources/views/tenant/meters/index.blade.php shows only assigned property meters
    - Ensure consumption history and trends are property-scoped
    - _Requirements: 9.1, 9.2, 9.3, 9.4_
  
  - [ ] 10.4 Verify tenant invoice views
    - Verify resources/views/tenant/invoices/index.blade.php filters to assigned property
    - Verify resources/views/tenant/invoices/show.blade.php displays correct invoice details
    - Ensure PDF download works correctly
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_
  
  - [ ] 10.5 Update tenant profile management views
    - Update resources/views/tenant/profile/show.blade.php to display assigned property
    - Display admin (parent user) contact information
    - Ensure password change functionality works
    - _Requirements: 16.1, 16.2, 16.3, 16.4_
  
  - [ ]* 10.6 Write property test for profile data completeness
    - **Property 20: Profile data completeness**
    - **Validates: Requirements 15.1, 16.1**

- [ ] 11. Implement email notifications
  - [x] 11.1 Create WelcomeEmail notification
    - Send to new tenant accounts
    - Include login credentials and property information
    - _Requirements: 5.4_
  
  - [x] 11.2 Create TenantReassignedEmail notification
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
  - [ ] 12.1 Update login redirect logic for superadmin
    - Update LoginController to redirect superadmin to /superadmin/dashboard
    - Add check for is_active field before allowing login
    - Display appropriate error message for deactivated accounts
    - _Requirements: 1.1, 7.1, 8.1, 8.4_
  
  - [ ] 12.2 Create superadmin route group
    - Add superadmin routes in routes/web.php with role:superadmin middleware
    - Include dashboard, organizations, subscriptions routes
    - Apply CheckSubscriptionStatus middleware only to admin routes, not superadmin
    - _Requirements: 1.1, 3.4, 8.2_
  
  - [ ]* 12.3 Write property test for account deactivation prevents login
    - **Property 11: Account deactivation prevents login**
    - **Validates: Requirements 7.1, 8.4**
  
  - [ ]* 12.4 Write property test for account reactivation restores login
    - **Property 12: Account reactivation restores login**
    - **Validates: Requirements 7.3**

- [ ] 13. Create database seeders and factories
  - [x] 13.1 Create SubscriptionFactory
    - Generate subscriptions with various statuses and plans
    - _Requirements: 2.3, 2.4_
  
  - [ ] 13.2 Update UserFactory for hierarchical users
    - Update to generate superadmin, admin, and tenant users
    - Set appropriate tenant_id and property_id
    - Set parent_user_id for tenants
    - Set organization_name for admins
    - _Requirements: 2.2, 5.2_
  
  - [ ] 13.3 Create HierarchicalUsersSeeder
    - Seed one superadmin account (email: superadmin@example.com)
    - Seed multiple admins with subscriptions and organization names
    - Seed tenants for each admin with property assignments
    - _Requirements: 1.1, 2.1, 5.1_
  
  - [ ] 13.4 Update existing seeders to respect tenant_id
    - Update TestUsersSeeder, TestPropertiesSeeder, TestBuildingsSeeder
    - Ensure all seeded data has appropriate tenant_id
    - _Requirements: 4.1, 4.4_

- [ ] 14. Create data migration command
  - [ ] 14.1 Create MigrateToHierarchicalUsersCommand
    - Create artisan command to update existing data
    - Assign unique tenant_id to existing admin/manager users
    - Convert existing 'manager' role to 'admin' role
    - Create default active subscriptions for existing admins
    - Set is_active = true for all existing users
    - _Requirements: 2.2, 3.2_
  
  - [ ] 14.2 Add rollback functionality to command
    - Add --rollback option to revert changes if needed
    - _Requirements: N/A (deployment safety)_

- [ ] 15. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 16. Update documentation
  - [ ] 16.1 Update README.md with hierarchical user structure
    - Document three-tier hierarchy (Superadmin → Admin → Tenant)
    - Explain subscription model and limits
    - Document new user roles and their permissions
    - _Requirements: N/A (documentation)_
  
  - [ ] 16.2 Create HIERARCHICAL_USER_GUIDE.md
    - Superadmin guide for managing organizations and subscriptions
    - Admin guide for managing tenants and properties
    - Tenant guide for using the system
    - _Requirements: N/A (documentation)_
  
  - [ ] 16.3 Update SETUP.md with migration instructions
    - Document how to run MigrateToHierarchicalUsersCommand
    - Document how to seed hierarchical users
    - Document new environment variables for subscription limits
    - _Requirements: N/A (documentation)_

- [ ] 17. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
