# Implementation Plan

- [x] 1. Set up foundation and data models
  - Create migrations for new tables (organization_invitations, system_health_metrics)
  - Add new fields to existing organizations table (domain, timezone, locale, currency)
  - Create OrganizationInvitation model with relationships and methods
  - Create SystemHealthMetric model with status checking methods
  - Update Organization model with new methods (isSuspended, suspend, reactivate, daysUntilExpiry)
  - Update Subscription model with new methods (renew, suspend, activate, daysUntilExpiry)
  - _Requirements: 2.1, 3.1, 13.1_

- [ ]* 1.1 Write property test for organization resource limits
  - **Property 2: Organization resource limit enforcement**
  - **Validates: Requirements 2.1, 3.1**

- [x] 2. Create dashboard widgets
- [x] 2.1 Create SubscriptionStatsWidget
  - Display total, active, expired, suspended subscription counts
  - Add color-coded badges for each status
  - Implement caching with 60-second TTL
  - _Requirements: 1.1, 1.2_

- [x] 2.2 Create OrganizationStatsWidget
  - Display total organizations, active/inactive counts
  - Show growth trend indicator
  - Implement caching with 5-minute TTL
  - _Requirements: 1.1_

- [x] 2.3 Create SystemHealthWidget
  - Display database, backup, queue, storage health indicators
  - Implement color-coded status (green/yellow/red)
  - Add quick action buttons for health checks
  - _Requirements: 1.4, 6.1, 6.2, 6.3, 6.4_

- [ ]* 2.4 Write property test for system health status accuracy
  - **Property 12: System health status accuracy**
  - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5**

- [x] 2.5 Create ExpiringSubscriptionsWidget
  - Display table of subscriptions expiring within 14 days
  - Add renewal action buttons
  - Implement sorting and filtering
  - _Requirements: 1.3, 8.1_

- [ ]* 2.6 Write property test for subscription expiry notification timing
  - **Property 6: Subscription expiry notification timing**
  - **Validates: Requirements 8.1**

- [x] 2.7 Create RecentActivityWidget
  - Display feed of recent superadmin and organization actions
  - Limit to last 10 activities
  - Add links to related resources
  - _Requirements: 1.1_

- [x] 2.8 Create TopOrganizationsWidget
  - Display bar chart of top 10 organizations by property count
  - Use Chart.js for visualization
  - Implement caching
  - _Requirements: 9.4_

- [x] 2.9 Create PlatformUsageWidget
  - Display line chart showing platform growth over time
  - Show properties, users, invoices trends
  - Use Chart.js for visualization
  - _Requirements: 9.1_

- [x] 3. Create SuperadminDashboard page
  - Create Filament page class with widget registration
  - Arrange widgets in 3-column responsive grid
  - Add quick action buttons (create org, create subscription)
  - Implement dashboard data export to PDF
  - Add authorization check (superadmin only)
  - _Requirements: 1.1, 1.5_

- [ ]* 3.1 Write property test for dashboard metrics consistency
  - **Property 1: Dashboard metrics consistency**
  - **Validates: Requirements 1.2**

- [ ]* 3.2 Write property test for dashboard widget data freshness
  - **Property 10: Dashboard widget data freshness**
  - **Validates: Requirements 18.1, 18.5**

- [x] 4. Enhance OrganizationResource
- [x] 4.1 Update form schema with all new fields
  - Add Organization Details section (name, slug, email, phone, domain)
  - Add Subscription & Limits section (plan, limits, dates)
  - Add Regional Settings section (timezone, locale, currency)
  - Add Status section (is_active, suspended_at, suspension_reason)
  - Implement live updates for plan selection
  - _Requirements: 2.1, 2.2_

- [x] 4.2 Update table columns and filters
  - Add all required columns with proper formatting
  - Implement plan badge with color coding
  - Add subscription expiry color coding
  - Create filters for plan, status, expiry
  - _Requirements: 2.4_

