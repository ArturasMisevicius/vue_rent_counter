<?php

use App\Filament\Pages\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant profile form with the current account details', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful()
        ->assertSeeText('My Profile')
        ->assertSeeText('Personal Information')
        ->assertSeeText('Change Password')
        ->assertSee('value="'.$tenant->user->name.'"', false)
        ->assertSee('value="'.$tenant->user->email.'"', false);
});

it('updates the tenant profile and locale', function () {
    $tenant = TenantPortalFactory::new()->create();

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('profileForm.name', 'Taylor Updated')
        ->set('profileForm.email', 'taylor.updated@example.com')
        ->set('profileForm.locale', 'lt')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($tenant->user->fresh())
        ->name->toBe('Taylor Updated')
        ->email->toBe('taylor.updated@example.com')
        ->locale->toBe('lt');
});

it('uses the newly selected locale on the redirected response', function () {
    $tenant = TenantPortalFactory::new()->create();

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('profileForm.name', 'Taylor Updated')
        ->set('profileForm.email', 'taylor.updated@example.com')
        ->set('profileForm.locale', 'lt')
        ->call('saveProfile')
        ->assertHasNoErrors();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful()
        ->assertSee('lang="lt"', false);
});

it('updates the tenant password', function () {
    $tenant = TenantPortalFactory::new()->create();

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('passwordForm.current_password', 'password')
        ->set('passwordForm.password', 'new-password-123')
        ->set('passwordForm.password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password-123', $tenant->user->fresh()->password))->toBeTrue();
});

it('requires the current password before changing the tenant password', function () {
    $tenant = TenantPortalFactory::new()->create();

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('passwordForm.current_password', '')
        ->set('passwordForm.password', 'new-password-123')
        ->set('passwordForm.password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});

it('requires the password confirmation to match', function () {
    $tenant = TenantPortalFactory::new()->create();

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('passwordForm.current_password', 'password')
        ->set('passwordForm.password', 'new-password-123')
        ->set('passwordForm.password_confirmation', 'different-password')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});

it('renders tenant profile copy in lithuanian for lithuanian tenants', function () {
    $tenant = TenantPortalFactory::new()->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful()
        ->assertSeeText('Mano profilis')
        ->assertSeeText('Asmeninė informacija');
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
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful()
        ->assertSee('wire:model.live="profileForm.locale"', false);

    preg_match('/<select[^>]*wire:model\.live="profileForm\\.locale"[^>]*>(.*?)<\/select>/s', $response->getContent(), $matches);

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

it('refreshes translated tenant profile copy when the shell locale changes', function () {
    $tenant = TenantPortalFactory::new()->create();

    $component = Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->assertSeeText(__('shell.profile.title', [], 'en'))
        ->assertSeeText(__('shell.profile.personal_information.heading', [], 'en'));

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($tenant->user->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('shell.profile.title', [], 'lt'))
        ->assertSeeText(__('shell.profile.personal_information.heading', [], 'lt'));
});
