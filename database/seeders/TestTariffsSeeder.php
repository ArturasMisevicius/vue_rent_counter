<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestTariffsSeeder extends Seeder
{
    /**
     * Seed test tariffs for all Lithuanian utility providers.
     */
    public function run(): void
    {
        $oneYearAgo = Carbon::now()->subYear();

        // Ignitis - Electricity with time-of-use configuration (day/night rates)
        $ignitis = Provider::where('name', 'Ignitis')->first();
        
        if ($ignitis) {
            Tariff::factory()->create([
                'provider_id' => $ignitis->id,
                'name' => 'Ignitis Standard Time-of-Use',
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => '07:00',
                            'end' => '23:00',
                            'rate' => 0.18,
                        ],
                        [
                            'id' => 'night',
                            'start' => '23:00',
                            'end' => '07:00',
                            'rate' => 0.10,
                        ],
                    ],
                    'weekend_logic' => 'apply_night_rate',
                    'fixed_fee' => 0.00,
                ],
                'active_from' => $oneYearAgo,
                'active_until' => null,
            ]);
        }

        // Vilniaus Vandenys - Water with flat rates (supply, sewage, fixed fee)
        $vilniausVandenys = Provider::where('name', 'Vilniaus Vandenys')->first();
        
        if ($vilniausVandenys) {
            Tariff::factory()->create([
                'provider_id' => $vilniausVandenys->id,
                'name' => 'VV Standard Water Rates',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'supply_rate' => 0.97,
                    'sewage_rate' => 1.23,
                    'fixed_fee' => 0.85,
                ],
                'active_from' => $oneYearAgo,
                'active_until' => null,
            ]);
        }

        // Vilniaus Energija - Heating with flat heating rate
        $vilniausEnergija = Provider::where('name', 'Vilniaus Energija')->first();
        
        if ($vilniausEnergija) {
            Tariff::factory()->create([
                'provider_id' => $vilniausEnergija->id,
                'name' => 'VE Heating Standard',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.065,
                    'fixed_fee' => 0.00,
                ],
                'active_from' => $oneYearAgo,
                'active_until' => null,
            ]);
        }
    }
}