- [x] 4.3 Add custom actions
  - Create Suspend action with reason form
  - Create Reactivate action
  - Create Impersonate action with audit logging
  - Create View Analytics action
  - _Requirements: 2.2, 11.1, 11.2_

- [ ]* 4.4 Write property test for organization suspension cascades
  - **Property 8: Organization suspension cascades**
  - **Validates: Requirements 2.5, 5.5**

- [ ]* 4.5 Write property test for organization deletion dependency validation
  - **Property 15: Organization deletion dependency validation**
  - **Validates: Requirements 2.5**

- [x] 4.6 Add bulk actions
  - Create BulkSuspendOrganizationsAction with confirmation
  - Create BulkReactivateAction
  - Create BulkChangePlanAction with plan selection
  - Create BulkExportAction (CSV/Excel)
  - _Requirements: 7.1, 7.2, 7.3_

- [ ]* 4.7 Write property test for bulk operation atomicity
  - **Property 5: Bulk operation atomicity**
  - **Validates: Requirements 7.2, 7.3, 7.4, 7.5**

- [ ]* 4.8 Write property test for bulk plan change limit updates
  - **Property 13: Bulk plan change limit updates**
  - **Validates: Requirements 7.3**

- [x] 4.9 Add relation managers
  - Create UsersRelationManager
  - Create PropertiesRelationManager
  - Create SubscriptionsRelationManager
  - Create ActivityLogsRelationManager
  - _Requirements: 2.3_

- [x] 5. Enhance SubscriptionResource
- [x] 5.1 Update form schema
  - Add Subscription Details section
  - Add Subscription Period section with date validation
  - Add Limits section with numeric validation
  - Implement live updates for plan type selection
  - _Requirements: 3.1, 3.2_

- [x] 5.2 Update table columns and filters
  - Add all required columns with badges
  - Implement days_until_expiry calculated column
  - Add color coding for expiry dates
  - Create filters for plan, status, expiry
  - _Requirements: 3.3_

- [x] 5.3 Add custom actions
  - Create Renew action with date form
  - Create Suspend action
  - Create Activate action
  - Create View Usage action
  - Create Send Renewal Reminder action
  - _Requirements: 3.2, 3.4, 3.5, 8.1_

- [ ]* 5.4 Write property test for subscription status transitions
  - **Property 3: Subscription status transitions**
  - **Validates: Requirements 3.2, 3.4, 3.5**

- [ ]* 5.5 Write property test for subscription renewal date extension
  - **Property 16: Subscription renewal date extension**
  - **Validates: Requirements 3.4, 8.3**

- [x] 5.6 Add bulk actions
  - Create BulkRenewAction with duration selection
  - Create BulkSuspendAction
  - Create BulkActivateAction
  - Create BulkExportAction
  - _Requirements: 3.3_

- [x] 6. Enhance OrganizationActivityLogResource
- [x] 6.1 Update table columns and filters
  - Add all required columns with proper formatting
  - Implement action badge with color coding
  - Add date range filter
  - Add organization, user, action type filters
  - _Requirements: 4.1, 4.2_

- [x] 6.2 Enhance view page
  - Display full context data in formatted JSON
  - Show related actions within time window
  - Display user session information
  - Add links to organization and user profiles
  - _Requirements: 4.4_

- [x] 6.3 Add export functionality
  - Create CSV export action
  - Create JSON export action
  - Implement bulk export
  - _Requirements: 4.5_

- [ ]* 6.4 Write property test for activity log completeness
  - **Property 4: Activity log completeness**
  - **Validates: Requirements 4.1, 16.1**

- [ ]* 6.5 Write property test for activity log filtering correctness
  - **Property 14: Activity log filtering correctness**
  - **Validates: Requirements 4.2**

- [x] 7. Create PlatformUserResource
- [x] 7.1 Create resource with form schema
  - Add User Details section
  - Add Status section
  - Implement organization relationship
  - Add role selection
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 7.2 Create table with columns and filters
  - Add all required columns
  - Implement role and status badges
  - Add filters for role, organization, status, last login
  - _Requirements: 5.1, 5.2_

