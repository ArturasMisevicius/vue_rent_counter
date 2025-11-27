# Subscription System API Documentation

## Overview

The subscription system provides subscription-based access control for Admin users, enforcing limits on properties and tenants based on their subscription plan.

**Related Files**:
- Model: `app/Models/Subscription.php`
- Service: `app/Services/SubscriptionService.php`
- Enum: `app/Enums/SubscriptionPlanType.php`
- Enum: `app/Enums/SubscriptionStatus.php`
- Config: `config/subscription.php`

**Date**: 2024-11-26  
**Status**: ✅ PRODUCTION READY

## Subscription Plans

| Plan | Max Properties | Max Tenants | Features |
|------|---------------|-------------|----------|
| **Basic** | 10 | 50 | Core billing features |
| **Professional** | 50 | 200 | Advanced reporting, bulk operations |
| **Enterprise** | Unlimited (9999) | Unlimited (9999) | Custom features, priority support |

### Plan Configuration

Plans are configured via environment variables in `.env`:

```env
# Basic Plan Limits
MAX_PROPERTIES_BASIC=10
MAX_TENANTS_BASIC=50

# Professional Plan Limits
MAX_PROPERTIES_PROFESSIONAL=50
MAX_TENANTS_PROFESSIONAL=200

# Enterprise Plan Limits
MAX_PROPERTIES_ENTERPRISE=9999
MAX_TENANTS_ENTERPRISE=9999
```

## Subscription Features

### Grace Period

- **Duration**: 7 days after expiry (configurable via `SUBSCRIPTION_GRACE_PERIOD_DAYS`)
- **Behavior**: Admin has read-only access during grace period
- **Purpose**: Allows time to renew without losing access to data

### Expiry Warning

- **Duration**: 14 days before expiry (configurable via `SUBSCRIPTION_EXPIRY_WARNING_DAYS`)
- **Behavior**: Admin sees renewal reminders in dashboard
- **Purpose**: Proactive notification to prevent service interruption

### Read-Only Mode

- **Trigger**: Subscription status is expired or suspended
- **Behavior**: Admin can view all data but cannot create/edit/delete
- **Enforcement**: Middleware checks subscription status on write operations

### Automatic Limits

- **Enforcement**: System checks limits before allowing resource creation
- **Properties**: Cannot create more properties than `max_properties`
- **Tenants**: Cannot create more tenants than `max_tenants`
- **Error**: Throws `SubscriptionLimitExceededException` when limit reached

## Subscription Status

### Active

- **Description**: Full access to all features within plan limits
- **Conditions**: `status = 'active'` AND `expires_at > now()`
- **Permissions**: Can create/edit/delete resources up to plan limits

### Expired

- **Description**: Read-only access, cannot create new resources
- **Conditions**: `expires_at < now()` OR `status = 'expired'`
- **Permissions**: Can view all data but cannot modify
- **Grace Period**: 7 days of read-only access before full restriction

### Suspended

- **Description**: Temporary suspension by Superadmin
- **Conditions**: `status = 'suspended'`
- **Permissions**: Read-only access
- **Reactivation**: Superadmin can change status back to 'active'

### Cancelled

- **Description**: Subscription terminated, account deactivated
- **Conditions**: `status = 'cancelled'`
- **Permissions**: No access
- **Reactivation**: Requires Superadmin intervention

## Subscription Model API

### Properties

```php
// Core properties
public int $id;
public int $user_id;              // Admin user who owns this subscription
public string $plan_type;         // 'basic', 'professional', 'enterprise'
public string $status;            // 'active', 'expired', 'suspended', 'cancelled'
public Carbon $starts_at;         // Subscription start date
public Carbon $expires_at;        // Subscription expiry date
public int $max_properties;       // Maximum properties allowed
public int $max_tenants;          // Maximum tenants allowed
```

### Relationships

```php
// Get the admin user who owns this subscription
public function user(): BelongsTo

// Usage
$subscription = Subscription::find(1);
$admin = $subscription->user;
```

### Status Check Methods

```php
// Check if subscription is currently active
public function isActive(): bool

// Returns true if status is 'active' AND expires_at is in the future
$subscription->isActive(); // true or false

// Check if subscription has expired
public function isExpired(): bool

// Returns true if expires_at is in the past
$subscription->isExpired(); // true or false

// Check if subscription is suspended
public function isSuspended(): bool

// Returns true if status is 'suspended'
$subscription->isSuspended(); // true or false
```

### Expiry Methods

```php
// Get days until expiry (negative if expired)
public function daysUntilExpiry(): int

// Returns number of days until expiry
$subscription->daysUntilExpiry(); // e.g., 45 or -7
```

### Limit Check Methods

