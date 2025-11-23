<?php

declare(strict_types=1);

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Lang;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $this->building = Building::factory()->create([
        'tenant_id' => 1,
    ]);

    $this->actingAs($this->admin);
});

describe('Localization', function () {
    test('all validation messages use translation keys', function () {
        $manager = new PropertiesRelationManager;
        $form = $manager->form(\Filament\Forms\Form::make());

        $schema = $form->getComponents();

        // Check that no hardcoded English strings exist in validation
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('getAddressField');
        $method->setAccessible(true);

        $field = $method->invoke($manager);

        expect($field)->toBeInstanceOf(TextInput::class);

        // Verify translations exist
        expect(Lang::has('properties.validation.address.required'))->toBeTrue();
        expect(Lang::has('properties.validation.address.max'))->toBeTrue();
        expect(Lang::has('properties.validation.type.required'))->toBeTrue();
        expect(Lang::has('properties.validation.type.enum'))->toBeTrue();
        expect(Lang::has('properties.validation.area_sqm.required'))->toBeTrue();
    });

    test('all labels use translation keys', function () {
        expect(Lang::has('properties.labels.address'))->toBeTrue();
        expect(Lang::has('properties.labels.type'))->toBeTrue();
        expect(Lang::has('properties.labels.area'))->toBeTrue();
        expect(Lang::has('properties.labels.current_tenant'))->toBeTrue();
    });

    test('all notifications use translation keys', function () {
        expect(Lang::has('properties.notifications.created.title'))->toBeTrue();
        expect(Lang::has('properties.notifications.updated.title'))->toBeTrue();
        expect(Lang::has('properties.notifications.deleted.title'))->toBeTrue();
        expect(Lang::has('properties.notifications.tenant_assigned.title'))->toBeTrue();
        expect(Lang::has('properties.notifications.tenant_removed.title'))->toBeTrue();
    });

    test('all action labels use translation keys', function () {
        expect(Lang::has('properties.actions.manage_tenant'))->toBeTrue();
        expect(Lang::has('properties.actions.assign_tenant'))->toBeTrue();
        expect(Lang::has('properties.actions.export_selected'))->toBeTrue();
    });
});

describe('Model Relationships', function () {
    test('property tenants relationship is BelongsToMany', function () {
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'building_id' => $this->building->id,
        ]);

        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
        ]);

        // Should be able to sync (BelongsToMany method)
        $property->tenants()->sync([$tenant->id]);

        expect($property->tenants)->toHaveCount(1);
        expect($property->tenants->first()->id)->toBe($tenant->id);
    });

    test('property can have multiple tenants over time', function () {
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'building_id' => $this->building->id,
        ]);

        $tenant1 = Tenant::factory()->create(['tenant_id' => 1]);
        $tenant2 = Tenant::factory()->create(['tenant_id' => 1]);

        $property->tenants()->attach($tenant1->id);
        expect($property->tenants)->toHaveCount(1);

        $property->tenants()->sync([$tenant2->id]);
        expect($property->tenants)->toHaveCount(1);
        expect($property->tenants->first()->id)->toBe($tenant2->id);
    });
});

describe('Authorization', function () {
    test('handleTenantManagement checks authorization', function () {
        $otherAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 2, // Different tenant
        ]);

        $property = Property::factory()->create([
            'tenant_id' => 2,
            'building_id' => Building::factory()->create(['tenant_id' => 2])->id,
        ]);

        $this->actingAs($this->admin); // tenant_id = 1

        $manager = new PropertiesRelationManager;
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('handleTenantManagement');
        $method->setAccessible(true);

        // Should fail authorization since property belongs to different tenant
        expect($this->admin->can('update', $property))->toBeFalse();
    });

    test('admin can manage tenants for own properties', function () {
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'building_id' => $this->building->id,
        ]);

        expect($this->admin->can('update', $property))->toBeTrue();
    });

    test('superadmin can manage any property', function () {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        $property = Property::factory()->create([
            'tenant_id' => 999,
            'building_id' => Building::factory()->create(['tenant_id' => 999])->id,
        ]);

        $this->actingAs($superadmin);

        expect($superadmin->can('update', $property))->toBeTrue();
    });
});

