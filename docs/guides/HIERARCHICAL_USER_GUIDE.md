# Hierarchical User Management Guide

## Overview

The Vilnius Utilities Billing System implements a three-tier hierarchical user management system that provides role-based access control and data isolation. This guide explains how each user role works and provides instructions for common tasks.

## User Hierarchy

```
Superadmin (System Owner)
    │
    ├─→ Admin 1 (Property Owner - Tenant ID: 1)
    │       ├─→ Tenant 1.1 (Apartment Resident - Property: A)
    │       ├─→ Tenant 1.2 (Apartment Resident - Property: B)
    │       └─→ Tenant 1.3 (Apartment Resident - Property: C)
    │
    └─→ Admin 2 (Property Owner - Tenant ID: 2)
            ├─→ Tenant 2.1 (Apartment Resident - Property: D)
            └─→ Tenant 2.2 (Apartment Resident - Property: E)
```

## Superadmin Guide

### Role Overview

**Purpose**: Manage the entire system across all organizations

**Key Responsibilities**:
- Create and manage Admin (property owner) accounts
- Manage subscriptions for all organizations
- Monitor system-wide activity and statistics
- Configure system settings and limits
- Provide support to Admins and resolve issues

**Access Level**: Full system access without restrictions (bypasses tenant scope)

### Getting Started

1. **Login**:
   - Navigate to the application URL
   - Use your Superadmin credentials
   - You'll be redirected to the Superadmin dashboard

2. **Dashboard Overview**:
   - Total organizations (Admin accounts)
   - Active subscriptions count
   - System-wide statistics (properties, tenants, invoices)
   - Recent admin activity
   - Expiring subscriptions alert

### Managing Admin Accounts

#### Creating a New Admin Account

1. Navigate to **Organizations** → **Create New**
2. Fill in the required information:
   - **Email**: Admin's email address (must be unique)
   - **Password**: Initial password (Admin can change later)
   - **Organization Name**: Name of the property management company
   - **Subscription Plan**: Basic, Professional, or Enterprise
3. Click **Create Admin Account**
4. The system will:
   - Assign a unique `tenant_id` for data isolation
   - Create an active subscription
   - Send a welcome email to the Admin

#### Viewing Admin Details

1. Navigate to **Organizations** → **View All**
2. Click on an organization to view:
   - Organization information
   - Subscription status and limits
   - Properties and tenants count
   - Recent activity
   - Usage statistics

#### Managing Subscriptions

**Viewing Subscription Status**:
1. Navigate to **Subscriptions** → **View All**
2. See all subscriptions with:
   - Plan type (Basic, Professional, Enterprise)
   - Status (Active, Expired, Suspended, Cancelled)
   - Expiry date
   - Usage (properties/tenants used vs. limit)

**Renewing a Subscription**:
1. Navigate to the subscription detail page
2. Click **Renew Subscription**
3. Set new expiry date
4. Click **Confirm Renewal**

**Suspending a Subscription**:
1. Navigate to the subscription detail page
2. Click **Suspend Subscription**
3. Provide a reason
4. Click **Confirm Suspension**
5. The Admin will be restricted to read-only access

**Cancelling a Subscription**:
1. Navigate to the subscription detail page
2. Click **Cancel Subscription**
3. Confirm the cancellation
4. The Admin account will be deactivated

### Monitoring System Activity

**View System-Wide Statistics**:
- Dashboard shows totals across all organizations
- Filter by date range to see trends
- Export reports for analysis

**View Admin Activity**:
- Navigate to **Organizations** → Select Admin → **Activity Log**
- See all actions performed by the Admin
- Filter by action type (create, update, delete)

**View Audit Logs**:
- Navigate to **Audit Logs**
- See all account management actions
- Filter by user, action type, or date range

### Best Practices

