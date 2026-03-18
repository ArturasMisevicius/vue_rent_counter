<?php

declare(strict_types=1);

use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Livewire\Shell\GlobalSearch;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('returns only the authenticated admins organization buildings when searching by name', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Building::factory()->for($organization)->create([
        'name' => 'Lakeside Tower',
    ]);
    Building::factory()->for($organization)->create([
        'name' => 'Lakeside Annex',
    ]);
    Building::factory()->for($otherOrganization)->create([
        'name' => 'Lakeside Foreign',
    ]);

    $component = Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', 'Lake');

    $results = $component->instance()->results();
    $buildingTitles = collect($results['buildings'] ?? [])->pluck('title')->all();

    expect($buildingTitles)
        ->toContain('Lakeside Tower', 'Lakeside Annex')
        ->not->toContain('Lakeside Foreign');
});

it('returns no admin building results for buildings that exist only in another organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Building::factory()->for($otherOrganization)->create([
        'name' => 'Harbor Point',
    ]);

    $component = Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', 'Harbor');

    expect($component->instance()->results()['buildings'] ?? [])->toBe([]);
});

it('returns building matches from all organizations for superadmins', function () {
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();

    $superadmin = User::factory()->superadmin()->create();

    Building::factory()->for($firstOrganization)->create([
        'name' => 'Skyline One',
    ]);
    Building::factory()->for($secondOrganization)->create([
        'name' => 'Skyline Two',
    ]);

    $component = Livewire::actingAs($superadmin)
        ->test(GlobalSearch::class)
        ->set('query', 'Skyline');

    $buildingTitles = collect($component->instance()->results()['buildings'] ?? [])->pluck('title')->all();

    expect($buildingTitles)
        ->toContain('Skyline One', 'Skyline Two');
});

it('returns only the authenticated tenants invoices', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    $propertyAssignment = PropertyAssignment::factory()->for($organization)->create([
        'tenant_user_id' => $tenant->id,
        'unassigned_at' => null,
    ]);
    $foreignAssignment = PropertyAssignment::factory()->for($otherOrganization)->create([
        'tenant_user_id' => $otherTenant->id,
        'unassigned_at' => null,
    ]);

    $ownInvoice = Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $propertyAssignment->property_id,
        'tenant_user_id' => $tenant->id,
        'invoice_number' => 'INV-OWN-1001',
    ]);
    Invoice::factory()->create([
        'organization_id' => $otherOrganization->id,
        'property_id' => $foreignAssignment->property_id,
        'tenant_user_id' => $otherTenant->id,
        'invoice_number' => 'INV-FOREIGN-1001',
    ]);

    $component = Livewire::actingAs($tenant)
        ->test(GlobalSearch::class)
        ->set('query', 'INV');

    $invoiceTitles = collect($component->instance()->results()['invoices'] ?? [])->pluck('title')->all();
    $invoiceUrls = collect($component->instance()->results()['invoices'] ?? [])->pluck('url')->all();

    expect($invoiceTitles)
        ->toContain($ownInvoice->invoice_number)
        ->not->toContain('INV-FOREIGN-1001');

    collect($invoiceUrls)->each(fn ($url) => expect($url)->toContain('#tenant-invoice-'));
});

it('returns a building result count that matches the scoped database query result count', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Building::factory()->for($organization)->create(['name' => 'Northgate Alpha']);
    Building::factory()->for($organization)->create(['name' => 'Northgate Beta']);
    Building::factory()->for($organization)->create(['name' => 'Northgate Gamma']);

    $query = 'North';
    $pattern = SearchQueryPattern::from($query)->likePattern();

    $component = Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', $query);

    $resultCount = count($component->instance()->results()['buildings'] ?? []);
    $databaseCount = Building::query()
        ->forOrganization($organization->id)
        ->where('name', 'like', $pattern)
        ->count();

    expect($resultCount)->toBe($databaseCount);
});
