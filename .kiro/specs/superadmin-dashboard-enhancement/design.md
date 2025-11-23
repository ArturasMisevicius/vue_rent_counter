# Design Document

## Overview

The Superadmin Dashboard Enhancement transforms the existing basic superadmin functionality into a comprehensive administrative control center. This design leverages Filament v4 resources, widgets, and pages to provide complete CRUD operations, real-time monitoring, analytics, and operational tools for managing the entire Vilnius Utilities Billing Platform.

The design follows a modular architecture where each major functional area (Organizations, Subscriptions, Activity Logs, System Health) is implemented as a dedicated Filament resource or page with specialized widgets, actions, and relation managers. The dashboard serves as the central hub, aggregating key metrics and providing quick access to critical operations.

## Architecture

### Component Structure

```
app/Filament/
├── Pages/
│   ├── SuperadminDashboard.php          # Main dashboard with widgets
│   ├── SystemHealth.php                  # System health monitoring page
│   ├── PlatformAnalytics.php            # Analytics and reporting page
│   └── SystemSettings.php                # Configuration management page
├── Resources/
│   ├── OrganizationResource.php          # Enhanced organization CRUD
│   ├── SubscriptionResource.php          # Enhanced subscription CRUD
│   ├── OrganizationActivityLogResource.php # Enhanced activity log viewing
│   ├── PlatformUserResource.php          # Cross-organization user management
│   └── OrganizationInvitationResource.php # Invitation management
├── Widgets/
│   ├── SubscriptionStatsWidget.php       # Subscription metrics
│   ├── OrganizationStatsWidget.php       # Organization metrics
│   ├── SystemHealthWidget.php            # Health indicators
│   ├── ExpiringSubscriptionsWidget.php   # Expiring subscriptions table
│   ├── RecentActivityWidget.php          # Recent activity feed
│   ├── TopOrganizationsWidget.php        # Top organizations chart
│   └── PlatformUsageWidget.php           # Platform-wide usage chart
└── Actions/
    ├── ImpersonateUserAction.php         # User impersonation
    ├── BulkSuspendOrganizationsAction.php # Bulk suspend
    ├── BulkChangePlanAction.php          # Bulk plan change
    └── SendPlatformNotificationAction.php # Platform notifications
```

### Data Flow

1. **Dashboard Loading**: SuperadminDashboard page loads widgets that fetch cached metrics from Redis
2. **CRUD Operations**: Filament resources handle form validation, authorization via policies, and model persistence
3. **Bulk Actions**: Custom bulk actions process multiple records with transaction safety and rollback on errors
4. **Real-time Updates**: Widgets use Livewire polling to refresh metrics every 30-60 seconds
5. **Activity Logging**: Observer pattern captures all superadmin actions and stores them in OrganizationActivityLog
6. **Impersonation**: Session-based context switching with audit trail and automatic timeout

### Technology Stack

- **Filament v4**: Admin panel framework for resources, pages, widgets, and actions
- **Livewire**: Real-time component updates and interactivity
- **Alpine.js**: Client-side interactions for charts and dynamic UI
- **Chart.js**: Data visualization for analytics widgets
- **Redis**: Caching layer for dashboard metrics and session data
- **Laravel Queue**: Background processing for bulk operations and exports
- **Spatie Laravel Backup**: Backup monitoring and management
- **Laravel Horizon**: Queue monitoring (optional)

## Components and Interfaces

### 1. SuperadminDashboard Page

**Purpose**: Central hub displaying key metrics, health indicators, and quick actions.

**Widgets**:
- `SubscriptionStatsWidget`: Displays total, active, expired, suspended subscriptions with color-coded badges
- `OrganizationStatsWidget`: Shows total organizations, active/inactive counts, and growth trends
- `SystemHealthWidget`: Real-time health indicators for database, backups, queues, and storage
- `ExpiringSubscriptionsWidget`: Table of subscriptions expiring within 14 days with renewal actions
- `RecentActivityWidget`: Feed of recent superadmin and organization actions
- `TopOrganizationsWidget`: Bar chart of top 10 organizations by property count
- `PlatformUsageWidget`: Line chart showing platform growth over time

