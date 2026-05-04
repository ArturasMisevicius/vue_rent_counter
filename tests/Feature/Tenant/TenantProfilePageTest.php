<?php

use App\Filament\Pages\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant profile form with the current account details', function () {
    $tenant = TenantPortalFactory::new()->create();

    $response = $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.profile'));

    $response
        ->assertSuccessful()
        ->assertSeeText('My Profile')
        ->assertSeeText('Personal Information')
        ->assertSeeText(__('shell.profile.avatar.heading'))
        ->assertSee('data-avatar-cropper', false)
        ->assertSee('data-avatar-canvas', false)
        ->assertDontSee('data-shell-locale="switcher"', false)
        ->assertSeeText('Change Password')
        ->assertSee('value="'.$tenant->user->name.'"', false)
        ->assertSee('value="'.$tenant->user->email.'"', false);

    expect($response->getContent())
        ->toMatch('/<div[^>]*class="[^"]*\bhidden\b[^"]*"[^>]*data-avatar-editor[^>]*>/')
        ->toMatch('/<button[^>]*data-avatar-save[^>]*disabled[^>]*>/');
});

it('stores a cropped tenant avatar and serves it through the authenticated avatar endpoint', function () {
    Storage::fake('local');

    $tenant = TenantPortalFactory::new()->create();

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('avatarForm.avatar', croppedAvatarDataUrl())
        ->call('saveProfileAvatar')
        ->assertHasNoErrors();

    $user = $tenant->user->fresh();

    expect($user->avatar_disk)->toBe('local')
        ->and($user->avatar_path)->not->toBeNull()
        ->and($user->avatar_mime_type)->toBe('image/png')
        ->and($user->avatar_updated_at)->not->toBeNull();

    Storage::disk('local')->assertExists((string) $user->avatar_path);

    $this->actingAs($user)
        ->get(route('profile.avatar.show'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'image/png');

    $this->actingAs($user)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSee('data-shell-user-avatar-image', false)
        ->assertSee(route('profile.avatar.show'), false);
});

it('updates the tenant profile and locale', function () {
    $tenant = TenantPortalFactory::new()->create();

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('profileForm.name', 'Taylor Updated')
        ->set('profileForm.email', 'taylor.updated@example.com')
        ->set('profileForm.phone', '+37069999123')
        ->set('profileForm.locale', 'lt')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($tenant->user->fresh())
        ->name->toBe('Taylor Updated')
        ->email->toBe('taylor.updated@example.com')
        ->phone->toBe('+37069999123')
        ->locale->toBe('lt');
});

it('shows the tenant phone on the profile form', function () {
    $tenant = TenantPortalFactory::new()->create();

    $tenant->user->forceFill([
        'phone' => '+37061112222',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful()
        ->assertSee('value="+37061112222"', false);
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

it('shows the tenant avatar required validation message in the selected locale', function () {
    $tenant = TenantPortalFactory::new()->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    Livewire::actingAs($tenant->user->fresh())
        ->test(Profile::class)
        ->call('saveProfileAvatar')
        ->assertHasErrors(['avatarForm.avatar'])
        ->assertSeeText('Laukas „profilio nuotrauka“ yra privalomas.')
        ->assertDontSeeText('The profilio nuotrauka field is required.');
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

function croppedAvatarDataUrl(): string
{
    $image = imagecreatetruecolor(512, 512);

    imagefill($image, 0, 0, imagecolorallocate($image, 19, 38, 63));

    ob_start();
    imagepng($image);
    $contents = ob_get_clean();

    imagedestroy($image);

    expect($contents)->toBeString();

    return 'data:image/png;base64,'.base64_encode($contents);
}
