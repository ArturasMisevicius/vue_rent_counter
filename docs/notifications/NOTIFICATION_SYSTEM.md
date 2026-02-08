# Notification System Documentation

## Overview

The notification system provides email notifications for key user actions and system events in the hierarchical user management system. All notifications implement `ShouldQueue` for asynchronous delivery and support multi-language localization.

## Architecture

### Notification Classes

All notification classes are located in `app/Notifications/` and follow Laravel's notification conventions:

- Extend `Illuminate\Notifications\Notification`
- Implement `ShouldQueue` for background processing
- Use `Queueable` trait for queue management
- Support `mail` channel with localized content
- Provide array representation for database storage

### Queue Processing

Notifications are queued for asynchronous delivery to prevent blocking user requests:

```php
// Notifications are automatically queued when sent
$user->notify(new WelcomeEmail($property, $password));
```

Process queued notifications:
```bash
php artisan queue:work
```

## Notification Types

### 1. WelcomeEmail

**Purpose**: Sent to newly created tenant accounts with login credentials and property information.

**Trigger**: When `AccountManagementService::createTenantAccount()` creates a new tenant.

**Recipients**: New tenant users

**Data Required**:
- `Property $property` - The assigned property
- `string $temporaryPassword` - Generated temporary password

**Usage Example**:
```php
use App\Notifications\WelcomeEmail;

$tenant->notify(new WelcomeEmail($property, $temporaryPassword));
```

**Email Content**:
- Welcome greeting with tenant name
- Property address and type
- Login credentials (email and temporary password)
- Password change reminder
- Login action button
- Support information

**Localization Keys**:
- `notifications.welcome.subject`
- `notifications.welcome.greeting`
- `notifications.welcome.account_created`
- `notifications.welcome.address`
- `notifications.welcome.property_type`
- `notifications.welcome.credentials_heading`
- `notifications.welcome.email`
- `notifications.welcome.temporary_password`
- `notifications.welcome.password_reminder`
- `notifications.welcome.action`
- `notifications.welcome.support`

### 2. TenantReassignedEmail

**Purpose**: Notifies tenants when they are assigned to a new property or reassigned from one property to another.

**Trigger**: When `AccountManagementService::reassignTenant()` changes a tenant's property assignment.

**Recipients**: Tenant users being reassigned

**Data Required**:
- `Property $newProperty` - The new property assignment
- `?Property $previousProperty` - The previous property (null for initial assignment)

**Usage Example**:
```php
use App\Notifications\TenantReassignedEmail;

// Reassignment
$tenant->notify(new TenantReassignedEmail($newProperty, $previousProperty));

// Initial assignment
$tenant->notify(new TenantReassignedEmail($newProperty));
```

**Email Content**:
- Personalized greeting
- Previous property address (if applicable)
- New property address and type
- Dashboard access link
- Support information

**Localization Keys**:
- `notifications.tenant_reassigned.subject`
- `notifications.tenant_reassigned.greeting`
- `notifications.tenant_reassigned.updated`
- `notifications.tenant_reassigned.previous`
- `notifications.tenant_reassigned.new`
- `notifications.tenant_reassigned.assigned`
- `notifications.tenant_reassigned.property`
- `notifications.tenant_reassigned.property_type`
- `notifications.tenant_reassigned.info`
- `notifications.tenant_reassigned.view_dashboard`
- `notifications.tenant_reassigned.support`

### 3. SubscriptionExpiryWarningEmail

**Purpose**: Warns admin users when their subscription is approaching expiration.

**Trigger**: Scheduled job or manual trigger when subscription has 14 days or less remaining.

**Recipients**: Admin users with expiring subscriptions

**Data Required**:
- `Subscription $subscription` - The expiring subscription

**Usage Example**:
```php
use App\Notifications\SubscriptionExpiryWarningEmail;

$admin->notify(new SubscriptionExpiryWarningEmail($subscription));
```

**Email Content**:
- Days remaining until expiration
- Expiration date
- Current plan type
- Property usage (used/max)
- Tenant usage (used/max)
- Renewal call-to-action
- Profile management link
- Support information

**Localization Keys**:
- `notifications.subscription_expiry.subject`
- `notifications.subscription_expiry.greeting`
- `notifications.subscription_expiry.intro`
- `notifications.subscription_expiry.plan`
- `notifications.subscription_expiry.properties`
- `notifications.subscription_expiry.tenants`
- `notifications.subscription_expiry.cta_intro`
- `notifications.subscription_expiry.cta_notice`
- `notifications.subscription_expiry.action`
- `notifications.subscription_expiry.support`

### 4. MeterReadingSubmittedEmail

**Purpose**: Notifies admin/manager users when a tenant submits a meter reading.

**Trigger**: When `MeterReadingObserver` detects a new meter reading creation by a tenant.

**Recipients**: Admin or manager users (parent user of the tenant)

**Data Required**:
- `MeterReading $meterReading` - The submitted meter reading
- `User $tenant` - The tenant who submitted the reading

**Usage Example**:
```php
use App\Notifications\MeterReadingSubmittedEmail;

$admin->notify(new MeterReadingSubmittedEmail($meterReading, $tenant));
```

**Email Content**:
- Tenant name who submitted the reading
- Property address
- Meter type and serial number
- Reading date and value
- Zone (if applicable)
- Calculated consumption (if available)
- Link to view meter readings
- Management hint