**Actions**:
- Quick links to create organization, create subscription, view all activity logs
- System health check button triggering diagnostic tests
- Export dashboard data to PDF

**Layout**: 3-column grid with responsive breakpoints, widgets arranged by priority (metrics top, tables middle, charts bottom)

### 2. Enhanced OrganizationResource

**Form Schema**:
```php
Section::make('Organization Details')
    - TextInput::make('name')->required()
    - TextInput::make('slug')->required()->unique()
    - TextInput::make('email')->email()->required()
    - TextInput::make('phone')->tel()
    - TextInput::make('domain')

Section::make('Subscription & Limits')
    - Select::make('plan')->options(['basic', 'professional', 'enterprise'])
    - TextInput::make('max_properties')->numeric()->required()
    - TextInput::make('max_users')->numeric()->required()
    - DateTimePicker::make('trial_ends_at')
    - DateTimePicker::make('subscription_ends_at')->required()

Section::make('Regional Settings')
    - Select::make('timezone')->options([...])
    - Select::make('locale')->options(['lt', 'en', 'ru'])
    - Select::make('currency')->options(['EUR', 'USD'])

Section::make('Status')
    - Toggle::make('is_active')->default(true)
    - DateTimePicker::make('suspended_at')->disabled()
    - Textarea::make('suspension_reason')->disabled()
```

**Table Columns**:
- TextColumn: name, email, plan (badge), users_count, properties_count
- IconColumn: is_active (boolean)
- TextColumn: subscription_ends_at (color-coded by expiry)
- TextColumn: created_at (toggleable)

**Filters**:
- SelectFilter: plan type
- TernaryFilter: is_active
- Filter: subscription_expired, expiring_soon (14 days)

**Actions**:
- View, Edit, Delete (with dependency validation)
- Suspend (with reason form)
- Reactivate
- Impersonate (switches to organization admin context)
- View Analytics (navigates to analytics page filtered by organization)

**Bulk Actions**:
- Bulk Suspend (with confirmation and reason)
- Bulk Reactivate
- Bulk Change Plan (with plan selection form)
- Bulk Export (CSV/Excel)

**Relation Managers**:
- UsersRelationManager: Shows all users in the organization
- PropertiesRelationManager: Shows all properties owned by organization
- SubscriptionsRelationManager: Shows subscription history
- ActivityLogsRelationManager: Shows organization-specific activity logs

### 3. Enhanced SubscriptionResource

**Form Schema**:
```php
Section::make('Subscription Details')
    - Select::make('user_id')->relationship('user', 'organization_name')->searchable()
    - Select::make('plan_type')->options([...])->live()
    - Select::make('status')->options(['active', 'expired', 'suspended', 'cancelled'])

Section::make('Subscription Period')
    - DateTimePicker::make('starts_at')->required()
    - DateTimePicker::make('expires_at')->required()->after('starts_at')

Section::make('Limits')
    - TextInput::make('max_properties')->numeric()->required()
    - TextInput::make('max_tenants')->numeric()->required()
```

**Table Columns**:
- TextColumn: user.organization_name, plan_type (badge), status (badge)
- TextColumn: starts_at, expires_at (color-coded)
- TextColumn: days_until_expiry (calculated, color-coded)
- TextColumn: max_properties, max_tenants (toggleable)

**Filters**:
- SelectFilter: plan_type, status
- Filter: expiring_soon (14 days), expired

**Actions**:
- View, Edit, Delete
- Renew (with new expiry date form)
- Suspend, Activate
- View Usage (shows current vs. limit metrics)
- Send Renewal Reminder (triggers email notification)

**Bulk Actions**:
- Bulk Renew (with duration selection)
- Bulk Suspend
- Bulk Activate
- Bulk Export

