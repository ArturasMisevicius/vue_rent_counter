<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    registerErrorPageFixtures();
});

it('shows the branded 403 page with a role-aware dashboard action', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('test.errors.forbidden'))
        ->assertForbidden()
        ->assertSeeText('You do not have permission to view this page')
        ->assertSee(route('filament.admin.pages.dashboard'), false);
});

it('shows the branded 404 page with a tenant dashboard action', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get('/__test/missing-page')
        ->assertNotFound()
        ->assertSeeText('The page you are looking for does not exist')
        ->assertSee(route('filament.admin.pages.dashboard'), false);
});

it('shows the support-safe 500 page when debug mode is disabled', function () {
    config()->set('app.debug', false);

    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('test.errors.server'))
        ->assertStatus(500)
        ->assertSeeText('Something went wrong on our side')
        ->assertSee(route('filament.admin.pages.dashboard'), false);
});

function registerErrorPageFixtures(): void
{
    if (! Route::has('test.errors.forbidden')) {
        Route::middleware('web')->get('/__test/errors/forbidden', fn () => abort(403))
            ->name('test.errors.forbidden');
    }

    if (! Route::has('test.errors.server')) {
        Route::middleware('web')->get('/__test/errors/server', function (): never {
            abort(500);
        })->name('test.errors.server');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}
