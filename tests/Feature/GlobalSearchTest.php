<?php

declare(strict_types=1);

use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Livewire\Shell\GlobalSearch;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
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

it('returns only the authenticated admins organization properties when searching by name', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Property::factory()->for($organization)->create([
        'name' => 'Riverside Flat',
    ]);
    Property::factory()->for($otherOrganization)->create([
        'name' => 'Riverside Foreign',
    ]);

    $component = Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', 'River');

    $propertyTitles = collect($component->instance()->results()['properties'] ?? [])->pluck('title')->all();

    expect($propertyTitles)
        ->toContain('Riverside Flat')
        ->not->toContain('Riverside Foreign');
});

it('returns only organization property matches for managers', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Property::factory()->for($organization)->create([
        'name' => 'Scope Property Local',
    ]);
    Property::factory()->for($otherOrganization)->create([
        'name' => 'Scope Property Foreign',
    ]);

    $component = Livewire::actingAs($manager)
        ->test(GlobalSearch::class)
        ->set('query', 'Scope');

    $propertyTitles = collect($component->instance()->results()['properties'] ?? [])->pluck('title')->all();

    expect($propertyTitles)
        ->toContain('Scope Property Local')
        ->not->toContain('Scope Property Foreign');
});

it('returns property matches from all organizations for superadmins', function () {
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    Property::factory()->for($firstOrganization)->create([
        'name' => 'Meridian Suite One',
    ]);
    Property::factory()->for($secondOrganization)->create([
        'name' => 'Meridian Suite Two',
    ]);

    $component = Livewire::actingAs($superadmin)
        ->test(GlobalSearch::class)
        ->set('query', 'Meri');

    $propertyTitles = collect($component->instance()->results()['properties'] ?? [])->pluck('title')->all();

    expect($propertyTitles)
        ->toContain('Meridian Suite One', 'Meridian Suite Two');
});

it('returns tenant matches from all organizations for superadmins', function () {
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    User::factory()->tenant()->create([
        'organization_id' => $firstOrganization->id,
        'name' => 'Tenant Orbit One',
        'email' => 'tenant-orbit-one@example.com',
    ]);
    User::factory()->tenant()->create([
        'organization_id' => $secondOrganization->id,
        'name' => 'Tenant Orbit Two',
        'email' => 'tenant-orbit-two@example.com',
    ]);

    $component = Livewire::actingAs($superadmin)
        ->test(GlobalSearch::class)
        ->set('query', 'Tenant');

    $tenantTitles = collect($component->instance()->results()['tenants'] ?? [])->pluck('title')->all();

    expect($tenantTitles)
        ->toContain('Tenant Orbit One', 'Tenant Orbit Two');
});

it('returns only organization tenant matches for managers', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Tenant Scope Local',
        'email' => 'tenant-scope-local@example.com',
    ]);
    User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
        'name' => 'Tenant Scope Foreign',
        'email' => 'tenant-scope-foreign@example.com',
    ]);

    $component = Livewire::actingAs($manager)
        ->test(GlobalSearch::class)
        ->set('query', 'Tenant');

    $tenantTitles = collect($component->instance()->results()['tenants'] ?? [])->pluck('title')->all();

    expect($tenantTitles)
        ->toContain('Tenant Scope Local')
        ->not->toContain('Tenant Scope Foreign');
});

it('returns only organization tenant matches for admins', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Tenant Admin Scope Local',
        'email' => 'tenant-admin-scope-local@example.com',
    ]);
    User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
        'name' => 'Tenant Admin Scope Foreign',
        'email' => 'tenant-admin-scope-foreign@example.com',
    ]);

    $component = Livewire::actingAs($admin)
        ->test(GlobalSearch::class)
        ->set('query', 'Tenant');

    $tenantTitles = collect($component->instance()->results()['tenants'] ?? [])->pluck('title')->all();

    expect($tenantTitles)
        ->toContain('Tenant Admin Scope Local')
        ->not->toContain('Tenant Admin Scope Foreign');
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

it('returns invoice matches from all organizations for superadmins', function () {
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    Invoice::factory()->for($firstOrganization)->create([
        'invoice_number' => 'INV-CROSS-1001',
    ]);
    Invoice::factory()->for($secondOrganization)->create([
        'invoice_number' => 'INV-CROSS-1002',
    ]);

    $component = Livewire::actingAs($superadmin)
        ->test(GlobalSearch::class)
        ->set('query', 'INV-CROSS');

    $invoiceTitles = collect($component->instance()->results()['invoices'] ?? [])->pluck('title')->all();

    expect($invoiceTitles)
        ->toContain('INV-CROSS-1001', 'INV-CROSS-1002');
});