1. **Regular Monitoring**: Check the dashboard daily for expiring subscriptions
2. **Proactive Communication**: Contact Admins before their subscription expires
3. **Data Backup**: Ensure automated backups are running correctly
4. **Security**: Regularly review audit logs for suspicious activity
5. **Support**: Respond promptly to Admin support requests

---

## Admin Guide

### Role Overview

**Purpose**: Manage your property portfolio and tenant accounts

**Key Responsibilities**:
- Create and manage buildings and properties
- Create and manage tenant accounts
- Assign tenants to properties
- Manage meters and meter readings
- Generate and manage invoices
- Monitor subscription status and usage

**Access Level**: Limited to your own `tenant_id` scope (data isolation)

**Subscription**: Requires active subscription with limits based on plan

### Getting Started

1. **Login**:
   - Navigate to the application URL
   - Use your Admin credentials
   - You'll be redirected to the Admin dashboard

2. **Dashboard Overview**:
   - Portfolio statistics (properties, tenants, pending tasks)
   - Subscription status and limits
   - Usage statistics (properties used/max, tenants used/max)
   - Renewal reminders (if subscription is near expiry)
   - Recent activity

### Managing Your Subscription

#### Viewing Subscription Status

1. Navigate to **Profile** → **Subscription**
2. View your subscription details:
   - Plan type (Basic, Professional, Enterprise)
   - Status (Active, Expired, Suspended)
   - Expiry date
   - Properties used / maximum allowed
   - Tenants used / maximum allowed

#### Understanding Subscription Limits

**Basic Plan**:
- 10 properties maximum
- 50 tenants maximum
- Core billing features

**Professional Plan**:
- 50 properties maximum
- 200 tenants maximum
- Advanced reporting and bulk operations

**Enterprise Plan**:
- Unlimited properties
- Unlimited tenants
- Custom features and priority support

#### Renewing Your Subscription

1. Navigate to **Profile** → **Subscription**
2. Click **Renew Subscription**
3. Follow the renewal process
4. Your subscription will be extended

**Note**: If your subscription expires, you'll have read-only access for 7 days (grace period). After that, you'll need to contact the Superadmin to renew.

### Managing Buildings and Properties

#### Creating a Building

1. Navigate to **Buildings** → **Create New**
2. Fill in the required information:
   - **Name**: Building name or address
   - **Address**: Full street address
   - **City**: City name
   - **Postal Code**: Postal code
3. Click **Create Building**

#### Creating a Property

1. Navigate to **Properties** → **Create New**
2. Fill in the required information:
   - **Building**: Select the building
   - **Unit Number**: Apartment or unit number
   - **Property Type**: Apartment, House, Commercial, etc.
   - **Area**: Property area in square meters
3. Click **Create Property**

**Note**: You cannot create more properties than your subscription allows. If you reach the limit, you'll need to upgrade your subscription.

### Managing Tenant Accounts

#### Creating a Tenant Account

1. Navigate to **Tenants** → **Create New**
2. Fill in the required information:
   - **Email**: Tenant's email address (must be unique)
   - **Password**: Initial password (tenant can change later)
   - **Name**: Tenant's full name
   - **Phone**: Contact phone number
   - **Property**: Select the property to assign
3. Click **Create Tenant Account**
4. The system will:
   - Inherit your `tenant_id` for data isolation
   - Assign the tenant to the selected property
   - Send a welcome email with login credentials

**Note**: You cannot create more tenants than your subscription allows. If you reach the limit, you'll need to upgrade your subscription.

#### Viewing Tenant Details

1. Navigate to **Tenants** → **View All**
2. Click on a tenant to view:
   - Personal information
   - Assigned property
   - Meter readings history
   - Invoice history
   - Account status (active/inactive)

#### Reassigning a Tenant

When a tenant moves to a different property within your portfolio:

1. Navigate to **Tenants** → Select Tenant → **Reassign**
2. Select the new property
3. Click **Reassign Tenant**
4. The system will:
   - Update the property assignment
   - Maintain historical records
   - Preserve all meter readings and invoices
   - Send a notification email to the tenant

