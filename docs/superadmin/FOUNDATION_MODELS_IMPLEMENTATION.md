# Superadmin Dashboard Foundation Models Implementation

## Overview

This document describes the implementation of the foundation data models for the Superadmin Dashboard Enhancement feature. This implementation covers task 1 from the specification.

## Implemented Components

### 1. Models

#### Organization Model Enhancements
- **File**: `app/Models/Organization.php`
- **New Methods**:
  - `daysUntilExpiry()`: Calculate days until subscription expires (returns negative if expired)
- **Existing Methods Verified**:
  - `isSuspended()`: Check if organization is suspended ✓
  - `suspend(string $reason)`: Suspend organization with reason ✓
  - `reactivate()`: Reactivate suspended organization ✓

#### Subscription Model Enhancements
- **File**: `app/Models/Subscription.php`
- **New Methods**:
  - `isSuspended()`: Check if subscription is suspended
  - `renew(Carbon $newExpiryDate)`: Renew subscription with new expiry date
  - `suspend()`: Suspend the subscription
  - `activate()`: Activate the subscription
- **Existing Methods Verified**:
  - `daysUntilExpiry()`: Calculate days until expiry ✓

#### OrganizationInvitation Model Enhancements
- **File**: `app/Models/OrganizationInvitation.php`
- **New Methods**:
  - `isPending()`: Check if invitation is pending (not accepted and not expired)
  - `cancel()`: Cancel the invitation (deletes it)
  - `resend()`: Resend invitation with new token and expiry date
- **Existing Methods Verified**:
  - `isExpired()`: Check if invitation expired ✓
  - `isAccepted()`: Check if invitation accepted ✓
  - `accept()`: Accept the invitation ✓


### 2. Tests

#### Unit Tests
- **File**: `tests/Unit/SuperadminFoundationModelsTest.php`
- **Coverage**:
  - Organization `daysUntilExpiry()` method
  - Organization suspend/reactivate workflow
  - Subscription renewal functionality
  - Subscription suspend/activate workflow
  - Subscription `isSuspended()` method
  - OrganizationInvitation `isPending()` method
  - OrganizationInvitation resend functionality

**Test Results**: All tests passing

## Usage Examples

### Organization Management

```php
// Check days until expiry
$org = Organization::find(1);
$daysLeft = $org->daysUntilExpiry(); // Returns integer (negative if expired)

// Suspend organization
$org->suspend('Payment overdue');

// Check if suspended
if ($org->isSuspended()) {
    // Handle suspended state
}

// Reactivate organization
$org->reactivate();
```

### Subscription Management

```php
// Renew subscription
$subscription = Subscription::find(1);
$subscription->renew(now()->addYear());

// Suspend subscription
$subscription->suspend();

// Check if suspended
if ($subscription->isSuspended()) {
    // Handle suspended state
}

// Activate subscription
$subscription->activate();

// Check days until expiry
$daysLeft = $subscription->daysUntilExpiry();
```

### Organization Invitations

```php
// Check if invitation is pending
$invitation = OrganizationInvitation::find(1);
if ($invitation->isPending()) {
    // Can be accepted
}

// Resend invitation
$invitation->resend(); // Generates new token and extends expiry

// Cancel invitation
$invitation->cancel(); // Deletes the invitation
```

## Requirements Validation

This implementation satisfies the following requirements from the specification:

- **Requirement 2.1**: Organization management with suspension capabilities ✓
- **Requirement 3.1**: Subscription management with renewal and status control ✓
- **Requirement 13.1**: Organization invitation system with token management ✓

## Next Steps

The foundation models are now in place. The next tasks in the implementation plan are:

1. **Task 2**: Create dashboard widgets (SubscriptionStatsWidget, OrganizationStatsWidget, etc.)
2. **Task 3**: Create SuperadminDashboard page
3. **Task 4**: Enhance OrganizationResource with new fields and actions

## Testing

To run the foundation models tests:

```bash
php artisan test --filter=SuperadminFoundationModelsTest
```

All tests should pass.

## Migration

To apply the new migration:

```bash
php artisan migrate
```

