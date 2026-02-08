# Notifications API Documentation

## Overview

This document describes the notification system API for the hierarchical user management feature. All notifications are queued for asynchronous delivery and support multi-language localization.

## Notification Classes

### WelcomeEmail

**Namespace**: `App\Notifications\WelcomeEmail`

**Purpose**: Sends welcome email to newly created tenant accounts with login credentials and property information.

**Implements**: `ShouldQueue` (queued for background processing)

**Constructor Parameters**:
```php
public function __construct(
    protected Property $property,      // The assigned property
    protected string $temporaryPassword // Generated temporary password
)
```

**Delivery Channels**: `['mail']`

**Usage**:
```php
use App\Notifications\WelcomeEmail;

$tenant->notify(new WelcomeEmail($property, $temporaryPassword));
```

**Email Structure**:
- **Subject**: Localized welcome message
- **Greeting**: Personalized with tenant name
- **Content**:
  - Account creation confirmation
  - Property address and type
  - Login credentials (email and temporary password)
  - Password change reminder
- **Action**: Login button linking to `/login`
- **Footer**: Support information

**Array Representation**:
```php
[
    'property_id' => int,
    'property_address' => string,
]
```

**Localization Keys**: `notifications.welcome.*`

**Requirements**: 5.4

---

### TenantReassignedEmail

**Namespace**: `App\Notifications\TenantReassignedEmail`

**Purpose**: Notifies tenants when assigned to a new property or reassigned from one property to another.

**Implements**: `ShouldQueue` (queued for background processing)

**Constructor Parameters**:
```php
public function __construct(
    protected Property $newProperty,        // The new property assignment
    protected ?Property $previousProperty = null // Previous property (null for initial assignment)
)
```

**Delivery Channels**: `['mail']`

**Usage**:
```php
use App\Notifications\TenantReassignedEmail;

// Reassignment scenario
$tenant->notify(new TenantReassignedEmail($newProperty, $previousProperty));

// Initial assignment scenario
$tenant->notify(new TenantReassignedEmail($newProperty));
```

**Email Structure**:
- **Subject**: Localized reassignment notification
- **Greeting**: Personalized with tenant name
- **Content**:
  - Previous property address (if applicable)
  - New property address and type
  - Property information notice
- **Action**: View Dashboard button linking to `/tenant/dashboard`
- **Footer**: Support information

**Array Representation**:
```php
[
    'new_property_id' => int,
    'new_property_address' => string,
    'previous_property_id' => ?int,
    'previous_property_address' => ?string,
]
```

**Localization Keys**: `notifications.tenant_reassigned.*`

**Requirements**: 6.5

---

### SubscriptionExpiryWarningEmail

**Namespace**: `App\Notifications\SubscriptionExpiryWarningEmail`

**Purpose**: Warns admin users when their subscription is approaching expiration (typically 14 days or less).

**Implements**: `ShouldQueue` (queued for background processing)

**Constructor Parameters**:
```php
public function __construct(
    protected Subscription $subscription // The expiring subscription
)
```

**Delivery Channels**: `['mail']`

**Usage**:
```php
use App\Notifications\SubscriptionExpiryWarningEmail;

$admin->notify(new SubscriptionExpiryWarningEmail($subscription));
```

**Email Structure**:
- **Subject**: Localized expiry warning
- **Greeting**: Personalized with admin name
- **Content**:
  - Days remaining until expiration
  - Expiration date
  - Current plan type
  - Property usage (used/max)
  - Tenant usage (used/max)
  - Renewal call-to-action
  - Read-only mode notice
- **Action**: Manage Subscription button linking to `/admin/profile`
- **Footer**: Support information

**Array Representation**:
```php
[
    'subscription_id' => int,
    'expires_at' => string, // ISO 8601 datetime
    'days_remaining' => int,
    'plan_type' => string,  // SubscriptionPlanType enum value
]
```

**Localization Keys**: `notifications.subscription_expiry.*`

**Requirements**: 15.4

---

### MeterReadingSubmittedEmail

**Namespace**: `App\Notifications\MeterReadingSubmittedEmail`

**Purpose**: Notifies admin/manager users when a tenant submits a meter reading.

**Implements**: `ShouldQueue` (queued for background processing)

**Constructor Parameters**:
```php
public function __construct(
    protected MeterReading $meterReading, // The submitted meter reading
    protected User $tenant                // The tenant who submitted the reading
)
```

