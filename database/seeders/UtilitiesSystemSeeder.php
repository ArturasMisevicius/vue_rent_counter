<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

final class UtilitiesSystemSeeder extends Seeder
{
    /**
     * Seed the utilities billing system with sample data.
     */
    public function run(): void
    {
        $this->command->info('Seeding Vilnius Utilities Billing System...');

        // Create providers
        $this->createProviders();
        
        // Create tariffs
        $this->createTariffs();
        
        // Create buildings
        $buildings = $this->createBuildings();
        
        // Create properties
        $properties = $this->createProperties($buildings);
        
        // Create tenants
        $tenants = $this->createTenants($properties);
        
        // Create meters
        $meters = $this->createMeters($properties);
        
        // Create meter readings
        $this->createMeterReadings($meters);

        $this->command->info('Utilities system seeded successfully!');
        $this->command->info("Created: {$buildings->count()} buildings, {$properties->count()} properties, {$tenants->count()} tenants, {$meters->count()} meters");
    }

    private function createProviders(): void
    {
        $providers = [
            [
                'name' => 'Vilniaus Energija',
                'service_type' => ServiceType::ELECTRICITY,
                'contact_info' => [
                    'email' => 'info@ve.lt',
                    'phone' => '+370 5 278 2222',
                    'website' => 'https://www.ve.lt',
                ],
            ],
            [
                'name' => 'Vilniaus Šilumos Tinklai',
                'service_type' => ServiceType::HEATING,
                'contact_info' => [
                    'email' => 'info@vst.lt',
                    'phone' => '+370 5 252 5252',
                    'website' => 'https://www.vst.lt',
                ],
            ],
            [
                'name' => 'Vilniaus Vandenys',
                'service_type' => ServiceType::WATER,
                'contact_info' => [
                    'email' => 'info@vv.lt',
                    'phone' => '+370 5 266 2662',
                    'website' => 'https://www.vv.lt',
                ],
            ],
        ];

        foreach ($providers as $providerData) {
            Provider::create($providerData);
        }

        $this->command->info('Created providers');
    }

    private function createTariffs(): void
    {
        $tariffs = [
            [
                'provider_id' => 1, // Vilniaus Energija
                'name' => 'Standard Electricity Rate 2024',
                'active_from' => '2024-01-01',
                'active_until' => '2024-12-31',
                'configuration' => [
                    'type' => 'flat',
                    'rate' => 0.1234, // €/kWh
                ],
            ],
            [
                'provider_id' => 2, // Vilniaus Šilumos Tinklai
                'name' => 'Heating Tariff 2024',
                'active_from' => '2024-01-01',
                'active_until' => '2024-12-31',
                'configuration' => [
                    'type' => 'flat',
                    'rate' => 0.0567, // €/kWh
                ],
            ],
            [
                'provider_id' => 3, // Vilniaus Vandenys
                'name' => 'Water Supply & Sewage 2024',
                'active_from' => '2024-01-01',
                'active_until' => '2024-12-31',
                'configuration' => [
                    'type' => 'water_combined',
                    'supply_rate' => 0.97, // €/m³
                    'sewage_rate' => 1.23, // €/m³
                    'fixed_fee' => 0.85, // €/month
                ],
            ],
        ];

        foreach ($tariffs as $tariffData) {
            Tariff::create($tariffData);
        }

        $this->command->info('Created tariffs');
    }

    private function createBuildings(): \Illuminate\Support\Collection
    {
        $buildings = [
            [
                'tenant_id' => 1, // Default tenant for seeding
                'name' => 'Gedimino pr. 15',
                'address' => 'Gedimino prospektas 15, Vilnius',
                'total_apartments' => 24,
                'gyvatukas_summer_average' => 15.50,
                'gyvatukas_last_calculated' => now(),
            ],
            [
                'tenant_id' => 1, // Default tenant for seeding
                'name' => 'Konstitucijos pr. 7A',
                'address' => 'Konstitucijos prospektas 7A, Vilnius',
                'total_apartments' => 36,
                'gyvatukas_summer_average' => 18.20,
                'gyvatukas_last_calculated' => now(),
            ],
            [
                'tenant_id' => 1, // Default tenant for seeding
                'name' => 'Pilies g. 22',
                'address' => 'Pilies gatvė 22, Vilnius',
                'total_apartments' => 12,
                'gyvatukas_summer_average' => 12.80,
                'gyvatukas_last_calculated' => now(),
            ],
        ];

        $collection = collect();
        foreach ($buildings as $buildingData) {
            $building = Building::create($buildingData);
            $collection->push($building);
        }

        $this->command->info('Created buildings');
        return $collection;
    }