- [x] 7.3 Add custom actions
  - Create Reset Password action
  - Create Deactivate action
  - Create Reactivate action
  - Create Impersonate action
  - Create View Activity action
  - _Requirements: 5.3, 5.4, 5.5, 11.1_

- [ ]* 7.4 Write property test for user password reset security
  - **Property 17: User password reset security**
  - **Validates: Requirements 5.4**

- [ ]* 7.5 Write property test for impersonation audit trail
  - **Property 7: Impersonation audit trail**
  - **Validates: Requirements 11.1, 11.4, 16.1**

- [x] 7.6 Add bulk actions
  - Create BulkDeactivateAction
  - Create BulkReactivateAction
  - Create BulkSendNotificationAction
  - Create BulkExportAction
  - _Requirements: 5.5_

- [x] 8. Create OrganizationInvitationResource
- [x] 8.1 Create resource with form schema
  - Add Invitation Details section
  - Implement plan type selection
  - Add expiry date with default (7 days)
  - _Requirements: 13.1, 13.2_

- [x] 8.2 Create table with columns and filters
  - Add all required columns with badges
  - Implement status badge (pending/accepted/expired)
  - Add filters for status, plan, expiry
  - _Requirements: 13.5_

- [x] 8.3 Add custom actions
  - Create Resend action with new token generation
  - Create Cancel action
  - Implement invitation acceptance flow
  - _Requirements: 13.2, 13.3, 13.4_

- [ ]* 8.4 Write property test for invitation token uniqueness
  - **Property 11: Invitation token uniqueness**
  - **Validates: Requirements 13.2**

- [x] 8.5 Add bulk actions
  - Create BulkResendAction
  - Create BulkCancelAction
  - Create BulkDeleteAction (expired only)
  - _Requirements: 13.5_

- [x] 9. Create SystemHealth page
- [x] 9.1 Create page with database health section
  - Display connection status
  - Show active connections count
  - Display slow query log
  - Show table sizes and growth rates
  - _Requirements: 6.1_

- [x] 9.2 Add backup status section
  - Display last backup timestamp
  - Show backup size and location
  - Display success/failure status
  - Add manual backup trigger button
  - _Requirements: 6.2_

- [x] 9.3 Add queue status section
  - Display pending jobs count by queue
  - Show failed jobs with retry button
  - Display average processing time
  - Add queue pause/resume controls
  - _Requirements: 6.3_

- [x] 9.4 Add storage metrics section
  - Display disk usage (total, used, available)
  - Show database size
  - Display log file sizes
  - Show growth trend chart
  - _Requirements: 6.4_

- [x] 9.5 Add cache status section
  - Display Redis connection status
  - Show cache hit rate
  - Display memory usage
  - Add cache clear button
  - _Requirements: 6.4_

- [x] 9.6 Add health check actions
  - Create Run Health Check action
  - Create Trigger Manual Backup action
  - Create Clear Cache action
  - Create Restart Queue Workers action
  - Create Download Diagnostic Report action
  - _Requirements: 6.5_

- [x] 10. Create PlatformAnalytics page
- [x] 10.1 Create page with organization analytics section
  - Display growth chart (new organizations over time)
  - Show plan distribution pie chart
  - Display active vs. inactive organizations
  - Show top organizations by properties, users, invoices
  - _Requirements: 9.1, 9.4_

- [x] 10.2 Add subscription analytics section
  - Display renewal rate
  - Show expiry forecast (next 90 days)
  - Display plan upgrade/downgrade trends
  - Show subscription lifecycle chart
  - _Requirements: 9.1_

- [x] 10.3 Add usage analytics section
  - Display total properties, buildings, meters, invoices
  - Show growth trends (daily/weekly/monthly)
  - Display feature usage heatmap
  - Show peak activity times
  - _Requirements: 9.1, 9.3_

