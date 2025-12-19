# Subscription Model - Quick Reference

**Model**: `app/Models/Subscription.php`  
**Table**: `subscriptions`  
**Purpose**: Manage subscription-based access control for Admin users

## Schema

```php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('plan_type'); // basic, professional, enterprise
    $table->string('status'); // active, expired, suspended, cancelled
    $table->timestamp('starts_at');
    $table->timestamp('expires_at');
    $table->integer('max_properties');
    $table->integer('max_tenants');
    $table->timestamps();
});
```

## Fillable Attributes

```php
[
    'user_id',
    'plan_type',
    'status',
    'starts_at',
    'expires_at',
    'max_properties',
    'max_tenants',
]
```

## Casts

```php
[
    'status' => SubscriptionStatus::class,
    'starts_at' => 'datetime',
    'expires_at' => 'datetime',
    'max_properties' => 'integer',
    'max_tenants' => 'integer',
]
```

## Relationships

### BelongsTo User
```php
$subscription->user; // Returns User instance
```

## Status Methods

### isActive()
Check if subscription is currently active.

```php
$subscription->isActive(); // bool
```

**Logic**: Returns `true` if status is ACTIVE and expires_at is in the future.

### isExpired()
Check if subscription has expired.

```php
$subscription->isExpired(); // bool
```

**Logic**: Returns `true` if expires_at is in the past.

### isSuspended()
Check if subscription is suspended.

```php
$subscription->isSuspended(); // bool
```

**Logic**: Returns `true` if status is SUSPENDED.

### daysUntilExpiry()
Get days until expiry (negative if expired).

```php
$subscription->daysUntilExpiry(); // int
```

**Returns**: Number of days until expiry (negative if already expired).

## Resource Limit Methods

### canAddProperty()
Check if user can add another property.

```php
$subscription->canAddProperty(); // bool
```

**Logic**:
- Returns `false` if subscription is not active
- Returns `false` if current property count >= max_properties
- Returns `true` otherwise

### canAddTenant()
Check if user can add another tenant.

```php
$subscription->canAddTenant(); // bool
```

**Logic**:
- Returns `false` if subscription is not active
- Returns `false` if current tenant count >= max_tenants
- Returns `true` otherwise

## State Transition Methods

### renew()
Renew subscription with new expiry date.

```php
$subscription->renew(now()->addYear());
```

**Effects**:
- Sets status to ACTIVE
- Updates expires_at to new date
- Invalidates cache

### suspend()
Suspend the subscription.

```php
$subscription->suspend();
```

**Effects**:
- Sets status to SUSPENDED
- Invalidates cache

### activate()
Activate the subscription.

```php
$subscription->activate();
```

**Effects**:
- Sets status to ACTIVE
- Invalidates cache

## Plan Types

### Basic Plan
```php
Subscription::factory()->basic()->create();
```
- **max_properties**: 10
- **max_tenants**: 50
- **Features**: Core billing features

### Professional Plan
```php
Subscription::factory()->professional()->create();
```
- **max_properties**: 50
- **max_tenants**: 200
- **Features**: Advanced reporting, bulk operations

### Enterprise Plan
```php
Subscription::factory()->enterprise()->create();
```
- **max_properties**: 999999 (unlimited)
- **max_tenants**: 999999 (unlimited)
- **Features**: Custom features, priority support

## Status Enum

```php
use App\Enums\SubscriptionStatus;

SubscriptionStatus::ACTIVE;     // Full access
SubscriptionStatus::EXPIRED;    // Read-only access
SubscriptionStatus::SUSPENDED;  // Temporary suspension
SubscriptionStatus::CANCELLED;  // Account deactivated
```

## Factory States

```php
// Plan types
Subscription::factory()->basic()->create();
Subscription::factory()->professional()->create();
Subscription::factory()->enterprise()->create();

// Status states
Subscription::factory()->suspended()->create();
Subscription::factory()->expired()->create();
Subscription::factory()->cancelled()->create();
Subscription::factory()->expiringSoon()->create(); // Expires within 7 days
```

## Cache Invalidation

The model automatically invalidates the SubscriptionChecker cache on:
- Save (create/update)
- Delete

```php
// Handled automatically by booted() method
$subscription->save();   // Cache invalidated
$subscription->delete(); // Cache invalidated
```

## Usage Examples

### Check if User Can Add Property
```php
$user = auth()->user();
$subscription = $user->subscription;

if ($subscription && $subscription->canAddProperty()) {
    // Allow property creation
} else {
    // Show limit reached message
}
```

### Check Subscription Status
```php
$subscription = $user->subscription;

if (!$subscription || !$subscription->isActive()) {
    // Redirect to subscription page
    return redirect()->route('subscription.index')
        ->with('warning', 'Your subscription has expired.');
}
```

### Renew Subscription
```php
$subscription = $user->subscription;
$subscription->renew(now()->addYear());

// Status is now ACTIVE
// expires_at is now +1 year
// Cache is invalidated
```

### Check Days Until Expiry
```php
$subscription = $user->subscription;
$days = $subscription->daysUntilExpiry();

if ($days <= 14 && $days > 0) {
    // Show renewal reminder
    session()->flash('info', "Your subscription expires in {$days} days.");
}
```

## Business Rules

1. **Active Status**: Subscription must have ACTIVE status AND future expires_at
2. **Resource Limits**: Enforced at model level via canAddProperty()/canAddTenant()
3. **Cache Invalidation**: Automatic on all state changes
4. **Grace Period**: 7 days after expiry (read-only access) - configured in `config/subscription.php`
5. **Expiry Warning**: 14 days before expiry - configured in `config/subscription.php`

## Configuration

**File**: `config/subscription.php`

```php
return [
    'grace_period_days' => 7,
    'expiry_warning_days' => 14,
    'plans' => [
        'basic' => [
            'max_properties' => 10,
            'max_tenants' => 50,
        ],
        'professional' => [
            'max_properties' => 50,
            'max_tenants' => 200,
        ],
        'enterprise' => [
            'max_properties' => 999999,
            'max_tenants' => 999999,
        ],
    ],
];
```

## Related Services

### SubscriptionChecker
**File**: `app/Services/SubscriptionChecker.php`

Provides cached subscription status checking:

```php
$checker = app(SubscriptionChecker::class);
$status = $checker->check($user);

// Returns: 'active', 'expired', 'suspended', 'cancelled', or 'missing'
```

### SubscriptionService
**File**: `app/Services/SubscriptionService.php`

Handles subscription business logic and operations.

## Testing

**Test File**: `tests/Unit/Models/SubscriptionTest.php`  
**Test Count**: 30 tests  
**Coverage**: 100%

```bash
# Run all subscription tests
php artisan test --filter=SubscriptionTest

# Run specific test
php artisan test --filter=SubscriptionTest::test_renew_method
```

## Related Documentation

- **Test Documentation**: [docs/testing/SUBSCRIPTION_MODEL_TEST_DOCUMENTATION.md](../testing/SUBSCRIPTION_MODEL_TEST_DOCUMENTATION.md)
- **Architecture**: [docs/architecture/SUBSCRIPTION_ARCHITECTURE.md](../architecture/SUBSCRIPTION_ARCHITECTURE.md)
- **Business Rules**: `docs/guides/SUBSCRIPTION_BUSINESS_RULES.md`
- **API Reference**: [docs/api/SUBSCRIPTION_API.md](../api/SUBSCRIPTION_API.md)

---

**Last Updated**: December 2025  
**Version**: Laravel 12, PHP 8.3+
