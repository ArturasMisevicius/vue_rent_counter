# Admin Panel Guide

## Overview

The Admin Panel is built with Filament 4 and provides a comprehensive interface for managing properties, buildings, meters, invoices, tariffs, and users. The panel is accessible at `/admin` and requires authentication with appropriate role permissions.

## Access & Authentication

### URL
- **Production**: `https://yourdomain.com/admin`
- **Local Development**: `http://vuerentcounter.test/admin`

### Login
1. Navigate to `/admin/login`
2. Enter your email and password
3. Click "Sign in"

### Roles & Permissions
- **Admin**: Full access to all resources within their tenant scope
- **Manager**: Limited access to operational resources (properties, meters, readings, invoices)
- **Tenant**: Read-only access to their own property and invoices

## Dashboard

The dashboard provides an at-a-glance view of your system:

### Admin Dashboard Stats
- **Total Properties**: Count of properties in your portfolio
- **Total Buildings**: Count of buildings managed
- **Active Tenants**: Number of active tenant accounts
- **Draft Invoices**: Invoices pending finalization
- **Pending Readings**: Meter readings awaiting verification
- **Total Revenue**: Monthly revenue from finalized invoices

### Quick Actions
Direct links to commonly used resources:
- Properties Management
- Buildings Management
- Invoices Management
- Users Management

## Resources

### Properties
**Path**: `/admin/properties`

Manage residential and commercial properties.

#### Features
- Create, edit, and delete properties
- Assign properties to buildings
- View associated meters and tenants
- Track property-specific invoices

#### Fields
- Address (required)
- Building (required, tenant-scoped)
- Floor number
- Apartment/unit number
- Total area (m²)
- Living area (m²)
- Number of residents
- Status (active/inactive)

### Buildings
**Path**: `/admin/buildings`

Manage multi-unit buildings and calculate hot water circulation fees.

#### Features
- Create, edit, and delete buildings
- Calculate hot water circulation (circulation fees)
- View all properties in a building
- Track building-level meters

#### Fields
- Name (required)
- Address (required)
- Total floors
- Total units
- Construction year
- Heating system type
- Water system type

### Meters
**Path**: `/admin/meters`

Manage utility meters for electricity, water, heating, and gas.

#### Features
- Create, edit, and delete meters
- Assign meters to properties
- View meter reading history
- Track meter status and calibration dates

#### Fields
- Meter number (required, unique)
- Meter type (electricity, water, heating, gas)
- Property (required, tenant-scoped)
- Installation date
- Last calibration date
- Status (active/inactive)

### Meter Readings
**Path**: `/admin/meter-readings`

Record and verify meter readings for billing.

#### Features
- Create, edit, and delete readings
- Verify readings
- View reading history
- Audit trail for all changes

#### Fields
- Meter (required, tenant-scoped)
- Reading date (required)
- Reading value (required)
- Reading type (regular, estimated, final)
- Notes
- Verified status

#### Validation Rules
- Readings must be monotonically increasing
- Reading date cannot be in the future
- Reading value must be numeric and positive

### Invoices
**Path**: `/admin/invoices`

Generate, finalize, and manage utility invoices.

#### Features
- Create draft invoices
- Add/edit invoice items
- Finalize invoices (locks them from editing)
- Mark invoices as paid
- Generate PDF invoices
- Send invoices via email

#### Fields
- Property (required, tenant-scoped)
- Billing period start (required)
- Billing period end (required)
- Due date
- Status (draft, finalized, paid)
- Total amount (calculated)

#### Invoice Items
Each invoice contains line items for:
- Electricity (day/night zones)
- Water (cold/hot)
- Heating
- Gas
- hot water circulation (circulation fees)

#### Workflow
1. Create draft invoice
2. Add invoice items (automatically calculated from meter readings)
3. Review and adjust if needed
4. Finalize invoice (locks it)
5. Send to tenant
6. Mark as paid when payment received

### Tariffs
**Path**: `/admin/tariffs`

Manage utility pricing tariffs.

#### Features
- Create, edit, and delete tariffs
- Set zone-based pricing (day/night for electricity)
- Track tariff history
- Duplicate tariffs for new periods

#### Fields
- Provider (required)
- Tariff type (electricity, water, heating, gas)
- Zone (day, night, peak, off-peak)
- Rate (cents per unit)
- Valid from date (required)
- Valid to date
- Currency (EUR)

### Providers
**Path**: `/admin/providers`

Manage utility service providers.

#### Features
- Create, edit, and delete providers
- Track provider contact information
- View associated tariffs

#### Fields
- Name (required)
- Service type (electricity, water, heating, gas)
- Contact email
- Contact phone
- Address
- Status (active/inactive)

### Users
**Path**: `/admin/users`

Manage user accounts and permissions.

#### Features
- Create, edit, and delete users
- Assign roles (admin, manager, tenant)
- Assign properties to tenants
- Activate/deactivate accounts
- Reset passwords