#### Deactivating a Tenant Account

When a tenant moves out:

1. Navigate to **Tenants** → Select Tenant → **Deactivate**
2. Provide a reason (optional)
3. Click **Confirm Deactivation**
4. The tenant will:
   - Be unable to log in
   - Retain all historical data
   - Can be reactivated later if needed

#### Reactivating a Tenant Account

If a tenant returns:

1. Navigate to **Tenants** → Select Tenant → **Reactivate**
2. Optionally update the property assignment
3. Click **Confirm Reactivation**
4. The tenant will regain login access

### Managing Meters and Readings

#### Creating Meters

1. Navigate to **Meters** → **Create New**
2. Fill in the required information:
   - **Property**: Select the property
   - **Meter Type**: Electricity, Water, Heating, etc.
   - **Serial Number**: Meter serial number
   - **Installation Date**: Date installed
3. Click **Create Meter**

#### Viewing Meter Readings

1. Navigate to **Meters** → Select Meter → **Readings**
2. View all readings with:
   - Reading date
   - Reading value
   - Consumption (difference from previous reading)
   - Submitted by (tenant or admin)

#### Submitting Meter Readings

1. Navigate to **Meters** → Select Meter → **Submit Reading**
2. Enter the reading value
3. Enter the reading date
4. Click **Submit Reading**
5. The system will validate:
   - Reading is not lower than previous reading
   - Reading date is not in the future

### Managing Invoices

#### Generating Invoices

1. Navigate to **Invoices** → **Generate**
2. Select the billing period
3. Select properties (or all)
4. Click **Generate Invoices**
5. The system will:
   - Calculate consumption for each meter
   - Apply appropriate tariffs
   - Create draft invoices

#### Finalizing Invoices

1. Navigate to **Invoices** → Select Invoice → **Finalize**
2. Review the invoice details
3. Click **Finalize Invoice**
4. The invoice becomes immutable and can be sent to the tenant

#### Viewing Invoice History

1. Navigate to **Invoices** → **View All**
2. Filter by:
   - Property
   - Tenant
   - Date range
   - Status (draft, finalized, paid)

### Best Practices

1. **Regular Monitoring**: Check your dashboard daily for pending tasks
2. **Subscription Management**: Monitor your usage and renew before expiry
3. **Tenant Communication**: Keep tenant contact information up to date
4. **Meter Readings**: Submit readings regularly for accurate billing
5. **Data Accuracy**: Review invoices before finalizing

---

## Tenant Guide

### Role Overview

**Purpose**: View billing information and submit meter readings for your apartment

**Key Responsibilities**:
- View your assigned property details
- View meters and consumption history
- Submit meter readings (if enabled)
- View and download invoices
- Update your profile information

**Access Level**: Limited to your assigned property only (`property_id` scope)

**Account Creation**: Your account is created by your property Admin

### Getting Started

1. **Login**:
   - Navigate to the application URL
   - Use the credentials provided by your Admin
   - You'll be redirected to the Tenant dashboard

2. **Dashboard Overview**:
   - Assigned property information
   - Current meter readings and consumption
   - Unpaid invoice balance
   - Recent activity

3. **First-Time Login**:
   - You'll be prompted to change your password
   - Update your profile information
   - Review your assigned property details

### Viewing Your Property

1. Navigate to **My Property**
2. View property details:
   - Building name and address
   - Unit number
   - Property type
   - Area (square meters)
   - Admin contact information

### Viewing Meters and Consumption

#### Viewing Your Meters

1. Navigate to **Meters**
2. See all meters for your property:
   - Meter type (Electricity, Water, Heating)
   - Serial number
   - Current reading
   - Last reading date

#### Viewing Consumption History

1. Navigate to **Meters** → Select Meter → **History**
2. View consumption data:
   - Monthly consumption
   - Consumption trends (graph)
   - Comparison with previous periods

### Submitting Meter Readings