```php
// Check if admin can add another property
public function canAddProperty(): bool

// Returns true if subscription is active AND current properties < max_properties
$subscription->canAddProperty(); // true or false

// Check if admin can add another tenant
public function canAddTenant(): bool

// Returns true if subscription is active AND current tenants < max_tenants
$subscription->canAddTenant(); // true or false
```

### Subscription Management Methods

```php
// Renew subscription with new expiry date
public function renew(Carbon $newExpiryDate): void

// Usage
$subscription->renew(now()->addYear());

// Suspend subscription
public function suspend(): void

// Usage
$subscription->suspend();

// Activate subscription
public function activate(): void

// Usage
$subscription->activate();
```

## SubscriptionService API

### Creating Subscriptions

```php
/**
 * Create a new subscription for an admin user
 *
 * @param User $admin The admin user
 * @param string|SubscriptionPlanType $planType Plan type
 * @param Carbon $expiresAt Expiration date
 * @return Subscription Created subscription
 */
public function createSubscription(
    User $admin,
    string|SubscriptionPlanType $planType,
    Carbon $expiresAt
): Subscription

// Usage
$subscriptionService = app(SubscriptionService::class);
$subscription = $subscriptionService->createSubscription(
    $admin,
    'professional',
    now()->addYear()
);
```

### Renewing Subscriptions

```php
/**
 * Renew an existing subscription
 *
 * @param Subscription $subscription The subscription to renew
 * @param Carbon $newExpiryDate New expiration date
 * @return Subscription Renewed subscription
 */
public function renewSubscription(
    Subscription $subscription,
    Carbon $newExpiryDate
): Subscription

// Usage
$renewed = $subscriptionService->renewSubscription(
    $subscription,
    now()->addYear()
);
```

### Suspending Subscriptions

```php
/**
 * Suspend a subscription
 *
 * @param Subscription $subscription The subscription to suspend
 * @param string $reason Reason for suspension
 * @return void
 */
public function suspendSubscription(
    Subscription $subscription,
    string $reason
): void

// Usage
$subscriptionService->suspendSubscription(
    $subscription,
    'Payment overdue'
);
```

### Cancelling Subscriptions

```php
/**
 * Cancel a subscription
 *
 * @param Subscription $subscription The subscription to cancel
 * @return void
 */
public function cancelSubscription(Subscription $subscription): void

// Usage
$subscriptionService->cancelSubscription($subscription);
```

### Checking Subscription Status

```php
/**
 * Check subscription status and return detailed information
 *
 * @param User $admin The admin user
 * @return array Status information
 */
public function checkSubscriptionStatus(User $admin): array

// Usage
$status = $subscriptionService->checkSubscriptionStatus($admin);

// Returns:
[
    'has_subscription' => true,
    'is_active' => true,
    'status' => 'active',
    'expires_at' => Carbon instance,
    'days_until_expiry' => 45,
    'max_properties' => 50,
    'max_tenants' => 200,
    'current_properties' => 12,
    'current_tenants' => 35,
    'can_add_property' => true,
    'can_add_tenant' => true,
]
```

### Enforcing Subscription Limits

```php
/**
 * Enforce subscription limits for an admin user
 *
 * @param User $admin The admin user
 * @param string|null $resourceType 'property' or 'tenant'
 * @return void
 * @throws SubscriptionExpiredException If subscription expired
 * @throws SubscriptionLimitExceededException If limit exceeded
 */
public function enforceSubscriptionLimits(
    User $admin,
    ?string $resourceType = null
): void

// Usage - Check before creating property
try {
    $subscriptionService->enforceSubscriptionLimits($admin, 'property');
    // Proceed with property creation
} catch (SubscriptionExpiredException $e) {
    // Handle expired subscription
} catch (SubscriptionLimitExceededException $e) {
    // Handle limit exceeded
}

// Usage - Check before creating tenant
try {
    $subscriptionService->enforceSubscriptionLimits($admin, 'tenant');
    // Proceed with tenant creation
} catch (SubscriptionExpiredException $e) {
    // Handle expired subscription
} catch (SubscriptionLimitExceededException $e) {
    // Handle limit exceeded
}
```

## Middleware Integration

### CheckSubscriptionStatus Middleware

Automatically checks subscription status for admin routes:

```php
// Applied to admin routes in bootstrap/app.php
Route::middleware(['auth', 'subscription'])->group(function () {
    // Admin routes
});

// Behavior:
// - Allows read operations for expired subscriptions (grace period)
// - Blocks write operations (create/update/delete) for expired subscriptions
// - Redirects to subscription page with renewal prompt
```

## Usage Examples

### Example 1: Creating Admin with Subscription

```php
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;

$accountService = app(AccountManagementService::class);

// Create admin account (subscription created automatically)
$admin = $accountService->createAdminAccount([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secure-password',
    'organization_name' => 'Acme Properties',
    'plan_type' => 'professional',
    'expires_at' => now()->addYear(),
], $superadmin);

// Admin now has a professional subscription
$subscription = $admin->subscription;
echo "Max properties: {$subscription->max_properties}"; // 50
echo "Max tenants: {$subscription->max_tenants}";       // 200
```

