<?php

declare(strict_types=1);

use App\Livewire\Pages\DashboardPage;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('renders the parent dashboard livewire component from the filament dashboard route', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Revenue by Plan')
        ->assertSeeText('Expiring Subscriptions')
        ->assertSeeText('Total Properties');
});

it('renders the superadmin dashboard through the parent dashboard page component', function () {
    $superadmin = User::factory()->superadmin()->create();

    Livewire::actingAs($superadmin)
        ->test(DashboardPage::class)
        ->assertSeeText('Revenue by Plan')
        ->assertSeeText('Expiring Subscriptions')
        ->assertSeeText('Organizations · Properties · Managers');
});

it('renders the admin dashboard through the parent dashboard page component', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($admin)
        ->test(DashboardPage::class)
        ->assertSeeText('Recent Invoices')
        ->assertSeeText('Upcoming Reading Deadlines');
});

it('renders the tenant dashboard through the parent dashboard page component', function () {
    $fixture = TenantPortalFactory::new()->create();

    Livewire::actingAs($fixture->user)
        ->test(DashboardPage::class)
        ->assertSeeText('No property assigned yet')
        ->assertSeeText('administrator assigns your property and meters');
});
