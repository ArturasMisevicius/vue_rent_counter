<?php

namespace Database\Seeders;

use App\Models\Building;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestBuildingsSeeder extends Seeder
{
    /**
     * Seed test buildings with realistic Vilnius addresses.
     */
    public function run(): void
    {
        // Building 1 for tenant 1 - Gedimino Avenue (prestigious central location)
        Building::factory()
            ->forTenantId(1)
            ->create([
                'name' => 'Gedimino 15',
                'address' => 'Gedimino pr. 15, Vilnius',
                'total_apartments' => 12,
                'gyvatukas_summer_average' => 150.50,
                'gyvatukas_last_calculated' => Carbon::create(2024, 10, 1),
            ]);

        // Building 2 for tenant 1 - Konstitucijos Avenue (modern business district)
        Building::factory()
            ->forTenantId(1)
            ->create([
                'name' => 'Konstitucijos 7',
                'address' => 'Konstitucijos pr. 7, Vilnius',
                'total_apartments' => 8,
                'gyvatukas_summer_average' => 120.30,
                'gyvatukas_last_calculated' => Carbon::create(2024, 10, 1),
            ]);

        // Building 3 for tenant 2 - Pilies Street (Old Town)
        Building::factory()
            ->forTenantId(2)
            ->create([
                'name' => 'Pilies 22',
                'address' => 'Pilies g. 22, Vilnius',
                'total_apartments' => 6,
                'gyvatukas_summer_average' => 95.75,
                'gyvatukas_last_calculated' => Carbon::create(2024, 10, 1),
            ]);
    }
}
