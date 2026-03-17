<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant profile form with the current account details', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.profile.edit'))
        ->assertSuccessful()
        ->assertSeeText('My Profile')
        ->assertSee('value="'.$tenant->user->name.'"', false)
        ->assertSee('value="'.$tenant->user->email.'"', false);
});

it('updates the tenant profile and locale', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->from(route('tenant.profile.edit'))
        ->put(route('tenant.profile.update'), [
            'name' => 'Taylor Updated',
            'email' => 'taylor.updated@example.com',
            'locale' => 'lt',
        ])
        ->assertRedirect(route('tenant.profile.edit'));

    expect($tenant->user->fresh())
        ->name->toBe('Taylor Updated')
        ->email->toBe('taylor.updated@example.com')
        ->locale->toBe('lt');
});

it('updates the tenant password', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->from(route('tenant.profile.edit'))
        ->put(route('tenant.profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertRedirect(route('tenant.profile.edit'));

    expect(Hash::check('new-password-123', $tenant->user->fresh()->password))->toBeTrue();
});