- [x] 10.4 Add user analytics section
  - Display total users by role
  - Show active users (last 7/30/90 days)
  - Display login frequency distribution
  - Show user growth trends
  - _Requirements: 9.3_

- [x] 10.5 Add export functionality
  - Create Export to PDF action (executive summary)
  - Create Export to CSV action (raw data)
  - Implement scheduled automated reports
  - _Requirements: 9.5, 12.4_

- [x] 11. Create SystemSettings page
- [x] 11.1 Create page with email configuration section
  - Add SMTP server settings form
  - Add sender name and address fields
  - Add notification templates editor
  - Add test email button
  - _Requirements: 10.2_

- [x] 11.2 Add backup configuration section
  - Add backup schedule (cron expression) field
  - Add retention period field
  - Add storage location field
  - Add backup notifications toggle
  - _Requirements: 10.3_

- [x] 11.3 Add queue configuration section
  - Add default queue connection field
  - Add queue priorities field
  - Add retry attempts field
  - Add timeout settings field
  - _Requirements: 10.3_

- [x] 11.4 Add feature flags section
  - Add global feature toggles
  - Add per-organization feature overrides
  - Add beta feature access controls
  - _Requirements: 10.4_

- [x] 11.5 Add platform settings section
  - Add default timezone field
  - Add default locale field
  - Add default currency field
  - Add session timeout field
  - Add password policy fields
  - _Requirements: 10.1_

- [x] 11.6 Add configuration actions
  - Create Save Configuration action
  - Create Reset to Defaults action
  - Create Export Configuration action (JSON)
  - Create Import Configuration action
  - _Requirements: 10.5_

- [-] 12. Implement impersonation system
- [x] 12.1 Create ImpersonationService
  - Implement startImpersonation method with audit logging
  - Implement endImpersonation method with session cleanup
  - Add impersonation banner component
  - Add automatic timeout (30 minutes)
  - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [-] 12.2 Add impersonation middleware
  - Create middleware to detect impersonation mode
  - Add banner display logic
  - Implement context switching
  - _Requirements: 11.3_

- [ ] 12.3 Create impersonation history view
  - Display all past impersonation sessions
  - Show duration and actions taken
  - Add filtering by superadmin, target user, date
  - _Requirements: 11.5_

- [ ] 13. Implement notification system
- [ ] 13.1 Create SendPlatformNotificationAction
  - Add targeting form (all, specific plans, individual orgs)
  - Add rich text editor for message composition
  - Add scheduling options (immediate/scheduled)
  - Implement email and in-app delivery
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [ ] 13.2 Create notification history view
  - Display all sent notifications
  - Show delivery status and read receipts
  - Add filtering by date, target, status
  - _Requirements: 15.5_

- [ ]* 13.3 Write property test for notification delivery targeting
  - **Property 20: Notification delivery targeting**
  - **Validates: Requirements 15.1, 15.4**

- [ ] 14. Implement global search
- [ ] 14.1 Create GlobalSearchProvider
  - Implement search across organizations, users, properties, buildings, meters, invoices
  - Add result grouping by resource type
  - Implement result ranking by relevance
  - _Requirements: 14.1, 14.2, 14.4_

- [ ] 14.2 Add search UI component
  - Create search input with autocomplete
  - Display grouped results with counts
  - Add navigation to detailed views
  - Implement search suggestions
  - _Requirements: 14.2, 14.3, 14.5_

- [ ]* 14.3 Write property test for search result accuracy
  - **Property 9: Search result accuracy**
  - **Validates: Requirements 14.1, 14.4**

- [ ] 15. Implement dashboard customization
- [ ] 15.1 Create DashboardCustomizationService
  - Implement widget add/remove functionality
  - Add widget rearrangement (drag-and-drop)
  - Add widget size adjustment
  - Add refresh interval configuration
  - _Requirements: 17.1, 17.2_

