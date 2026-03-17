<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('pulls the locale from the authenticated user', function () {
    Route::middleware(['web', 'auth', 'set.auth.locale'])
        ->get('/__test/locale', fn () => response(app()->getLocale()));

    $user = User::factory()->admin()->create([
        'locale' => 'lt',
    ]);

    $this->actingAs($user)
        ->get('/__test/locale')
        ->assertSee('lt');
});
