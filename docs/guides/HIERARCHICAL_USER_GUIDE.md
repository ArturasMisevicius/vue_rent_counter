# Hierarchical User Management Guide

This guide provides detailed instructions for using the Vilnius Utilities Billing System based on your user role.

## Table of Contents

- [Superadmin Guide](#superadmin-guide)
- [Admin Guide](#admin-guide)
- [Tenant Guide](#tenant-guide)

---

## Superadmin Guide

As a Superadmin, you have complete control over the entire system, including all organizations, subscriptions, and users.

### Accessing the System

1. Navigate to the login page
2. Enter your superadmin credentials
3. You'll be redirected to the Superadmin Dashboard at `/superadmin/dashboard`

### Dashboard Overview

The Superadmin Dashboard displays:
- **Total Organizations**: Number of active Admin accounts
- **Active Subscriptions**: Count of currently active subscriptions
- **Subscription Status Breakdown**: Active, expired, suspended, cancelled
- **System-Wide Metrics**: Total properties, tenants, and invoices across all organizations
- **Recent Activity**: Latest admin account actions

### Managing Organizations

#### Creating a New Admin Account

1. Navigate to **Organizations** → **Create New**
2. Fill in the required information:
   - **Email**: Admin's login email (must be unique)
   - **Password**: Initial password (admin can change later)
   - **Organization Name**: Company or property management name
3. Select a subscription plan:
   - **Basic**: 10 properties, 50 tenants
   - **Professional**: 50 properties, 200 tenants
   - **Enterprise**: Unlimited
4. Set subscription expiry date
5. Click **Create Admin Account**

The system will:
- Assign a unique `tenant_id` for data isolation
- Create an active subscription
- Send welcome email to the admin

#### Viewing Organization Details

1. Navigate to **Organizations** → **All Organizations**
2. Click on an organization name
3. View:
   - Organization profile and contact information
   - Subscription status and limits
   - Properties and buildings managed
   - Tenant accounts created
   - Usage statistics
   - Activity history

#### Editing Organization Information

1. Navigate to the organization detail page
2. Click **Edit Organization**
3. Update:
   - Organization name
   - Admin email
   - Contact information
4. Click **Save Changes**

### Managing Subscriptions

#### Viewing All Subscriptions

1. Navigate to **Subscriptions** → **All Subscriptions**
2. View list with:
   - Organization name
   - Plan type
   - Status (active, expired, suspended, cancelled)
   - Expiry date
   - Usage (properties/tenants vs. limits)

#### Viewing Subscription Details

1. Click on a subscription from the list
2. View:
   - Complete subscription information
   - Usage statistics
   - Payment history
   - Renewal history

#### Renewing a Subscription

1. Navigate to subscription detail page
2. Click **Renew Subscription**
3. Select new expiry date
4. Confirm renewal

The system will:
- Update subscription status to active
- Restore full access for the admin
- Update expiry date

#### Suspending a Subscription

1. Navigate to subscription detail page
2. Click **Suspend Subscription**
3. Enter reason for suspension
4. Confirm suspension

The admin will:
- Lose access to the system
- Be unable to login
- Receive notification of suspension

#### Cancelling a Subscription

1. Navigate to subscription detail page
2. Click **Cancel Subscription**
3. Confirm cancellation

Note: Cancellation is permanent. Historical data is preserved but the admin account is deactivated.

### System Monitoring

#### Viewing System-Wide Reports

1. Navigate to **Reports** → **System Overview**
2. View metrics:
   - Total active organizations
   - Revenue from subscriptions
   - Growth trends
   - Feature usage statistics

#### Exporting Data

1. Navigate to the relevant section (Organizations, Subscriptions, etc.)
2. Click **Export to CSV**
3. Download the generated file

### Audit Logs

1. Navigate to **Audit Logs**
2. Filter by:
   - Date range
   - Organization
   - Action type (created, updated, deleted, etc.)
3. View complete history of all account management actions

### Best Practices

- **Regular Monitoring**: Check subscription expiry dates weekly
- **Proactive Renewals**: Contact admins before subscriptions expire
- **Usage Tracking**: Monitor which organizations are approaching limits
- **Security**: Regularly review audit logs for suspicious activity
- **Backup**: Ensure regular database backups are configured

---

## Admin Guide

As an Admin (Property Owner), you manage your rental property portfolio and tenant accounts within your subscription limits.

### Accessing the System

1. Navigate to the login page
2. Enter your admin credentials
3. You'll be redirected to the Admin Dashboard at `/admin/dashboard`

### Dashboard Overview

The Admin Dashboard displays:
- **Portfolio Statistics**: Total properties, active tenants, buildings
- **Subscription Status**: Plan type, expiry date, usage vs. limits
- **Pending Tasks**: Meter readings needed, invoices to generate
- **Usage Statistics**: Consumption trends, invoice generation activity
- **Renewal Reminders**: Warnings when subscription nears expiry

### Managing Your Organization Profile

#### Viewing Your Profile

1. Navigate to **Profile** → **Organization Profile**
2. View:
   - Organization name
   - Contact email
   - Subscription details
   - Usage statistics

#### Updating Profile Information

1. Click **Edit Profile**
2. Update:
   - Organization name
   - Contact email
   - Phone number
3. Click **Save Changes**

Note: Email must be unique across all admins.

#### Changing Password

1. Navigate to **Profile** → **Security**
2. Enter current password
3. Enter new password
4. Confirm new password
5. Click **Update Password**

### Managing Buildings and Properties

#### Creating a Building

1. Navigate to **Buildings** → **Create New**
2. Fill in:
   - Building name
   - Address
   - Number of apartments
3. Click **Create Building**

All buildings are automatically associated with your `tenant_id`.

#### Creating a Property

1. Navigate to **Properties** → **Create New**
2. Fill in:
   - Property type (apartment, house)
   - Building (if apartment)
   - Address
   - Area (square meters)
3. Click **Create Property**

Note: Check your subscription limits before creating properties.

#### Managing Meters

1. Navigate to a property detail page
2. Click **Add Meter**
3. Fill in:
   - Meter type (electricity, water_cold, water_hot, heating)
   - Serial number
   - Initial reading
4. Click **Create Meter**

### Managing Tenant Accounts

#### Creating a Tenant Account

1. Navigate to **Tenants** → **Create New**
2. Fill in:
   - **Email**: Tenant's login email (must be unique)
   - **Password**: Initial password
   - **Name**: Tenant's full name
   - **Phone**: Contact number
   - **Property**: Select from your properties
3. Click **Create Tenant**

The system will:
- Inherit your `tenant_id` for data isolation
- Assign the selected `property_id`
- Set you as the `parent_user_id`
- Send welcome email with login credentials
- Create audit log entry

Note: Check your subscription limits before creating tenants.

#### Viewing Tenant List

1. Navigate to **Tenants** → **All Tenants**
2. View list with:
   - Tenant name and email
   - Assigned property
   - Active/inactive status
   - Last login date

#### Viewing Tenant Details

1. Click on a tenant from the list
2. View:
   - Contact information
   - Assigned property
   - Meter reading history
   - Invoice history
   - Property assignment history
   - Account status

#### Reassigning a Tenant

1. Navigate to tenant detail page
2. Click **Reassign to Different Property**
3. Select new property from your portfolio
4. Enter reason for reassignment (optional)
5. Click **Confirm Reassignment**

The system will:
- Update property assignment
- Preserve all historical data
- Create audit log entry
- Send notification email to tenant

#### Deactivating a Tenant Account

1. Navigate to tenant detail page
2. Click **Deactivate Account**
3. Enter reason for deactivation
4. Click **Confirm Deactivation**

The tenant will:
- Be unable to login
- Retain all historical data
- Appear as "Inactive" in tenant list

#### Reactivating a Tenant Account

1. Navigate to deactivated tenant detail page
2. Click **Reactivate Account**
3. Click **Confirm Reactivation**

The tenant will:
- Regain login access
- Appear as "Active" in tenant list

### Meter Readings and Billing

#### Entering Meter Readings

1. Navigate to **Meter Readings** → **Enter New**
2. Select property and meter
3. Enter:
   - Reading value
   - Reading date
4. Click **Submit Reading**

The system validates:
- Reading is not lower than previous reading
- Date is not in the future
- Reading is reasonable (not excessively high)

#### Generating Invoices

1. Navigate to **Invoices** → **Generate Bulk**
2. Select:
   - Billing period (month/year)
   - Properties to include
3. Click **Generate Invoices**

The system will:
- Calculate consumption for each meter
- Apply current tariffs
- Create draft invoices
- Snapshot prices for historical accuracy

#### Finalizing Invoices

1. Navigate to **Invoices** → **Draft Invoices**
2. Review invoice details
3. Click **Finalize Invoice**

Note: Finalized invoices cannot be edited.

### Subscription Management

#### Viewing Subscription Status

Your subscription status is always visible in the dashboard header:
- **Active**: Green indicator, full access
- **Expiring Soon**: Yellow indicator with days remaining
- **Expired**: Red indicator, read-only access

#### Understanding Subscription Limits

Navigate to **Profile** → **Subscription** to view:
- Current plan type
- Properties: X used / Y maximum
- Tenants: X used / Y maximum
- Expiry date

#### Renewing Your Subscription

When your subscription nears expiry:
1. You'll see renewal reminders in the dashboard
2. Contact the Superadmin for renewal
3. Superadmin will update your subscription
4. Full access will be restored

#### Handling Expired Subscriptions

If your subscription expires:
- You can still login
- You can view all data (read-only)
- You cannot create or modify data
- Contact Superadmin to renew

### Reports and Analytics

#### Viewing Portfolio Statistics

1. Navigate to **Reports** → **Portfolio Overview**
2. View:
   - Total properties and occupancy rate
   - Active vs. inactive tenants
   - Meter reading submission rates
   - Invoice generation activity

#### Viewing Consumption Trends

1. Navigate to **Reports** → **Consumption Trends**
2. Select date range
3. View aggregated usage across all properties

#### Exporting Reports

1. Navigate to the relevant report
2. Click **Export to PDF**
3. Download the generated report

### Best Practices

- **Regular Meter Readings**: Enter readings monthly for accurate billing
- **Prompt Tenant Management**: Deactivate accounts when tenants move out
- **Monitor Subscription**: Keep track of usage vs. limits
- **Maintain Contact Info**: Keep tenant contact information up to date
- **Review Invoices**: Always review draft invoices before finalizing
- **Plan Renewals**: Renew subscription before expiry to avoid disruption

### Troubleshooting

#### Cannot Create Property or Tenant

**Cause**: Subscription limit reached
**Solution**: 
- Check subscription limits in your profile
- Contact Superadmin to upgrade plan

#### Tenant Cannot Login

**Possible Causes**:
- Account is deactivated
- Incorrect password
- Email typo

**Solution**:
- Check tenant status in tenant list
- Reset password if needed
- Verify email address is correct

#### Cannot Finalize Invoice

**Possible Causes**:
- Missing meter readings
- Subscription expired

**Solution**:
- Ensure all meter readings are entered
- Renew subscription if expired

---

## Tenant Guide

As a Tenant (Apartment Resident), you can view your utility information, submit meter readings, and track your consumption.

### Accessing the System

1. Navigate to the login page
2. Enter the credentials provided by your property manager
3. You'll be redirected to the Tenant Dashboard at `/tenant/dashboard`

### First-Time Login

On your first login:
1. You'll be prompted to change your password
2. Enter the temporary password provided
3. Create a new secure password
4. Click **Update Password**

### Dashboard Overview

The Tenant Dashboard displays:
- **Assigned Property**: Your apartment or house details
- **Current Meter Readings**: Latest readings for all meters
- **Consumption Summary**: Usage for current month
- **Unpaid Balance**: Total amount due
- **Recent Activity**: Latest meter readings and invoices

### Viewing Your Property

#### Property Details

1. Navigate to **My Property**
2. View:
   - Property address
   - Property type (apartment/house)
   - Building information (if apartment)
   - Area (square meters)
   - Property manager contact

#### Meter Information

1. Navigate to **My Property** → **Meters**
2. View all meters for your property:
   - Meter type (electricity, water, heating)
   - Serial number
   - Current reading
   - Last reading date

### Submitting Meter Readings

#### How to Submit a Reading

1. Navigate to **Meter Readings** → **Submit New**
2. Select the meter
3. Enter:
   - Current reading value
   - Reading date (defaults to today)
4. Click **Submit Reading**

The system will:
- Validate the reading
- Store the reading
- Notify your property manager
- Update your consumption history

#### Reading Validation

Your reading must:
- Be higher than the previous reading
- Not be dated in the future
- Be reasonable (not excessively high)

If validation fails, you'll see an error message explaining the issue.

#### Viewing Reading History

1. Navigate to **Meter Readings** → **History**
2. View:
   - All submitted readings
   - Reading dates
   - Consumption between readings
   - Submission timestamps

### Viewing Consumption History

#### Monthly Consumption

1. Navigate to **Consumption** → **Monthly View**
2. View consumption for each utility type:
   - Electricity (kWh)
   - Cold water (m³)
   - Hot water (m³)
   - Heating (kWh or m³)

#### Consumption Trends

1. Navigate to **Consumption** → **Trends**
2. View:
   - Graph showing last 12 months
   - Comparison to previous periods
   - Seasonal patterns

### Viewing Invoices

#### Invoice List

1. Navigate to **Invoices** → **All Invoices**
2. View list with:
   - Invoice number
   - Billing period
   - Total amount
   - Payment status (paid/unpaid)
   - Due date

#### Invoice Details

1. Click on an invoice from the list
2. View:
   - Detailed line items
   - Consumption for each utility
   - Tariff rates applied
   - Calculations breakdown
   - Payment information

#### Downloading Invoices

1. Navigate to invoice detail page
2. Click **Download PDF**
3. Save or print the invoice

### Managing Your Profile

#### Viewing Profile

1. Navigate to **Profile** → **My Profile**
2. View:
   - Your name and email
   - Phone number
   - Assigned property
   - Property manager contact

#### Updating Contact Information

1. Click **Edit Profile**
2. Update:
   - Phone number
   - Email address (requires verification)
3. Click **Save Changes**

#### Changing Password

1. Navigate to **Profile** → **Security**
2. Enter current password
3. Enter new password
4. Confirm new password
5. Click **Update Password**

### Understanding Your Bills

#### Electricity Charges

- **Day Rate**: Consumption during day hours (7:00-23:00)
- **Night Rate**: Consumption during night hours (23:00-7:00)
- **Fixed Fee**: Monthly meter maintenance fee

#### Water Charges

- **Cold Water**: Consumption × tariff rate
- **Hot Water**: Consumption × tariff rate
- **Sewage**: Based on total water consumption
- **Fixed Fee**: Monthly meter maintenance fee

#### Heating Charges

- **Heating**: Consumption × tariff rate
- **Gyvatukas (Circulation Fee)**: 
  - Summer (May-Sep): Based on actual consumption
  - Winter (Oct-Apr): Based on summer average

### Best Practices

- **Regular Readings**: Submit meter readings monthly on the same date
- **Accurate Readings**: Double-check readings before submitting
- **Monitor Consumption**: Review trends to identify unusual usage
- **Timely Payments**: Pay invoices by due date to avoid late fees
- **Keep Records**: Download and save invoice PDFs
- **Update Contact Info**: Keep email and phone current

### Troubleshooting

#### Cannot Submit Meter Reading

**Possible Causes**:
- Reading is lower than previous reading
- Date is in the future
- Account is deactivated

**Solution**:
- Verify you're reading the meter correctly
- Check the date
- Contact your property manager if account issues

#### Cannot Login

**Possible Causes**:
- Incorrect password
- Account deactivated
- Email typo

**Solution**:
- Try password reset
- Contact your property manager

#### Don't See My Property

**Cause**: Property not assigned or account issue

**Solution**: Contact your property manager immediately

#### Invoice Seems Incorrect

**Steps**:
1. Review the invoice details carefully
2. Check meter readings used for calculation
3. Verify consumption matches your submitted readings
4. Contact your property manager with specific questions

### Getting Help

If you need assistance:

1. **Property Manager Contact**: Available in your profile
2. **Email**: Contact your property manager via email
3. **Phone**: Call your property manager during business hours

Your property manager can help with:
- Account issues
- Billing questions
- Meter reading problems
- Property information updates

---

## Common Questions

### For All Users

**Q: How do I reset my password?**
A: Click "Forgot Password" on the login page and follow the email instructions.

**Q: Can I access the system from mobile devices?**
A: Yes, the system is mobile-responsive and works on smartphones and tablets.

**Q: How is my data protected?**
A: The system uses multi-tenancy with strict data isolation. You can only access data within your scope.

### For Admins

**Q: What happens when my subscription expires?**
A: You'll have read-only access. You can view data but cannot create or modify anything. Contact the Superadmin to renew.

**Q: Can I transfer a tenant between my properties?**
A: Yes, use the "Reassign" feature. All historical data is preserved.

**Q: How do I upgrade my subscription plan?**
A: Contact the Superadmin to discuss plan upgrades.

### For Tenants

**Q: Why can't I see other apartments in my building?**
A: For privacy, you can only see your own property's data.

**Q: How often should I submit meter readings?**
A: Monthly, ideally on the same date each month for consistency.

**Q: Can I edit a submitted meter reading?**
A: No, only your property manager can correct readings. Contact them if you made an error.

---

## Support

For technical issues or questions not covered in this guide, contact your system administrator or property manager.