### 4. Enhanced OrganizationActivityLogResource

**Table Columns**:
- TextColumn: created_at (timestamp), organization.name, user.name
- TextColumn: action (badge with color coding), resource_type, resource_id
- TextColumn: ip_address (toggleable)

**Filters**:
- SelectFilter: organization_id, user_id, action type
- DateRangeFilter: created_at (from/until)

**Actions**:
- View (shows full details including before/after data)
- Export (CSV/JSON)

**Bulk Actions**:
- Bulk Export
- Bulk Delete (with confirmation, for old logs)

**View Page Enhancements**:
- Display full context data in formatted JSON
- Show related actions (actions on same resource within time window)
- Display user session information
- Link to organization and user profiles

### 5. PlatformUserResource

**Purpose**: Manage users across all organizations from a single interface.

**Form Schema**:
```php
Section::make('User Details')
    - TextInput::make('name')->required()
    - TextInput::make('email')->email()->required()->unique()
    - Select::make('role')->options([...])->required()
    - Select::make('organization_id')->relationship('organization', 'name')->searchable()

Section::make('Status')
    - Toggle::make('is_active')->default(true)
    - DateTimePicker::make('last_login_at')->disabled()
    - DateTimePicker::make('email_verified_at')
```

**Table Columns**:
- TextColumn: name, email, role (badge), organization.name
- IconColumn: is_active, email_verified_at (boolean)
- TextColumn: last_login_at, created_at

**Filters**:
- SelectFilter: role, organization_id
- TernaryFilter: is_active, email_verified
- Filter: last_login (within 7/30/90 days)

**Actions**:
- View, Edit, Delete
- Reset Password (generates temporary password)
- Deactivate, Reactivate
- Impersonate (for support)
- View Activity (filtered activity logs)

**Bulk Actions**:
- Bulk Deactivate
- Bulk Reactivate
- Bulk Send Notification
- Bulk Export

### 6. OrganizationInvitationResource

**Form Schema**:
```php
Section::make('Invitation Details')
    - TextInput::make('organization_name')->required()
    - TextInput::make('admin_email')->email()->required()
    - Select::make('plan_type')->options([...])->required()
    - TextInput::make('max_properties')->numeric()->required()
    - TextInput::make('max_users')->numeric()->required()
    - DateTimePicker::make('expires_at')->default(now()->addDays(7))
```

**Table Columns**:
- TextColumn: organization_name, admin_email, plan_type (badge)
- TextColumn: status (badge: pending/accepted/expired)
- TextColumn: created_at, expires_at, accepted_at

**Filters**:
- SelectFilter: status, plan_type
- Filter: expiring_soon, expired

**Actions**:
- View, Edit (only for pending), Delete
- Resend (generates new token and sends email)
- Cancel (marks as cancelled)

**Bulk Actions**:
- Bulk Resend
- Bulk Cancel
- Bulk Delete (expired only)

### 7. SystemHealth Page

**Purpose**: Real-time monitoring of platform health and infrastructure.

**Sections**:

**Database Health**:
- Connection status (green/red indicator)
- Active connections count
- Slow query log (queries > 1s)
- Table sizes and growth rates
- Index usage statistics

**Backup Status**:
- Last backup timestamp
- Backup size and location
- Success/failure status
- Next scheduled backup
- Manual backup trigger button

**Queue Status**:
- Pending jobs count by queue
- Failed jobs count with retry button
- Average processing time
- Worker status (running/stopped)
- Queue pause/resume controls

**Storage Metrics**:
- Disk usage (total, used, available)
- Database size
- Log file sizes
- Backup storage usage
- Growth trend chart (30 days)

**Cache Status**:
- Redis connection status
- Cache hit rate
- Memory usage
- Key count
- Cache clear button

