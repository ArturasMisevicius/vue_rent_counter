<?php

use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\Dashboard\BuildAdminAttentionDashboard;
use App\Livewire\Pages\Dashboard\AdminDashboard;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-03-15 09:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('renders the admin attention dashboard component for admin users', function (): void {
    $admin = seedAdminAttentionDashboardComponentData();

    Livewire::actingAs($admin)
        ->test(AdminDashboard::class)
        ->assertSeeText('Admin Attention Dashboard')
        ->assertSeeText('Needs action today')
        ->assertSeeText('Billing progress for March 2026')
        ->assertSeeText('Waiting for readings')
        ->assertSeeText('Tenant onboarding')
        ->assertSeeText('Service configuration health')
        ->assertSeeText('Contract attention')
        ->assertSeeText('Recent activity')
        ->assertDontSeeText('INV-OUTSIDE-001')
        ->assertSeeHtml('wire:poll.visible.30s="refreshDashboardOnInterval"');
});

it('does not render unauthorized widget groups for managers without permissions', function (): void {
    $manager = seedAdminAttentionDashboardComponentData(role: 'manager');

    Livewire::actingAs($manager)
        ->test(AdminDashboard::class)
        ->assertSeeText('Admin Attention Dashboard')
        ->assertDontSeeText('Billing progress for March 2026')
        ->assertDontSeeText('Tenant onboarding')
        ->assertDontSeeText('Contract attention');
});

it('renders the forbidden experience when a tenant tries to render the admin dashboard component', function (): void {
    $tenant = User::factory()->tenant()->create();

    Livewire::actingAs($tenant)
        ->test(AdminDashboard::class)
        ->assertStatus(403)
        ->assertSeeText('You do not have permission to view this page')
        ->assertSeeText('403');
});

it('returns the same computed dashboard payload as the attention dashboard service', function (): void {
    $admin = seedAdminAttentionDashboardComponentData();

    $component = Livewire::actingAs($admin)->test(AdminDashboard::class);

    expect($component->instance()->dashboard())
        ->toEqual(app(BuildAdminAttentionDashboard::class)->handle((int) $admin->organization_id, $admin->id)->toArray());
});

it('refreshes translated admin dashboard copy when the shell locale changes', function (): void {
    $admin = seedAdminAttentionDashboardComponentData();

    $component = Livewire::actingAs($admin)
        ->test(AdminDashboard::class)
        ->assertSeeText(__('dashboard.attention.sections.needs_action', [], 'en'));

    $admin->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($admin->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('dashboard.attention.sections.needs_action', [], 'lt'))
        ->assertSeeText(__('dashboard.attention.header.title', [], 'lt'));
});

function seedAdminAttentionDashboardComponentData(string $role = 'admin'): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->{$role}()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $period = BillingPeriod::factory()->for($organization)->create([
        'name' => 'March 2026',
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-03-31',
    ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-WAITING-001',
            'billing_period_id' => $period->id,
            'billing_period_start' => '2026-03-01',
            'billing_period_end' => '2026-03-31',
            'status' => InvoiceStatus::DRAFT,
            'automation_level' => 'reading_request',
            'approval_status' => 'pending',
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    Invoice::factory()
        ->for($otherOrganization)
        ->for($otherProperty)
        ->for($otherTenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-OUTSIDE-001',
            'status' => InvoiceStatus::PAID,
            'amount_paid' => 999.99,
            'total_amount' => 999.99,
            'paid_at' => now(),
        ]);

    return $user->fresh();
}