**Localization Keys**:
- `notifications.meter_reading_submitted.subject`
- `notifications.meter_reading_submitted.greeting`
- `notifications.meter_reading_submitted.submitted_by`
- `notifications.meter_reading_submitted.details`
- `notifications.meter_reading_submitted.property`
- `notifications.meter_reading_submitted.meter_type`
- `notifications.meter_reading_submitted.serial`
- `notifications.meter_reading_submitted.reading_date`
- `notifications.meter_reading_submitted.reading_value`
- `notifications.meter_reading_submitted.zone`
- `notifications.meter_reading_submitted.consumption`
- `notifications.meter_reading_submitted.view`
- `notifications.meter_reading_submitted.manage_hint`

## Verification Script

### Purpose

The `verify-notifications.php` script validates that all notification classes exist and properly implement the `ShouldQueue` interface for background processing.

### Location

`verify-notifications.php` (project root)

### Usage

```bash
php verify-notifications.php
```

### Output

```
Checking notification classes...

1. WelcomeEmail: ✓ Exists and implements ShouldQueue
2. TenantReassignedEmail: ✓ Exists and implements ShouldQueue
3. SubscriptionExpiryWarningEmail: ✓ Exists and implements ShouldQueue
4. MeterReadingSubmittedEmail: ✓ Exists and implements ShouldQueue

✓ All notification classes are properly implemented!
```

### Implementation Details

The script:
1. Bootstraps the Laravel application
2. Uses reflection to check class existence
3. Verifies `ShouldQueue` interface implementation
4. Reports status for each notification class

## Testing Notifications

### Manual Testing

Send test notifications in Tinker:

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

Property test for email notifications (Task 11.5):

```php
test('email notifications are sent on account actions', function () {
    Notification::fake();
    
    // Test welcome email
    $property = Property::factory()->create();
    $tenant = createTenantAccount($property, 'password123');
    
    Notification::assertSentTo(
        $tenant,
        WelcomeEmail::class,
        function ($notification) use ($property) {
            return $notification->property->id === $property->id;
        }
    );
    
    // Test reassignment email
    $newProperty = Property::factory()->create();
    reassignTenant($tenant, $newProperty);
    
    Notification::assertSentTo(
        $tenant,
        TenantReassignedEmail::class
    );
});
```

## Configuration

### Queue Configuration

Configure queue driver in `.env`:

```env
QUEUE_CONNECTION=database
```

Run migrations for queue tables:

```bash
php artisan queue:table
php artisan migrate
```

### Mail Configuration

Configure mail settings in `.env`:

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

## Localization

### Adding Translations

Add notification translations to language files:

**English** (`lang/en/notifications.php`):
```php
return [
    'welcome' => [
        'subject' => 'Welcome to :app_name',
        'greeting' => 'Hello :name,',
        // ... more keys
    ],
    // ... more notification groups
];
```

**Lithuanian** (`lang/lt/notifications.php`):
```php
return [
    'welcome' => [
        'subject' => 'Sveiki atvykę į :app_name',
        'greeting' => 'Sveiki :name,',
        // ... more keys
    ],
    // ... more notification groups
];
```

**Russian** (`lang/ru/notifications.php`):
```php
return [
    'welcome' => [
        'subject' => 'Добро пожаловать в :app_name',
        'greeting' => 'Здравствуйте, :name,',
        // ... more keys
    ],
    // ... more notification groups
];
```

## Best Practices

### 1. Always Queue Notifications

All notifications implement `ShouldQueue` to prevent blocking user requests:

```php
class WelcomeEmail extends Notification implements ShouldQueue
{
    use Queueable;
    // ...
}
```

### 2. Provide Array Representation

Include array representation for database notification channel:

```php
public function toArray(object $notifiable): array
{
    return [
        'property_id' => $this->property->id,
        'property_address' => $this->property->address,
    ];
}
```

### 3. Use Localization

Always use translation keys for user-facing text:

```php
->subject(__('notifications.welcome.subject'))
->greeting(__('notifications.welcome.greeting', ['name' => $notifiable->name]))
```

### 4. Include Action Links

Provide clear action buttons for user navigation:

```php
->action(__('notifications.welcome.action'), url('/login'))
```

### 5. Handle Enum Labels Safely

Use helper methods to get enum labels with fallback:

```php
$typeLabel = method_exists($this->property->type, 'label')
    ? $this->property->type->label()
    : $this->property->type->value;
```

## Troubleshooting

### Notifications Not Sending

1. Check queue worker is running:
   ```bash
   php artisan queue:work
   ```

2. Verify mail configuration:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

3. Check failed jobs:
   ```bash
   php artisan queue:failed
   ```

4. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```

### Missing Translations

1. Verify translation files exist in `lang/{locale}/notifications.php`
2. Clear translation cache:
   ```bash
   php artisan cache:clear
   ```

### Queue Processing Issues

1. Check queue connection in `.env`
2. Verify database queue tables exist
3. Monitor queue with Horizon (if installed)
4. Check logs in `storage/logs/laravel.log`

## Related Documentation

- [Hierarchical User Management](../../.kiro/specs/3-hierarchical-user-management/requirements.md)
- [Account Management Service](../services/ACCOUNT_MANAGEMENT_SERVICE.md)
- [Subscription Service](../services/SUBSCRIPTION_SERVICE.md)
- [Laravel Notifications](https://laravel.com/docs/12.x/notifications)
- [Laravel Queues](https://laravel.com/docs/12.x/queues)
