<?php

use App\Livewire\Shell\LanguageSwitcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

uses(RefreshDatabase::class);

it('shows the current locale abbreviation and locale names in their own language', function () {
    $user = User::factory()->admin()->create([
        'locale' => 'lt',
    ]);

    Livewire::actingAs($user)
        ->test(LanguageSwitcher::class)
        ->assertSee('LT')
        ->assertSee('English')
        ->assertSee('Lietuvių')
        ->assertSee('Русский');
});

it('persists the selected locale for the authenticated user', function () {
    $user = User::factory()->manager()->create([
        'locale' => 'en',
    ]);

    Livewire::actingAs($user)
        ->test(LanguageSwitcher::class)
        ->call('changeLocale', 'ru')
        ->assertSet('currentLocale', 'ru')
        ->assertDispatched('shell-locale-updated');

    expect($user->fresh()->locale)->toBe('ru')
        ->and(app()->getLocale())->toBe('ru');
});

it('rejects unsupported locales through the shared form request rules', function () {
    $user = User::factory()->admin()->create([
        'locale' => 'en',
    ]);

    Livewire::actingAs($user)
        ->test(LanguageSwitcher::class)
        ->call('changeLocale', 'zz')
        ->assertHasErrors(['locale']);

    expect($user->fresh()->locale)->toBe('en');
});

it('persists the selected locale for guests in session and dispatches a refresh event', function () {
    withSession([]);

    Livewire::test(LanguageSwitcher::class)
        ->call('changeLocale', 'lt')
        ->assertSet('currentLocale', 'lt')
        ->assertDispatched('shell-locale-updated');

    expect(session(config('app.guest_locale_session_key', 'guest_locale')))->toBe('lt')
        ->and(app()->getLocale())->toBe('lt');
});

it('uses the updated locale on the next authenticated response', function () {
    $user = User::factory()->tenant()->create([
        'locale' => 'en',
    ]);

    Livewire::actingAs($user)
        ->test(LanguageSwitcher::class)
        ->call('changeLocale', 'lt');

    actingAs($user->fresh());

    get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText(__('tenant.shell.eyebrow', [], 'lt'))
        ->assertSeeText(__('tenant.navigation.home', [], 'lt'))
        ->assertSeeText(__('dashboard.logout_button', [], 'lt'));
});
