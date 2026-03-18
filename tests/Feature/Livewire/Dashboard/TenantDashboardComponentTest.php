<?php

use App\Enums\MeterType;
use App\Filament\Support\Tenant\Portal\TenantHomePresenter;
use App\Livewire\Pages\Dashboard\TenantDashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('renders the tenant dashboard component for tenants', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->withMeters(2)
        ->withReadings()
        ->create();

    $fixture->meters[0]->forceFill(['type' => MeterType::WATER])->save();
    $fixture->meters[1]->forceFill(['type' => MeterType::ELECTRICITY])->save();

    Livewire::actingAs($fixture->user)
        ->test(TenantDashboard::class)
        ->assertSeeText('Outstanding Balance')
        ->assertSeeText('This Month')
        ->assertSeeText('Current Month Consumption')
        ->assertSeeText('Recent Readings')
        ->assertSeeText('Assigned Property')
        ->assertSeeText('Apartment 12')
        ->assertSeeText('123 Garden Street')
        ->assertSeeHtml('wire:poll.120s');
});

it('renders the forbidden experience when an admin tries to render the tenant dashboard component', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(TenantDashboard::class)
        ->assertSeeText('You do not have permission to view this page')
        ->assertSeeText('403');
});

it('returns the same computed summary payload as the tenant home presenter', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->withMeters(2)
        ->withReadings()
        ->create();

    $component = Livewire::actingAs($fixture->user)->test(TenantDashboard::class);

    expect($component->instance()->summary())
        ->toEqual(app(TenantHomePresenter::class)->for($fixture->user));
});

it('shows an empty-state message when the tenant has no assigned property yet', function () {
    $fixture = TenantPortalFactory::new()->create();

    Livewire::actingAs($fixture->user)
        ->test(TenantDashboard::class)
        ->assertSeeText('No property assigned yet')
        ->assertSeeText('administrator assigns your property and meters');
});
