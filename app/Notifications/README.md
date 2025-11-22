# Email Notifications

This directory contains all email notification classes for the Vilnius Utilities Billing System.

## Available Notifications

### 1. WelcomeEmail
**Purpose:** Sent to new tenant accounts upon creation  
**Requirements:** 5.4  
**Usage:**
```php
$tenant->notify(new WelcomeEmail($property, $temporaryPassword));
```

### 2. TenantReassignedEmail
**Purpose:** Sent when a tenant is reassigned to a different property  
**Requirements:** 6.5  
**Usage:**
```php
$tenant->notify(new TenantReassignedEmail($newProperty, $previousProperty));
```

### 3. SubscriptionExpiryWarningEmail
**Purpose:** Sent when an admin's subscription is near expiry  
**Requirements:** 15.4  
**Usage:**
```php
$admin->notify(new SubscriptionExpiryWarningEmail($subscription));
```

**Recommended Schedule:** Send at 14 days, 7 days, and 1 day before expiry

### 4. MeterReadingSubmittedEmail
**Purpose:** Sent to admin when a tenant submits a meter reading  
**Requirements:** 10.4  
**Usage:**
```php
$admin->notify(new MeterReadingSubmittedEmail($meterReading, $tenant));
```

## Implementation Notes

- All notifications implement `ShouldQueue` for asynchronous processing
- Notifications use Laravel's mail channel
- Each notification includes both email and array representations
- Email templates use Laravel's MailMessage fluent API

## Queue Configuration

Ensure your queue worker is running to process notifications:
```bash
php artisan queue:work
```

For development, you can use the sync driver in `.env`:
```
QUEUE_CONNECTION=sync
```

## Testing Notifications

You can test notifications in Tinker:
```bash
php artisan tinker
```

```php
// Test WelcomeEmail
$tenant = User::where('role', 'tenant')->first();
$property = $tenant->property;
$tenant->notify(new \App\Notifications\WelcomeEmail($property, 'temp123'));

// Test SubscriptionExpiryWarningEmail
$admin = User::where('role', 'admin')->first();
$subscription = $admin->subscription;
$admin->notify(new \App\Notifications\SubscriptionExpiryWarningEmail($subscription));

// Test MeterReadingSubmittedEmail
$reading = MeterReading::latest()->first();
$tenant = $reading->enteredBy;
$admin = $tenant->parentUser;
$admin->notify(new \App\Notifications\MeterReadingSubmittedEmail($reading, $tenant));

// Test TenantReassignedEmail
$tenant = User::where('role', 'tenant')->first();
$newProperty = Property::find(2);
$oldProperty = $tenant->property;
$tenant->notify(new \App\Notifications\TenantReassignedEmail($newProperty, $oldProperty));
```
