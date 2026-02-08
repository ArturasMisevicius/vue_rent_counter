# Notification System

## Quick Start

The notification system provides automated email notifications for key user actions in the hierarchical user management system.

### Running the Verification Script

```bash
php verify-notifications.php
```

### Sending a Test Notification

```bash
php artisan tinker
```

```php
$tenant = User::where('role', 'tenant')->first();
$property = Property::first();
$tenant->notify(new \App\Notifications\WelcomeEmail($property, 'temp123'));
```

### Processing Queued Notifications

```bash
php artisan queue:work
```

## Documentation

- **[Notification System Overview](NOTIFICATION_SYSTEM.md)** - Complete system documentation with usage examples
- **[API Reference](../api/NOTIFICATIONS_API.md)** - Detailed API documentation for all notification classes
- **[Changelog](CHANGELOG.md)** - Version history and release notes

## Notification Types

| Notification | Purpose | Recipient | Trigger |
|-------------|---------|-----------|---------|
| **WelcomeEmail** | Welcome new tenants | Tenant | Account creation |
| **TenantReassignedEmail** | Property assignment changes | Tenant | Property reassignment |
| **SubscriptionExpiryWarningEmail** | Subscription expiring soon | Admin | 14 days before expiry |
| **MeterReadingSubmittedEmail** | New meter reading | Admin/Manager | Tenant submits reading |

## Key Features

- ✅ **Asynchronous Delivery**: All notifications are queued for background processing
- ✅ **Multi-language Support**: EN/LT/RU localization
- ✅ **Queue Integration**: Laravel queue system with retry logic
- ✅ **Comprehensive Testing**: Verification script and property tests
- ✅ **Error Handling**: Failed job tracking and retry mechanism
- ✅ **Localization**: Translation keys for all user-facing text
- ✅ **Action Links**: Direct navigation to relevant pages

## Configuration

### Queue Setup

```env
QUEUE_CONNECTION=database
```

```bash
php artisan queue:table
php artisan migrate
```

### Mail Setup

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Testing

### Verification Script

Validates all notification classes:

```bash
php verify-notifications.php
```

### Manual Testing

```bash
php artisan tinker
```

```php
// Test each notification type
$tenant = User::where('role', 'tenant')->first();
$admin = User::where('role', 'admin')->first();

// WelcomeEmail
$tenant->notify(new \App\Notifications\WelcomeEmail($property, 'temp123'));

// TenantReassignedEmail
$tenant->notify(new \App\Notifications\TenantReassignedEmail($newProperty, $oldProperty));

// SubscriptionExpiryWarningEmail
$admin->notify(new \App\Notifications\SubscriptionExpiryWarningEmail($subscription));

// MeterReadingSubmittedEmail
$admin->notify(new \App\Notifications\MeterReadingSubmittedEmail($reading, $tenant));
```

### Automated Testing

```php
use Illuminate\Support\Facades\Notification;

test('notifications are sent correctly', function () {
    Notification::fake();
    
    // Trigger notification
    $tenant->notify(new WelcomeEmail($property, 'password'));
    
    // Assert notification was sent
    Notification::assertSentTo($tenant, WelcomeEmail::class);
});
```

## Troubleshooting

### Notifications Not Sending

1. **Check queue worker**:
   ```bash
   php artisan queue:work
   ```

2. **Check failed jobs**:
   ```bash
   php artisan queue:failed
   ```

3. **Retry failed jobs**:
   ```bash
   php artisan queue:retry all
   ```

### Mail Configuration Issues

1. **Clear config cache**:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

2. **Test mail configuration**:
   ```bash
   php artisan tinker
   Mail::raw('Test', function($msg) { $msg->to('test@example.com'); });
   ```

### Missing Translations

1. **Check translation files exist**: `lang/{locale}/notifications.php`
2. **Clear cache**:
   ```bash
   php artisan cache:clear
   ```

## Requirements Mapping

| Requirement | Notification | Status |
|------------|--------------|--------|
| 5.4 | WelcomeEmail | ✅ Complete |
| 6.5 | TenantReassignedEmail | ✅ Complete |
| 10.4 | MeterReadingSubmittedEmail | ✅ Complete |
| 15.4 | SubscriptionExpiryWarningEmail | ✅ Complete |

## Architecture

```
app/Notifications/
├── WelcomeEmail.php                    # New tenant welcome
├── TenantReassignedEmail.php           # Property reassignment
├── SubscriptionExpiryWarningEmail.php  # Subscription warning
└── MeterReadingSubmittedEmail.php      # Meter reading notification

docs/notifications/
├── README.md                           # This file
├── NOTIFICATION_SYSTEM.md              # Complete system documentation
└── CHANGELOG.md                        # Version history

docs/api/
└── NOTIFICATIONS_API.md                # API reference

verify-notifications.php                # Verification script
```

## Related Documentation

- [Hierarchical User Management Spec](../../.kiro/specs/3-hierarchical-user-management/requirements.md)
- [Account Management Service](../services/ACCOUNT_MANAGEMENT_SERVICE.md)
- [Subscription Service](../services/SUBSCRIPTION_SERVICE.md)
- [Laravel Notifications](https://laravel.com/docs/12.x/notifications)
- [Laravel Queues](https://laravel.com/docs/12.x/queues)

## Support

For issues or questions:

1. Review the [complete documentation](NOTIFICATION_SYSTEM.md)
2. Check the [API reference](../api/NOTIFICATIONS_API.md)
3. Run the verification script
4. Check application logs: `storage/logs/laravel.log`
5. Review failed queue jobs: `php artisan queue:failed`

## Contributing

When adding new notifications:

1. Extend `Notification` class
2. Implement `ShouldQueue` interface
3. Use `Queueable` trait
4. Add comprehensive DocBlocks
5. Support multi-language localization
6. Update verification script
7. Add to documentation
8. Write property tests
9. Update this README

## License

This notification system is part of the Vilnius Utilities Billing Platform and follows the project's license terms.
