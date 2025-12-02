# Admin Panel Documentation

Complete documentation for the Vilnius Utilities Billing Platform admin panel built with Filament 4.

## Documentation Index

### Getting Started

- **[Quick Start Guide](QUICK_START.md)** - Get up and running in minutes
  - First login
  - Initial setup
  - Common tasks
  - Keyboard shortcuts

### User Guides

- **[Admin Panel Guide](ADMIN_PANEL_GUIDE.md)** - Comprehensive admin panel documentation
  - Access & authentication
  - Dashboard overview
  - Resource management (Properties, Buildings, Meters, Invoices, etc.)
  - Navigation & search
  - Security & multi-tenancy
  - Troubleshooting

### Testing

- **[Testing Guide](ADMIN_PANEL_TESTING.md)** - Testing strategy and test suites
  - Test suites overview
  - Running tests
  - Test helpers
  - Coverage areas
  - Debugging failed tests

## Quick Links

### Access

- **URL**: `/admin`
- **Login**: `/admin/login`
- **Roles**: Admin, Manager

### Key Features

- Multi-tenant data isolation
- Role-based access control
- Real-time dashboard stats
- Meter reading management
- Invoice generation & finalization
- Tariff management
- User management
- Audit trails

## Architecture

### Technology Stack

- **Framework**: Laravel 11
- **Admin Panel**: Filament 4
- **Database**: SQLite (dev), MySQL/PostgreSQL (prod)
- **Frontend**: Blade + Tailwind CSS (CDN) + Alpine.js (CDN)
- **Authentication**: Laravel Breeze
- **Testing**: Pest PHP

### Key Components

```
app/
├── Filament/
│   ├── Pages/
│   │   └── Dashboard.php          # Custom dashboard
│   ├── Resources/                  # Filament resources
│   │   ├── PropertyResource.php
│   │   ├── BuildingResource.php
│   │   ├── MeterResource.php
│   │   ├── MeterReadingResource.php
│   │   ├── InvoiceResource.php
│   │   ├── TariffResource.php
│   │   ├── ProviderResource.php
│   │   └── UserResource.php
│   └── Widgets/                    # Dashboard widgets
├── Http/
│   └── Middleware/
│       └── EnsureUserIsAdminOrManager.php
├── Policies/                       # Authorization policies
└── Providers/
    └── Filament/
        └── AdminPanelProvider.php  # Panel configuration
```

### Resources

Each Filament resource provides:
- List view with search, filters, and sorting
- Create form with validation
- Edit form with authorization
- Delete with confirmation
- Bulk actions
- Tenant-scoped queries

## User Roles

### Admin

**Access**: Full access to all resources within their tenant

**Capabilities**:
- Manage properties, buildings, meters
- Create and manage invoices
- Manage tariffs and providers
- Create and manage users (tenants, managers)
- View audit logs
- Access system settings
- Generate reports

**Dashboard Stats**:
- Total properties
- Total buildings
- Active tenants
- Draft invoices
- Pending meter readings
- Monthly revenue

### Manager

**Access**: Limited to operational resources

**Capabilities**:
- View properties, buildings, meters
- Record meter readings
- Create and finalize invoices
- View tariffs and providers (read-only)
- View audit logs (limited)

**Dashboard Stats**:
- Total properties
- Total buildings
- Pending meter readings
- Draft invoices

### Tenant

**Access**: No access to admin panel

**Capabilities**:
- Access tenant-specific dashboard at `/tenant/dashboard`
- View own property details
- View own meter readings
- View and download own invoices

## Security

### Multi-Tenancy

All data is automatically scoped by `tenant_id`:
- `BelongsToTenant` trait on models
- `TenantScope` global scope
- `TenantContext` service
- Policy-based authorization

### Authorization

- Policies guard every resource
- Role-based access control (RBAC)
- Action-level permissions (view, create, edit, delete)
- Middleware enforces admin/manager access

### Audit Trail

- Meter reading changes logged
- Invoice modifications tracked
- User account changes recorded
- Authorization failures logged

## Data Flow

### Meter Reading → Invoice Flow

1. **Record Reading**
   - Manager enters meter reading
   - System validates monotonicity
   - Reading saved with timestamp

2. **Create Invoice**
   - Select property and billing period
   - System finds readings in period
   - Calculates consumption (current - previous)
   - Applies tariffs (snapshotted)
   - Adds gyvatukas fees
   - Creates draft invoice

3. **Finalize Invoice**
   - Admin/manager reviews draft
   - Clicks "Finalize"
   - Invoice locked (cannot edit)
   - Status changed to "finalized"
   - Ready to send to tenant