#### Fields
- Name (required)
- Email (required, unique)
- Password (required on creation)
- Role (required)
- Organization name (for admins)
- Assigned property (for tenants)
- Active status

#### Role-Specific Fields
- **Admin**: Organization name
- **Manager**: No special fields
- **Tenant**: Assigned property (required)

### Subscriptions
**Path**: `/admin/subscriptions`

View and manage organization subscriptions (admin/superadmin only).

#### Features
- View subscription details
- Track expiry dates
- Monitor seat usage
- Renew subscriptions

#### Fields
- Organization (admin user)
- Plan type
- Start date
- End date
- Max properties
- Max tenants
- Status (active, expired, suspended)

## Navigation

### Main Navigation Groups

#### Administration
- Users
- Organizations (superadmin only)
- Subscriptions (superadmin only)

#### Property Management
- Properties
- Buildings
- Meters

#### Billing
- Meter Readings
- Invoices
- Tariffs
- Providers

#### System
- Privacy Policy
- Terms of Service
- GDPR Compliance
- Languages (superadmin only)
- Translations (superadmin only)

### User Menu
Located in the top-right corner:
- Profile
- Settings
- Logout

## Search & Filters

### Global Search
Press `Cmd+K` (Mac) or `Ctrl+K` (Windows/Linux) to open global search.

Search across:
- Properties (by address)
- Buildings (by name/address)
- Meters (by meter number)
- Users (by name/email)
- Invoices (by invoice number)

### Table Filters
Each resource table includes:
- Search bar (searches multiple fields)
- Column sorting (click column headers)
- Toggleable columns (show/hide columns)
- Pagination (25, 50, 100 items per page)

## Bulk Actions

### Available Bulk Actions
- **Delete**: Remove multiple records at once
- **Export**: Export selected records to CSV/Excel
- **Bulk Update**: Update multiple records simultaneously

### How to Use
1. Select records using checkboxes
2. Click "Bulk Actions" dropdown
3. Choose desired action
4. Confirm action

## Multi-Tenancy

### Tenant Scope
All data is automatically scoped to your tenant (organization):
- You can only see and manage data within your tenant
- Cross-tenant data access is prevented by `TenantScope`
- All queries are automatically filtered by `tenant_id`

### Tenant Context
- Set automatically on login
- Persists throughout session
- Cleared on logout

## Security

### Authorization
- All resources protected by policies
- Role-based access control (RBAC)
- Action-level permissions (view, create, edit, delete)

### Audit Trail
- All meter reading changes logged
- Invoice modifications tracked
- User account changes recorded
- Accessible via Audit Log resource

### Session Management
- Sessions regenerated on login
- Automatic logout after inactivity
- CSRF protection on all forms

## Accessibility

### Keyboard Navigation
- `Tab`: Navigate between fields
- `Enter`: Submit forms
- `Esc`: Close modals
- `Cmd+K` / `Ctrl+K`: Global search

### Screen Reader Support
- All forms have proper labels
- Error messages announced
- Status changes announced
- ARIA attributes on interactive elements

## Troubleshooting

### Cannot Access Admin Panel
1. Verify you're logged in
2. Check your user role (must be admin or manager)
3. Verify your account is active
4. Clear browser cache and cookies

### 404 Error on Dashboard
1. Run `php artisan route:cache`
2. Run `php artisan config:cache`
3. Verify Filament is installed: `composer show filament/filament`
4. Check `app/Providers/Filament/AdminPanelProvider.php` is registered

### Cannot See Resources
1. Check your role permissions
2. Verify `shouldRegisterNavigation()` in resource
3. Check policy authorization methods
4. Review tenant scope filtering

### Data Not Showing
1. Verify tenant context is set
2. Check `tenant_id` on records
3. Review global scopes
4. Check database seeding

## Best Practices

### Data Entry
- Always verify meter readings before finalizing invoices
- Use consistent date formats
- Add notes to readings for context
- Review draft invoices before finalizing

### Invoice Management
- Finalize invoices only when ready (cannot be edited after)
- Generate invoices at consistent intervals
- Send invoices promptly after finalization
- Track payment status diligently

### User Management
- Use strong passwords
- Deactivate accounts instead of deleting
- Assign appropriate roles
- Review user access regularly

### Performance
- Use filters to narrow large datasets
- Export data in batches
- Archive old records periodically
- Monitor database size

## Support

### Documentation
- Frontend Guide: [docs/frontend/FRONTEND.md](../frontend/FRONTEND.md)
- Routes Documentation: [docs/routes/ROUTES_IMPLEMENTATION_COMPLETE.md](../routes/ROUTES_IMPLEMENTATION_COMPLETE.md)
- Testing Guide: [docs/tests/TESTING_GUIDE.md](../guides/TESTING_GUIDE.md)

### Common Commands
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache

# Run migrations
php artisan migrate

# Seed test data
php artisan test:setup --fresh
```

### Getting Help
1. Check this documentation
2. Review error logs: `storage/logs/laravel.log`
3. Run diagnostics: `php artisan about`
4. Contact system administrator
