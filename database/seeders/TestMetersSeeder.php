<?php

namespace Database\Seeders;

use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestMetersSeeder extends Seeder
{
    /**
     * Seed test meters for all properties.
     * 
     * Creates meters for each property:
     * - Electricity meter (supports_zones=true) for all properties
     * - Water cold meter for all properties
     * - Water hot meter for all properties
     * - Heating meter for apartments in buildings only
     */
    public function run(): void
    {
        $properties = Property::all();
        $installationDate = Carbon::now()->subYears(2);

        foreach ($properties as $property) {
            // Electricity meter (supports day/night zones)
            Meter::create([
                'tenant_id' => $property->tenant_id,
                'serial_number' => 'EL-' . str_pad($property->id, 6, '0', STR_PAD_LEFT),
                'type' => MeterType::ELECTRICITY,
                'property_id' => $property->id,
                'installation_date' => $installationDate,
                'supports_zones' => true,
            ]);

            // Cold water meter
            Meter::create([
                'tenant_id' => $property->tenant_id,
                'serial_number' => 'WC-' . str_pad($property->id, 6, '0', STR_PAD_LEFT),
                'type' => MeterType::WATER_COLD,
                'property_id' => $property->id,
                'installation_date' => $installationDate,
                'supports_zones' => false,
            ]);

            // Hot water meter
            Meter::create([
                'tenant_id' => $property->tenant_id,
                'serial_number' => 'WH-' . str_pad($property->id, 6, '0', STR_PAD_LEFT),
                'type' => MeterType::WATER_HOT,
                'property_id' => $property->id,
                'installation_date' => $installationDate,
                'supports_zones' => false,
            ]);

            // Heating meter (only for apartments in buildings)
            if ($property->building_id !== null) {
                Meter::create([
                    'tenant_id' => $property->tenant_id,
                    'serial_number' => 'HT-' . str_pad($property->id, 6, '0', STR_PAD_LEFT),
                    'type' => MeterType::HEATING,
                    'property_id' => $property->id,
                    'installation_date' => $installationDate,
                    'supports_zones' => false,
                ]);
            }
        }
    }
}