    private function createProperties(\Illuminate\Support\Collection $buildings): \Illuminate\Support\Collection
    {
        $collection = collect();
        
        foreach ($buildings as $building) {
            $apartmentCount = $building->total_apartments;
            
            for ($i = 1; $i <= $apartmentCount; $i++) {
                $property = Property::create([
                    'tenant_id' => 1, // Default tenant for seeding
                    'building_id' => $building->id,
                    'unit_number' => 'Apt ' . $i,
                    'address' => $building->address . ', Apt ' . $i,
                    'type' => PropertyType::APARTMENT,
                    'area_sqm' => fake()->numberBetween(35, 120),
                ]);
                
                $collection->push($property);
            }
        }

        $this->command->info('Created properties');
        return $collection;
    }

    private function createTenants(\Illuminate\Support\Collection $properties): \Illuminate\Support\Collection
    {
        $collection = collect();
        
        // Create tenants for about 80% of properties
        $propertiesToFill = $properties->random((int) ($properties->count() * 0.8));
        
        foreach ($propertiesToFill as $property) {
            $firstName = fake('lt_LT')->firstName();
            $lastName = fake('lt_LT')->lastName();
            
            $tenant = Tenant::create([
                'tenant_id' => 1, // Organization ID for multi-tenancy
                'property_id' => $property->id,
                'name' => $firstName . ' ' . $lastName,
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake('lt_LT')->phoneNumber(),
                'lease_start' => fake()->dateTimeBetween('-2 years', '-1 month'),
                'lease_end' => fake()->optional(0.3)->dateTimeBetween('+1 month', '+2 years'),
            ]);
            
            $collection->push($tenant);
        }

        $this->command->info('Created tenants');
        return $collection;
    }

    private function createMeters(\Illuminate\Support\Collection $properties): \Illuminate\Support\Collection
    {
        $collection = collect();
        
        foreach ($properties as $property) {
            // Each property gets electricity, water, and heating meters
            $meterTypes = [
                MeterType::ELECTRICITY,
                MeterType::WATER_COLD,
                MeterType::HEATING,
            ];
            
            foreach ($meterTypes as $meterType) {
                $meter = Meter::create([
                    'tenant_id' => 1, // Default tenant for seeding
                    'property_id' => $property->id,
                    'serial_number' => $this->generateMeterNumber($meterType),
                    'type' => $meterType,
                    'installation_date' => fake()->dateTimeBetween('-5 years', '-1 year'),
                    'supports_zones' => $meterType === MeterType::ELECTRICITY ? fake()->boolean(20) : false,
                ]);
                
                $collection->push($meter);
            }
        }

        $this->command->info('Created meters');
        return $collection;
    }

    private function createMeterReadings(\Illuminate\Support\Collection $meters): void
    {
        $readingCount = 0;
        
        foreach ($meters as $meter) {
            // Create readings for the last 6 months
            $startDate = Carbon::now()->subMonths(6)->startOfMonth();
            $currentValue = fake()->numberBetween(1000, 5000);
            
            for ($month = 0; $month < 6; $month++) {
                $readingDate = $startDate->copy()->addMonths($month)->endOfMonth();
                
                // Simulate monthly consumption
                $consumption = match ($meter->type) {
                    MeterType::ELECTRICITY => fake()->numberBetween(150, 400), // kWh
                    MeterType::WATER_COLD => fake()->numberBetween(3, 15), // m³
                    MeterType::HEATING => fake()->numberBetween(200, 800), // kWh
                };
                
                $currentValue += $consumption;
                
                MeterReading::create([
                    'tenant_id' => 1, // Default tenant for seeding
                    'meter_id' => $meter->id,
                    'reading_date' => $readingDate,
                    'value' => $currentValue,
                    'zone' => null,
                    'entered_by' => 1, // Assuming admin user ID 1
                ]);
                
                $readingCount++;
            }
        }

        $this->command->info("Created {$readingCount} meter readings");
    }

    private function generateMeterNumber(MeterType $meterType): string
    {
        $prefix = match ($meterType) {
            MeterType::ELECTRICITY => 'EL',
            MeterType::WATER_COLD => 'WC',
            MeterType::WATER_HOT => 'WH',
            MeterType::HEATING => 'HT',
        };
        
        return $prefix . fake()->unique()->numberBetween(100000, 999999);
    }
}