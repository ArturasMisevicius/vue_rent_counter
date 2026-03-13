<?php

use App\Enums\SubscriptionPlanType;
use App\Models\PlatformOrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email' => 'superadmin@test.com',
    ]);
});

test('superadmin can view platform organization invitations index', function () {
    $this->actingAs($this->superadmin);

    $response = $this->get('/admin/platform-organization-invitations');

    $response->assertOk();
});

test('superadmin can create platform organization invitation', function () {
    $this->actingAs($this->superadmin);

    $invitation = PlatformOrganizationInvitation::factory()->create([
        'invited_by' => $this->superadmin->id,
    ]);

    expect(PlatformOrganizationInvitation::count())->toBe(1);
    expect(PlatformOrganizationInvitation::first()->organization_name)->toBe($invitation->organization_name);
});

test('invitation has correct default values', function () {
    $invitation = PlatformOrganizationInvitation::factory()->create([
        'invited_by' => $this->superadmin->id,
    ]);

    expect($invitation->status)->toBe('pending');
    expect($invitation->token)->not->toBeNull();
    expect($invitation->expires_at)->not->toBeNull();
});

test('invitation can be marked as pending', function () {
    $invitation = PlatformOrganizationInvitation::factory()->create([
        'invited_by' => $this->superadmin->id,
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    expect($invitation->isPending())->toBeTrue();
    expect($invitation->isExpired())->toBeFalse();
    expect($invitation->isAccepted())->toBeFalse();
});

test('invitation can be marked as expired', function () {
    $invitation = PlatformOrganizationInvitation::factory()->expired()->create([
        'invited_by' => $this->superadmin->id,
    ]);

    expect($invitation->isExpired())->toBeTrue();
    expect($invitation->isPending())->toBeFalse();
});

test('invitation can be marked as accepted', function () {
    $invitation = PlatformOrganizationInvitation::factory()->accepted()->create([
        'invited_by' => $this->superadmin->id,
    ]);

    expect($invitation->isAccepted())->toBeTrue();
    expect($invitation->isPending())->toBeFalse();
});

test('invitation can be cancelled', function () {
    $invitation = PlatformOrganizationInvitation::factory()->create([
        'invited_by' => $this->superadmin->id,
        'status' => 'pending',
    ]);

    $invitation->cancel();

    expect($invitation->fresh()->status)->toBe('cancelled');
});

test('invitation can be resent', function () {
    $invitation = PlatformOrganizationInvitation::factory()->create([
        'invited_by' => $this->superadmin->id,
        'status' => 'pending',
        'expires_at' => now()->addDays(1), // Set a specific expiry date
    ]);

    $originalToken = $invitation->token;
    $originalExpiry = $invitation->expires_at->copy();

    sleep(1); // Ensure time passes
    $invitation->resend();
    $invitation->refresh();

    expect($invitation->token)->not->toBe($originalToken);
    expect($invitation->expires_at->greaterThan($originalExpiry))->toBeTrue();
    expect($invitation->status)->toBe('pending');
});

test('non-superadmin cannot access platform organization invitations', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $response = $this->get('/admin/platform-organization-invitations');

    $response->assertForbidden();
});