it('returns only organization invoice matches for managers', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Invoice::factory()->for($organization)->create([
        'invoice_number' => 'INV-SCOPE-LOCAL',
    ]);
    Invoice::factory()->for($otherOrganization)->create([
        'invoice_number' => 'INV-SCOPE-FOREIGN',
    ]);

    $component = Livewire::actingAs($manager)
        ->test(GlobalSearch::class)
        ->set('query', 'INV-SCOPE');

    $invoiceTitles = collect($component->instance()->results()['invoices'] ?? [])->pluck('title')->all();

    expect($invoiceTitles)
        ->toContain('INV-SCOPE-LOCAL')
        ->not->toContain('INV-SCOPE-FOREIGN');
});

it('returns only tenant-submitted meter readings from the active tenant workspace', function () {
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

    $ownMeter = Meter::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $propertyAssignment->property_id,
        'name' => 'Flow Sensor A',
        'identifier' => 'FLOW-A-001',
    ]);

    MeterReading::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $propertyAssignment->property_id,
        'meter_id' => $ownMeter->id,
        'submitted_by_user_id' => $tenant->id,
    ]);

    $foreignMeter = Meter::factory()->create([
        'organization_id' => $otherOrganization->id,
        'property_id' => $foreignAssignment->property_id,
        'name' => 'Flow Sensor Foreign',
        'identifier' => 'FLOW-F-001',
    ]);

    MeterReading::factory()->create([
        'organization_id' => $otherOrganization->id,
        'property_id' => $foreignAssignment->property_id,
        'meter_id' => $foreignMeter->id,
        'submitted_by_user_id' => $otherTenant->id,
    ]);

    $component = Livewire::actingAs($tenant)
        ->test(GlobalSearch::class)
        ->set('query', 'Flow');

    $readingTitles = collect($component->instance()->results()['readings'] ?? [])->pluck('title')->all();
    $readingUrls = collect($component->instance()->results()['readings'] ?? [])->pluck('url')->all();

    expect($readingTitles)
        ->toContain('Flow Sensor A')
        ->not->toContain('Flow Sensor Foreign');

    collect($readingUrls)->each(fn ($url) => expect($url)->toContain('#tenant-reading-'));
});

it('returns meter reading matches from all organizations for superadmins', function () {
    $firstOrganization = Organization::factory()->create();
    $secondOrganization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    $firstMeter = Meter::factory()->create([
        'organization_id' => $firstOrganization->id,
        'name' => 'Signal Meter One',
        'identifier' => 'SIGNAL-ONE',
    ]);
    $secondMeter = Meter::factory()->create([
        'organization_id' => $secondOrganization->id,
        'name' => 'Signal Meter Two',
        'identifier' => 'SIGNAL-TWO',
    ]);

    MeterReading::factory()->create([
        'organization_id' => $firstOrganization->id,
        'property_id' => $firstMeter->property_id,
        'meter_id' => $firstMeter->id,
    ]);
    MeterReading::factory()->create([
        'organization_id' => $secondOrganization->id,
        'property_id' => $secondMeter->property_id,
        'meter_id' => $secondMeter->id,
    ]);

    $component = Livewire::actingAs($superadmin)
        ->test(GlobalSearch::class)
        ->set('query', 'Signal');

    $readingTitles = collect($component->instance()->results()['readings'] ?? [])->pluck('title')->all();

    expect($readingTitles)
        ->toContain('Signal Meter One', 'Signal Meter Two');
});

it('returns only organization meter reading matches for managers', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $localMeter = Meter::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Signal Scope Local',
        'identifier' => 'SIGNAL-SCOPE-LOCAL',
    ]);
    $foreignMeter = Meter::factory()->create([
        'organization_id' => $otherOrganization->id,
        'name' => 'Signal Scope Foreign',
        'identifier' => 'SIGNAL-SCOPE-FOREIGN',
    ]);

    MeterReading::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $localMeter->property_id,
        'meter_id' => $localMeter->id,
    ]);
    MeterReading::factory()->create([
        'organization_id' => $otherOrganization->id,
        'property_id' => $foreignMeter->property_id,
        'meter_id' => $foreignMeter->id,
    ]);

    $component = Livewire::actingAs($manager)
        ->test(GlobalSearch::class)
        ->set('query', 'Signal');

    $readingTitles = collect($component->instance()->results()['readings'] ?? [])->pluck('title')->all();

    expect($readingTitles)
        ->toContain('Signal Scope Local')
        ->not->toContain('Signal Scope Foreign');
});

it('returns no tenant invoice or reading results when the tenant has no active property assignment', function () {
    $organization = Organization::factory()->create();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    Invoice::factory()->for($organization)->create([
        'tenant_user_id' => $tenant->id,
        'invoice_number' => 'INV-NO-ASSIGNMENT-001',
    ]);

    $meter = Meter::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Orphan Flow Meter',
        'identifier' => 'FLOW-ORPHAN-001',
    ]);

    MeterReading::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $meter->property_id,
        'meter_id' => $meter->id,
        'submitted_by_user_id' => $tenant->id,
    ]);

    $component = Livewire::actingAs($tenant)
        ->test(GlobalSearch::class)
        ->set('query', 'FLOW');

    expect($component->instance()->results()['invoices'] ?? [])->toBe([]);
    expect($component->instance()->results()['readings'] ?? [])->toBe([]);
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