- [ ] 15.2 Add customization UI
  - Create customization mode toggle
  - Add widget library panel
  - Implement drag-and-drop interface
  - Add widget configuration modals
  - _Requirements: 17.1, 17.2_

- [ ] 15.3 Implement layout persistence
  - Save layout to user preferences
  - Load layout on dashboard access
  - Add reset to default option
  - _Requirements: 17.3, 17.4_

- [ ]* 15.4 Write property test for dashboard customization persistence
  - **Property 18: Dashboard customization persistence**
  - **Validates: Requirements 17.3**

- [ ] 15.5 Add layout sharing
  - Create export layout action
  - Create import layout action
  - _Requirements: 17.5_

- [ ] 16. Implement data export system
- [ ] 16.1 Create ExportService
  - Implement CSV export for organizations
  - Implement CSV export for subscriptions
  - Implement CSV export for activity logs
  - Implement Excel export with formatting
  - _Requirements: 12.1, 12.2, 12.3_

- [ ] 16.2 Create PDF report generator
  - Implement PDF generation with charts
  - Add executive summary template
  - Add table formatting
  - _Requirements: 12.4_

- [ ] 16.3 Add scheduled export functionality
  - Create scheduled export configuration
  - Implement automated daily/weekly/monthly exports
  - Add email delivery
  - _Requirements: 12.5_

- [ ]* 16.4 Write property test for export data completeness
  - **Property 19: Export data completeness**
  - **Validates: Requirements 12.1, 12.2, 12.3**

- [ ] 17. Implement subscription automation
- [ ] 17.1 Create SubscriptionAutomationService
  - Implement expiry notification scheduling (30, 14, 7 days)
  - Add auto-renewal configuration
  - Implement auto-renewal execution
  - Add renewal failure handling
  - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [ ] 17.2 Create subscription monitoring command
  - Create artisan command to check expiring subscriptions
  - Add notification queueing
  - Add auto-renewal triggering
  - Schedule command to run daily
  - _Requirements: 8.1, 8.2, 8.3_

- [ ] 17.3 Create renewal history view
  - Display all past renewals
  - Show dates, durations, triggering method
  - Add filtering by organization, date, method
  - _Requirements: 8.5_

- [ ] 18. Implement caching and performance optimization
- [ ] 18.1 Add Redis caching for dashboard metrics
  - Cache subscription stats (60s TTL)
  - Cache organization stats (5min TTL)
  - Cache system health metrics (30s TTL)
  - Implement cache warming
  - _Requirements: 18.1_

- [ ] 18.2 Add database query optimization
  - Add indexes on frequently queried columns
  - Implement eager loading for relationships
  - Add query result caching
  - _Requirements: 18.3_

- [ ] 18.3 Implement background processing
  - Queue bulk operations
  - Queue export generation
  - Queue activity log cleanup
  - Queue subscription expiry checks
  - _Requirements: 18.2_

- [ ] 18.4 Add frontend optimization
  - Implement Livewire lazy loading for widgets
  - Add asset bundling and minification
  - Optimize Chart.js rendering
  - _Requirements: 18.4, 18.5_

- [ ] 19. Add authorization and security
- [ ] 19.1 Create OrganizationPolicy enhancements
  - Add viewAny, view, create, update, delete methods
  - Add suspend, reactivate, impersonate methods
  - Ensure superadmin-only access
  - _Requirements: 2.1, 2.2, 2.5, 11.1_

- [ ] 19.2 Create SubscriptionPolicy enhancements
  - Add viewAny, view, create, update, delete methods
  - Add renew, suspend, activate methods
  - Ensure superadmin-only access
  - _Requirements: 3.1, 3.2, 3.4, 3.5_

- [ ] 19.3 Create PlatformUserPolicy
  - Add viewAny, view, update methods
  - Add resetPassword, deactivate, reactivate, impersonate methods
  - Ensure superadmin-only access
  - _Requirements: 5.1, 5.3, 5.4, 5.5_

