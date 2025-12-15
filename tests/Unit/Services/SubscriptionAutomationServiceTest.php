<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use App\Notifications\SubscriptionExpiryWarningEmail;
use App\Services\SubscriptionAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionAutomationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubscriptionAutomationService::class);
        Notification::fake();
        Cache::flush();
    }

    /** @test */
    public function process_expiry_notifications_sends_notifications_for_correct_intervals(): void
    {
        // Create subscriptions expiring in 30, 14, and 7 days
        $user30 = User::factory()->create();
        $user14 = User::factory()->create();
        $user7 = User::factory()->create();

        $subscription30 = Subscription::factory()->create([
            'user_id' => $user30->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(30),
        ]);

        $subscription14 = Subscription::factory()->create([
            'user_id' => $user14->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(14),
        ]);

        $subscription7 = Subscription::factory()->create([
            'user_id' => $user7->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(7),
        ]);

        $result = $this->service->processExpiryNotifications();

        $this->assertEquals(3, $result['notifications_sent']);
        $this->assertEmpty($result['errors']);
        $this->assertCount(3, $result['processed_subscriptions']);

        Notification::assertSentTo($user30, SubscriptionExpiryWarningEmail::class);
        Notification::assertSentTo($user14, SubscriptionExpiryWarningEmail::class);
        Notification::assertSentTo($user7, SubscriptionExpiryWarningEmail::class);
    }

    /** @test */
    public function process_expiry_notifications_skips_inactive_subscriptions(): void
    {
        $user = User::factory()->create();
        
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::SUSPENDED,
            'expires_at' => now()->addDays(7),
        ]);

        $result = $this->service->processExpiryNotifications();

        $this->assertEquals(0, $result['notifications_sent']);
        Notification::assertNothingSent();
    }

    /** @test */
    public function process_expiry_notifications_prevents_duplicate_notifications(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(7),
        ]);

        // First call should send notification
        $result1 = $this->service->processExpiryNotifications();
        $this->assertEquals(1, $result1['notifications_sent']);

        // Second call should not send notification (cached)
        $result2 = $this->service->processExpiryNotifications();
        $this->assertEquals(0, $result2['notifications_sent']);

        Notification::assertSentToTimes($user, SubscriptionExpiryWarningEmail::class, 1);
    }

    /** @test */
    public function configure_auto_renewal_updates_subscription_settings(): void
    {
        $subscription = Subscription::factory()->create([
            'auto_renew' => false,
        ]);

        $this->service->configureAutoRenewal($subscription, true, 'quarterly');

        $subscription->refresh();
        $this->assertTrue($subscription->auto_renew);
        $this->assertEquals('quarterly', $subscription->renewal_period);
    }

    /** @test */
    public function process_auto_renewals_renews_eligible_subscriptions(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->startOfDay(),
            'auto_renew' => true,
            'renewal_period' => 'annually',
        ]);

        $originalExpiry = $subscription->expires_at;

        $result = $this->service->processAutoRenewals();

        $this->assertEquals(1, $result['renewals_processed']);
        $this->assertEmpty($result['failures']);

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->expires_at->isAfter($originalExpiry));
        $this->assertTrue($subscription->expires_at->isSameDay($originalExpiry->copy()->addYear()));
    }

    /** @test */
    public function process_auto_renewals_skips_subscriptions_without_auto_renew(): void
    {
        $user = User::factory()->create();
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->startOfDay(),
            'auto_renew' => false,
        ]);

        $result = $this->service->processAutoRenewals();

        $this->assertEquals(0, $result['renewals_processed']);
    }

    /** @test */
    public function process_auto_renewals_handles_failures_gracefully(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->startOfDay(),
            'auto_renew' => true,
            'renewal_period' => 'annually',
        ]);

        $service = new class extends SubscriptionAutomationService {
            protected function executeAutoRenewal(Subscription $subscription): void
            {
                throw new \Exception('Payment failed');
            }
        };

        $result = $service->processAutoRenewals();

        $this->assertEquals(0, $result['renewals_processed']);
        $this->assertCount(1, $result['failures']);
        $this->assertSame($subscription->id, $result['failures'][0]['subscription_id']);
    }

    /** @test */
    public function calculate_new_expiry_date_works_for_different_periods(): void
    {
        $currentExpiry = Carbon::parse('2024-01-01');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateNewExpiryDate');
        $method->setAccessible(true);

        $monthlyExpiry = $method->invoke($this->service, $currentExpiry->copy(), 'monthly');
        $quarterlyExpiry = $method->invoke($this->service, $currentExpiry->copy(), 'quarterly');
        $annuallyExpiry = $method->invoke($this->service, $currentExpiry->copy(), 'annually');

        $this->assertEquals('2024-02-01', $monthlyExpiry->format('Y-m-d'));
        $this->assertEquals('2024-04-01', $quarterlyExpiry->format('Y-m-d'));
        $this->assertEquals('2025-01-01', $annuallyExpiry->format('Y-m-d'));
    }

    /** @test */
    public function get_renewal_history_returns_filtered_results(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        // Create renewal records
        SubscriptionRenewal::factory()->create([
            'subscription_id' => $subscription->id,
            'method' => 'automatic',
            'period' => 'annually',
            'created_at' => now()->subDays(10),
        ]);

        SubscriptionRenewal::factory()->create([
            'subscription_id' => $subscription->id,
            'method' => 'manual',
            'period' => 'monthly',
            'created_at' => now()->subDays(5),
        ]);

        // Test filtering by method
        $result = $this->service->getRenewalHistory($subscription, ['method' => 'automatic']);
        
        $this->assertArrayHasKey('renewals', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertEquals(['method' => 'automatic'], $result['filters_applied']);
    }

    /** @test */
    public function get_subscriptions_expiring_in_returns_correct_subscriptions(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Subscription expiring in exactly 7 days
        $subscription7 = Subscription::factory()->create([
            'user_id' => $user1->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(7)->startOfDay(),
        ]);

        // Subscription expiring in 8 days (should not be included)
        Subscription::factory()->create([
            'user_id' => $user2->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(8)->startOfDay(),
        ]);

        // Inactive subscription expiring in 7 days (should not be included)
        Subscription::factory()->create([
            'user_id' => $user3->id,
            'status' => SubscriptionStatus::SUSPENDED,
            'expires_at' => now()->addDays(7)->startOfDay(),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getSubscriptionsExpiringIn');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 7);

        $this->assertCount(1, $result);
        $this->assertEquals($subscription7->id, $result->first()->id);
    }
}
