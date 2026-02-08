<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_has_fillable_attributes(): void
    {
        $fillable = [
            'user_id', 'plan_type', 'status', 'starts_at', 'expires_at',
            'max_properties', 'max_tenants', 'auto_renew', 'renewal_period',
        ];

        $subscription = new Subscription();
        $this->assertEquals($fillable, $subscription->getFillable());
    }

    public function test_subscription_casts_attributes_correctly(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'max_properties' => 10,
            'max_tenants' => 50,
        ]);

        $this->assertInstanceOf(SubscriptionStatus::class, $subscription->status);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscription->starts_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscription->expires_at);
        $this->assertIsInt($subscription->max_properties);
        $this->assertIsInt($subscription->max_tenants);
    }

    public function test_subscription_belongs_to_user(): void
    {
        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $subscription->user);
        $this->assertEquals($user->id, $subscription->user->id);
    }

    public function test_is_active_method(): void
    {
        $activeSubscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addMonth(),
        ]);

        $expiredSubscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($activeSubscription->isActive());
        $this->assertFalse($expiredSubscription->isActive());
    }

    public function test_is_active_returns_false_for_suspended_subscription(): void
    {
        $subscription = Subscription::factory()->suspended()->create([
            'expires_at' => now()->addMonth(),
        ]);

        $this->assertFalse($subscription->isActive());
    }

    public function test_is_active_returns_false_for_cancelled_subscription(): void
    {
        $subscription = Subscription::factory()->cancelled()->create([
            'expires_at' => now()->addMonth(),
        ]);

        $this->assertFalse($subscription->isActive());
    }

    public function test_is_active_returns_false_for_expired_status(): void
    {
        $subscription = Subscription::factory()->expired()->create();

        $this->assertFalse($subscription->isActive());
    }

    public function test_is_expired_method(): void
    {
        $expiredSubscription = Subscription::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $activeSubscription = Subscription::factory()->create([
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($expiredSubscription->isExpired());
        $this->assertFalse($activeSubscription->isExpired());
    }

    public function test_is_expired_returns_true_on_exact_expiry_date(): void
    {
        // Boundary test: expires_at is exactly now
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->subSecond(),
        ]);

        $this->assertTrue($subscription->isExpired());
    }

    public function test_days_until_expiry_method(): void
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(10),
        ]);

        $daysUntilExpiry = $subscription->daysUntilExpiry();

        $this->assertEquals(10, $daysUntilExpiry);
    }

    public function test_days_until_expiry_returns_negative_for_expired(): void
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->subDays(5),
        ]);

        $daysUntilExpiry = $subscription->daysUntilExpiry();

        $this->assertEquals(-5, $daysUntilExpiry);
    }

    public function test_days_until_expiry_returns_zero_for_today(): void
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->endOfDay(),
        ]);

        $daysUntilExpiry = $subscription->daysUntilExpiry();

        $this->assertEquals(0, $daysUntilExpiry);
    }

    public function test_can_add_property_method(): void
    {
        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addMonth(),
            'max_properties' => 10,
        ]);

        $this->assertTrue($subscription->canAddProperty());
    }

    public function test_can_add_property_returns_false_when_limit_reached(): void
    {
        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addMonth(),
            'max_properties' => 2,
        ]);

        // Create properties up to the limit (properties belong to buildings, which belong to tenant)
        $building = \App\Models\Building::factory()->create(['tenant_id' => $user->tenant_id]);
        Property::factory()->count(2)->create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $building->id,
        ]);

        $this->assertFalse($subscription->fresh()->canAddProperty());
    }

    public function test_can_add_property_returns_false_for_inactive_subscription(): void
    {
        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->expired()->create([
            'user_id' => $user->id,
            'max_properties' => 10,
        ]);

        $this->assertFalse($subscription->canAddProperty());
    }

    public function test_can_add_tenant_method(): void
    {
        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addMonth(),
            'max_tenants' => 50,
        ]);

        $this->assertTrue($subscription->canAddTenant());
    }

    public function test_can_add_tenant_returns_false_when_limit_reached(): void
    {
        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addMonth(),
            'max_tenants' => 2,
        ]);

        // Create tenant users up to the limit (use correct column name: parent_user_id)
        User::factory()->tenant()->count(2)->create([
            'tenant_id' => $user->tenant_id,
            'parent_user_id' => $user->id,
        ]);

        $this->assertFalse($subscription->fresh()->canAddTenant());
    }

    public function test_can_add_tenant_returns_false_for_inactive_subscription(): void
    {
        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->suspended()->create([
            'user_id' => $user->id,
            'max_tenants' => 50,
        ]);

        $this->assertFalse($subscription->canAddTenant());
    }

    public function test_is_suspended_method(): void
    {
        $suspendedSubscription = Subscription::factory()->suspended()->create();

        $activeSubscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $this->assertTrue($suspendedSubscription->isSuspended());
        $this->assertFalse($activeSubscription->isSuspended());
    }

    public function test_renew_method(): void
    {
        $subscription = Subscription::factory()->expired()->create();

        $newExpiryDate = now()->addYear();
        $subscription->renew($newExpiryDate);

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->fresh()->status);
        $this->assertEquals($newExpiryDate->format('Y-m-d'), $subscription->fresh()->expires_at->format('Y-m-d'));
    }

    public function test_renew_method_invalidates_cache(): void
    {
        // Mock MUST be registered BEFORE creating the model
        $checker = $this->mock(SubscriptionChecker::class);
        $checker->shouldReceive('invalidateCache')
            ->atLeast()->once()
            ->withAnyArgs();

        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->expired()->create([
            'user_id' => $user->id,
        ]);

        $subscription->renew(now()->addYear());
    }

    public function test_suspend_method(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $subscription->suspend();

        $this->assertEquals(SubscriptionStatus::SUSPENDED, $subscription->fresh()->status);
    }

    public function test_suspend_method_invalidates_cache(): void
    {
        // Mock MUST be registered BEFORE creating the model
        $checker = $this->mock(SubscriptionChecker::class);
        $checker->shouldReceive('invalidateCache')
            ->atLeast()->once()
            ->withAnyArgs();

        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $subscription->suspend();
    }

    public function test_activate_method(): void
    {
        $subscription = Subscription::factory()->suspended()->create();

        $subscription->activate();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->fresh()->status);
    }

    public function test_activate_method_invalidates_cache(): void
    {
        // Mock MUST be registered BEFORE creating the model
        $checker = $this->mock(SubscriptionChecker::class);
        $checker->shouldReceive('invalidateCache')
            ->atLeast()->once()
            ->withAnyArgs();

        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->suspended()->create([
            'user_id' => $user->id,
        ]);

        $subscription->activate();
    }

    public function test_subscription_factory_creates_valid_subscription(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertNotNull($subscription->user_id);
        $this->assertNotNull($subscription->plan_type);
        $this->assertInstanceOf(SubscriptionStatus::class, $subscription->status);
        $this->assertIsInt($subscription->max_properties);
        $this->assertIsInt($subscription->max_tenants);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscription->starts_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscription->expires_at);
    }

    public function test_subscription_expires_after_starts(): void
    {
        $subscription = Subscription::factory()->create([
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertTrue($subscription->expires_at->isAfter($subscription->starts_at));
    }

    public function test_subscription_cache_invalidated_on_save(): void
    {
        // Mock MUST be registered BEFORE creating the model
        $checker = $this->mock(SubscriptionChecker::class);
        $checker->shouldReceive('invalidateCache')
            ->once()
            ->withAnyArgs();

        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->make([
            'user_id' => $user->id,
        ]);

        $subscription->save();
    }

    public function test_subscription_cache_invalidated_on_delete(): void
    {
        // Mock MUST be registered BEFORE creating the model
        $checker = $this->mock(SubscriptionChecker::class);
        $checker->shouldReceive('invalidateCache')
            ->atLeast()->once()
            ->withAnyArgs();

        $user = User::factory()->admin()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
        ]);

        $subscription->delete();
    }

    public function test_basic_plan_has_correct_limits(): void
    {
        $subscription = Subscription::factory()->basic()->create();

        $this->assertEquals(10, $subscription->max_properties);
        $this->assertEquals(50, $subscription->max_tenants);
    }

    public function test_professional_plan_has_correct_limits(): void
    {
        $subscription = Subscription::factory()->professional()->create();

        $this->assertEquals(50, $subscription->max_properties);
        $this->assertEquals(200, $subscription->max_tenants);
    }

    public function test_enterprise_plan_has_correct_limits(): void
    {
        $subscription = Subscription::factory()->enterprise()->create();

        $this->assertEquals(999999, $subscription->max_properties);
        $this->assertEquals(999999, $subscription->max_tenants);
    }

    public function test_cancelled_subscription_cannot_be_active(): void
    {
        $subscription = Subscription::factory()->cancelled()->create([
            'expires_at' => now()->addYear(),
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertEquals(SubscriptionStatus::CANCELLED, $subscription->status);
    }

    public function test_expiring_soon_factory_state(): void
    {
        $subscription = Subscription::factory()->expiringSoon()->create();

        $daysUntilExpiry = $subscription->daysUntilExpiry();

        $this->assertLessThanOrEqual(7, $daysUntilExpiry);
        $this->assertGreaterThan(0, $daysUntilExpiry);
    }
}
