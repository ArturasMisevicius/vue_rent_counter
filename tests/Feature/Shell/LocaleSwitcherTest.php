<?php

use App\Livewire\Shell\LanguageSwitcher;
use App\Livewire\Shell\Topbar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the current locale abbreviation and native locale names', function () {
    $user = User::factory()->tenant()->create([
        'locale' => 'en',
    ]);

    $this->actingAs($user);

    Livewire::test(LanguageSwitcher::class)
        ->assertSee('EN')
        ->assertSee('English')
        ->assertSee('Lietuvių')
        ->assertSee('Русский')
        ->assertSee('Español');
});

it('switches locale immediately and persists it on the user', function () {
    $user = User::factory()->tenant()->create([
        'locale' => 'en',
    ]);

    $this->actingAs($user);

    Livewire::test(LanguageSwitcher::class)
        ->call('switchLocale', 'lt')
        ->assertSet('currentLocale', 'lt')
        ->assertSee('LT')
        ->assertSee('Lietuvių');

    expect($user->fresh()->locale)->toBe('lt');
    expect(app()->getLocale())->toBe('lt');
});

it('uses the persisted locale on the next rendered response', function () {
    $user = User::factory()->tenant()->create([
        'locale' => 'en',
    ]);

    $this->actingAs($user);

    Livewire::test(LanguageSwitcher::class)
        ->call('switchLocale', 'lt');

    $this->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('Ieškoti visko');
});

it('refreshes shared topbar copy after a locale update event', function () {
    $user = User::factory()->tenant()->create([
        'locale' => 'en',
    ]);

    $this->actingAs($user);

    app()->setLocale('en');

    $topbar = Livewire::test(Topbar::class)
        ->assertSee('My Profile')
        ->assertSee('Log Out');

    app()->setLocale('lt');

    $topbar
        ->dispatch('shell-locale-updated', locale: 'lt')
        ->assertSee('Mano profilis')
        ->assertSee('Atsijungti');
});
