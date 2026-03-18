<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant property details and assigned meters without edit actions', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful()
        ->assertSeeText('My Property')
        ->assertSeeText($tenant->property->address)
        ->assertSeeText($tenant->meters->firstOrFail()->name)
        ->assertDontSeeText('Edit')
        ->assertDontSeeText('Delete');
});

it('shows the empty reading state when a meter has no recorded reading', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful()
        ->assertSeeText('Last reading: None recorded yet');
});