If your Admin has enabled tenant meter reading submission:

1. Navigate to **Meters** → Select Meter → **Submit Reading**
2. Enter the current reading value
3. Enter the reading date (usually today)
4. Click **Submit Reading**
5. The system will:
   - Validate the reading (must be higher than previous)
   - Validate the date (cannot be in the future)
   - Notify your Admin of the submission

**Tips for Accurate Readings**:
- Read the meter at the same time each month
- Double-check the value before submitting
- Take a photo of the meter for your records
- Submit readings promptly when requested

### Viewing Invoices

#### Viewing Invoice List

1. Navigate to **Invoices**
2. See all invoices for your property:
   - Invoice number
   - Billing period
   - Total amount
   - Status (draft, finalized, paid)
   - Due date

#### Viewing Invoice Details

1. Navigate to **Invoices** → Select Invoice
2. View detailed breakdown:
   - Meter readings (start and end)
   - Consumption by meter
   - Tariffs applied
   - Line items with costs
   - Total amount due

#### Downloading Invoices

1. Navigate to **Invoices** → Select Invoice
2. Click **Download PDF**
3. Save the PDF for your records

### Managing Your Profile

#### Updating Profile Information

1. Navigate to **Profile**
2. Update your information:
   - Name
   - Email (must be unique)
   - Phone number
3. Click **Save Changes**

#### Changing Your Password

1. Navigate to **Profile** → **Change Password**
2. Enter your current password
3. Enter your new password
4. Confirm your new password
5. Click **Change Password**

#### Viewing Admin Contact

1. Navigate to **Profile** → **Admin Contact**
2. View your Admin's contact information:
   - Organization name
   - Email
   - Phone number
3. Use this information if you need support

### Frequently Asked Questions

**Q: I can't log in. What should I do?**
A: Contact your property Admin using the contact information provided when your account was created. They can reset your password or reactivate your account if it was deactivated.

**Q: I submitted an incorrect meter reading. Can I change it?**
A: Contact your Admin immediately. They can correct the reading before the invoice is finalized.

**Q: When will I receive my invoice?**
A: Invoices are typically generated monthly. Check with your Admin for the specific billing schedule.

**Q: How do I pay my invoice?**
A: Payment instructions are provided by your Admin. The system tracks invoice status but does not process payments directly.

**Q: I'm moving to a different apartment in the same building. What happens to my account?**
A: Your Admin will reassign your account to the new property. All your historical data will be preserved.

**Q: I'm moving out. What happens to my account?**
A: Inform your Admin. They will deactivate your account, but all historical data will be preserved for record-keeping.

### Best Practices

1. **Regular Monitoring**: Check your dashboard regularly for new invoices
2. **Accurate Readings**: Submit meter readings accurately and on time
3. **Profile Updates**: Keep your contact information up to date
4. **Invoice Review**: Review invoices promptly and contact your Admin with questions
5. **Password Security**: Use a strong password and change it regularly

---

## Data Isolation and Security

### How Data Isolation Works

The system uses a multi-level data isolation approach:

1. **Tenant ID Scope**: Each Admin has a unique `tenant_id`. All their data (buildings, properties, meters, readings, invoices) is tagged with this `tenant_id`.

2. **Property ID Scope**: Each Tenant is assigned to a specific property. They can only access data for that property.

3. **Automatic Filtering**: The system automatically filters all queries based on the authenticated user's role and scope.

### Security Features

- **Role-Based Access Control**: Each role has specific permissions
- **Data Isolation**: Admins cannot see other Admins' data
- **Audit Logging**: All account management actions are logged
- **Session Management**: Sessions are regenerated on login for security
- **Password Security**: Passwords are hashed using bcrypt
- **CSRF Protection**: All forms are protected against CSRF attacks

### Privacy

- **Tenant Privacy**: Tenants can only see their own property data
- **Admin Privacy**: Admins cannot see other Admins' data
- **Superadmin Access**: Superadmin can access all data for system management

