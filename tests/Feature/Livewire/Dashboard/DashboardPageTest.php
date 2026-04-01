<?php

declare(strict_types=1);

use App\Livewire\Pages\DashboardPage;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders the parent dashboard livewire component from the filament dashboard route', function () {
    $superadmin = User::factory()->superadmin()->create();

    Auth::login($superadmin);

    get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSeeText(__('dashboard.platform_sections.revenue_by_plan'))
        ->assertSeeText(__('dashboard.platform_sections.expiring_subscriptions'))
        ->assertSeeText(__('dashboard.platform_sections.stalled_projects'))
        ->assertSeeText(__('dashboard.platform_sections.recent_organizations'))
        ->assertDontSeeText('Total Properties');
});

it('renders the superadmin dashboard through the parent dashboard page component', function () {
    $superadmin = User::factory()->superadmin()->create();

    Auth::login($superadmin);

    Livewire::test(DashboardPage::class)
        ->assertSeeText(__('dashboard.platform_sections.revenue_by_plan'))
        ->assertSeeText(__('dashboard.platform_sections.expiring_subscriptions'))
        ->assertSeeText(__('dashboard.platform_sections.stalled_projects'))
        ->assertSeeText(__('dashboard.platform_sections.recent_organizations'))
        ->assertDontSeeText('Organizations · Properties · Managers');
});

it('renders the admin dashboard through the parent dashboard page component', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Auth::login($admin);

    Livewire::test(DashboardPage::class)
        ->assertSeeText('Recent Invoices')
        ->assertSeeText('Upcoming Reading Deadlines');
});

it('renders the tenant dashboard through the parent dashboard page component', function () {
    $fixture = TenantPortalFactory::new()->create();

    Auth::login($fixture->user);

    Livewire::test(DashboardPage::class)
        ->assertSeeText('No property assigned yet')
        ->assertSeeText('administrator assigns your property and meters');
});
