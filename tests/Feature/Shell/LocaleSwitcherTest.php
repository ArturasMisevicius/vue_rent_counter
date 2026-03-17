<?php

use App\Livewire\Shell\LanguageSwitcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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

it('uses the updated locale on the next authenticated response', function () {
    $user = User::factory()->tenant()->create([
        'locale' => 'en',
    ]);

    Livewire::actingAs($user)
        ->test(LanguageSwitcher::class)
        ->call('changeLocale', 'lt');

    $this->actingAs($user->fresh())
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('Nuomininko portalas')
        ->assertSeeText('Profilis');
});