**Delivery Channels**: `['mail']`

**Usage**:
```php
use App\Notifications\MeterReadingSubmittedEmail;

$admin->notify(new MeterReadingSubmittedEmail($meterReading, $tenant));
```

**Email Structure**:
- **Subject**: Localized meter reading notification
- **Greeting**: Personalized with admin/manager name
- **Content**:
  - Tenant name who submitted the reading
  - Property address
  - Meter type and serial number
  - Reading date and value
  - Zone (if applicable for multi-zone meters)
  - Calculated consumption (if previous reading exists)
  - Management hint
- **Action**: View Readings button linking to `/manager/meter-readings`
- **Footer**: Management information

**Array Representation**:
```php
[
    'meter_reading_id' => int,
    'meter_id' => int,
    'tenant_id' => int,
    'tenant_name' => string,
    'reading_date' => string, // ISO 8601 datetime
    'value' => float,
    'zone' => ?string,
]
```

**Localization Keys**: `notifications.meter_reading_submitted.*`

**Requirements**: 10.4

---

## Queue Configuration

### Queue Connection

All notifications use Laravel's queue system for asynchronous delivery. Configure in `.env`:

```env
QUEUE_CONNECTION=database
```

### Queue Tables

Create queue tables:
```bash
php artisan queue:table
php artisan migrate
```

### Queue Worker

Process queued notifications:
```bash
# Development
php artisan queue:work

# Production (with supervisor)
php artisan queue:work --tries=3 --timeout=90
```

### Failed Jobs

Handle failed notifications:
```bash
# List failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry {job-id}

# Delete failed job
php artisan queue:forget {job-id}
```

---

## Mail Configuration

### SMTP Settings

Configure mail driver in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Testing with Mailtrap

For development, use Mailtrap:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
```

### Production Mail Services

Recommended services:
- **AWS SES**: High volume, low cost
- **Mailgun**: Developer-friendly API
- **Postmark**: Transactional email specialist
- **SendGrid**: Enterprise features

---

## Localization

### Translation Files

Notification translations are stored in `lang/{locale}/notifications.php`:

**Structure**:
```php
return [
    'welcome' => [
        'subject' => 'Welcome to :app_name',
        'greeting' => 'Hello :name,',
        'account_created' => 'Your tenant account has been created.',
        // ... more keys
    ],
    'tenant_reassigned' => [
        'subject' => 'Property Assignment Updated',
        // ... more keys
    ],
    'subscription_expiry' => [
        'subject' => 'Subscription Expiring Soon',
        // ... more keys
    ],
    'meter_reading_submitted' => [
        'subject' => 'New Meter Reading Submitted',
        // ... more keys
    ],
];
```

### Supported Languages

- **English** (`en`): Default language
- **Lithuanian** (`lt`): Primary language for Vilnius utilities
- **Russian** (`ru`): Secondary language support

### Adding New Languages

1. Create translation file: `lang/{locale}/notifications.php`
2. Copy structure from `lang/en/notifications.php`
3. Translate all keys
4. Test with locale switcher

---

## Error Handling

### Common Errors

**1. Queue Not Processing**
```
Error: Notifications not being sent
Solution: Ensure queue worker is running
Command: php artisan queue:work
```

**2. Mail Configuration Error**
```
Error: Connection refused [smtp.example.com:587]
Solution: Verify SMTP credentials in .env
Command: php artisan config:clear && php artisan config:cache
```

**3. Missing Translation Keys**
```
Error: Translation key not found
Solution: Add missing keys to lang/{locale}/notifications.php
Command: php artisan cache:clear
```

**4. Queue Job Failed**
```
Error: Job failed after 3 attempts
Solution: Check logs and retry
Command: php artisan queue:failed
         php artisan queue:retry {job-id}
```

### Logging

Notification errors are logged to `storage/logs/laravel.log`:

```php
// Example log entry
[2024-01-15 10:30:45] production.ERROR: 
Failed to send notification: Connection timeout
Context: {"notification":"WelcomeEmail","user_id":123}
```

---

## Testing

### Manual Testing

Test notifications in Tinker:

```bash
php artisan tinker
```

```php
// Test WelcomeEmail
$tenant = User::where('role', 'tenant')->first();
$property = Property::first();
$tenant->notify(new \App\Notifications\WelcomeEmail($property, 'temp123'));

