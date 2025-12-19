# TariffResource Usage Guide

## Quick Start

The TariffResource provides a Filament admin interface for managing utility tariffs. This guide covers common usage scenarios and best practices.

## Access Requirements

### Who Can Access?

- ✅ **SUPERADMIN**: Full access to all tariff operations
- ✅ **ADMIN**: Full access to all tariff operations
- ❌ **MANAGER**: No access (operational resources only)
- ❌ **TENANT**: No access (tenant-specific resources only)

### Navigation

Tariffs appear in the **Configuration** navigation group for SUPERADMIN and ADMIN users only.

## Creating Tariffs

### Flat Rate Tariff

Use flat rate tariffs for simple, single-rate pricing:

1. Navigate to **Configuration > Tariffs**
2. Click **New Tariff**
3. Fill in the form:
   - **Provider**: Select the utility provider
   - **Name**: Enter a descriptive name (e.g., "Standard Electricity Rate")
   - **Service Type**: Select the service (Electricity, Water, Heating)
   - **Tariff Type**: Select "Flat"
   - **Active From**: Start date
   - **Active Until**: End date (optional)
   - **Rate**: Enter the rate per unit (e.g., 0.15 EUR/kWh)
   - **Fixed Fee**: Enter monthly base charge (optional)
4. Click **Create**

**Example**:
```
Provider: Vilnius Energy
Name: Standard Electricity Rate 2024
Service Type: Electricity
Tariff Type: Flat
Active From: 2024-01-01
Active Until: 2024-12-31
Rate: 0.15 EUR/kWh
Fixed Fee: 5.00 EUR/month
```

### Time-of-Use Tariff

Use time-of-use tariffs for day/night or multi-zone pricing:

1. Navigate to **Configuration > Tariffs**
2. Click **New Tariff**
3. Fill in basic information (same as flat rate)
4. **Tariff Type**: Select "Time of Use"
5. Configure zones:
   - Click **Add Zone**
   - **Zone**: Select zone type (Day, Night, Peak, Off-Peak)
   - **Rate**: Enter rate for this zone
   - **Start Time**: Enter start time (HH:MM format)
   - **End Time**: Enter end time (HH:MM format)
   - Repeat for each zone
6. **Weekend Logic**: Select how weekends are handled
   - Same Rate: Use weekday rates
   - Separate Rate: Use different rates for weekends
   - All Off-Peak: Treat all weekend hours as off-peak
7. Click **Create**

**Example**:
```
Provider: Vilnius Energy
Name: Day/Night Electricity Rate 2024
Service Type: Electricity
Tariff Type: Time of Use
Active From: 2024-01-01
Active Until: 2024-12-31

Zones:
  - Zone: Day
    Rate: 0.18 EUR/kWh
    Start Time: 07:00
    End Time: 23:00
  
  - Zone: Night
    Rate: 0.09 EUR/kWh
    Start Time: 23:00
    End Time: 07:00

Weekend Logic: Separate Rate
Fixed Fee: 5.00 EUR/month
```

## Editing Tariffs

### When to Edit

- Correcting errors in tariff configuration
- Updating rates for existing tariff periods
- Adjusting effective dates

### How to Edit

1. Navigate to **Configuration > Tariffs**
2. Find the tariff in the table
3. Click the **Edit** action
4. Modify fields as needed
5. Click **Save**

**Important Notes**:
- All changes are logged via TariffObserver
- Old and new values are recorded in audit logs
- Changes affect future billing calculations
- Existing invoices are not recalculated

### Best Practices

- **Don't edit active tariffs**: Create new tariffs instead
- **Use effective dates**: Set `active_until` on old tariff and `active_from` on new tariff
- **Document changes**: Add notes in the tariff name or description
- **Test first**: Verify calculations before activating

## Deleting Tariffs

### When to Delete

- Tariff was created in error
- Tariff is no longer needed and has no associated invoices
- Cleaning up test data

### How to Delete

1. Navigate to **Configuration > Tariffs**
2. Find the tariff in the table
3. Click the **Delete** action
4. Confirm deletion

**Important Notes**:
- Deletion is logged via TariffObserver
- Cannot delete tariffs with associated invoices
- Consider deactivating instead of deleting

## Validation Rules

### Required Fields

- Provider
- Name
- Service Type
- Tariff Type
- Active From date

### Conditional Requirements

**For Flat Rate Tariffs**:
- Rate (required, numeric, 0-999999.99)

