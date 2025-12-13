<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationViewRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin user
        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        // Create test organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function view_page_displays_users_relation(): void
    {
        $this->actingAs($this->superadmin);

        // Create users
        $users = User::factory()->count(3)->create([
            'tenant_id' => $this->organization->id,
            'role' => UserRole::ADMIN,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee($users[0]->name)
            ->assertSee($users[1]->name)
            ->assertSee($users[2]->name);
    }

    /** @test */
    public function view_page_displays_properties_relation(): void
    {
        $this->actingAs($this->superadmin);

        // Create properties
        $properties = Property::factory()->count(3)->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee($properties[0]->address)
            ->assertSee($properties[1]->address)
            ->assertSee($properties[2]->address);
    }

    /** @test */
    public function view_page_displays_buildings_relation(): void
    {
        $this->actingAs($this->superadmin);

        // Create buildings
        $buildings = Building::factory()->count(3)->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee($buildings[0]->name)
            ->assertSee($buildings[1]->name)
            ->assertSee($buildings[2]->name);
    }

    /** @test */
    public function view_page_displays_invoices_count(): void
    {
        $this->actingAs($this->superadmin);

        // Create invoices
        Invoice::factory()->count(5)->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee('5 invoices');
    }

    /** @test */
    public function view_page_displays_meters_count(): void
    {
        $this->actingAs($this->superadmin);

        // Create meters
        Meter::factory()->count(7)->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee('7 meters');
    }

    /** @test */
    public function view_page_displays_tenants_relation(): void
    {
        $this->actingAs($this->superadmin);

        // Create tenants
        $tenants = Tenant::factory()->count(3)->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee($tenants[0]->name)
            ->assertSee($tenants[1]->name)
            ->assertSee($tenants[2]->name);
    }

    /** @test */
    public function view_page_displays_invitations_count(): void
    {
        $this->actingAs($this->superadmin);

        // Create invitations
        OrganizationInvitation::factory()->count(4)->create([
            'organization_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee('4 invitations');
    }

    /** @test */
    public function view_page_displays_activity_logs_count(): void
    {
        $this->actingAs($this->superadmin);

        // Create activity logs
        OrganizationActivityLog::factory()->count(10)->create([
            'organization_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee('10 logs');
    }

    /** @test */
    public function view_page_shows_none_for_empty_relations(): void
    {
        $this->actingAs($this->superadmin);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee('None');
    }

    /** @test */
    public function view_page_truncates_long_relation_lists(): void
    {
        $this->actingAs($this->superadmin);

        // Create more than 5 users
        User::factory()->count(8)->create([
            'tenant_id' => $this->organization->id,
            'role' => UserRole::ADMIN,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertSee('and 3 more');
    }

    /** @test */
    public function view_page_displays_all_relation_sections(): void
    {
        $this->actingAs($this->superadmin);

        // Create at least one of each relation type
        User::factory()->create([
            'tenant_id' => $this->organization->id,
            'role' => UserRole::ADMIN,
        ]);
        
        Property::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);
        
        Building::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);
        
        Invoice::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);
        
        Meter::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);
        
        Tenant::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);
        
        OrganizationInvitation::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        
        OrganizationActivityLog::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $component = Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful();

        // Check that all relation labels are present
        $component->assertSee('Users');
        $component->assertSee('Properties');
        $component->assertSee('Buildings');
        $component->assertSee('Invoices');
        $component->assertSee('Meters');
        $component->assertSee('Tenants');
        $component->assertSee('Invitations');
        $component->assertSee('Activity Logs');
    }

    /** @test */
    public function non_superadmin_cannot_view_organization(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $this->organization->id,
        ]);

        $this->actingAs($admin);

        $this->get(route('filament.superadmin.resources.organizations.view', $this->organization))
            ->assertForbidden();
    }
}