**Actions**:
- Run Health Check (executes diagnostic tests)
- Trigger Manual Backup
- Clear Cache
- Restart Queue Workers
- Download Diagnostic Report (PDF)

### 8. PlatformAnalytics Page

**Purpose**: Comprehensive analytics and reporting for platform usage.

**Sections**:

**Organization Analytics**:
- Growth chart (new organizations over time)
- Plan distribution pie chart
- Active vs. inactive organizations
- Top organizations by properties, users, invoices
- Organization churn rate

**Subscription Analytics**:
- Renewal rate
- Expiry forecast (next 90 days)
- Plan upgrade/downgrade trends
- Revenue projection (if pricing data available)
- Subscription lifecycle chart

**Usage Analytics**:
- Total properties, buildings, meters, invoices
- Growth trends (daily/weekly/monthly)
- Feature usage heatmap
- Peak activity times
- Geographic distribution (if location data available)

**User Analytics**:
- Total users by role
- Active users (last 7/30/90 days)
- Login frequency distribution
- User growth trends
- Session duration averages

**Export Options**:
- Export to PDF (executive summary with charts)
- Export to CSV (raw data)
- Schedule automated reports (daily/weekly/monthly)

### 9. SystemSettings Page

**Purpose**: Manage platform-wide configuration settings.

**Sections**:

**Email Configuration**:
- SMTP server settings
- Sender name and address
- Notification templates
- Test email button

**Backup Configuration**:
- Backup schedule (cron expression)
- Retention period (days)
- Storage location
- Backup notifications

**Queue Configuration**:
- Default queue connection
- Queue priorities
- Retry attempts
- Timeout settings

**Feature Flags**:
- Enable/disable features globally
- Per-organization feature overrides
- Beta feature access

**Platform Settings**:
- Default timezone
- Default locale
- Default currency
- Session timeout
- Password policy

**Actions**:
- Save Configuration
- Reset to Defaults
- Export Configuration (JSON)
- Import Configuration

## Data Models

### Organization (Enhanced)

```php
class Organization extends Model
{
    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'domain',
        'plan', 'max_properties', 'max_users',
        'trial_ends_at', 'subscription_ends_at',
        'timezone', 'locale', 'currency',
        'is_active', 'suspended_at', 'suspension_reason'
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    public function properties(): HasMany
    public function subscriptions(): HasMany
    public function activityLogs(): HasMany
    
    public function isSuspended(): bool
    public function suspend(string $reason): void
    public function reactivate(): void
    public function isTrialActive(): bool
    public function isSubscriptionActive(): bool
    public function daysUntilExpiry(): int
}
```

### Subscription (Enhanced)

```php
class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_type', 'status',
        'starts_at', 'expires_at',
        'max_properties', 'max_tenants',
        'auto_renew', 'renewal_period'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function user(): BelongsTo
    public function renewals(): HasMany
    
    public function isActive(): bool
    public function isExpired(): bool
    public function isSuspended(): bool
    public function daysUntilExpiry(): int
    public function renew(Carbon $newExpiryDate): void
    public function suspend(): void
    public function activate(): void
}
```

### OrganizationActivityLog (Enhanced)

```php
class OrganizationActivityLog extends Model
{
    protected $fillable = [
        'organization_id', 'user_id', 'action',
        'resource_type', 'resource_id',
        'before_data', 'after_data',
        'ip_address', 'user_agent'
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    public function organization(): BelongsTo
    public function user(): BelongsTo
    
    public function getChanges(): array
    public function getResourceUrl(): ?string
}
```

### OrganizationInvitation (New)

```php
class OrganizationInvitation extends Model
{
    protected $fillable = [
        'organization_name', 'admin_email', 'plan_type',
        'max_properties', 'max_users',
        'token', 'status', 'expires_at', 'accepted_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function isPending(): bool
    public function isExpired(): bool
    public function isAccepted(): bool
    public function accept(): Organization
    public function cancel(): void
    public function resend(): void
}
```

### SystemHealthMetric (New)

