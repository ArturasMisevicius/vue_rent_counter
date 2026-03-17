<?php

use App\Livewire\Shell\GlobalSearch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the global search field in the shared topbar', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSee('data-shell-search="global"', false)
        ->assertSeeText('Search anything');
});

it('shows role-appropriate group labels and clears on escape', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', 'mar')
        ->assertSet('open', true)
        ->assertSee('Organization')
        ->dispatch('shell-search-dismissed')
        ->assertSet('open', false)
        ->assertSet('query', '');
});

it('never includes another organizations records in results', function () {
    registerGlobalSearchFixtures();

    config()->set('tenanto.search.providers.users.route', 'test.search.users.show');

    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $visibleUser = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'name' => 'Marta Manager',
        'email' => 'marta.manager@example.com',
    ]);

    $hiddenUser = User::factory()->manager()->create([
        'organization_id' => $otherOrganization->id,
        'name' => 'Marta External',
        'email' => 'marta.external@example.com',
    ]);

    Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', 'Marta')
        ->assertSee('Marta Manager')
        ->assertSee(route('test.search.users.show', $visibleUser), false)
        ->assertDontSee('Marta External')
        ->assertDontSee(route('test.search.users.show', $hiddenUser), false);
});

it('returns no clickable result when a destination route is missing', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'name' => 'Asta User',
        'email' => 'asta@example.com',
    ]);

    config()->set('tenanto.search.providers.users.route', 'missing.search.route');

    Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', 'Asta')
        ->assertSee('Organization')
        ->assertDontSee('Asta User')
        ->assertSee('No results yet');
});

function registerGlobalSearchFixtures(): void
{
    if (! Route::has('test.search.users.show')) {
        Route::get('/__test/search/users/{user}', fn (User $user) => $user->name)
            ->name('test.search.users.show');
    }

    if (! Route::has('test.search.organizations.show')) {
        Route::get('/__test/search/organizations/{organization}', fn (Organization $organization) => $organization->name)
            ->name('test.search.organizations.show');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}
