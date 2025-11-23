# Admin Panel Quick Start

## Accessing the Admin Panel

1. Navigate to `/admin` in your browser
2. Login with admin or manager credentials
3. You'll be redirected to the dashboard

## First Steps

### For Admins

1. **Set Up Your Organization**
   - Go to Users → Create your first admin account
   - Add organization name during creation

2. **Add Buildings**
   - Navigate to Buildings → Create
   - Fill in building details (name, address, floors, units)

3. **Add Properties**
   - Navigate to Properties → Create
   - Assign to a building
   - Set property details (floor, unit, area)

4. **Add Meters**
   - Navigate to Meters → Create
   - Assign to a property
   - Set meter type (electricity, water, heating, gas)

5. **Create Tenants**
   - Navigate to Users → Create
   - Select "Tenant" role
   - Assign to a property

### For Managers

1. **Record Meter Readings**
   - Navigate to Meter Readings → Create
   - Select meter and enter reading value
   - Add notes if needed

2. **Generate Invoices**
   - Navigate to Invoices → Create
   - Select property and billing period
   - System auto-calculates from meter readings

3. **Finalize Invoices**
   - Review draft invoices
   - Click "Finalize" to lock invoice
   - Send to tenant via email

## Common Tasks

### Adding a New Tenant

1. Create property first (if not exists)
2. Go to Users → Create
3. Fill in tenant details:
   - Name
   - Email
   - Password
   - Role: Tenant
   - Assigned Property
4. Click "Create"
5. Tenant receives welcome email

### Recording Monthly Readings

1. Go to Meter Readings → Create
2. Select meter from dropdown
3. Enter reading date (today's date)
4. Enter reading value
5. Add notes (optional)
6. Click "Create"
7. Reading is saved and ready for invoicing

### Creating an Invoice

1. Go to Invoices → Create
2. Select property
3. Set billing period (start and end dates)
4. System automatically:
   - Finds meter readings in period
   - Calculates consumption
   - Applies tariffs
   - Adds gyvatukas fees
5. Review invoice items
6. Click "Create" to save as draft

### Finalizing an Invoice

1. Go to Invoices
2. Find draft invoice
3. Click "View" to review
4. Verify all items are correct
5. Click "Finalize"
6. Invoice is locked (cannot be edited)
7. Send to tenant

## Navigation

### Main Menu

- **Dashboard**: Overview and quick stats
- **Properties**: Manage properties
- **Buildings**: Manage buildings
- **Meters**: Manage utility meters
- **Meter Readings**: Record and verify readings
- **Invoices**: Generate and manage invoices
- **Tariffs**: Manage utility rates
- **Providers**: Manage utility providers
- **Users**: Manage user accounts (admin only)

### User Menu (Top Right)

- Profile
- Settings
- Logout

## Keyboard Shortcuts

- `Cmd+K` / `Ctrl+K`: Global search
- `Tab`: Navigate between fields
- `Enter`: Submit forms
- `Esc`: Close modals

## Tips & Tricks

### Search

Use the global search (Cmd+K) to quickly find:
- Properties by address
- Meters by number
- Users by name or email
- Invoices by number

### Filters

Each table has filters:
- Click column headers to sort
- Use search bar to filter results
- Toggle columns to show/hide

### Bulk Actions

Select multiple items to:
- Delete in bulk
- Export to CSV
- Update multiple records

### Quick Stats

Dashboard shows:
- Total properties
- Total buildings
- Active tenants
- Draft invoices
- Pending readings
- Monthly revenue

## Troubleshooting

### Can't Access Admin Panel

- Verify you're logged in
- Check your role (must be admin or manager)
- Clear browser cache
- Try incognito/private mode

### Can't See Certain Resources

- Check your role permissions
- Admins see all resources
- Managers see limited resources
- Tenants cannot access admin panel

### Data Not Showing

- Verify tenant context
- Check filters are not hiding data
- Refresh the page
- Clear application cache

### Invoice Not Calculating

- Verify meter readings exist for period
- Check tariffs are configured
- Ensure property has meters assigned
- Review billing period dates

## Getting Help

### Documentation

- [Full Admin Guide](./ADMIN_PANEL_GUIDE.md)
- [Testing Guide](./ADMIN_PANEL_TESTING.md)
- [Frontend Docs](../frontend/FRONTEND.md)

### Support Commands

```bash
# Clear caches
php artisan optimize:clear

# Rebuild routes
php artisan route:cache

# Check system status
php artisan about

# View logs
tail -f storage/logs/laravel.log
```

### Common Commands

```bash
# Seed test data
php artisan test:setup --fresh

# Run migrations
php artisan migrate

# Create backup
php artisan backup:run

# Clear specific cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Next Steps

1. Complete initial setup (buildings, properties, meters)
2. Create tenant accounts
3. Record first meter readings
4. Generate first invoices
5. Explore reports and analytics
6. Configure system settings
7. Set up automated backups

## Best Practices

- Record meter readings consistently (same day each month)
- Review draft invoices before finalizing
- Keep tariffs up to date
- Deactivate users instead of deleting
- Use notes fields for important context
- Export data regularly for backup
- Monitor pending readings dashboard
- Send invoices promptly after finalization