```php
class SystemHealthMetric extends Model
{
    protected $fillable = [
        'metric_type', 'metric_name', 'value',
        'status', 'checked_at'
    ];

    protected $casts = [
        'value' => 'array',
        'checked_at' => 'datetime',
    ];

    public function isHealthy(): bool
    public function getStatusColor(): string
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Dashboard metrics consistency

*For any* dashboard load, the sum of active, expired, suspended, and cancelled subscriptions should equal the total subscription count.

**Validates: Requirements 1.2**

### Property 2: Organization resource limit enforcement

*For any* organization, the current count of properties and users should never exceed the max_properties and max_users limits defined in their subscription.

**Validates: Requirements 2.1, 3.1**

### Property 3: Subscription status transitions

*For any* subscription, status transitions should follow valid state machine rules: active can become expired/suspended/cancelled, expired can become active (renewal), suspended can become active/cancelled, cancelled is terminal.

**Validates: Requirements 3.2, 3.4, 3.5**

### Property 4: Activity log completeness

*For any* superadmin action (create, update, delete, suspend, reactivate), an activity log entry should be created with complete before/after data.

**Validates: Requirements 4.1, 16.1**

### Property 5: Bulk operation atomicity

*For any* bulk operation, either all selected records should be updated successfully, or all changes should be rolled back on any failure.

**Validates: Requirements 7.2, 7.3, 7.4, 7.5**

### Property 6: Subscription expiry notification timing

*For any* subscription, expiry notifications should be sent at exactly 30, 14, and 7 days before expiration, with no duplicates.

**Validates: Requirements 8.1**

### Property 7: Impersonation audit trail

*For any* impersonation session, both the start and end should be logged with superadmin identity, target user, reason, and duration.

**Validates: Requirements 11.1, 11.4, 16.1**

### Property 8: Organization suspension cascades

*For any* organization suspension, all associated users should be prevented from login while preserving all data and audit trails.

**Validates: Requirements 2.5, 5.5**

### Property 9: Search result accuracy

*For any* search query, all returned results should match the search term in at least one searchable field (name, email, ID, address).

**Validates: Requirements 14.1, 14.4**

### Property 10: Dashboard widget data freshness

*For any* dashboard widget displaying cached data, the cache should be refreshed within the configured TTL (30-60 seconds) to ensure data accuracy.

**Validates: Requirements 18.1, 18.5**

### Property 11: Invitation token uniqueness

*For any* organization invitation, the generated token should be cryptographically unique and not reused across invitations.

**Validates: Requirements 13.2**

### Property 12: System health status accuracy

*For any* health check, the displayed status (healthy/warning/danger) should accurately reflect the actual system state based on defined thresholds.

**Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5**

### Property 13: Bulk plan change limit updates

*For any* bulk plan change operation, the max_properties and max_users limits should be updated to match the new plan's default limits.

**Validates: Requirements 7.3**

### Property 14: Activity log filtering correctness

*For any* activity log filter combination, all returned logs should match all active filter criteria simultaneously.

**Validates: Requirements 4.2**

### Property 15: Organization deletion dependency validation

*For any* organization deletion attempt, the operation should be blocked if active subscriptions, properties, or users exist.

**Validates: Requirements 2.5**

### Property 16: Subscription renewal date extension

*For any* subscription renewal, the new expiry date should be later than the current expiry date, and status should be set to active.

**Validates: Requirements 3.4, 8.3**

### Property 17: User password reset security

*For any* password reset operation, a secure temporary password should be generated, hashed before storage, and sent only to the user's verified email.

**Validates: Requirements 5.4**

### Property 18: Dashboard customization persistence

*For any* dashboard layout customization, the saved configuration should be restored exactly on next login for that superadmin user.

**Validates: Requirements 17.3**

### Property 19: Export data completeness

*For any* data export operation, all records matching the current filters should be included in the export file.

**Validates: Requirements 12.1, 12.2, 12.3**

### Property 20: Notification delivery targeting

*For any* platform notification, it should be delivered only to organizations matching the targeting criteria (all, specific plans, or individual organizations).

**Validates: Requirements 15.1, 15.4**

## Error Handling

### Validation Errors

- **Form Validation**: Filament form validation displays inline errors with specific field messages
- **Unique Constraint Violations**: Display user-friendly messages for duplicate slugs, emails, or tokens
- **Foreign Key Violations**: Prevent deletion with clear messages about dependencies
- **Date Range Validation**: Ensure start dates are before end dates with helpful error messages

### Authorization Errors

- **Non-Superadmin Access**: Redirect to appropriate dashboard with "Unauthorized" message
- **Impersonation Failures**: Log failed impersonation attempts and display error to superadmin
- **Resource Access Denied**: Display 403 page with explanation of required permissions

### System Errors

- **Database Connection Failures**: Display maintenance page and alert superadmins
- **Cache Connection Failures**: Fall back to database queries and log warning
- **Queue Processing Failures**: Retry failed jobs with exponential backoff, alert after 3 failures
- **Backup Failures**: Send immediate email alert to all superadmins with failure details

### Bulk Operation Errors

- **Partial Failures**: Display summary of successful and failed operations with error details
- **Transaction Rollbacks**: Ensure all-or-nothing semantics with clear rollback messages
- **Timeout Errors**: Queue long-running bulk operations and notify on completion

### Data Integrity Errors

- **Orphaned Records**: Prevent creation of subscriptions without valid organizations
- **Limit Violations**: Block operations that would exceed subscription limits with clear messages
- **State Transition Errors**: Prevent invalid status transitions with explanation of valid states

## Testing Strategy

### Unit Tests

- **Model Methods**: Test all custom methods on Organization, Subscription, OrganizationActivityLog models
- **Service Classes**: Test BulkOperationService, ImpersonationService, NotificationService
- **Validation Rules**: Test custom validation rules for dates, limits, and constraints
- **Helper Functions**: Test dashboard metric calculations, date formatting, status color logic

### Property-Based Tests

The testing strategy uses **fast-check** (JavaScript/TypeScript) or **PHPUnit with Faker** (PHP) for property-based testing. Each property test should run a minimum of 100 iterations.

- **Property 1 Test**: Generate random subscription counts by status, verify sum equals total
- **Property 2 Test**: Generate random organizations with limits, verify current usage never exceeds limits
- **Property 3 Test**: Generate random subscription status transitions, verify all follow valid state machine
- **Property 4 Test**: Generate random superadmin actions, verify all create activity log entries
- **Property 5 Test**: Generate random bulk operations, verify atomicity (all succeed or all rollback)
- **Property 6 Test**: Generate random subscriptions with expiry dates, verify notification timing
- **Property 7 Test**: Generate random impersonation sessions, verify complete audit trail
- **Property 8 Test**: Generate random organization suspensions, verify user login prevention
- **Property 9 Test**: Generate random search queries, verify all results match search term
- **Property 10 Test**: Generate random dashboard loads, verify cache freshness within TTL
- **Property 11 Test**: Generate random invitations, verify token uniqueness
- **Property 12 Test**: Generate random system states, verify health status accuracy
- **Property 13 Test**: Generate random bulk plan changes, verify limit updates
- **Property 14 Test**: Generate random filter combinations, verify all logs match all filters
- **Property 15 Test**: Generate random deletion attempts, verify dependency blocking
- **Property 16 Test**: Generate random renewals, verify date extension and status update
- **Property 17 Test**: Generate random password resets, verify security requirements
- **Property 18 Test**: Generate random dashboard customizations, verify persistence
- **Property 19 Test**: Generate random export operations, verify data completeness
- **Property 20 Test**: Generate random notifications, verify targeting accuracy

### Integration Tests

- **Dashboard Loading**: Test full dashboard page load with all widgets
- **CRUD Workflows**: Test complete create-read-update-delete flows for each resource
- **Bulk Operations**: Test bulk suspend, reactivate, plan change with multiple records
- **Impersonation Flow**: Test full impersonation cycle from start to end
- **Invitation Flow**: Test invitation creation, email sending, acceptance, and organization creation
- **System Health Checks**: Test health check execution and status display
- **Export Operations**: Test CSV/PDF export generation and download

### Filament-Specific Tests

- **Resource Authorization**: Test that only superadmins can access resources
- **Form Validation**: Test all form fields with valid and invalid data
- **Table Filtering**: Test all filters return correct results
- **Bulk Actions**: Test bulk action execution and confirmation dialogs
- **Relation Managers**: Test relation manager data loading and actions
- **Widget Rendering**: Test widget data fetching and display

### Performance Tests

- **Dashboard Load Time**: Verify dashboard loads within 500ms with cached data
- **Large Dataset Pagination**: Test pagination with 10,000+ records
- **Bulk Operation Performance**: Test bulk operations with 100+ records
- **Search Performance**: Test search with large datasets returns within 1 second
- **Widget Refresh Performance**: Test widget polling doesn't degrade performance

### Accessibility Tests

- **Keyboard Navigation**: Test all forms and tables are keyboard accessible
- **Screen Reader Compatibility**: Test ARIA labels and semantic HTML
- **Color Contrast**: Verify all text meets WCAG AA standards
- **Focus Indicators**: Test visible focus indicators on all interactive elements

## Security Considerations

### Authentication & Authorization

- All routes protected by `auth` and `role:superadmin` middleware
- Filament resources use `shouldRegisterNavigation()` to hide from non-superadmins
- Policies enforce superadmin-only access to all CRUD operations
- Impersonation requires explicit confirmation and logs all actions

### Data Protection

- Sensitive data (passwords, tokens) never displayed in activity logs
- Organization data isolated by tenant_id even for superadmins viewing
- Bulk operations use database transactions to prevent partial updates
- Exports sanitize sensitive fields before generation

### Audit Trail

- All superadmin actions logged with IP address, user agent, and timestamp
- Activity logs immutable (no edit/delete except by other superadmins)
- Impersonation sessions tracked with start/end times and actions taken
- Failed authentication attempts logged and monitored

### Rate Limiting

- Dashboard API endpoints rate-limited to 60 requests/minute per superadmin
- Bulk operations rate-limited to 10 operations/minute to prevent abuse
- Export operations rate-limited to 5 exports/minute
- Password reset operations rate-limited to 3 attempts/hour per user

### Input Validation

- All form inputs validated server-side with Laravel validation rules
- SQL injection prevented by Eloquent ORM and parameterized queries
- XSS prevented by Blade template escaping
- CSRF protection on all state-changing operations

## Performance Optimization

### Caching Strategy

- Dashboard metrics cached in Redis with 60-second TTL
- Organization counts cached with 5-minute TTL
- System health metrics cached with 30-second TTL
- Widget data cached per superadmin user to allow customization

### Database Optimization

- Indexes on frequently queried columns: organization_id, user_id, status, created_at
- Eager loading of relationships to prevent N+1 queries
- Database query caching for expensive aggregations
- Pagination for all large datasets (25-100 records per page)

### Background Processing

- Bulk operations queued for background processing
- Export generation queued with email notification on completion
- Activity log cleanup scheduled daily for logs older than 90 days
- Subscription expiry checks scheduled daily with notification queueing

### Frontend Optimization

- Livewire lazy loading for below-the-fold widgets
- Chart.js for client-side rendering of visualizations
- Alpine.js for lightweight interactivity without full page reloads
- Asset bundling and minification for production

### Monitoring

- Laravel Telescope for debugging in development
- Laravel Horizon for queue monitoring in production
- Redis monitoring for cache hit rates
- Database slow query logging for optimization opportunities
