<?php

use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a friendly empty state on first-run organization lists', function (string $resourceClass, string $heading, string $actionLabel) {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get($resourceClass::getUrl())
        ->assertSuccessful()
        ->assertSeeText($heading)
        ->assertSeeText($actionLabel)
        ->assertSee('fi-empty-state', false);
})->with([
    'buildings' => [
        BuildingResource::class,
        'You have not added any buildings yet',
        'Add Your First Building',
    ],
    'properties' => [
        PropertyResource::class,
        'You have not added any properties yet',
        'Add Your First Property',
    ],
    'tenants' => [
        TenantResource::class,
        'You have not added any tenants yet',
        'Invite Your First Tenant',
    ],
    'meters' => [
        MeterResource::class,
        'You have not added any meters yet',
        'Add Your First Meter',
    ],
]);
