<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationInvitationModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function is_expired_returns_true_for_past_expiry_date(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'expires_at' => now()->subDays(1),
        ]);

        $this->assertTrue($invitation->isExpired());
    }

    /** @test */
    public function is_expired_returns_false_for_future_expiry_date(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'expires_at' => now()->addDays(1),
        ]);

        $this->assertFalse($invitation->isExpired());
    }

    /** @test */
    public function is_accepted_returns_true_when_accepted_at_is_set(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'accepted_at' => now(),
        ]);

        $this->assertTrue($invitation->isAccepted());
    }

    /** @test */
    public function is_accepted_returns_false_when_accepted_at_is_null(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'accepted_at' => null,
        ]);

        $this->assertFalse($invitation->isAccepted());
    }

    /** @test */
    public function is_pending_returns_true_for_unaccepted_unexpired_invitation(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'accepted_at' => null,
            'expires_at' => now()->addDays(3),
        ]);

        $this->assertTrue($invitation->isPending());
    }

    /** @test */
    public function is_pending_returns_false_for_accepted_invitation(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'accepted_at' => now(),
            'expires_at' => now()->addDays(3),
        ]);

        $this->assertFalse($invitation->isPending());
    }

    /** @test */
    public function is_pending_returns_false_for_expired_invitation(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'accepted_at' => null,
            'expires_at' => now()->subDays(1),
        ]);

        $this->assertFalse($invitation->isPending());
    }

    /** @test */
    public function accept_sets_accepted_at_timestamp(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'accepted_at' => null,
        ]);

        $invitation->accept();

        $this->assertNotNull($invitation->accepted_at);
        $this->assertTrue($invitation->accepted_at->isToday());
    }

    /** @test */
    public function resend_generates_new_token_and_extends_expiry(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'token' => 'old-token',
            'expires_at' => now()->subDays(1),
        ]);

        $originalToken = $invitation->token;
        $invitation->resend();

        $this->assertNotEquals($originalToken, $invitation->token);
        $this->assertTrue($invitation->expires_at->isFuture());
        $this->assertEquals(7, $invitation->expires_at->diffInDays(now()));
    }

    /** @test */
    public function cancel_deletes_the_invitation(): void
    {
        $invitation = OrganizationInvitation::factory()->create();
        $invitationId = $invitation->id;

        $invitation->cancel();

        $this->assertDatabaseMissing('organization_invitations', ['id' => $invitationId]);
    }

    /** @test */
    public function creating_invitation_generates_token_automatically(): void
    {
        $invitation = OrganizationInvitation::factory()->create(['token' => null]);

        $this->assertNotNull($invitation->token);
        $this->assertEquals(64, strlen($invitation->token));
    }

    /** @test */
    public function creating_invitation_sets_default_expiry(): void
    {
        $invitation = OrganizationInvitation::factory()->create(['expires_at' => null]);

        $this->assertNotNull($invitation->expires_at);
        $this->assertEquals(7, $invitation->expires_at->diffInDays(now()));
    }

    /** @test */
    public function pending_scope_returns_only_pending_invitations(): void
    {
        // Create accepted invitation
        OrganizationInvitation::factory()->create([
            'accepted_at' => now(),
            'expires_at' => now()->addDays(3),
        ]);

        // Create expired invitation
        OrganizationInvitation::factory()->create([
            'accepted_at' => null,
            'expires_at' => now()->subDays(1),
        ]);

        // Create pending invitation
        $pendingInvitation = OrganizationInvitation::factory()->create([
            'accepted_at' => null,
            'expires_at' => now()->addDays(3),
        ]);

        $pendingInvitations = OrganizationInvitation::pending()->get();

        $this->assertCount(1, $pendingInvitations);
        $this->assertEquals($pendingInvitation->id, $pendingInvitations->first()->id);
    }
}