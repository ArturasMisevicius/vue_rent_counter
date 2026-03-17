<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('applies the selected guest locale to public auth pages', function () {
    $this->from('/')
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect('/');

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSeeText(__('auth.login_title', [], 'ru'));
});

it('stores the active guest locale on newly registered admins', function () {
    $this->from('/')
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect('/');

    $this->post(route('register.store'), [
        'name' => 'Asta Admin',
        'email' => 'asta@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('welcome.show'));

    expect(User::query()->firstOrFail()->locale)->toBe('ru');
});

it('rejects unsupported guest locales', function () {
    $this->from('/')
        ->post(route('locale.update'), [
            'locale' => 'de',
        ])
        ->assertRedirect('/')
        ->assertSessionHasErrors(['locale']);
});

it('pulls the locale from the authenticated user even when a guest locale is present', function () {
    Route::middleware(['web', 'auth', 'set.auth.locale'])
        ->get('/__test/locale', fn () => response(app()->getLocale()));

    $user = User::factory()->admin()->create([
        'locale' => 'lt',
    ]);

    $this->withSession([
        'guest_locale' => 'ru',
    ])->actingAs($user)
        ->get('/__test/locale')
        ->assertSee('lt');
});
