<?php

use App\Livewire\Tenant\PropertyDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
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

it('shows normal tenant information in lithuanian instead of a not-available fallback', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    app()->setLocale('lt');

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful()
        ->assertSeeText('Nuomininko informacija')
        ->assertSeeText($tenant->user->name)
        ->assertSeeText($tenant->user->email);
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

it('refreshes translated property details copy when the shell locale changes', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $component = Livewire::actingAs($tenant->user)
        ->test(PropertyDetails::class)
        ->assertSeeText(__('tenant.pages.property.eyebrow', [], 'en'))
        ->assertSeeText(__('tenant.pages.property.last_reading_none', [], 'en'));

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($tenant->user->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('tenant.pages.property.eyebrow', [], 'lt'))
        ->assertSeeText(__('tenant.pages.property.last_reading_none', [], 'lt'));
});
