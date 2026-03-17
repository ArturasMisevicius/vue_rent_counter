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

it('uses the newly selected locale on the redirected response', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->from(route('tenant.profile.edit'))
        ->followingRedirects()
        ->put(route('tenant.profile.update'), [
            'name' => 'Taylor Updated',
            'email' => 'taylor.updated@example.com',
            'locale' => 'lt',
        ])
        ->assertSuccessful()
        ->assertSee('<html lang="lt">', false);
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

it('requires the current password before changing the tenant password', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->from(route('tenant.profile.edit'))
        ->put(route('tenant.profile.password.update'), [
            'current_password' => '',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertRedirect(route('tenant.profile.edit'))
        ->assertSessionHasErrors(['current_password']);
});

it('requires the password confirmation to match', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->from(route('tenant.profile.edit'))
        ->put(route('tenant.profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ])
        ->assertRedirect(route('tenant.profile.edit'))
        ->assertSessionHasErrors(['password']);
});

it('renders tenant profile copy in lithuanian for lithuanian tenants', function () {
    $tenant = TenantPortalFactory::new()->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('tenant.profile.edit'))
        ->assertSuccessful()
        ->assertSeeText('Mano profilis')
        ->assertSeeText('Kalbos pasirinkimas');
});

it('shows human-readable labels for supported locales only on the tenant profile form', function () {
    config()->set('app.supported_locales', [
        'en' => 'EN',
        'lt' => 'LT',
        'ru' => 'RU',
    ]);
    config()->set('tenanto.locales', [
        'en' => 'English',
        'lt' => 'Lietuvių',
        'ru' => 'Русский',
    ]);

    $tenant = TenantPortalFactory::new()->create();

    $response = $this->actingAs($tenant->user)
        ->get(route('tenant.profile.edit'))
        ->assertSuccessful()
        ->assertSee('id="locale"', false);

    preg_match('/<select[^>]*id="locale"[^>]*>(.*?)<\/select>/s', $response->getContent(), $matches);

    $localeSelect = $matches[1] ?? null;

    expect($localeSelect)
        ->not->toBeNull()
        ->toContain('>English</option>')
        ->toContain('>Lietuvių</option>')
        ->toContain('>Русский</option>')
        ->not->toContain('>EN</option>')
        ->not->toContain('>LT</option>')
        ->not->toContain('>RU</option>')
        ->not->toContain('>Deutsch</option>');
});