4. **Mark Paid**
   - Tenant pays invoice
   - Admin/manager marks as paid
   - Status changed to "paid"
   - Payment date recorded

## Configuration

### Panel Configuration

Located in `app/Providers/Filament/AdminPanelProvider.php`:

```php
->id('admin')
->path('admin')
->login()
->authGuard('web')
->authMiddleware([
    Authenticate::class,
    EnsureUserIsAdminOrManager::class,
])
->colors([
    'primary' => Color::Amber,
])
```

### Navigation Groups

- **Administration**: Users, Organizations, Subscriptions
- **Property Management**: Properties, Buildings, Meters
- **Billing**: Meter Readings, Invoices, Tariffs, Providers
- **System**: Settings, Policies, Compliance

## Customization

### Adding a New Resource

1. Create resource:
```bash
php artisan make:filament-resource ModelName
```

2. Configure form and table in resource class

3. Add policy for authorization:
```bash
php artisan make:policy ModelNamePolicy --model=ModelName
```

4. Register policy in `AuthServiceProvider`

5. Add tests:
```bash
php artisan make:test Filament/ModelNameResourceTest
```

### Customizing Dashboard

Edit `app/Filament/Pages/Dashboard.php`:
- Modify `getWidgets()` to add/remove widgets
- Update `DashboardStatsWidget` for custom stats
- Edit `resources/views/filament/pages/dashboard.blade.php` for layout

### Adding Navigation Items

In resource class:

```php
protected static ?string $navigationIcon = 'heroicon-o-document-text';
protected static ?string $navigationLabel = 'Custom Label';
protected static ?string $navigationGroup = 'Group Name';
protected static ?int $navigationSort = 1;

public static function shouldRegisterNavigation(): bool
{
    return auth()->user()->role === UserRole::ADMIN;
}
```

## Performance

### Optimization Tips

- Use eager loading for relationships
- Index frequently queried columns (`tenant_id`, `meter_id`, etc.)
- Cache dashboard stats
- Paginate large datasets
- Use database transactions for bulk operations

### Monitoring

```bash
# View logs
php artisan pail

# Check queue status
php artisan queue:work

# Monitor database
php artisan db:show

# Check cache
php artisan cache:clear
```

## Deployment

### Pre-Deployment Checklist

- [ ] Run migrations: `php artisan migrate --force`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Rebuild caches: `php artisan optimize`
- [ ] Run tests: `php artisan test`
- [ ] Seed data (if needed): `php artisan test:setup --fresh`
- [ ] Configure backups: `php artisan backup:run`

### Post-Deployment

- [ ] Verify admin panel accessible
- [ ] Test login with admin account
- [ ] Check dashboard loads correctly
- [ ] Verify resources are accessible
- [ ] Test creating/editing records
- [ ] Monitor error logs

## Troubleshooting

### Common Issues

**404 on /admin**
```bash
php artisan route:cache
php artisan config:cache
```

**403 Forbidden**
- Check user role
- Verify middleware configuration
- Review policy methods

**Blank Dashboard**
- Check database connection
- Verify tenant context
- Clear view cache: `php artisan view:clear`

**Slow Performance**
- Enable query caching
- Add database indexes
- Optimize eager loading
- Use pagination

## Support

### Resources

- [Filament Documentation](https://filamentphp.com/docs)
- [Laravel Documentation](https://laravel.com/docs)
- [Project Repository](https://github.com/your-repo)

### Commands

```bash
# System info
php artisan about

# List routes
php artisan route:list --path=admin

# Check config
php artisan config:show filament

# Run diagnostics
php artisan test --filter=Admin
```

## Contributing

When adding features to the admin panel:

1. Create/update Filament resource
2. Add/update policies
3. Write tests
4. Update documentation
5. Test multi-tenancy
6. Verify authorization
7. Submit pull request

## Changelog

### v1.0.0 (Current)

- ✅ Custom dashboard with role-based stats
- ✅ Property, Building, Meter resources
- ✅ Meter Reading resource with validation
- ✅ Invoice resource with finalization workflow
- ✅ Tariff and Provider resources
- ✅ User management resource
- ✅ Multi-tenant data isolation
- ✅ Role-based access control
- ✅ Comprehensive test coverage
- ✅ Full documentation

### Roadmap

- [ ] Advanced reporting dashboard
- [ ] Bulk meter reading import
- [ ] Invoice PDF customization
- [ ] Email notification templates
- [ ] API endpoints for mobile app
- [ ] Multi-language support
- [ ] Dark mode theme

## License

Proprietary - All rights reserved
