<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PropertyResource Feature Tests
 *
 * Tests Filament PropertyResource integration including:
 * - Page rendering (List, Create, Edit)
 * - Tenant-scoped data access
 * - Authorization (403 for unauthorized access)
 * - Navigation visibility by role
 *
 * @group filament
 * @group property-resource
 */
class PropertyResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ProvidersSeeder::class);
    }

    /** @test */
    public function admin_can_render_property_list_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListProperties::class)
            ->assertSuccessful();
    }

    /** @test */
    public function manager_can_render_property_list_page(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(ListProperties::class)
            ->assertSuccessful();
    }

    /** @test */
    public function tenant_user_cannot_access_property_list_page(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($tenantUser);

        // HTTP 403 Forbidden
        Livewire::test(ListProperties::class)
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_see_properties_scoped_to_their_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $tenant1->id,
        ]);

        // Create properties in admin's tenant
        $tenant1Properties = Property::factory()->count(3)->create(['tenant_id' => $tenant1->id]);

        // Create properties in different tenant
        $tenant2Properties = Property::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

        $this->actingAs($admin);

        Livewire::test(ListProperties::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($tenant1Properties)
            ->assertCanNotSeeTableRecords($tenant2Properties);
    }

    /** @test */
    public function superadmin_can_see_all_properties_across_tenants(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        $tenant1Properties = Property::factory()->count(3)->create(['tenant_id' => 1]);
        $tenant2Properties = Property::factory()->count(2)->create(['tenant_id' => 2]);

        $this->actingAs($superadmin);

        Livewire::test(ListProperties::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($tenant1Properties)
            ->assertCanSeeTableRecords($tenant2Properties);
    }

    /** @test */
    public function admin_can_render_create_property_page(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateProperty::class)
            ->assertSuccessful();
    }

    /** @test */
    public function manager_can_render_create_property_page(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(CreateProperty::class)
            ->assertSuccessful();
    }

    /** @test */
    public function tenant_user_cannot_access_create_property_page(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($tenantUser);

        // HTTP 403 Forbidden
        Livewire::test(CreateProperty::class)
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_render_edit_property_page(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditProperty::class, ['record' => $property->id])
            ->assertSuccessful();
    }

    /** @test */
    public function manager_can_render_edit_property_page_for_their_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(EditProperty::class, ['record' => $property->id])
            ->assertSuccessful();
    }

    /** @test */
    public function manager_cannot_edit_property_from_other_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create manager first to ensure they have a tenant_id
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant1->id,
        ]);

        // Create property in a different tenant
        $property = Property::factory()->create(['tenant_id' => $tenant2->id]);

        $this->actingAs($manager);

        // Should throw 404 because HierarchicalScope filters it out,
        // which Filament interprets as ModelNotFoundException
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(EditProperty::class, ['record' => $property->id]);
    }

    /** @test */
    public function tenant_user_cannot_edit_any_property(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($tenantUser);

        // HTTP 403 Forbidden
        Livewire::test(EditProperty::class, ['record' => $property->id])
            ->assertForbidden();
    }

    /** @test */
    public function property_resource_hidden_from_tenant_users(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($tenantUser);

        $this->assertFalse(
            PropertyResource::shouldRegisterNavigation(),
            'PropertyResource should be hidden from tenant users'
        );
    }

    /** @test */
    public function property_resource_visible_to_manager_users(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        $this->assertTrue(
            PropertyResource::shouldRegisterNavigation(),
            'PropertyResource should be visible to manager users'
        );
    }

    /** @test */
    public function property_resource_visible_to_admin_users(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->actingAs($admin);

        $this->assertTrue(
            PropertyResource::shouldRegisterNavigation(),
            'PropertyResource should be visible to admin users'
        );
    }

    /** @test */
    public function property_resource_visible_to_superadmin_users(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        $this->actingAs($superadmin);

        $this->assertTrue(
            PropertyResource::shouldRegisterNavigation(),
            'PropertyResource should be visible to superadmin users'
        );
    }

    /** @test */
    public function navigation_badge_shows_all_properties_for_superadmin(): void
    {
        // Create superadmin
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        // Authenticate BEFORE creating properties
        $this->actingAs($superadmin);

        Property::factory()->count(3)->create(['tenant_id' => 1]);
        Property::factory()->count(4)->create(['tenant_id' => 2]);

        $badge = PropertyResource::getNavigationBadge();

        $this->assertEquals('7', $badge);
    }

    /** @test */
    public function resource_label_is_localized(): void
    {
        $label = PropertyResource::getLabel();
        $pluralLabel = PropertyResource::getPluralLabel();

        $this->assertEquals(__('properties.labels.property'), $label);
        $this->assertEquals(__('properties.labels.properties'), $pluralLabel);
    }

    /** @test */
    public function resource_navigation_label_is_localized(): void
    {
        $navigationLabel = PropertyResource::getNavigationLabel();

        $this->assertEquals(__('properties.labels.properties'), $navigationLabel);
    }

    /** @test */
    public function resource_has_correct_model(): void
    {
        $reflection = new \ReflectionClass(PropertyResource::class);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);

        $this->assertEquals(Property::class, $property->getValue());
    }
}
