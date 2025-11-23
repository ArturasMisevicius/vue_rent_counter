# Superadmin CRUD Resources

## Overview

Complete Filament CRUD resources for superadmin dashboard management, providing full control over organizations, subscriptions, and activity logs.

## Created Resources

### 1. OrganizationResource (`/admin/organizations`)

**Purpose**: Manage multi-tenant organizations (property management companies)

**Features**:
- Full CRUD operations (Create, Read, Update, Delete)
- Organization details: name, slug, email, phone, domain
- Subscription & limits: plan type, max properties, max users
- Regional settings: timezone, locale, currency
- Status management: active/inactive, suspension with reason
- Trial and subscription end dates
- Usage statistics: properties, users, buildings, invoices
- Remaining quotas display

**Actions**:
- Suspend organization (with reason)
- Reactivate suspended organization
- View detailed organization info with usage stats

**Filters**:
- Plan type (basic, professional, enterprise)
- Active/inactive status
- Expired subscriptions
- Expiring soon (14 days)

**Navigation**: System Management group, visible only to superadmins

---

### 2. SubscriptionResource (`/admin/subscriptions`)

**Purpose**: Manage organization subscriptions and limits

**Features**:
- Full CRUD operations
- Subscription details: plan type, status, dates
- Limits: max properties, max tenants
- Usage tracking: properties used/remaining, tenants used/remaining
- Days until expiry calculation
- Organization association

**Actions**:
- Renew subscription (set new expiration date)
- Suspend active subscription
- Activate suspended/expired subscription

**Filters**:
- Plan type (basic, professional, enterprise)
- Status (active, expired, suspended, cancelled)
- Expiring soon (14 days)
- Expired subscriptions

**Navigation**: System Management group, visible only to superadmins

---

### 3. OrganizationActivityLogResource (`/admin/organization-activity-logs`)

**Purpose**: Audit trail for all organization activities

**Features**:
- Read-only resource (logs created automatically)
- Activity details: timestamp, organization, user, action
- Resource tracking: type and ID
- Request information: IP address, user agent
- Metadata display (JSON formatted)

**Filters**:
- Organization
- User
- Action type (create, update, delete, view)
- Date range

**Navigation**: System Management group, visible only to superadmins

**Note**: Cannot create or edit logs manually - they're generated automatically by the system

---

## Policies

### OrganizationPolicy
- All operations restricted to superadmin only
- Enforces tenant isolation at policy level

### SubscriptionPolicy (Enhanced)
- Superadmin: full access to all subscriptions
- Admin: can view and renew their own subscription
- All modifications restricted to superadmin

### OrganizationActivityLogPolicy
- View: superadmin only
- Create/Update: disabled (automatic logging)
- Delete: superadmin only

---

## Integration Points

### AppServiceProvider
Policies registered in `app/Providers/AppServiceProvider.php`:
```php
protected $policies = [
    // ... existing policies
    \App\Models\Organization::class => \App\Policies\OrganizationPolicy::class,
    \App\Models\OrganizationActivityLog::class => \App\Policies\OrganizationActivityLogPolicy::class,
    \App\Models\Subscription::class => \App\Policies\SubscriptionPolicy::class,
];
```

### Routes
- Filament routes: `/admin/organizations`, `/admin/subscriptions`, `/admin/organization-activity-logs`
- Legacy routes: `/superadmin/organizations`, `/superadmin/subscriptions` (still functional)

### Navigation
All resources grouped under "System Management" in Filament sidebar, visible only when:
```php
auth()->user()?->isSuperadmin() ?? false
```

---

## Usage Examples

### Creating an Organization
1. Navigate to `/admin/organizations`
2. Click "New Organization"
3. Fill in organization details
4. Select plan type (auto-populates limits)
5. Set subscription dates
6. Configure regional settings
7. Save

### Managing Subscriptions
1. Navigate to `/admin/subscriptions`
2. Filter by status or expiring soon
3. Click on subscription to view details
4. Use actions to renew, suspend, or activate
5. View usage statistics vs limits

### Viewing Activity Logs
1. Navigate to `/admin/organization-activity-logs`
2. Filter by organization, user, or date range
3. Click on log entry to view full details
4. Review metadata and request information

---

## Data Models

### Organization
- Primary tenant entity in multi-tenancy architecture
- Tracks plan, limits, settings, features
- Auto-generates unique slug from name
- Initializes default settings and features on creation

### Subscription
- Belongs to User (admin role)
- Tracks plan type, status, dates, limits
- Provides helper methods: `isActive()`, `isExpired()`, `daysUntilExpiry()`
- Enforces property and tenant quotas

### OrganizationActivityLog
- Immutable audit trail
- Captures action, resource, metadata
- Records IP address and user agent
- Automatic creation via `OrganizationActivityLog::log()`

---

## Security Considerations

1. **Authorization**: All resources check `isSuperadmin()` before display
2. **Tenant Isolation**: Uses `withoutGlobalScopes()` to access cross-tenant data
3. **Audit Trail**: All organization changes logged automatically
4. **Immutable Logs**: Activity logs cannot be edited, only viewed/deleted by superadmin
5. **Suspension Tracking**: Suspension reason and timestamp captured for accountability

---

## Testing

Run Filament resource tests:
```bash
php artisan test --filter=Filament
```

Verify routes:
```bash
php artisan route:list --path=admin | grep -E "(organization|subscription)"
```

Check policies:
```bash
php artisan tinker
>>> $user = User::where('role', 'superadmin')->first();
>>> Gate::forUser($user)->allows('viewAny', Organization::class);
```

---

## Future Enhancements

- [ ] Bulk organization operations (suspend, reactivate)
- [ ] Subscription renewal reminders (automated emails)
- [ ] Organization usage charts and analytics
- [ ] Export activity logs to CSV/PDF
- [ ] Organization invitation system integration
- [ ] Custom plan creation interface
