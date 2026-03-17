<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function registerErrorRouteFixtures(): void
{
    if (! Route::has('test.forbidden')) {
        Route::middleware(['web', 'auth'])->get('/__test/forbidden', fn () => abort(403))->name('test.forbidden');
    }

    if (! Route::has('test.failure')) {
        Route::middleware(['web', 'auth'])->get('/__test/failure', function (): never {
            throw new RuntimeException('Boom');
        })->name('test.failure');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

it('shows the branded 403 page with a dashboard action', function () {
    registerErrorRouteFixtures();

    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('test.forbidden'))
        ->assertForbidden()
        ->assertSeeText('You do not have permission to view this page')
        ->assertSee('href="'.route('tenant.home').'"', false);
});

it('shows the branded 404 page with a dashboard action', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get('/definitely-missing')
        ->assertNotFound()
        ->assertSeeText('The page you are looking for does not exist')
        ->assertSee('href="'.route('filament.admin.pages.platform-dashboard').'"', false);
});

it('shows the branded 500 page when debug is disabled', function () {
    registerErrorRouteFixtures();

    config()->set('app.debug', false);

    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('test.failure'))
        ->assertStatus(500)
        ->assertSeeText('Something went wrong on our end. We have been notified and are working on it')
        ->assertSee('href="'.route('filament.admin.pages.organization-dashboard').'"', false);
});