---

## Troubleshooting

### Common Issues

#### "Subscription Expired" Message

**Problem**: You see a message that your subscription has expired.

**Solution**:
- You have 7 days of read-only access (grace period)
- Contact the Superadmin to renew your subscription
- Or navigate to **Profile** → **Subscription** → **Renew**

#### "Subscription Limit Reached" Error

**Problem**: You cannot create a new property or tenant.

**Solution**:
- Check your subscription limits in **Profile** → **Subscription**
- Upgrade your subscription plan
- Or contact the Superadmin for assistance

#### Cannot See Expected Data

**Problem**: You cannot see properties, tenants, or invoices you expect to see.

**Solution**:
- **Admin**: Verify the data has your `tenant_id` (check with Superadmin)
- **Tenant**: Verify you're assigned to the correct property (check with Admin)
- Clear your browser cache and log in again

#### Account Deactivated

**Problem**: You cannot log in and see "Account deactivated" message.

**Solution**:
- **Admin**: Contact the Superadmin to reactivate your account
- **Tenant**: Contact your Admin to reactivate your account

### Getting Help

- **Tenants**: Contact your property Admin using the contact information in your profile
- **Admins**: Contact the Superadmin or system administrator
- **Superadmin**: Refer to system documentation or contact technical support

---

## Appendix

### Subscription Plan Comparison

| Feature | Basic | Professional | Enterprise |
|---------|-------|--------------|------------|
| Max Properties | 10 | 50 | Unlimited |
| Max Tenants | 50 | 200 | Unlimited |
| Billing Features | ✓ | ✓ | ✓ |
| Meter Management | ✓ | ✓ | ✓ |
| Invoice Generation | ✓ | ✓ | ✓ |
| Advanced Reporting | ✗ | ✓ | ✓ |
| Bulk Operations | ✗ | ✓ | ✓ |
| Custom Features | ✗ | ✗ | ✓ |
| Priority Support | ✗ | ✗ | ✓ |

### User Role Permissions Matrix

| Permission | Superadmin | Admin | Tenant |
|------------|-----------|-------|--------|
| View all organizations | ✓ | ✗ | ✗ |
| Manage subscriptions | ✓ | View own | ✗ |
| Create Admin accounts | ✓ | ✗ | ✗ |
| Create buildings | ✗ | ✓ | ✗ |
| Create properties | ✗ | ✓ | ✗ |
| Create tenant accounts | ✗ | ✓ | ✗ |
| Assign tenants | ✗ | ✓ | ✗ |
| Create meters | ✗ | ✓ | ✗ |
| Submit meter readings | ✗ | ✓ | ✓* |
| Generate invoices | ✗ | ✓ | ✗ |
| View own property | ✗ | ✓ | ✓ |
| View own invoices | ✗ | ✓ | ✓ |
| Update own profile | ✓ | ✓ | ✓ |

*If enabled by Admin

### Glossary

- **Tenant ID**: Unique identifier for data isolation between Admins
- **Property ID**: Unique identifier for a specific apartment or unit
- **Subscription**: Paid service plan with limits on properties and tenants
- **Grace Period**: Period after subscription expiry with read-only access
- **Data Isolation**: Enforcement of access boundaries between users
- **Audit Log**: Record of all account management actions
- **Meter Reading**: Recorded value from a utility meter
- **Invoice**: Bill for utility consumption during a billing period
- **Tariff**: Pricing structure for utility consumption

---

## Additional Resources

- [README.md](../overview/readme.md) - Project overview and quick start
- [SETUP.md](SETUP.md) - Installation and configuration guide
- [TESTING_GUIDE.md](TESTING_GUIDE.md) - Testing approach and conventions
- [Project Overview](../overview/readme.md) - Detailed feature documentation
- [Laravel Documentation](https://laravel.com/docs) - Framework documentation
- [Filament Documentation](https://filamentphp.com/docs) - Admin panel documentation
