<?php

use App\Livewire\Shell\GlobalSearch;
use App\Models\Organization;
use App\Models\User;
use App\Support\Shell\Search\GlobalSearchRegistry;
use App\Support\Shell\Search\Providers\UserSearchProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function registerSearchDestinationFixtures(): void
{
    $organizationViewRoute = (string) config('tenanto.routes.search.organizations.view');
    $userViewRoute = (string) config('tenanto.routes.search.users.view');

    if (! Route::has($organizationViewRoute)) {
        Route::get('/__test/search/organizations/{organization}', fn (Organization $organization): string => $organization->name)
            ->name($organizationViewRoute);
    }

    if (! Route::has($userViewRoute)) {
        Route::get('/__test/search/users/{user}', fn (User $user): string => $user->name)
            ->name($userViewRoute);
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

it('renders the search field and role appropriate group labels', function (Closure $userFactory, array $expectedLabels, array $unexpectedLabels) {
    $user = $userFactory();

    $this->actingAs($user);

    Livewire::test(GlobalSearch::class)
        ->assertSee('Search anything')
        ->call('openSearch')
        ->assertSet('isOpen', true)
        ->assertSee($expectedLabels)
        ->assertDontSee($unexpectedLabels);
})->with([
    'superadmin' => [
        fn () => User::factory()->superadmin()->create(),
        ['Organizations', 'Users', 'Buildings', 'Invoices'],
        ['Meter Readings'],
    ],
    'tenant' => [
        fn () => User::factory()->tenant()->create(),
        ['Invoices', 'Meter Readings'],
        ['Organizations', 'Users'],
    ],
]);

it('clears and closes the overlay through the close action', function () {
    $user = User::factory()->superadmin()->create();

    $this->actingAs($user);

    Livewire::test(GlobalSearch::class)
        ->call('openSearch')
        ->set('query', 'acme')
        ->call('closeSearch')
        ->assertSet('isOpen', false)
        ->assertSet('query', '');
});

it('returns no clickable results when destination routes are missing', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Acme Search',
    ]);

    User::factory()->create([
        'organization_id' => $organization->getKey(),
        'name' => 'Acme Owner',
        'email' => 'owner@example.com',
    ]);

    config()->set('tenanto.routes.search.organizations.view', '__missing.organizations.view');
    config()->set('tenanto.routes.search.users.view', '__missing.users.view');

    $results = app(GlobalSearchRegistry::class)->search($superadmin, 'Acme');

    expect($results['organizations'])->toBe([])
        ->and($results['users'])->toBe([]);
});

it('returns clickable organization and user results when superadmin destinations exist', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Acme Search',
    ]);

    $user = User::factory()->create([
        'organization_id' => $organization->getKey(),
        'name' => 'Acme Owner',
        'email' => 'owner@example.com',
    ]);

    $results = app(GlobalSearchRegistry::class)->search($superadmin, 'Acme');

    expect($results['organizations'])->toHaveCount(1)
        ->and($results['organizations'][0]->label)->toBe('Acme Search')
        ->and($results['organizations'][0]->url)->toBe(route('filament.admin.resources.organizations.view', $organization))
        ->and($results['users'])->toHaveCount(1)
        ->and($results['users'][0]->label)->toBe('Acme Owner')
        ->and($results['users'][0]->url)->toBe(route('filament.admin.resources.users.view', $user));
});

it('never includes another organizations users in provider results', function () {
    registerSearchDestinationFixtures();

    $organizationA = Organization::factory()->create([
        'name' => 'Acme Search',
    ]);

    $organizationB = Organization::factory()->create([
        'name' => 'Bravo Search',
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organizationA->getKey(),
    ]);

    User::factory()->create([
        'organization_id' => $organizationA->getKey(),
        'name' => 'Alice Acme',
        'email' => 'alice@acme.test',
    ]);

    User::factory()->create([
        'organization_id' => $organizationB->getKey(),
        'name' => 'Bob Bravo',
        'email' => 'bob@bravo.test',
    ]);

    $results = app(UserSearchProvider::class)->search($manager, 'alice');

    expect(collect($results)->pluck('label')->all())
        ->toContain('Alice Acme')
        ->not->toContain('Bob Bravo');
});