### Example 2: Checking Limits Before Creating Property

```php
use App\Services\SubscriptionService;
use App\Exceptions\SubscriptionLimitExceededException;

$subscriptionService = app(SubscriptionService::class);

try {
    // Check if admin can create another property
    $subscriptionService->enforceSubscriptionLimits($admin, 'property');
    
    // Limit check passed, create property
    $property = Property::create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
        'unit_number' => '101',
        // ...
    ]);
    
} catch (SubscriptionLimitExceededException $e) {
    // Admin has reached their property limit
    return redirect()->back()->with('error', $e->getMessage());
}
```

### Example 3: Displaying Subscription Status in Dashboard

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);
$status = $subscriptionService->checkSubscriptionStatus($admin);

// Display in view
return view('admin.dashboard', [
    'subscription_status' => $status,
]);

// In Blade template:
@if($subscription_status['is_active'])
    <div class="alert alert-success">
        Subscription Active - {{ $subscription_status['days_until_expiry'] }} days remaining
    </div>
    
    <div class="usage-stats">
        <p>Properties: {{ $subscription_status['current_properties'] }} / {{ $subscription_status['max_properties'] }}</p>
        <p>Tenants: {{ $subscription_status['current_tenants'] }} / {{ $subscription_status['max_tenants'] }}</p>
    </div>
@else
    <div class="alert alert-warning">
        Subscription Expired - Read-only access
        <a href="{{ route('subscription.renew') }}">Renew Now</a>
    </div>
@endif
```

### Example 4: Renewing Subscription

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

// Renew for another year
$subscription = $admin->subscription;
$subscriptionService->renewSubscription(
    $subscription,
    now()->addYear()
);

// Subscription is now active again
```

## Error Handling

### SubscriptionExpiredException

Thrown when attempting to perform write operations with an expired subscription:

```php
try {
    $subscriptionService->enforceSubscriptionLimits($admin, 'property');
} catch (\App\Exceptions\SubscriptionExpiredException $e) {
    // Handle expired subscription
    return redirect()->route('subscription.renew')
        ->with('error', 'Your subscription has expired. Please renew to continue.');
}
```

### SubscriptionLimitExceededException

Thrown when attempting to create resources beyond subscription limits:

```php
try {
    $subscriptionService->enforceSubscriptionLimits($admin, 'tenant');
} catch (\App\Exceptions\SubscriptionLimitExceededException $e) {
    // Handle limit exceeded
    return redirect()->back()
        ->with('error', 'You have reached your tenant limit. Please upgrade your subscription.');
}
```

## Testing

### Factory Usage

```php
use App\Models\Subscription;
use App\Models\User;

// Create subscription with factory
$subscription = Subscription::factory()->basic()->create();
$subscription = Subscription::factory()->professional()->create();
$subscription = Subscription::factory()->enterprise()->create();

// Create admin with subscription
$admin = User::factory()->admin(1)->create();
$subscription = Subscription::factory()->professional()->create([
    'user_id' => $admin->id,
]);
```

### Test Examples

```php
it('enforces property limits for basic plan', function () {
    $admin = User::factory()->admin(1)->create();
    $subscription = Subscription::factory()->basic()->create([
        'user_id' => $admin->id,
    ]);
    
    // Create 10 properties (basic limit)
    Property::factory()->count(10)->create(['tenant_id' => $admin->tenant_id]);
    
    // Attempting 11th should throw exception
    $subscriptionService = app(SubscriptionService::class);
    
    expect(fn() => $subscriptionService->enforceSubscriptionLimits($admin, 'property'))
        ->toThrow(SubscriptionLimitExceededException::class);
});

it('allows read access during grace period', function () {
    $admin = User::factory()->admin(1)->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => 'expired',
        'expires_at' => now()->subDays(3), // Within 7-day grace period
    ]);
    
    // Should be able to view data
    actingAs($admin);
    $response = get(route('admin.properties.index'));
    $response->assertOk();
    
    // Should not be able to create
    $response = post(route('admin.properties.store'), [/* data */]);
    $response->assertRedirect(); // Redirected to subscription page
});
```

## Related Documentation

- [User Model API](./USER_MODEL_API.md)
- [Account Management Service API](./ACCOUNT_MANAGEMENT_API.md)
- [Hierarchical User Guide](../guides/HIERARCHICAL_USER_GUIDE.md)
- [Setup Guide](../guides/SETUP.md)
- [README](../../README.md)

## Changelog

### 2024-11-26 - Documentation Update
- Added comprehensive subscription system API documentation
- Documented subscription plans, features, and status
- Added usage examples and error handling
- Documented middleware integration
- Added testing examples