// Test TenantReassignedEmail
$newProperty = Property::find(2);
$oldProperty = Property::find(1);
$tenant->notify(new \App\Notifications\TenantReassignedEmail($newProperty, $oldProperty));

// Test SubscriptionExpiryWarningEmail
$admin = User::where('role', 'admin')->first();
$subscription = $admin->subscription;
$admin->notify(new \App\Notifications\SubscriptionExpiryWarningEmail($subscription));

// Test MeterReadingSubmittedEmail
$reading = MeterReading::latest()->first();
$tenant = $reading->meter->property->tenant;
$admin = $tenant->parentUser;
$admin->notify(new \App\Notifications\MeterReadingSubmittedEmail($reading, $tenant));
```

### Automated Testing

Property test example:

```php
use Illuminate\Support\Facades\Notification;

test('email notifications are sent on account actions', function () {
    Notification::fake();
    
    // Create tenant account
    $property = Property::factory()->create();
    $service = app(AccountManagementService::class);
    $result = $service->createTenantAccount([
        'name' => 'Test Tenant',
        'email' => 'tenant@example.com',
        'property_id' => $property->id,
    ]);
    
    // Assert WelcomeEmail was sent
    Notification::assertSentTo(
        $result->data['user'],
        WelcomeEmail::class,
        function ($notification) use ($property) {
            return $notification->property->id === $property->id;
        }
    );
    
    // Reassign tenant
    $newProperty = Property::factory()->create();
    $service->reassignTenant($result->data['user'], $newProperty);
    
    // Assert TenantReassignedEmail was sent
    Notification::assertSentTo(
        $result->data['user'],
        TenantReassignedEmail::class
    );
});
```

### Verification Script

Run the verification script to check all notification classes:

```bash
php verify-notifications.php
```

Expected output:
```
Checking notification classes...

1. WelcomeEmail: ✓ Exists and implements ShouldQueue
2. TenantReassignedEmail: ✓ Exists and implements ShouldQueue
3. SubscriptionExpiryWarningEmail: ✓ Exists and implements ShouldQueue
4. MeterReadingSubmittedEmail: ✓ Exists and implements ShouldQueue

✓ All notification classes are properly implemented!
```

---

## Performance Considerations

### Queue Optimization

1. **Use Database Queue for Development**:
   ```env
   QUEUE_CONNECTION=database
   ```

2. **Use Redis for Production**:
   ```env
   QUEUE_CONNECTION=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

3. **Configure Queue Workers**:
   ```bash
   # Multiple workers for high volume
   php artisan queue:work --queue=high,default,low --tries=3
   ```

### Email Rate Limiting

Implement rate limiting for notification sending:

```php
// In notification class
public function __construct()
{
    $this->afterCommit(); // Wait for database transaction
}
```

### Batch Notifications

For bulk notifications, use notification batching:

```php
use Illuminate\Support\Facades\Notification;

// Send to multiple users efficiently
Notification::send($users, new SubscriptionExpiryWarningEmail($subscription));
```

---

## Security Considerations

### Sensitive Data

- **Never log passwords**: Temporary passwords are not logged
- **Sanitize email content**: All user input is escaped
- **Secure queue storage**: Use encrypted queue connections in production

### Authentication

- **Verify recipients**: Ensure notifications are sent to correct users
- **Check permissions**: Validate user relationships before sending
- **Audit trail**: Log all notification sends for compliance

### Rate Limiting

Prevent notification spam:

```php
// In AccountManagementService
RateLimiter::attempt(
    'send-notification:'.$user->id,
    $perMinute = 5,
    function() use ($user, $notification) {
        $user->notify($notification);
    }
);
```

---

## Related Documentation

- [Notification System Overview](../notifications/NOTIFICATION_SYSTEM.md)
- [Account Management Service](../services/ACCOUNT_MANAGEMENT_SERVICE.md)
- [Subscription Service](../services/SUBSCRIPTION_SERVICE.md)
- [Hierarchical User Management Spec](../../.kiro/specs/3-hierarchical-user-management/requirements.md)
- [Laravel Notifications](https://laravel.com/docs/12.x/notifications)
- [Laravel Queues](https://laravel.com/docs/12.x/queues)
- [Laravel Mail](https://laravel.com/docs/12.x/mail)