**For Time-of-Use Tariffs**:
- At least one zone configuration
- Each zone requires:
  - Zone ID (valid TariffZone enum value)
  - Rate (numeric, 0-999999.99)
  - Start Time (HH:MM format)
  - End Time (HH:MM format)

### Date Validation

- `active_from` must be today or later
- `active_until` must be after `active_from` (if provided)

### Numeric Validation

- Rates: 0-999999.99 (prevents overflow)
- Fixed Fee: 0-999999.99 (prevents overflow)
- All numeric fields validated for type and range

## Common Scenarios

### Scenario 1: Annual Rate Increase

**Problem**: Need to increase rates for the new year

**Solution**:
1. Keep existing tariff active until Dec 31
2. Create new tariff with increased rates
3. Set new tariff `active_from` to Jan 1
4. Set old tariff `active_until` to Dec 31

**Example**:
```
Old Tariff:
  Name: Standard Rate 2024
  Active From: 2024-01-01
  Active Until: 2024-12-31
  Rate: 0.15

New Tariff:
  Name: Standard Rate 2025
  Active From: 2025-01-01
  Active Until: null
  Rate: 0.17
```

### Scenario 2: Switching from Flat to Time-of-Use

**Problem**: Provider introduces day/night pricing

**Solution**:
1. Set end date on flat rate tariff
2. Create new time-of-use tariff
3. Configure day and night zones
4. Set appropriate effective dates

**Example**:
```
Old Tariff:
  Name: Standard Flat Rate
  Tariff Type: Flat
  Active Until: 2024-06-30
  Rate: 0.15

New Tariff:
  Name: Day/Night Rate
  Tariff Type: Time of Use
  Active From: 2024-07-01
  Zones:
    - Day: 0.18 (07:00-23:00)
    - Night: 0.09 (23:00-07:00)
```

### Scenario 3: Temporary Rate Adjustment

**Problem**: Need temporary rate for summer months

**Solution**:
1. Create tariff with specific date range
2. Ensure dates don't overlap with other tariffs
3. System will automatically use correct tariff based on dates

**Example**:
```
Summer Tariff:
  Name: Summer Rate 2024
  Active From: 2024-06-01
  Active Until: 2024-08-31
  Rate: 0.12

Regular Tariff:
  Name: Standard Rate 2024
  Active From: 2024-01-01
  Active Until: 2024-12-31
  Rate: 0.15
```

## Troubleshooting

### "Provider field is required"

**Cause**: No provider selected

**Solution**: Select a provider from the dropdown

### "Rate must be a number between 0 and 999999.99"

**Cause**: Invalid rate value

**Solution**: Enter a valid numeric rate within the allowed range

### "At least one zone is required for time-of-use tariffs"

**Cause**: No zones configured for time-of-use tariff

**Solution**: Add at least one zone with valid configuration

### "Start time must be in HH:MM format"

**Cause**: Invalid time format

**Solution**: Enter time in 24-hour format (e.g., 07:00, 23:00)

### "This action is unauthorized"

**Cause**: User doesn't have permission to access tariffs

**Solution**: Ensure user has SUPERADMIN or ADMIN role

### Tariff not appearing in navigation

**Cause**: User role doesn't have access

**Solution**: 
- Check user role (must be SUPERADMIN or ADMIN)
- Verify user is authenticated
- Clear browser cache if needed

## Security Considerations

### XSS Prevention

- All text inputs are sanitized
- Time formats validated with regex
- HTML tags stripped from user input

### Numeric Overflow Protection

- Maximum values enforced (999999.99)
- Type validation on all numeric fields
- Database constraints prevent overflow

### Tenant Isolation

- All tariffs scoped to current tenant
- Provider selection limited to tenant's providers
- Cross-tenant access prevented

### Audit Logging

- All create/update/delete operations logged
- User ID and timestamp recorded
- Old and new values captured for updates
- IP address and user agent logged

## Related Resources

- [TariffResource API Documentation](TARIFF_RESOURCE_API.md)
- [Tariff Model Documentation](../models/TARIFF_MODEL.md)
- [Tariff Policy Documentation](../policies/TARIFF_POLICY.md)
- [Role-Based Navigation Visibility](role-based-navigation-visibility.md)
- [Billing Service Documentation](../services/BILLING_SERVICE.md)

## Support

For additional help:

1. Review the [API Documentation](TARIFF_RESOURCE_API.md)
2. Check the [Test Files](../../tests/Feature/Filament/) for examples
3. Consult the [Project README](../overview/readme.md)
4. Review related documentation in `docs/filament/`
