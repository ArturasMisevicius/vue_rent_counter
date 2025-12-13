<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function is_suspended_returns_true_when_suspended_at_is_set(): void
    {
        $organization = Organization::factory()->create([
            'suspended_at' => now(),
        ]);

        $this->assertTrue($organization->isSuspended());
    }

    /** @test */
    public function is_suspended_returns_false_when_suspended_at_is_null(): void
    {
        $organization = Organization::factory()->create([
            'suspended_at' => null,
        ]);

        $this->assertFalse($organization->isSuspended());
    }

    /** @test */
    public function suspend_sets_suspended_at_and_reason(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => true,
            'suspended_at' => null,
        ]);

        $reason = 'Payment overdue';
        $organization->suspend($reason);

        $this->assertFalse($organization->is_active);
        $this->assertNotNull($organization->suspended_at);
        $this->assertEquals($reason, $organization->suspension_reason);
    }

    /** @test */
    public function reactivate_clears_suspension_data(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => 'Test suspension',
        ]);

        $organization->reactivate();

        $this->assertTrue($organization->is_active);
        $this->assertNull($organization->suspended_at);
        $this->assertNull($organization->suspension_reason);
    }

    /** @test */
    public function days_until_expiry_returns_correct_days(): void
    {
        $futureDate = now()->addDays(10);
        $organization = Organization::factory()->create([
            'subscription_ends_at' => $futureDate,
        ]);

        $this->assertEquals(10, $organization->daysUntilExpiry());
    }

    /** @test */
    public function days_until_expiry_returns_negative_for_expired(): void
    {
        $pastDate = now()->subDays(5);
        $organization = Organization::factory()->create([
            'subscription_ends_at' => $pastDate,
        ]);

        $this->assertEquals(-5, $organization->daysUntilExpiry());
    }

    /** @test */
    public function days_until_expiry_returns_zero_when_no_expiry_date(): void
    {
        $organization = Organization::factory()->create([
            'subscription_ends_at' => null,
        ]);

        $this->assertEquals(0, $organization->daysUntilExpiry());
    }

    /** @test */
    public function is_active_returns_true_when_active_and_not_suspended(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => true,
            'suspended_at' => null,
        ]);

        $this->assertTrue($organization->isActive());
    }

    /** @test */
    public function is_active_returns_false_when_suspended(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => true,
            'suspended_at' => now(),
        ]);

        $this->assertFalse($organization->isActive());
    }

    /** @test */
    public function is_active_returns_false_when_not_active(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => false,
            'suspended_at' => null,
        ]);

        $this->assertFalse($organization->isActive());
    }

    /** @test */
    public function has_active_subscription_returns_true_for_future_expiry(): void
    {
        $organization = Organization::factory()->create([
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $this->assertTrue($organization->hasActiveSubscription());
    }

    /** @test */
    public function has_active_subscription_returns_false_for_past_expiry(): void
    {
        $organization = Organization::factory()->create([
            'subscription_ends_at' => now()->subDays(1),
        ]);

        $this->assertFalse($organization->hasActiveSubscription());
    }

    /** @test */
    public function has_active_subscription_returns_true_when_on_trial(): void
    {
        $organization = Organization::factory()->create([
            'trial_ends_at' => now()->addDays(7),
            'subscription_ends_at' => now()->subDays(1), // Expired subscription
        ]);

        $this->assertTrue($organization->hasActiveSubscription());
    }

    /** @test */
    public function can_add_property_returns_true_when_under_limit(): void
    {
        $organization = Organization::factory()->create([
            'max_properties' => 5,
        ]);

        // Create 3 properties (under limit of 5)
        Property::factory()->count(3)->create(['tenant_id' => $organization->id]);

        $this->assertTrue($organization->canAddProperty());
    }

    /** @test */
    public function can_add_property_returns_false_when_at_limit(): void
    {
        $organization = Organization::factory()->create([
            'max_properties' => 3,
        ]);

        // Create 3 properties (at limit)
        Property::factory()->count(3)->create(['tenant_id' => $organization->id]);

        $this->assertFalse($organization->canAddProperty());
    }

    /** @test */
    public function can_add_user_returns_true_when_under_limit(): void
    {
        $organization = Organization::factory()->create([
            'max_users' => 5,
        ]);

        // Create 3 users (under limit of 5)
        User::factory()->count(3)->create(['tenant_id' => $organization->id]);

        $this->assertTrue($organization->canAddUser());
    }

    /** @test */
    public function can_add_user_returns_false_when_at_limit(): void
    {
        $organization = Organization::factory()->create([
            'max_users' => 3,
        ]);

        // Create 3 users (at limit)
        User::factory()->count(3)->create(['tenant_id' => $organization->id]);

        $this->assertFalse($organization->canAddUser());
    }

    /** @test */
    public function upgrade_plan_updates_limits_and_features(): void
    {
        $organization = Organization::factory()->create([
            'plan' => SubscriptionPlan::BASIC,
            'max_properties' => 100,
            'max_users' => 10,
        ]);

        $organization->upgradePlan(SubscriptionPlan::PROFESSIONAL);

        $this->assertEquals(SubscriptionPlan::PROFESSIONAL, $organization->plan);
        $this->assertEquals(500, $organization->max_properties);
        $this->assertEquals(50, $organization->max_users);
        $this->assertTrue($organization->features['advanced_reporting']);
        $this->assertFalse($organization->features['api_access']); // Only for enterprise
    }

    /** @test */
    public function get_tenant_status_returns_suspended_when_suspended(): void
    {
        $organization = Organization::factory()->create([
            'suspended_at' => now(),
        ]);

        $this->assertEquals(TenantStatus::SUSPENDED, $organization->getTenantStatus());
    }

    /** @test */
    public function get_tenant_status_returns_cancelled_when_inactive(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => false,
            'suspended_at' => null,
        ]);

        $this->assertEquals(TenantStatus::CANCELLED, $organization->getTenantStatus());
    }

    /** @test */
    public function get_tenant_status_returns_active_when_active_with_subscription(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => true,
            'suspended_at' => null,
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $this->assertEquals(TenantStatus::ACTIVE, $organization->getTenantStatus());
    }

    /** @test */
    public function get_remaining_properties_returns_correct_count(): void
    {
        $organization = Organization::factory()->create([
            'max_properties' => 10,
        ]);

        Property::factory()->count(3)->create(['tenant_id' => $organization->id]);

        $this->assertEquals(7, $organization->getRemainingProperties());
    }

    /** @test */
    public function get_remaining_users_returns_correct_count(): void
    {
        $organization = Organization::factory()->create([
            'max_users' => 10,
        ]);

        User::factory()->count(4)->create(['tenant_id' => $organization->id]);

        $this->assertEquals(6, $organization->getRemainingUsers());
    }

    /** @test */
    public function has_feature_returns_true_for_enabled_features(): void
    {
        $organization = Organization::factory()->create([
            'features' => [
                'advanced_reporting' => true,
                'api_access' => false,
            ],
        ]);

        $this->assertTrue($organization->hasFeature('advanced_reporting'));
        $this->assertFalse($organization->hasFeature('api_access'));
        $this->assertFalse($organization->hasFeature('non_existent_feature'));
    }

    /** @test */
    public function is_over_quota_detects_storage_overuse(): void
    {
        $organization = Organization::factory()->create([
            'storage_used_mb' => 1500,
            'resource_quotas' => ['storage_mb' => 1000],
        ]);

        $this->assertTrue($organization->isOverQuota('storage_mb'));
    }

    /** @test */
    public function is_over_quota_detects_api_calls_overuse(): void
    {
        $organization = Organization::factory()->create([
            'api_calls_today' => 15000,
            'api_calls_quota' => 10000,
        ]);

        $this->assertTrue($organization->isOverQuota('api_calls'));
    }

    /** @test */
    public function calculate_monthly_revenue_returns_correct_amount(): void
    {
        $basicOrg = Organization::factory()->create(['plan' => SubscriptionPlan::BASIC]);
        $professionalOrg = Organization::factory()->create(['plan' => SubscriptionPlan::PROFESSIONAL]);
        $enterpriseOrg = Organization::factory()->create(['plan' => SubscriptionPlan::ENTERPRISE]);

        $this->assertEquals(29.99, $basicOrg->calculateMonthlyRevenue());
        $this->assertEquals(99.99, $professionalOrg->calculateMonthlyRevenue());
        $this->assertEquals(299.99, $enterpriseOrg->calculateMonthlyRevenue());
    }
}