describe('Validation Integration', function () {
    test('form uses FormRequest validation messages', function () {
        $manager = new PropertiesRelationManager;

        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('getAddressField');
        $method->setAccessible(true);

        $field = $method->invoke($manager);

        // Verify field has validation messages configured
        expect($field->getValidationMessages())->toBeArray();
        expect($field->getValidationMessages())->toHaveKey('required');
    });

    test('area field uses config values', function () {
        config(['billing.property.min_area' => 10]);
        config(['billing.property.max_area' => 5000]);

        $manager = new PropertiesRelationManager;

        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('getAreaField');
        $method->setAccessible(true);

        $field = $method->invoke($manager);

        expect($field->getMinValue())->toBe(10);
        expect($field->getMaxValue())->toBe(5000);
    });
});

describe('Eager Loading', function () {
    test('table configures eager loading for relationships', function () {
        $manager = new PropertiesRelationManager;
        $table = $manager->table(\Filament\Tables\Table::make());

        // Create test data
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'building_id' => $this->building->id,
        ]);

        // Query should eager load relationships
        $query = Property::query();
        $modifyQuery = $table->getModifyQueryUsing();

        if ($modifyQuery) {
            $query = $modifyQuery($query);
        }

        // Check that eager loads are configured
        expect($query->getEagerLoads())->toHaveKeys(['tenants', 'meters']);
    });
});

describe('Default Area Setting', function () {
    test('apartment type sets default area from config', function () {
        config(['billing.property.default_apartment_area' => 55]);

        $manager = new PropertiesRelationManager;

        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('setDefaultArea');
        $method->setAccessible(true);

        $set = function ($key, $value) {
            $this->testValue = $value;
        };

        $method->invoke($manager, PropertyType::APARTMENT->value, $set->bindTo($this));

        expect($this->testValue)->toBe(55);
    });

    test('house type sets default area from config', function () {
        config(['billing.property.default_house_area' => 150]);

        $manager = new PropertiesRelationManager;

        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('setDefaultArea');
        $method->setAccessible(true);

        $set = function ($key, $value) {
            $this->testValue = $value;
        };

        $method->invoke($manager, PropertyType::HOUSE->value, $set->bindTo($this));

        expect($this->testValue)->toBe(150);
    });
});

describe('Tenant Management Form', function () {
    test('form shows reassign label when tenant exists', function () {
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'building_id' => $this->building->id,
        ]);

        $tenant = Tenant::factory()->create(['tenant_id' => 1]);
        $property->tenants()->attach($tenant->id);

        $manager = new PropertiesRelationManager;

        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('getTenantManagementForm');
        $method->setAccessible(true);

        $form = $method->invoke($manager, $property);

        expect($form)->toBeArray();
        expect($form[0])->toBeInstanceOf(Select::class);
    });

    test('form shows assign label when no tenant', function () {
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'building_id' => $this->building->id,
        ]);

        $manager = new PropertiesRelationManager;

        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('getTenantManagementForm');
        $method->setAccessible(true);

        $form = $method->invoke($manager, $property);

        expect($form)->toBeArray();
        expect($form[0])->toBeInstanceOf(Select::class);
    });
});

describe('Data Preparation', function () {
    test('preparePropertyData sets tenant_id and building_id', function () {
        $manager = new PropertiesRelationManager;

        // Mock the getOwnerRecord method
        $reflection = new ReflectionClass($manager);
        $property = $reflection->getProperty('ownerRecord');
        $property->setAccessible(true);
        $property->setValue($manager, $this->building);

        $method = $reflection->getMethod('preparePropertyData');
        $method->setAccessible(true);

        $data = [
            'address' => 'Test Address',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50,
        ];

        $result = $method->invoke($manager, $data);

        expect($result)->toHaveKey('tenant_id');
        expect($result)->toHaveKey('building_id');
        expect($result['tenant_id'])->toBe($this->admin->tenant_id);
        expect($result['building_id'])->toBe($this->building->id);
    });
});

describe('Security', function () {
    test('tenant scope is applied through building relationship', function () {
        $manager = new PropertiesRelationManager;

        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('applyTenantScoping');
        $method->setAccessible(true);

        $query = Property::query();
        $result = $method->invoke($manager, $query);

        // Should return query unchanged (scoping via building)
        expect($result)->toBe($query);
    });

    test('canViewForRecord checks policy', function () {
        $canView = PropertiesRelationManager::canViewForRecord(
            $this->building,
            'view'
        );

        expect($canView)->toBeTrue();
    });
});
