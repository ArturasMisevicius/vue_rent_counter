<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Models\User;
use App\ValueObjects\UserState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User State Value Object Tests
 * 
 * Tests the UserState value object to ensure proper
 * user status and state checking.
 */
class UserStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_active_returns_true_for_active_user(): void
    {
        $user = User::factory()->create(['is_active' => true, 'suspended_at' => null]);
        $state = new UserState($user);

        $this->assertTrue($state->isActive());
    }

    public function test_is_active_returns_false_for_inactive_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $state = new UserState($user);

        $this->assertFalse($state->isActive());
    }

    public function test_is_active_returns_false_for_suspended_user(): void
    {
        $user = User::factory()->create(['is_active' => true, 'suspended_at' => now()]);
        $state = new UserState($user);

        $this->assertFalse($state->isActive());
    }

    public function test_is_suspended_returns_true_for_suspended_user(): void
    {
        $user = User::factory()->create(['suspended_at' => now()]);
        $state = new UserState($user);

        $this->assertTrue($state->isSuspended());
    }

    public function test_is_suspended_returns_false_for_non_suspended_user(): void
    {
        $user = User::factory()->create(['suspended_at' => null]);
        $state = new UserState($user);

        $this->assertFalse($state->isSuspended());
    }

    public function test_is_email_verified_returns_true_for_verified_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $state = new UserState($user);

        $this->assertTrue($state->isEmailVerified());
    }

    public function test_is_email_verified_returns_false_for_unverified_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $state = new UserState($user);

        $this->assertFalse($state->isEmailVerified());
    }

    public function test_has_recent_activity_returns_true_for_recent_login(): void
    {
        $user = User::factory()->create(['last_login_at' => now()->subDays(15)]);
        $state = new UserState($user);

        $this->assertTrue($state->hasRecentActivity());
    }

    public function test_has_recent_activity_returns_false_for_old_login(): void
    {
        $user = User::factory()->create(['last_login_at' => now()->subDays(45)]);
        $state = new UserState($user);

        $this->assertFalse($state->hasRecentActivity());
    }

    public function test_has_recent_activity_returns_false_for_never_logged_in(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);
        $state = new UserState($user);

        $this->assertFalse($state->hasRecentActivity());
    }

    public function test_days_since_last_login_returns_correct_value(): void
    {
        $user = User::factory()->create(['last_login_at' => now()->subDays(10)]);
        $state = new UserState($user);

        $this->assertEquals(10, $state->daysSinceLastLogin());
    }

    public function test_days_since_last_login_returns_null_for_never_logged_in(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);
        $state = new UserState($user);

        $this->assertNull($state->daysSinceLastLogin());
    }

    public function test_get_suspension_reason_returns_reason_for_suspended_user(): void
    {
        $reason = 'Policy violation';
        $user = User::factory()->create([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
        $state = new UserState($user);

        $this->assertEquals($reason, $state->getSuspensionReason());
    }

    public function test_get_suspension_reason_returns_null_for_non_suspended_user(): void
    {
        $user = User::factory()->create(['suspended_at' => null]);
        $state = new UserState($user);

        $this->assertNull($state->getSuspensionReason());
    }

    public function test_get_suspension_date_returns_date_for_suspended_user(): void
    {
        $suspensionDate = now();
        $user = User::factory()->create(['suspended_at' => $suspensionDate]);
        $state = new UserState($user);

        $this->assertEquals($suspensionDate->toDateTimeString(), $state->getSuspensionDate()->toDateTimeString());
    }

    public function test_can_perform_actions_returns_true_for_active_verified_user(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'suspended_at' => null,
            'email_verified_at' => now(),
        ]);
        $state = new UserState($user);

        $this->assertTrue($state->canPerformActions());
    }

    public function test_can_perform_actions_returns_false_for_inactive_user(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'email_verified_at' => now(),
        ]);
        $state = new UserState($user);

        $this->assertFalse($state->canPerformActions());
    }

    public function test_can_perform_actions_returns_false_for_unverified_user(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'suspended_at' => null,
            'email_verified_at' => null,
        ]);
        $state = new UserState($user);

        $this->assertFalse($state->canPerformActions());
    }

    public function test_get_status_label_returns_correct_labels(): void
    {
        $suspendedUser = User::factory()->create(['suspended_at' => now()]);
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);
        $activeUser = User::factory()->create([
            'is_active' => true,
            'suspended_at' => null,
            'email_verified_at' => now(),
        ]);

        $this->assertEquals('suspended', (new UserState($suspendedUser))->getStatusLabel());
        $this->assertEquals('inactive', (new UserState($inactiveUser))->getStatusLabel());
        $this->assertEquals('unverified', (new UserState($unverifiedUser))->getStatusLabel());
        $this->assertEquals('active', (new UserState($activeUser))->getStatusLabel());
    }

    public function test_to_array_returns_all_state_information(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'suspended_at' => null,
            'email_verified_at' => now(),
            'last_login_at' => now()->subDays(5),
        ]);
        $state = new UserState($user);

        $array = $state->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('is_suspended', $array);
        $this->assertArrayHasKey('is_email_verified', $array);
        $this->assertArrayHasKey('has_recent_activity', $array);
        $this->assertArrayHasKey('days_since_last_login', $array);
        $this->assertArrayHasKey('suspension_reason', $array);
        $this->assertArrayHasKey('suspension_date', $array);
        $this->assertArrayHasKey('can_perform_actions', $array);
        $this->assertArrayHasKey('status_label', $array);

        $this->assertTrue($array['is_active']);
        $this->assertTrue($array['is_email_verified']);
        $this->assertEquals('active', $array['status_label']);
    }
}