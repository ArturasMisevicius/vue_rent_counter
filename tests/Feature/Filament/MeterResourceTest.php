<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Filament\Resources\MeterResource;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MeterResource Feature Tests
 *
 * Tests the Filament MeterResource functionality including:
 * - Tenant scope isolation
 * - Navigation visibility by role
 * - Badge counting
 * - Form validation integration
 * - Table filtering and sorting
 */
class MeterResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ProvidersSeeder::class);
    }

    public function test_scope_to_user_tenant_filters_by_tenant_id(): void
    {
        // Create two tenants with properties
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $property1 = Property::factory()->create(['tenant_id' => $tenant1->id]);
        $property2 = Property::factory()->create(['tenant_id' => $tenant2->id]);

        // Create manager for tenant1
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant1->id,
        ]);

        $this->actingAs($manager);

        // Test the scopeToUserTenant method directly
        $query = Property::query();
        $reflection = new \ReflectionClass(MeterResource::class);
        $method = $reflection->getMethod('scopeToUserTenant');
        $method->setAccessible(true);
        
        $scopedQuery = $method->invoke(null, $query);
        $propertyIds = $scopedQuery->pluck('id')->toArray();

        // Should include property1 but not property2
        $this->assertContains($property1->id, $propertyIds);
        $this->assertNotContains($property2->id, $propertyIds);
        
        // All returned properties should belong to tenant1
        $properties = $scopedQuery->get();
        foreach ($properties as $property) {
            $this->assertEquals($tenant1->id, $property->tenant_id);
        }
    }

    public function test_meter_resource_hidden_from_tenant_users(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
        ]);

        $this->actingAs($tenantUser);

        $this->assertFalse(
            MeterResource::shouldRegisterNavigation(),
            'MeterResource should be hidden from tenant users'
        );
    }

    public function test_meter_resource_visible_to_manager_users(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        $this->assertTrue(
            MeterResource::shouldRegisterNavigation(),
            'MeterResource should be visible to manager users'
        );
    }

    public function test_meter_resource_visible_to_admin_users(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->actingAs($admin);

        $this->assertTrue(
            MeterResource::shouldRegisterNavigation(),
            'MeterResource should be visible to admin users'
        );
    }

    public function test_meter_resource_visible_to_superadmin_users(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        $this->actingAs($superadmin);

        $this->assertTrue(
            MeterResource::shouldRegisterNavigation(),
            'MeterResource should be visible to superadmin users'
        );
    }

    public function test_navigation_badge_shows_meter_count_for_manager(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);

        // Create 3 meters for this tenant
        Meter::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
        ]);

        // Create meters for another tenant (should not be counted)
        $otherTenant = Tenant::factory()->create();
        $otherProperty = Property::factory()->create(['tenant_id' => $otherTenant->id]);
        Meter::factory()->count(2)->create([
            'tenant_id' => $otherTenant->id,
            'property_id' => $otherProperty->id,
        ]);

        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        $badge = MeterResource::getNavigationBadge();

        $this->assertEquals('3', $badge);
    }

    public function test_navigation_badge_shows_all_meters_for_superadmin(): void
    {
        $tenant1 = Tenant::factory()->create();
        $property1 = Property::factory()->create(['tenant_id' => $tenant1->id]);
        Meter::factory()->count(3)->create([
            'tenant_id' => $tenant1->id,
            'property_id' => $property1->id,
        ]);

        $tenant2 = Tenant::factory()->create();
        $property2 = Property::factory()->create(['tenant_id' => $tenant2->id]);
        Meter::factory()->count(2)->create([
            'tenant_id' => $tenant2->id,
            'property_id' => $property2->id,
        ]);

        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        $this->actingAs($superadmin);

        $badge = MeterResource::getNavigationBadge();

        $this->assertEquals('5', $badge);
    }

    public function test_navigation_badge_returns_null_when_no_meters(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        $badge = MeterResource::getNavigationBadge();

        $this->assertNull($badge);
    }

    public function test_resource_has_correct_model(): void
    {
        $reflection = new \ReflectionClass(MeterResource::class);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);

        $this->assertEquals(Meter::class, $property->getValue());
    }

    public function test_resource_has_correct_navigation_sort(): void
    {
        $reflection = new \ReflectionClass(MeterResource::class);
        $property = $reflection->getProperty('navigationSort');
        $property->setAccessible(true);

        $this->assertEquals(4, $property->getValue());
    }

    public function test_resource_has_correct_record_title_attribute(): void
    {
        $reflection = new \ReflectionClass(MeterResource::class);
        $property = $reflection->getProperty('recordTitleAttribute');
        $property->setAccessible(true);

        $this->assertEquals('serial_number', $property->getValue());
    }

    public function test_resource_uses_translated_validation_trait(): void
    {
        $reflection = new \ReflectionClass(MeterResource::class);
        $traits = $reflection->getTraitNames();

        $this->assertContains(
            'App\Filament\Concerns\HasTranslatedValidation',
            $traits
        );
    }

    public function test_resource_has_correct_translation_prefix(): void
    {
        $reflection = new \ReflectionClass(MeterResource::class);
        $property = $reflection->getProperty('translationPrefix');
        $property->setAccessible(true);

        $this->assertEquals('meters.validation', $property->getValue());
    }

    public function test_resource_uses_meter_policy(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
        ]);

        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        // Test that policy is being used
        $this->assertTrue($manager->can('view', $meter));
        $this->assertTrue($manager->can('update', $meter));
        $this->assertTrue($manager->can('delete', $meter));
    }

    public function test_resource_label_is_localized(): void
    {
        $label = MeterResource::getLabel();
        $pluralLabel = MeterResource::getPluralLabel();

        $this->assertEquals(__('meters.labels.meter'), $label);
        $this->assertEquals(__('meters.labels.meters'), $pluralLabel);
    }

    public function test_resource_navigation_label_is_localized(): void
    {
        $navigationLabel = MeterResource::getNavigationLabel();

        $this->assertEquals(__('meters.labels.meters'), $navigationLabel);
    }

    public function test_resource_navigation_group_is_localized(): void
    {
        $navigationGroup = MeterResource::getNavigationGroup();

        $this->assertEquals(__('app.nav_groups.operations'), $navigationGroup);
    }
}