- [ ] 19.4 Add rate limiting
  - Implement rate limiting for dashboard API endpoints
  - Add rate limiting for bulk operations
  - Add rate limiting for exports
  - Add rate limiting for password resets
  - _Requirements: Security considerations_

- [ ] 19.5 Add audit logging for superadmin actions
  - Create observer for Organization model
  - Create observer for Subscription model
  - Create observer for User model
  - Log all superadmin actions with IP and user agent
  - _Requirements: 16.1, 16.2_

- [ ] 20. Create comprehensive test suite
- [ ]* 20.1 Write unit tests for models
  - Test Organization methods (isSuspended, suspend, reactivate, daysUntilExpiry)
  - Test Subscription methods (renew, suspend, activate, daysUntilExpiry)
  - Test OrganizationInvitation methods (accept, cancel, resend)
  - Test SystemHealthMetric methods (isHealthy, getStatusColor)

- [ ]* 20.2 Write unit tests for services
  - Test ImpersonationService (start, end, audit)
  - Test SubscriptionAutomationService (notifications, auto-renewal)
  - Test ExportService (CSV, Excel, PDF generation)
  - Test DashboardCustomizationService (add, remove, rearrange widgets)

- [ ]* 20.3 Write integration tests for dashboard
  - Test dashboard page load with all widgets
  - Test widget data fetching and caching
  - Test quick action buttons
  - Test dashboard export

- [ ]* 20.4 Write integration tests for CRUD workflows
  - Test organization create-read-update-delete flow
  - Test subscription create-read-update-delete flow
  - Test invitation create-send-accept flow
  - Test user management workflows

- [ ]* 20.5 Write integration tests for bulk operations
  - Test bulk suspend organizations
  - Test bulk change plan
  - Test bulk renew subscriptions
  - Test bulk export

- [ ]* 20.6 Write integration tests for impersonation
  - Test impersonation start with audit logging
  - Test impersonation context switching
  - Test impersonation end with cleanup
  - Test impersonation timeout

- [ ]* 20.7 Write Filament-specific tests
  - Test resource authorization (superadmin-only)
  - Test form validation for all resources
  - Test table filtering and sorting
  - Test bulk actions execution
  - Test relation managers

- [ ]* 20.8 Write performance tests
  - Test dashboard load time (<500ms)
  - Test pagination with large datasets
  - Test bulk operations with 100+ records
  - Test search performance (<1s)

- [ ] 21. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 22. Update documentation and translations
- [ ] 22.1 Update translation files
  - Add English translations for all new UI elements
  - Add Lithuanian translations
  - Add Russian translations
  - _Requirements: All_

- [ ] 22.2 Create user documentation
  - Write superadmin dashboard guide
  - Document organization management workflows
  - Document subscription management workflows
  - Document system health monitoring
  - Document analytics and reporting

- [ ] 22.3 Create technical documentation
  - Document architecture and component structure
  - Document caching strategy
  - Document background processing
  - Document security considerations
  - Document performance optimization

- [ ] 23. Final integration and polish
- [ ] 23.1 Add navigation menu items
  - Add dashboard link to superadmin navigation
  - Add system health link
  - Add analytics link
  - Add settings link
  - Ensure proper ordering and icons

- [ ] 23.2 Add breadcrumbs
  - Implement breadcrumbs for all pages
  - Add proper navigation hierarchy
  - _Requirements: User experience_

- [ ] 23.3 Add loading states and error handling
  - Add loading spinners for widgets
  - Add error messages for failed operations
  - Add retry mechanisms for failed requests
  - Add user-friendly error pages

- [ ] 23.4 Perform accessibility audit
  - Test keyboard navigation
  - Test screen reader compatibility
  - Verify color contrast
  - Test focus indicators

- [ ] 23.5 Perform final testing
  - Test all CRUD operations
  - Test all bulk actions
  - Test all custom actions
  - Test impersonation flow
  - Test export functionality
  - Test dashboard customization
  - Verify all translations
