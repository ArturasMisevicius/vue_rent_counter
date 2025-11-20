<?php

namespace Database\Seeders;

use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Property;
use Illuminate\Database\Seeder;

class TestPropertiesSeeder extends Seeder
{
    /**
     * Seed test properties (apartments and houses) for test buildings.
     */
    public function run(): void
    {
        // Get buildings for each tenant
        $building1 = Building::where('tenant_id', 1)
            ->where('address', 'Gedimino pr. 15, Vilnius')
            ->first();
        
        $building2 = Building::where('tenant_id', 1)
            ->where('address', 'Konstitucijos pr. 7, Vilnius')
            ->first();
        
        $building3 = Building::where('tenant_id', 2)
            ->where('address', 'Pilies g. 22, Vilnius')
            ->first();

        // Create 6 apartments for tenant 1 buildings
        // 4 apartments in building 1 (Gedimino pr. 15)
        for ($i = 1; $i <= 4; $i++) {
            Property::create([
                'tenant_id' => 1,
                'address' => "{$building1->address}, Apt {$i}",
                'type' => PropertyType::APARTMENT,
                'area_sqm' => fake()->numberBetween(45, 85),
                'building_id' => $building1->id,
            ]);
        }

        // 2 apartments in building 2 (Konstitucijos pr. 7)
        for ($i = 1; $i <= 2; $i++) {
            Property::create([
                'tenant_id' => 1,
                'address' => "{$building2->address}, Apt {$i}",
                'type' => PropertyType::APARTMENT,
                'area_sqm' => fake()->numberBetween(50, 90),
                'building_id' => $building2->id,
            ]);
        }

        // Create 1 standalone house for tenant 1 (no building_id)
        Property::create([
            'tenant_id' => 1,
            'address' => 'Žvėryno g. 5, Vilnius',
            'type' => PropertyType::HOUSE,
            'area_sqm' => 150,
            'building_id' => null,
        ]);

        // Create 3 apartments for tenant 2 building (Pilies g. 22)
        for ($i = 1; $i <= 3; $i++) {
            Property::create([
                'tenant_id' => 2,
                'address' => "{$building3->address}, Apt {$i}",
                'type' => PropertyType::APARTMENT,
                'area_sqm' => fake()->numberBetween(40, 75),
                'building_id' => $building3->id,
            ]);
        }
    }
}

