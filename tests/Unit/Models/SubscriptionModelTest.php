<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function is_active_returns_true_for_active_future_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertTrue($subscription->isActive());
    }

    /** @test */
    public function is_active_returns_false_for_expired_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->subDays(1),
        ]);

        $this->assertFalse($subscription->isActive());
    }

    /** @test */
    public function is_active_returns_false_for_suspended_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::SUSPENDED,
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertFalse($subscription->isActive());
    }

    /** @test */
    public function is_expired_returns_true_for_past_expiry_date(): void
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->subDays(1),
        ]);

        $this->assertTrue($subscription->isExpired());
    }

    /** @test */
    public function is_expired_returns_false_for_future_expiry_date(): void
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(1),
        ]);

        $this->assertFalse($subscription->isExpired());
    }

    /** @test */
    public function days_until_expiry_returns_correct_positive_days(): void
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(15),
        ]);

        $this->assertEquals(15, $subscription->daysUntilExpiry());
    }

    /** @test */
    public function days_until_expiry_returns_correct_negative_days(): void
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->subDays(5),
        ]);

        $this->assertEquals(-5, $subscription->daysUntilExpiry());
    }

    /** @test */
    public function renew_updates_expiry_date_and_activates(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::EXPIRED,
            'expires_at' => now()->subDays(10),
        ]);

        $newExpiryDate = now()->addDays(365);
        $subscription->renew($newExpiryDate);

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals($newExpiryDate->format('Y-m-d H:i:s'), $subscription->expires_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function suspend_changes_status_to_suspended(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $subscription->suspend();

        $this->assertEquals(SubscriptionStatus::SUSPENDED, $subscription->status);
    }

    /** @test */
    public function activate_changes_status_to_active(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::SUSPENDED,
        ]);

        $subscription->activate();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    /** @test */
    public function is_suspended_returns_true_for_suspended_status(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::SUSPENDED,
        ]);

        $this->assertTrue($subscription->isSuspended());
    }

    /** @test */
    public function is_suspended_returns_false_for_active_status(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $this->assertFalse($subscription->isSuspended());
    }

    /** @test */
    public function can_add_property_returns_false_for_inactive_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::EXPIRED,
            'max_properties' => 10,
        ]);

        $this->assertFalse($subscription->canAddProperty());
    }

    /** @test */
    public function can_add_tenant_returns_false_for_inactive_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::SUSPENDED,
            'max_tenants' => 50,
        ]);

        $this->assertFalse($subscription->canAddTenant());
    }
}