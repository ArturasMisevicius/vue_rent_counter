<?php

use App\Livewire\Shell\GlobalSearch;
use App\Models\Building;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the global search field in the shared topbar', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSee('data-shell-search="global"', false)
        ->assertSeeText('Search anything');
});

it('renders a valid alpine selector expression for search result navigation', function () {
    $organization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $markup = Livewire::actingAs($tenant)
        ->test(GlobalSearch::class)
        ->html();

    expect($markup)
        ->toContain("querySelectorAll('[data-search-result=item]')");
});

it('shows model type group labels for admins and clears on escape', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    Building::factory()->for($organization)->create([
        'name' => 'Marina Heights',
    ]);

    Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', 'mar')
        ->assertSet('open', true)
        ->assertSee('Buildings')
        ->dispatch('shell-search-dismissed')
        ->assertSet('open', false)
        ->assertSet('query', '');
});

it('reuses the admin organization search groups for managers', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);
    Building::factory()->for($organization)->create([
        'name' => 'Marina Court',
    ]);

    Livewire::actingAs($manager)
        ->test(GlobalSearch::class)
        ->set('query', 'mar')
        ->assertSet('open', true)
        ->assertSee('Buildings')
        ->assertDontSee('Organizations');
});

it('keeps the query string in sync with the validated search term', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', '  Marina   ');

    expect($component->instance()->query)->toBe('Marina');
});
